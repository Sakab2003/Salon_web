<?php

namespace App\Http\Middleware;

use Closure;

class localization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle($request, Closure $next)
    {
        // Check header request and determine localizaton
        $sessionLocal = session()->get('locale') ? session()->get('locale') : 'fr';

        // Accepter salon-localization (mobile) OU frezka-localization (web)
        if ($request->hasHeader('salon-localization')) {
            $local = $request->header('salon-localization');
        } elseif ($request->hasHeader('frezka-localization')) {
            $local = $request->header('frezka-localization');
        } else {
            $local = $sessionLocal;
        }
        // set laravel localization
        app()->setLocale($local);
        // continue request
        return $next($request);
    }
}
