<?php

namespace Modules\Service\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Category\Models\Category;
use Modules\Service\Http\Requests\HairstyleModelRequest;
use Modules\Service\Models\HairstyleModel;
use Modules\Service\Models\Service;
use Yajra\DataTables\DataTables;

class HairstyleModelController extends Controller
{
    public function __construct()
    {
        $this->module_title = 'Modèle de coiffure';
        $this->module_name = 'hairstyle-models';
        $this->module_icon = 'fa-solid fa-scissors';

        view()->share([
            'module_title' => $this->module_title,
            'module_icon' => $this->module_icon,
            'module_name' => $this->module_name,
        ]);

        $this->middleware(['permission:view_hairstyle_model'])->only('index', 'index_data', 'visualize');
        $this->middleware(['permission:add_hairstyle_model'])->only('store');
        $this->middleware(['permission:edit_hairstyle_model'])->only('edit', 'update', 'update_status');
        $this->middleware(['permission:delete_hairstyle_model'])->only('destroy', 'bulk_action');
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $filter = [
            'status' => $request->status,
            'service_id' => $request->service_id,
        ];
        $module_action = 'Liste des';

        $services = Service::active()
            ->where(function ($q) {
                $q->whereHas('category', function ($query) {
                    $query->where('name', 'like', '%coiffure%')
                        ->orWhere('slug', 'like', '%coiffure%')
                        ->orWhere('name', 'like', '%hair%')
                        ->orWhere('slug', 'like', '%hair%');
                })
                ->orWhereHas('sub_category', function ($query) {
                    $query->where('name', 'like', '%coiffure%')
                        ->orWhere('slug', 'like', '%coiffure%')
                        ->orWhere('name', 'like', '%hair%')
                        ->orWhere('slug', 'like', '%hair%');
                });
            })
            ->select('id', 'name', 'category_id', 'sub_category_id')
            ->get();

        return view('service::backend.hairstyle_models.index_datatable', compact('module_action', 'filter', 'services'));
    }

    /**
     * Interactive visual card gallery view grouped by Service.
     */
    public function visualize(Request $request)
    {
        $module_action = 'Visualiser les';

        $services = Service::active()
            ->with(['hairstyle_models' => function($q) {
                $q->active()->orderBy('id', 'desc');
            }, 'category'])
            ->has('hairstyle_models')
            ->get();

        if ($services->isEmpty()) {
            $services = Service::active()
                ->with(['hairstyle_models' => function($q) {
                    $q->active()->orderBy('id', 'desc');
                }, 'category'])
                ->get();
        }

        return view('service::backend.hairstyle_models.visualize', compact('module_action', 'services'));
    }

    /**
     * Display the specified resource.
     */
    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $data = HairstyleModel::with('service')->findOrFail($id);
        $data->feature_image_url = $data->feature_image;
        $data->feature_images = $data->feature_images;
        $data->feature_image_items = $data->feature_image_items;

        return response()->json(['data' => $data, 'status' => true]);
    }

    /**
     * Datatable index data endpoint.
     */
    public function index_data(Datatables $datatable, Request $request)
    {
        $module_name = $this->module_name;
        $query = HairstyleModel::query()->with('service');

        $filter = $request->filter;

        if (isset($filter)) {
            if (isset($filter['column_status']) && $filter['column_status'] !== '') {
                $query->where('status', $filter['column_status']);
            }
            if (isset($filter['service_id']) && $filter['service_id'] !== '') {
                $query->where('service_id', $filter['service_id']);
            }
        }

        return $datatable->eloquent($query)
            ->addColumn('check', function ($data) {
                return '<input type="checkbox" class="form-check-input select-table-row" id="datatable-row-'.$data->id.'" name="datatable_ids[]" value="'.$data->id.'" onclick="dataTableRowCheck('.$data->id.')">';
            })
            ->addColumn('image', function ($data) {
                $items = $data->feature_image_items;
                $count = count($items);
                $firstUrl = $data->feature_image;
                $serviceName = e($data->service ? $data->service->name : '');
                $modelName = e($data->name);
                $encodedData = e(json_encode([
                    'id' => $data->id,
                    'name' => $data->name,
                    'service' => $data->service ? $data->service->name : '',
                    'description' => $data->description,
                    'items' => $items,
                ]));

                $countBadge = '';
                if ($count > 1) {
                    $extra = $count - 1;
                    $countBadge = '<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary border border-2 border-white shadow-sm" style="font-size:0.7rem; font-weight:700;">+'.$extra.'</span>';
                }

                return '
                    <div class="position-relative d-inline-block open-futuristic-gallery" data-model-info="'.$encodedData.'" style="cursor: pointer;" title="Cliquer pour voir la galerie ('.$count.' photos)">
                        <img src="'.$firstUrl.'" class="avatar avatar-50 rounded-3 border border-2 border-primary-subtle shadow-sm object-cover transition-all hover-scale" style="object-fit: cover; width: 48px; height: 48px;">
                        '.$countBadge.'
                    </div>
                ';
            })
            ->addColumn('service', function ($data) {
                return $data->service ? '<span class="badge bg-soft-primary">'.$data->service->name.'</span>' : '-';
            })
            ->editColumn('status', function ($row) {
                $checked = $row->status ? 'checked="checked"' : '';
                $label = $row->status ? '<span class="badge bg-soft-success ms-2">Public</span>' : '<span class="badge bg-soft-secondary ms-2">Privé</span>';
                
                return '
                    <div class="d-flex align-items-center">
                        <div class="form-check form-switch m-0">
                            <input type="checkbox" data-url="'.route('backend.hairstyle-models.update_status', $row->id).'" data-token="'.csrf_token().'" class="switch-status-change form-check-input" id="datatable-row-'.$row->id.'" name="status" value="'.$row->id.'" '.$checked.'>
                        </div>
                        '.$label.'
                    </div>
                ';
            })
            ->editColumn('created_at', function ($data) {
                return $data->created_at ? $data->created_at->isoFormat('LLLL') : '-';
            })
            ->editColumn('updated_at', function ($data) {
                $diff = Carbon::now()->diffInHours($data->updated_at);
                if ($diff < 25) {
                    return $data->updated_at->diffForHumans();
                } else {
                    return $data->updated_at->isoFormat('llll');
                }
            })
            ->addColumn('action', function ($data) use ($module_name) {
                return view('service::backend.hairstyle_models.action_column', compact('module_name', 'data'));
            })
            ->rawColumns(['action', 'image', 'service', 'status', 'check'])
            ->orderColumns(['id'], '-:column $1')
            ->toJson();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(HairstyleModelRequest $request)
    {
        $data = $request->except(['feature_image', 'remove_image_ids']);
        $model = HairstyleModel::create($data);

        if ($request->hasFile('feature_image')) {
            $files = $request->file('feature_image');
            if (is_array($files)) {
                foreach ($files as $file) {
                    if ($file) {
                        $model->addMedia($file)->toMediaCollection('feature_image');
                    }
                }
            } else {
                $model->addMedia($files)->toMediaCollection('feature_image');
            }
        }

        return response()->json(['message' => 'Modèle de coiffure créé avec succès.', 'status' => true], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $data = HairstyleModel::findOrFail($id);
        $data->feature_image_url = $data->feature_image;
        $data->feature_images = $data->feature_images;
        $data->feature_image_items = $data->feature_image_items;

        return response()->json(['data' => $data, 'status' => true]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(HairstyleModelRequest $request, $id)
    {
        $model = HairstyleModel::findOrFail($id);
        $request_data = $request->except(['feature_image', 'remove_image_ids']);
        $model->update($request_data);

        // Remove media items marked for deletion
        if ($request->has('remove_image_ids')) {
            $removeIds = $request->input('remove_image_ids');
            if (is_array($removeIds)) {
                foreach ($removeIds as $mediaId) {
                    $media = $model->media()->find($mediaId);
                    if ($media) {
                        $media->delete();
                    }
                }
                $model->unsetRelation('media');
            }
        }

        // Add newly uploaded media items
        if ($request->hasFile('feature_image')) {
            $files = $request->file('feature_image');
            if (is_array($files)) {
                foreach ($files as $file) {
                    if ($file) {
                        $model->addMedia($file)->toMediaCollection('feature_image');
                    }
                }
            } else {
                $model->addMedia($files)->toMediaCollection('feature_image');
            }
            $model->unsetRelation('media');
        }

        return response()->json(['message' => 'Modèle de coiffure mis à jour avec succès.', 'status' => true], 200);
    }

    /**
     * Delete specific images from a hairstyle model entry.
     */
    public function delete_images(Request $request, $id)
    {
        $model = HairstyleModel::findOrFail($id);
        $mediaIds = $request->input('media_ids', []);

        if (is_array($mediaIds) && !empty($mediaIds)) {
            foreach ($mediaIds as $mediaId) {
                $media = $model->media()->find($mediaId);
                if ($media) {
                    $media->delete();
                }
            }
            $model->unsetRelation('media');
        }

        $remainingMediaCount = $model->getMedia('feature_image')->count();

        // If no media items remain and user opted to delete model if empty, soft delete the model record
        if ($remainingMediaCount === 0 && $request->boolean('delete_model_if_empty')) {
            $model->delete();
            return response()->json([
                'status' => true,
                'message' => 'Toutes les images et le modèle ont été supprimés avec succès.',
                'model_deleted' => true,
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Les images sélectionnées ont été supprimées avec succès.',
            'remaining_count' => $remainingMediaCount,
            'model_deleted' => false,
        ]);
    }

    /**
     * Toggle status (Public/Privé).
     */
    public function update_status(Request $request, $id)
    {
        $model = HairstyleModel::findOrFail($id);
        $model->update(['status' => $request->status]);

        return response()->json(['status' => true, 'message' => 'Statut du modèle mis à jour']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $model = HairstyleModel::findOrFail($id);
        $model->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['message' => 'Modèle de coiffure supprimé avec succès.', 'status' => true], 200);
        }

        return redirect()->route('backend.hairstyle-models.index')->withSuccess('Modèle de coiffure supprimé avec succès.');
    }

    /**
     * Bulk action (delete / status change).
     */
    public function bulk_action(Request $request)
    {
        $ids = explode(',', $request->rowIds);
        $actionType = $request->action_type;

        switch ($actionType) {
            case 'change-status':
                HairstyleModel::whereIn('id', $ids)->update(['status' => $request->status]);
                $message = __('messages.bulk_update');
                break;
            case 'delete':
                HairstyleModel::whereIn('id', $ids)->delete();
                $message = __('messages.bulk_delete');
                break;
            default:
                return response()->json(['status' => false, 'message' => __('branch.invalid_action')]);
        }

        return response()->json(['status' => true, 'message' => $message]);
    }
}
