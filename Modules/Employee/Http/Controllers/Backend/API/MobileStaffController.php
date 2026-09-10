<?php

namespace Modules\Employee\Http\Controllers\Backend\API;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use App\Notifications\AccountAutoEmailCreated;
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

        // Générer un email automatique si aucun email fourni (app mobile)
        $emailProvided = !empty($request->email);
        $email = $emailProvided
            ? $request->email
            : $this->generateUniqueEmail($request->first_name, $request->last_name, $request->mobile);

        $plainPassword = $request->password;

        $employee = User::create([
            'first_name'        => $request->first_name,
            'last_name'         => $request->last_name,
            'email'             => $email,
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

        // Envoyer notification in-app si l'email a été généré automatiquement
        if (!$emailProvided) {
            $employee->notify(new AccountAutoEmailCreated($email, $plainPassword, 'employee'));
        }

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

    /**
     * Génère un email unique automatiquement au format prenom.nom.mobile@salon.app
     */
    protected function generateUniqueEmail(string $firstName, string $lastName, string $mobile): string
    {
        $first = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $this->removeAccents($firstName)));
        $last  = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $this->removeAccents($lastName)));
        $phone = preg_replace('/[^0-9]/', '', $mobile);

        $email = "{$first}.{$last}.{$phone}@salon.app";
        $i = 1;
        while (User::where('email', $email)->exists()) {
            $email = "{$first}.{$last}.{$phone}{$i}@salon.app";
            $i++;
        }
        return $email;
    }

    /**
     * Supprime les accents d'une chaîne (é→e, ç→c, etc.)
     */
    protected function removeAccents(string $str): string
    {
        $from = ['à','â','ä','á','ã','å','è','é','ê','ë','ì','î','ï','ó','ô','ö','ò','õ','ù','û','ü','ú','ý','ÿ','ñ','ç',
                 'À','Â','Ä','Á','Ã','Å','È','É','Ê','Ë','Ì','Î','Ï','Ó','Ô','Ö','Ò','Õ','Ù','Û','Ü','Ú','Ý','Ñ','Ç'];
        $to   = ['a','a','a','a','a','a','e','e','e','e','i','i','i','o','o','o','o','o','u','u','u','u','y','y','n','c',
                 'A','A','A','A','A','A','E','E','E','E','I','I','I','O','O','O','O','O','U','U','U','U','Y','N','C'];
        return str_replace($from, $to, $str);
    }
}
