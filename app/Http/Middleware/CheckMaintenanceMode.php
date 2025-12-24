<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\SystemSetting;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        // Check if maintenance mode is enabled
        if (SystemSetting::isMaintenanceMode()) {
            // Allow admins and sysadmins through
            if (
                auth()->check() &&
                in_array(auth()->user()->role, ['administrator', 'director', 'system_admin'])
            ) {
                return $next($request);
            }

            // Block regular users
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'System is currently under maintenance. Please try again later.'
                ], 503);
            }

            return response()->view('errors.maintenance', [], 503);
        }

        return $next($request);
    }
}
