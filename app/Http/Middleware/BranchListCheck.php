<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use Closure;
use Illuminate\Http\Request;

class BranchListCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            $branchId = request()->session()->get('selected_branch');
            $branches = Branch::getAllBranches();
            $selected_branch = $branches->where('id', $branchId)->first();
            $auth = auth()->user();

            if (auth()->user()->hasRole('manager')) {
                $mgrBranch = Branch::where('manager_id', auth()->id())->first() ?: (auth()->user()->branch_id ? Branch::find(auth()->user()->branch_id) : null);
                if ($mgrBranch) {
                    $selected_branch = $mgrBranch;
                    $branches = collect([$mgrBranch]);
                }
            } elseif (auth()->user()->hasRole('employee')) {
                try {
                    $empBranch = optional(auth()->user()->mainBranch()->first()) ?: (auth()->user()->branch ? Branch::find(auth()->user()->branch->branch_id) : null);
                    if ($empBranch) {
                        $selected_branch = $empBranch;
                        $branches = collect([$empBranch]);
                    }
                } catch (\Exception $e) {
                    \Log::error($e->getMessage());
                }
            } elseif (auth()->user()->hasRole('admin')) {
                if (str_contains($request->route()->getName(), 'backend.bookings')
                      && $request->route()->getName() !== 'backend.bookings.index_data'
                      && $request->route()->getName() !== 'backend.bookings.datatable_view'
                ) {
                    if (! isset($selected_branch) && count($branches) > 0) {
                        $selected_branch = $branches[0];
                    }
                }
            }

            $isSingleBranch = false;

            if (count($branches) == 1) {
                $isSingleBranch = true;
                $selected_branch = $branches[0] ?? $branches->first();
            }

            $data = [
                'auth_user_branches' => $branches,
                'selected_branch' => $selected_branch,
                'selected_branch_id' => isset($selected_branch) ? $selected_branch->id : 0,
                'is_single_branch' => $isSingleBranch,
                'permissions' => auth()->user()->getAllPermissions()->pluck('name')->toArray(),
            ];

            $request->merge([
                'selected_session_branch_id' => isset($selected_branch) ? $selected_branch->id : null,
                'is_single_branch' => $isSingleBranch,
            ]);

            view()->share($data);
        }

        return $next($request);
    }
}
