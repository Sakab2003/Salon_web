<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureMobileAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        abort_unless($user && $user->hasMobileAccess(), 403, 'Votre période d’essai mobile est terminée ou votre abonnement est inactif.');

        return $next($request);
    }
}