<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Ensure the request is made by an authenticated staff user.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $staff = auth('staff')->user();

        if (! $staff) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('filament.admin.auth.login');
        }

        // Optional role gate: allow admins (and super admins if present).
        if (method_exists($staff, 'hasRole') && ! $staff->hasRole(['admin', 'super_admin'])) {
            abort(403);
        }

        return $next($request);
    }
}

