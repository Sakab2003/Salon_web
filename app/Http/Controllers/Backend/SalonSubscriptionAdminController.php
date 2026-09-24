<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SalonSubscriptionAdminController extends Controller
{
    /**
     * Afficher le tableau de bord de gestion des abonnements SALON
     * Réservé exclusivement au super-administrateur admin@salon.com
     */
    public function index()
    {
        if (!auth()->check() || auth()->user()->email !== 'admin@salon.com') {
            abort(403, "Accès réservé exclusivement à l'administrateur principal (admin@salon.com).");
        }

        $plans = \Modules\Subscriptions\Models\Plan::whereNull('deleted_at')
            ->where('status', 1)
            ->orderBy('duration', 'asc')
            ->get();

        return view('backend.subscriptions.hub', compact('plans'));
    }

    public function updatePrices(Request $request)
    {
        if (!auth()->check() || auth()->user()->email !== 'admin@salon.com') {
            abort(403, "Accès non autorisé.");
        }

        $request->validate([
            'prices' => 'required|array',
            'discounts' => 'nullable|array'
        ]);

        foreach ($request->prices as $planId => $amount) {
            $discount = $request->input("discounts.{$planId}");
            \Modules\Subscriptions\Models\Plan::where('id', $planId)
                ->update([
                    'amount' => max(1, (int) $amount),
                    'discount_percentage' => $discount !== null ? max(0, (float) $discount) : null
                ]);
        }

        return redirect()->route('backend.subscriptions.hub')
            ->with('success', '✅ Prix des abonnements mis à jour avec succès. L\'application mobile utilisera ces nouveaux tarifs.');
    }
}
