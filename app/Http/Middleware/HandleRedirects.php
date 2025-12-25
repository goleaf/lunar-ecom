<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\RedirectService;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to handle URL redirects for old slugs.
 */
class HandleRedirects
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip redirects for admin, API, and other non-public routes
        if ($request->is('admin/*') || 
            $request->is('api/*') || 
            $request->is('_debugbar/*') ||
            $request->is('storage/*')) {
            return $next($request);
        }

        $redirectService = app(RedirectService::class);
        $redirect = $redirectService->handleRedirect($request);

        if ($redirect) {
            return $redirect;
        }

        return $next($request);
    }
}

