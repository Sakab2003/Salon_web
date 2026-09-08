<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureMobileAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Authentification requise.');
        }

        // Admins always have access
        if ($user->hasAnyRole(['admin', 'super-admin'])) {
            return $next($request);
        }

        // Auto-start trial on first mobile access if not started yet
        if ($user->hasAnyRole(['manager', 'employee', 'receptionist', 'staff', 'coiffeur', 'barber', 'stylist'])) {
            $owner = $user->mobileSubscriptionOwner();
            if ($owner->mobile_trial_started_at === null) {
                $owner->update(['mobile_trial_started_at' => now()]);
                $owner->refresh();
                if ($user->id !== $owner->id) {
                    $user->refresh();
                }
            }
        }

        if (! $user->hasMobileAccess()) {
            $message = "Votre periode d'essai mobile est terminee ou votre abonnement est inactif.";

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => $message,
                    'code' => 'subscription_required',
                ], 403);
            }

            abort(403, $message);
        }

        return $next($request);
    }
}