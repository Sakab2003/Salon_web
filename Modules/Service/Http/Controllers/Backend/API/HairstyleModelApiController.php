<?php

namespace Modules\Service\Http\Controllers\Backend\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Service\Models\HairstyleModel;
use Modules\Service\Models\Service;

class HairstyleModelApiController extends Controller
{
    /**
     * Resolve active branch ID for request or authenticated user.
     */
    protected function resolveBranchId(Request $request)
    {
        $user = auth('sanctum')->user();

        // A manager or employee must always use the salon assigned to the
        // authenticated account. Never trust a branch_id sent by the app.
        if ($user) {
            if ($user->hasRole('manager')) {
                return $user->branch_id ?: optional(\App\Models\Branch::where('manager_id', $user->id)->first())->id;
            } elseif ($user->hasRole('employee')) {
                $mainBranch = $user->mainBranch()->first();
                if ($mainBranch && $mainBranch->id) {
                    return $mainBranch->id;
                }
                $userBranch = $user->branch()->first();
                if ($userBranch && $userBranch->getBranch && $userBranch->getBranch->id) {
                    return $userBranch->getBranch->id;
                }
                return $user->branch_id;
            } elseif ($user->branch_id) {
                return $user->branch_id;
            }

            if ($user->hasRole('admin')) {
                $branchId = $request->branch_id;
                return !empty($branchId) && $branchId != '0' ? (int) $branchId : null;
            }
        }

        $branchId = $request->branch_id;
        if (!empty($branchId) && $branchId != '0') {
            return (int) $branchId;
        }

        return null;
    }

    /**
     * Get list of all hairstyle models for the active salon.
     */
    public function index(Request $request)
    {
        $user = auth('sanctum')->user();
        $branchId = $this->resolveBranchId($request);

        $query = HairstyleModel::with(['service'])->orderBy('id', 'desc');

        if ($user) {
            if (!empty($branchId) && !$user->hasRole('admin')) {
                $query->where('branch_id', $branchId);
            }
        } else {
            $query->where('status', 1);
        }

        if ($request->filled('service_id')) {
            $query->where('service_id', $request->service_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $models = $query->get();

        $data = $models->map(function ($model) {
            return [
                'id'                   => $model->id,
                'name'                 => $model->name,
                'service_id'           => $model->service_id,
                'service_name'         => optional($model->service)->name ?? '-',
                'status'               => (int) $model->status,
                'description'          => $model->description,
                'feature_image'        => $model->feature_image,
                'feature_images'       => $model->feature_images,
                'feature_image_items'  => $model->feature_image_items,
                'created_at'           => $model->created_at ? $model->created_at->format('Y-m-d H:i:s') : null,
            ];
        });

        return response()->json([
            'status'  => true,
            'data'    => $data,
            'message' => 'Liste des modèles récupérée avec succès'
        ]);
    }


    /**
     * Get services with their models for the visualizer (Strictly scoped by salon).
     */
    public function visualize(Request $request)
    {
        $user = auth('sanctum')->user();
        $branchId = $this->resolveBranchId($request);

        $query = Service::active()
            ->with(['hairstyle_models' => function ($q) use ($branchId) {
                $q->active()->orderBy('id', 'desc');
                // Filter models strictly by branch_id
                if (!empty($branchId)) {
                    $q->where('branch_id', $branchId);
                }
            }, 'commission']);

        if (!empty($branchId) && (!$user || !$user->hasRole('admin'))) {
            $query->whereHas('branches', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        $services = (clone $query)->has('hairstyle_models')->get();

        // If authenticated salon has no models yet, show its own services (with empty models list)
        // NEVER leak other salons' models!
        if ($services->isEmpty()) {
            $services = $query->get();
        }

        $data = $services->map(function ($service) {
            $models = $service->hairstyle_models ?? collect();
            $flatImages = [];
            $modelsList = [];

            foreach ($models as $m) {
                $imgs = $m->feature_images;
                if (empty($imgs)) {
                    $imgs = $m->feature_image ? [$m->feature_image] : [];
                }
                foreach ($imgs as $img) {
                    if (!empty($img)) {
                        $flatImages[] = $img;
                    }
                }
                $modelsList[] = [
                    'id' => $m->id,
                    'name' => $m->name,
                    'description' => $m->description,
                    'feature_image' => $m->feature_image,
                    'feature_images' => $imgs,
                    'feature_image_items' => $m->feature_image_items,
                    'status' => (int) $m->status,
                ];
            }

            $coverImage = !empty($service->feature_image)
                ? $service->feature_image
                : (!empty($flatImages) ? $flatImages[0] : default_feature_image());

            return [
                'service_id' => $service->id,
                'service_name' => $service->name,
                'service_description' => $service->description,
                'cover_image' => $coverImage,
                'total_models' => count($modelsList),
                'total_photos' => count($flatImages),
                'models' => $modelsList,
                'photos' => $flatImages,
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $data,
            'message' => 'Visualiseur de modèles récupéré avec succès'
        ]);
    }

    /**
     * Get single model detail.
     */
    public function show(Request $request, $id)
    {
        $model = HairstyleModel::with(['service'])->findOrFail($id);
        $this->ensureAccessible($request, $model);

        return response()->json([
            'status' => true,
            'data'   => [
                'id'                  => $model->id,
                'name'                => $model->name,
                'service_id'          => $model->service_id,
                'service_name'        => optional($model->service)->name ?? '-',
                'status'              => (int) $model->status,
                'description'         => $model->description,
                'feature_image'       => $model->feature_image,
                'feature_images'      => $model->feature_images,
                'feature_image_items' => $model->feature_image_items,
                'created_at'          => $model->created_at ? $model->created_at->format('Y-m-d H:i:s') : null,
            ],
            'message' => 'Détail du modèle'
        ]);
    }

    /**
     * Helper to attach images from request to model.
     */
    protected function attachMediaFiles(HairstyleModel $model, Request $request)
    {
        $attached = [];
        foreach ($request->allFiles() as $files) {
            $list = is_array($files) ? $files : [$files];
            foreach ($list as $file) {
                if (! $file) {
                    continue;
                }
                $path = method_exists($file, 'getRealPath') ? $file->getRealPath() : spl_object_hash($file);
                if (isset($attached[$path])) {
                    continue;
                }
                $attached[$path] = true;
                $model->addMedia($file)->toMediaCollection('feature_image');
            }
        }
    }

    /**
     * Store a new hairstyle model.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'service_id' => 'required|integer',
        ]);

        $branchId = $this->resolveBranchId($request);
        $user = auth('sanctum')->user();
        $service = Service::active()->find($request->service_id);

        abort_unless($service, 422, 'Le service sélectionné n’existe plus. Actualisez la liste avant de créer le modèle.');

        if ($user && ! $user->hasRole('admin')) {
            abort_unless($branchId, 422, 'Aucun salon n’est associé à ce compte.');
            abort_unless(
                $service->branches()->where('branch_id', $branchId)->exists(),
                422,
                'Ce service n’appartient pas à votre salon.'
            );
        }

        $model = HairstyleModel::create([
            'name' => $request->name,
            'service_id' => $request->service_id,
            'status' => $request->has('status') ? (int) $request->status : 1,
            'description' => $request->description,
            'branch_id' => $branchId,
            'created_by' => auth('sanctum')->id(),
            'updated_by' => auth('sanctum')->id(),
        ]);

        $this->attachMediaFiles($model, $request);

        $model->refresh();

        return response()->json([
            'status' => true,
            'data' => [
                'id' => $model->id,
                'name' => $model->name,
                'service_id' => $model->service_id,
                'service_name' => optional($model->service)->name ?? '-',
                'status' => (int) $model->status,
                'description' => $model->description,
                'feature_image' => $model->feature_image,
                'feature_images' => $model->feature_images,
                'feature_image_items' => $model->feature_image_items,
            ],
            'message' => 'Modèle créé avec succès'
        ]);
    }

    /**
     * Update an existing hairstyle model.
     */
    public function update(Request $request, $id)
    {
        $model = HairstyleModel::findOrFail($id);
        $this->ensureAccessible($request, $model);

        $updateData = [];
        if ($request->filled('name'))        $updateData['name']        = $request->name;
        if ($request->filled('service_id'))  $updateData['service_id']  = $request->service_id;
        if ($request->has('status'))         $updateData['status']      = (int) $request->status;
        if ($request->has('description'))    $updateData['description'] = $request->description;
        $updateData['updated_by'] = auth('sanctum')->id();

        if (!empty($updateData)) {
            $model->update($updateData);
        }

        if ($request->has('remove_image_ids')) {
            $removeIds = $request->input('remove_image_ids');
            if (is_string($removeIds)) {
                $removeIds = json_decode($removeIds, true) ?: explode(',', $removeIds);
            }
            if (is_array($removeIds)) {
                foreach ($removeIds as $mediaId) {
                    $media = $model->media()->find($mediaId);
                    if ($media) $media->delete();
                }
                $model->unsetRelation('media');
            }
        }

        $this->attachMediaFiles($model, $request);
        $model->refresh();

        return response()->json([
            'status'  => true,
            'data'    => [
                'id'                  => $model->id,
                'name'                => $model->name,
                'service_id'          => $model->service_id,
                'service_name'        => optional($model->service)->name ?? '-',
                'status'              => (int) $model->status,
                'description'         => $model->description,
                'feature_image'       => $model->feature_image,
                'feature_images'      => $model->feature_images,
                'feature_image_items' => $model->feature_image_items,
            ],
            'message' => 'Modèle mis à jour avec succès'
        ]);
    }

    /**
     * Delete specific images from a hairstyle model.
     */
    public function deleteImages(Request $request, $id)
    {
        $model = HairstyleModel::findOrFail($id);
        $this->ensureAccessible($request, $model);
        $mediaIds = $request->input('media_ids', $request->input('remove_image_ids', []));

        if (is_string($mediaIds)) {
            $mediaIds = json_decode($mediaIds, true) ?: explode(',', $mediaIds);
        }

        if (is_array($mediaIds) && !empty($mediaIds)) {
            foreach ($mediaIds as $mediaId) {
                $media = $model->media()->find($mediaId);
                if ($media) {
                    $media->delete();
                }
            }
            $model->unsetRelation('media');
        }

        $remainingCount = $model->getMedia('feature_image')->count();

        return response()->json([
            'status' => true,
            'message' => 'Images supprimées avec succès.',
            'remaining_count' => $remainingCount,
        ]);
    }

    /**
     * Delete a hairstyle model.
     */
    public function destroy(Request $request, $id)
    {
        $model = HairstyleModel::query()->whereKey($id)->first();
        if (! $model) {
            return response()->json([
                'status' => true,
                'message' => 'Ce modèle est introuvable ou a déjà été supprimé.',
            ], 200);
        }
        $this->ensureAccessible($request, $model);
        $model->clearMediaCollection('feature_image');
        $model->delete();

        return response()->json([
            'status' => true,
            'message' => 'Modèle supprimé avec succès'
        ]);
    }

    /**
     * Bulk actions (delete or change status).
     */
    public function bulkAction(Request $request)
    {
        $ids = $request->ids ?: explode(',', $request->rowIds ?? '');
        $action = $request->action ?: $request->action_type;

        if (empty($ids)) {
            return response()->json(['status' => false, 'message' => 'Aucun élément sélectionné.'], 400);
        }

        if ($action === 'delete') {
            $this->accessibleQuery($request)->whereIn('id', $ids)->delete();
            return response()->json(['status' => true, 'message' => 'Modèles supprimés avec succès.']);
        } elseif ($action === 'change-status') {
            $this->accessibleQuery($request)->whereIn('id', $ids)->update(['status' => (int) $request->status]);
            return response()->json(['status' => true, 'message' => 'Statuts mis à jour avec succès.']);
        }

        return response()->json(['status' => false, 'message' => 'Action invalide.'], 400);
    }

    protected function accessibleQuery(Request $request)
    {
        $user = auth('sanctum')->user();
        $branchId = $this->resolveBranchId($request);
        $query = HairstyleModel::query();

        if ($user && ! $user->hasRole('admin') && $branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query;
    }

    protected function ensureAccessible(Request $request, HairstyleModel $model): void
    {
        $user = auth('sanctum')->user();
        $branchId = $this->resolveBranchId($request);

        if ($user && ! $user->hasRole('admin') && $branchId) {
            abort_unless((int) $model->branch_id === (int) $branchId, 403, 'Ce modèle appartient à un autre salon.');
        }
    }
}
