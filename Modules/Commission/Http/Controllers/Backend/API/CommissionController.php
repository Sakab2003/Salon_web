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

    /**
     * Liste complète (avec statut) pour les managers — CRUD mobile.
     */
    public function allCommissions(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->hasAnyRole(['manager', 'admin']), 403, 'Accès réservé aux managers.');

        $branchId = $request->input('branch_id') ?: $this->resolveManagerBranch($user);

        $query = Commission::with('branches:id,name');

        if ($branchId && !$user->hasRole('admin')) {
            $query->where(function ($q) use ($branchId) {
                $q->whereDoesntHave('branches')
                    ->orWhereHas('branches', function ($bq) use ($branchId) {
                        $bq->where('branches.id', $branchId);
                    });
            });
        }

        $commissions = $query->orderBy('title')->get()->map(fn (Commission $c) => $this->format($c));

        return response()->json([
            'status' => true,
            'data' => $commissions,
            'message' => 'Liste complète des commissions',
        ]);
    }

    /**
     * Créer un nouveau modèle de commission.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->hasAnyRole(['manager', 'admin']), 403, 'Accès réservé aux managers.');

        $request->validate([
            'title'            => 'required|string|max:255',
            'commission_type'  => 'required|in:percentage,fixed',
            'commission_value' => 'required|numeric|min:0',
        ]);

        $commission = Commission::create([
            'title'            => $request->title,
            'commission_type'  => $request->commission_type,
            'commission_value' => $request->commission_value,
            'status'           => 1,
        ]);

        // Associer au salon du manager si pas admin
        if (!$user->hasRole('admin')) {
            $branchId = $this->resolveManagerBranch($user);
            if ($branchId) {
                $commission->branches()->sync([$branchId]);
            }
        } elseif ($request->filled('branch_ids')) {
            $commission->branches()->sync($request->branch_ids);
        }

        $commission->load('branches:id,name');

        return response()->json([
            'status'  => true,
            'data'    => $this->format($commission),
            'message' => 'Commission créée avec succès',
        ], 201);
    }

    /**
     * Modifier un modèle de commission existant.
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        abort_unless($user && $user->hasAnyRole(['manager', 'admin']), 403, 'Accès réservé aux managers.');

        $request->validate([
            'title'            => 'sometimes|required|string|max:255',
            'commission_type'  => 'sometimes|required|in:percentage,fixed',
            'commission_value' => 'sometimes|required|numeric|min:0',
            'status'           => 'sometimes|boolean',
        ]);

        $commission = Commission::findOrFail($id);

        // Un manager ne peut modifier que les commissions de son salon
        if (!$user->hasRole('admin')) {
            $branchId = $this->resolveManagerBranch($user);
            if ($branchId) {
                $isOwned = $commission->branches->isEmpty() ||
                    $commission->branches->contains('id', $branchId);
                abort_unless($isOwned, 403, 'Vous ne pouvez pas modifier cette commission.');
            }
        }

        $commission->update($request->only(['title', 'commission_type', 'commission_value', 'status']));

        if ($request->has('branch_ids') && $user->hasRole('admin')) {
            $commission->branches()->sync($request->branch_ids);
        }

        $commission->load('branches:id,name');

        return response()->json([
            'status'  => true,
            'data'    => $this->format($commission),
            'message' => 'Commission mise à jour avec succès',
        ]);
    }

    /**
     * Supprimer un modèle de commission.
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        abort_unless($user && $user->hasAnyRole(['manager', 'admin']), 403, 'Accès réservé aux managers.');

        $commission = Commission::findOrFail($id);

        // Un manager ne peut supprimer que les commissions de son salon
        if (!$user->hasRole('admin')) {
            $branchId = $this->resolveManagerBranch($user);
            if ($branchId) {
                $isOwned = $commission->branches->isEmpty() ||
                    $commission->branches->contains('id', $branchId);
                abort_unless($isOwned, 403, 'Vous ne pouvez pas supprimer cette commission.');
            }
        }

        $commission->branches()->detach();
        $commission->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Commission supprimée avec succès',
        ]);
    }

    // ---------------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------------

    protected function format(Commission $commission): array
    {
        return [
            'id'               => $commission->id,
            'title'            => $commission->title,
            'commission_type'  => $commission->commission_type,
            'commission_value' => $commission->commission_value,
            'status'           => (int) $commission->status,
            'branches'         => $commission->branches->pluck('name')->values()->all(),
            'branch_ids'       => $commission->branches->pluck('id')->values()->all(),
            'is_global'        => $commission->branches->isEmpty(),
            'display_value'    => $commission->commission_type === 'percentage'
                ? $commission->commission_value . ' %'
                : $commission->commission_value . ' F',
        ];
    }

    protected function resolveManagerBranch($user): ?int
    {
        if ($user->branch_id) {
            return (int) $user->branch_id;
        }
        return \App\Models\Branch::where('manager_id', $user->id)->value('id');
    }
}
