<?php

namespace Modules\Commission\Http\Controllers\Backend;

use App\Authorizable;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Commission\Models\Commission;

class CommissionsController extends Controller
{
    // use Authorizable;

    public function __construct()
    {
        // Page Title
        $this->module_title = 'Commissions';

        // module name
        $this->module_name = 'commissions';

        // directory path of the module
        $this->module_path = 'commission::backend';

        view()->share([
            'module_title' => $this->module_title,
            'module_icon' => 'fa-regular fa-sun',
            'module_name' => $this->module_name,
            'module_path' => $this->module_path,
        ]);

        $this->middleware(['permission:view_commission'])->only('index');
        $this->middleware(['permission:edit_commission'])->only('edit', 'update');
        $this->middleware(['permission:add_commission'])->only('store');
        $this->middleware(['permission:delete_commission'])->only('destroy');
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $module_action = 'List';

        return view('commission::backend.commissions.index_datatable', compact('module_action'));
    }

    /**
     * Select Options for Select 2 Request/ Response.
     *
     * @return Response
     */
    public function index_list(Request $request)
    {
        $term = trim($request->q);

        $query_data = Commission::where('status', 1)
            ->where(function ($q) {
                if (! empty($term)) {
                    $q->orWhere('name', 'LIKE', "%$term%");
                }
            })->get();

        $data = [];

        foreach ($query_data as $row) {
            $data[] = [
                'id' => $row->id,
                'name' => __($row->title),
                'type' => $row->commission_type,
                'value' => $row->commission_value,

            ];
        }

        return response()->json($data);
    }

    public function index_data(\Yajra\DataTables\DataTables $datatable)
    {
        $module_name = $this->module_name;
        $query = Commission::with('branches');

        return $datatable->eloquent($query)
            ->addColumn('action', function ($data) use ($module_name) {
                return view('commission::backend.commissions.action_column', compact('module_name', 'data'));
            })
            ->editColumn('title', function ($data) {
                return e($data->title);
            })
            ->editColumn('commission_type', function ($data) {
                return $data->commission_type === 'percentage' ? 'Pourcentage (%)' : 'Montant fixe';
            })
            ->editColumn('commission_value', function ($data) {
                return $data->commission_type === 'percentage' ? $data->commission_value . ' %' : $data->commission_value;
            })
            ->addColumn('branches', function ($data) {
                if ($data->branches->count() > 0) {
                    return $data->branches->pluck('name')->map(function($n) {
                        return '<span class="badge bg-soft-info me-1">' . e($n) . '</span>';
                    })->implode(' ');
                }
                return '<span class="badge bg-soft-primary">Tous les salons</span>';
            })
            ->rawColumns(['action', 'branches'])
            ->toJson();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $module_action = 'Create';
        $branches = \App\Models\Branch::where('status', 1)->get();

        return view('commission::backend.commissions.create', compact('module_action', 'branches'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'commission_type' => 'required|string',
            'commission_value' => 'required|numeric',
        ]);

        $data = $request->except('branches');
        $commission = Commission::create($data);

        if ($request->has('branches')) {
            $commission->branches()->sync($request->branches);
        }

        $message = 'Nouvelle commission ajoutée avec succès';

        if ($request->wantsJson()) {
            return response()->json(['message' => $message, 'status' => true], 200);
        }

        return redirect()->route('backend.commissions.index')->with('success', $message);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        $module_action = 'Show';

        $data = Commission::with('branches')->findOrFail($id);

        return view('commission::backend.commissions.show', compact('module_action', 'data'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        $data = Commission::with('branches')->findOrFail($id);
        $branches = \App\Models\Branch::where('status', 1)->get();

        if (request()->wantsJson()) {
            $data['branch_ids'] = $data->branches->pluck('id')->toArray();
            return response()->json(['data' => $data, 'status' => true]);
        }

        $module_action = 'Edit';
        return view('commission::backend.commissions.edit', compact('module_action', 'data', 'branches'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'commission_type' => 'required|string',
            'commission_value' => 'required|numeric',
        ]);

        $commission = Commission::findOrFail($id);
        $commission->update($request->except('branches'));

        if ($request->has('branches')) {
            $commission->branches()->sync($request->branches);
        } else {
            $commission->branches()->detach();
        }

        $message = 'Commission mise à jour avec succès';

        if ($request->wantsJson()) {
            return response()->json(['message' => $message, 'status' => true], 200);
        }

        return redirect()->route('backend.commissions.index')->with('success', $message);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        if (env('IS_DEMO')) {
            return response()->json(['message' => __('messages.permission_denied'), 'status' => false], 200);
        }
        $data = Commission::findOrFail($id);

        $data->delete();

        $message = Str::singular('Commissions').' Deleted Successfully';

        if (request()->wantsJson()) {
            return response()->json(['message' => $message, 'status' => true], 200);
        } else {
            flash('<i class="fas fa-check"></i> '.label_case($this->module_name).' Deleted Successfully!')->success()->important();

            return redirect("app//notification/$this->module_name");
        }
    }

    /**
     * List of trashed ertries
     * works if the softdelete is enabled.
     *
     * @return Response
     */
    public function trashed()
    {
        $module_name = $this->module_name;

        $module_name_singular = Str::singular($module_name);

        $module_action = 'Trash List';

        $data = Commission::onlyTrashed()->orderBy('deleted_at', 'desc')->paginate();

        return view('commission::backend.commissions.trash', compact("$data", 'module_name_singular', 'module_action'));
    }

    /**
     * Restore a soft deleted entry.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function restore($id)
    {
        $module_action = 'Restore';

        $data = Commission::withTrashed()->find($id);
        $data->restore();

        $message = __('messages.commission_data');

        return response()->json(['message' => $message, 'status' => true], 200);
    }
}
