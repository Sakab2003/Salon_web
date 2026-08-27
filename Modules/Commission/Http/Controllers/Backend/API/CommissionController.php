<?php

namespace Modules\Commission\Http\Controllers\Backend\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Commission\Models\Commission;

class CommissionController extends Controller
{
    public function commissionList(Request $request)
    {
        $branchId = $request->input('branch_id');

        $query = Commission::query()
            ->where('status', 1)
            ->with('branches:id,name');

        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->whereDoesntHave('branches')
                    ->orWhereHas('branches', function ($branchQuery) use ($branchId) {
                        $branchQuery->where('branches.id', $branchId);
                    });
            });
        }

        $commissions = $query->orderBy('title')->get()->map(function (Commission $commission) {
            return [
                'id' => $commission->id,
                'title' => $commission->title,
                'commission_type' => $commission->commission_type,
                'commission_value' => $commission->commission_value,
                'branches' => $commission->branches->pluck('name')->values()->all(),
                'is_global' => $commission->branches->isEmpty(),
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $commissions,
            'message' => 'Commission list',
        ]);
    }
}
