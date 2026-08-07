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

        $hairCategoryIds = Category::whereIn('slug', [
            'hair', 'haircuts', 'hairstyling', 'coloring', 'hair-coloring', 
            'permanent-hair-coloring', 'highlights', 'hair-treatments', 
            'hair-repair-treatments', 'scalp-treatments', 'hair-texture-services', 
            'keratin smothings', 'hair-extension'
        ])->orWhere('name', 'LIKE', '%Coiffure%')->orWhere('name', 'LIKE', '%Cheveux%')->pluck('id');

        $services = Service::active()
            ->where(function($q) use ($hairCategoryIds) {
                if (count($hairCategoryIds) > 0) {
                    $q->whereIn('category_id', $hairCategoryIds)
                      ->orWhereIn('sub_category_id', $hairCategoryIds);
                }
            })
            ->select('id', 'name', 'category_id')
            ->get();

        if ($services->isEmpty()) {
            $services = Service::active()->select('id', 'name', 'category_id')->get();
        }

        return view('service::backend.hairstyle_models.index_datatable', compact('module_action', 'filter', 'services'));
    }

    /**
     * Interactive visual card gallery view per Hair Service.
     */
    public function visualize(Request $request)
    {
        $module_action = 'Visualiser les';

        $hairCategoryIds = Category::whereIn('slug', [
            'hair', 'haircuts', 'hairstyling', 'coloring', 'hair-coloring', 
            'permanent-hair-coloring', 'highlights', 'hair-treatments', 
            'hair-repair-treatments', 'scalp-treatments', 'hair-texture-services', 
            'keratin smothings', 'hair-extension'
        ])->orWhere('name', 'LIKE', '%Coiffure%')->orWhere('name', 'LIKE', '%Cheveux%')->pluck('id');

        $servicesQuery = Service::active()
            ->where(function($q) use ($hairCategoryIds) {
                if (count($hairCategoryIds) > 0) {
                    $q->whereIn('category_id', $hairCategoryIds)
                      ->orWhereIn('sub_category_id', $hairCategoryIds);
                }
            })
            ->with(['hairstyle_models' => function($q) {
                $q->active()->orderBy('id', 'desc');
            }, 'category', 'sub_category'])
            ->has('hairstyle_models');

        $services = $servicesQuery->get();

        // If no services have models yet under hair category, fallback to all services with models
        if ($services->isEmpty()) {
            $services = Service::active()
                ->with(['hairstyle_models' => function($q) {
                    $q->active()->orderBy('id', 'desc');
                }, 'category', 'sub_category'])
                ->has('hairstyle_models')
                ->get();
        }

        return view('service::backend.hairstyle_models.visualize', compact('module_action', 'services'));
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
                return '<img src='.$data->feature_image." class='avatar avatar-50 rounded-pill'>";
            })
            ->addColumn('service', function ($data) {
                return $data->service ? $data->service->name : '-';
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
            ->rawColumns(['action', 'image', 'status', 'check'])
            ->orderColumns(['id'], '-:column $1')
            ->toJson();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(HairstyleModelRequest $request)
    {
        $data = $request->except('feature_image');
        $model = HairstyleModel::create($data);

        if ($request->hasFile('feature_image')) {
            storeMediaFile($model, $request->file('feature_image'));
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

        return response()->json(['data' => $data, 'status' => true]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(HairstyleModelRequest $request, $id)
    {
        $model = HairstyleModel::findOrFail($id);
        $request_data = $request->except('feature_image');
        $model->update($request_data);

        if ($request->hasFile('feature_image')) {
            storeMediaFile($model, $request->file('feature_image'), 'feature_image');
        }

        return response()->json(['message' => 'Modèle de coiffure mis à jour avec succès.', 'status' => true], 200);
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

        return response()->json(['message' => 'Modèle de coiffure supprimé avec succès.', 'status' => true], 200);
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
