<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MustChangePassword
{
    /**
     * Handle an incoming request.
     *
     * Redirect users who must change their password before continuing.
     * Only allows access to password.change and logout routes.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->must_change_password) {
            // Allow access to password change form, update submission and logout routes
            if (!$request->routeIs('password.change') && !$request->routeIs('change.update') && !$request->routeIs('logout')) {
                return redirect()->route('password.change')
                    ->with('warning', 'You must change your password before continuing.');
            }
        }

        return $next($request);
    }
}
