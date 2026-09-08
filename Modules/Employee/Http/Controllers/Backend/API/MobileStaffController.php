<?php

namespace Modules\Employee\Http\Controllers\Backend\API;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Carbon\Carbon;
use Hash;
use Illuminate\Http\Request;
use Modules\Employee\Models\BranchEmployee;
use Modules\Employee\Transformers\EmployeeResource;
use Modules\Service\Models\ServiceEmployee;

class MobileStaffController extends Controller
{
    public function staffList(Request $request)
    {
        $manager = $this->resolveManager($request);
        $branchId = $this->resolveBranchId($manager, $request);

        abort_unless($branchId, 403, 'Salon introuvable pour ce manager.');

        $staff = User::role(['employee', 'manager'])
            ->with(['media', 'branches', 'profile', 'rating', 'commissions.mainCommission'])
            ->whereHas('branches', function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })
            ->orderBy('first_name')
            ->get();

        return response()->json([
            'status' => true,
            'data' => EmployeeResource::collection($staff),
            'message' => __('employee.employee_list'),
        ]);
    }

    public function store(Request $request)
    {
        $manager  = $this->resolveManager($request);
        $branchId = $this->resolveBranchId($manager, $request);

        abort_unless($branchId, 403, 'Salon introuvable pour ce manager.');

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'nullable|email|unique:users,email',
            'mobile'     => 'required|string|unique:users,mobile',
            'password'   => 'required|min:8',
        ]);

        $employee = User::create([
            'first_name'        => $request->first_name,
            'last_name'         => $request->last_name,
            'email'             => $request->email ?: null,
            'mobile'            => $request->mobile,
            'password'          => Hash::make($request->password),
            'email_verified_at' => Carbon::now(),
            'status'            => 1,
            'show_in_calender'  => 1,
        ]);

        $employee->syncRoles(['employee']);

        BranchEmployee::create([
            'employee_id' => $employee->id,
            'branch_id'   => $branchId,
        ]);

        return response()->json([
            'status'  => true,
            'data'    => new EmployeeResource($employee->load(['media', 'branches'])),
            'message' => __('messages.create_form', ['form' => __('employee.singular_title')]),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $manager  = $this->resolveManager($request);
        $branchId = $this->resolveBranchId($manager, $request);

        abort_unless($branchId, 403, 'Salon introuvable pour ce manager.');

        $employee = $this->findBranchStaff($branchId, $id);

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'nullable|email|unique:users,email,' . $employee->id,
            'mobile'     => 'required|string|unique:users,mobile,' . $employee->id,
            'password'   => 'nullable|min:8',
            'status'     => 'nullable|boolean',
        ]);

        $payload = $request->only(['first_name', 'last_name', 'email', 'mobile', 'status']);

        if ($request->filled('password')) {
            $payload['password'] = Hash::make($request->password);
        }

        $employee->update($payload);

        return response()->json([
            'status'  => true,
            'data'    => new EmployeeResource($employee->fresh(['media', 'branches'])),
            'message' => __('messages.update_form', ['form' => __('employee.singular_title')]),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $manager  = $this->resolveManager($request);
        $branchId = $this->resolveBranchId($manager, $request);

        abort_unless($branchId, 403, 'Salon introuvable pour ce manager.');

        $employee = $this->findBranchStaff($branchId, $id);

        abort_if($employee->id === $manager->id, 403, 'Vous ne pouvez pas supprimer votre propre compte.');

        ServiceEmployee::where('employee_id', $employee->id)->delete();
        BranchEmployee::where('employee_id', $employee->id)->delete();
        $employee->delete();

        return response()->json([
            'status'  => true,
            'message' => __('messages.delete_form', ['form' => __('employee.singular_title')]),
        ]);
    }

    public function toggleStatus(Request $request, $id)
    {
        $manager  = $this->resolveManager($request);
        $branchId = $this->resolveBranchId($manager, $request);

        abort_unless($branchId, 403, 'Salon introuvable pour ce manager.');

        $employee = $this->findBranchStaff($branchId, $id);

        abort_if($employee->id === $manager->id, 403, 'Vous ne pouvez pas désactiver votre propre compte.');

        $employee->update(['status' => $employee->status ? 0 : 1]);

        return response()->json([
            'status'  => true,
            'data'    => new EmployeeResource($employee->fresh(['media', 'branches'])),
            'message' => __('messages.update_form', ['form' => __('employee.singular_title')]),
        ]);
    }

    protected function resolveManager(Request $request): User
    {
        $user = $request->user();
        abort_unless($user && ($user->hasAnyRole(['manager', 'admin']) || $user->is_manager), 403, 'Acces reserve aux managers.');
        return $user;
    }

    protected function resolveBranchId(User $manager, Request $request): ?int
    {
        $requestedBranchId = (int) $request->input('branch_id', 0);
        $branchId = $requestedBranchId > 0 ? $requestedBranchId : $manager->branch_id;

        if (!$branchId) {
            $branchId = Branch::where('manager_id', $manager->id)->value('id');
        }

        if (!$branchId && $manager->hasRole('admin')) {
            $branchId = $request->input('selected_session_branch_id')
                ?: session('selected_session_branch_id');
            if (!$branchId) {
                $branchId = Branch::where('status', 1)->value('id');
            }
        }

        return $branchId ? (int) $branchId : null;
    }

    protected function findBranchStaff(int $branchId, int $employeeId): User
    {
        return User::role(['employee', 'manager'])
            ->whereHas('branches', function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })
            ->findOrFail($employeeId);
    }
}
