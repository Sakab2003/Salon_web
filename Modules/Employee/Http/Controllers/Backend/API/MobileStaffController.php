<?php

namespace Modules\Employee\Http\Controllers\Backend\API;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Carbon\Carbon;
use Hash;
use Illuminate\Http\Request;
use Modules\Commission\Models\EmployeeCommission;
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
            ->with(['media', 'branches', 'commissions.mainCommission'])
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
        $manager = $this->resolveManager($request);
        $branchId = $this->resolveBranchId($manager, $request);

        abort_unless($branchId, 403, 'Salon introuvable pour ce manager.');

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'mobile' => 'required|string',
            'password' => 'required|min:8',
            'commission_id' => 'nullable|integer|exists:commissions,id',
        ]);

        $employee = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'password' => Hash::make($request->password),
            'email_verified_at' => Carbon::now(),
            'status' => 1,
        ]);

        $employee->syncRoles(['employee']);

        BranchEmployee::create([
            'employee_id' => $employee->id,
            'branch_id' => $branchId,
        ]);

        if ($request->filled('commission_id')) {
            EmployeeCommission::updateOrCreate(
                ['employee_id' => $employee->id],
                ['commission_id' => $request->commission_id]
            );
        }

        return response()->json([
            'status' => true,
            'data' => new EmployeeResource($employee->load(['media', 'branches', 'commissions.mainCommission'])),
            'message' => __('messages.create_form', ['form' => __('employee.singular_title')]),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $manager = $this->resolveManager($request);
        $branchId = $this->resolveBranchId($manager, $request);

        abort_unless($branchId, 403, 'Salon introuvable pour ce manager.');

        $employee = $this->findBranchStaff($branchId, $id);

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$employee->id,
            'mobile' => 'required|string',
            'password' => 'nullable|min:8',
            'commission_id' => 'nullable|integer|exists:commissions,id',
            'status' => 'nullable|boolean',
        ]);

        $payload = $request->only(['first_name', 'last_name', 'email', 'mobile', 'status']);

        if ($request->filled('password')) {
            $payload['password'] = Hash::make($request->password);
        }

        $employee->update($payload);

        if ($request->has('commission_id')) {
            if ($request->commission_id) {
                EmployeeCommission::updateOrCreate(
                    ['employee_id' => $employee->id],
                    ['commission_id' => $request->commission_id]
                );
            } else {
                EmployeeCommission::where('employee_id', $employee->id)->delete();
            }
        }

        return response()->json([
            'status' => true,
            'data' => new EmployeeResource($employee->fresh(['media', 'branches', 'commissions.mainCommission'])),
            'message' => __('messages.update_form', ['form' => __('employee.singular_title')]),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $manager = $this->resolveManager($request);
        $branchId = $this->resolveBranchId($manager, $request);

        abort_unless($branchId, 403, 'Salon introuvable pour ce manager.');

        $employee = $this->findBranchStaff($branchId, $id);

        abort_if($employee->id === $manager->id, 403, 'Vous ne pouvez pas supprimer votre propre compte.');

        ServiceEmployee::where('employee_id', $employee->id)->delete();
        BranchEmployee::where('employee_id', $employee->id)->delete();
        EmployeeCommission::where('employee_id', $employee->id)->delete();
        $employee->delete();

        return response()->json([
            'status' => true,
            'message' => __('messages.delete_form', ['form' => __('employee.singular_title')]),
        ]);
    }

    public function toggleStatus(Request $request, $id)
    {
        $manager = $this->resolveManager($request);
        $branchId = $this->resolveBranchId($manager, $request);

        abort_unless($branchId, 403, 'Salon introuvable pour ce manager.');

        $employee = $this->findBranchStaff($branchId, $id);

        abort_if($employee->id === $manager->id, 403, 'Vous ne pouvez pas désactiver votre propre compte.');

        $employee->update(['status' => $employee->status ? 0 : 1]);

        return response()->json([
            'status' => true,
            'data' => new EmployeeResource($employee->fresh(['media', 'branches', 'commissions.mainCommission'])),
            'message' => __('messages.update_form', ['form' => __('employee.singular_title')]),
        ]);
    }

    protected function resolveManager(Request $request): User
    {
        $user = $request->user();

        abort_unless($user && $user->hasRole('manager'), 403, 'Accès réservé aux managers.');

        return $user;
    }

    protected function resolveBranchId(User $manager, Request $request): ?int
    {
        $branchId = $request->input('branch_id') ?: $manager->branch_id;

        if (! $branchId) {
            $branchId = Branch::where('manager_id', $manager->id)->value('id');
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
