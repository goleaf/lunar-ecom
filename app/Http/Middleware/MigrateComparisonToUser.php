<?php

namespace App\Http\Middleware;

use App\Services\ComparisonService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to migrate session-based comparisons to user-based when user logs in.
 */
class MigrateComparisonToUser
{
    public function __construct(
        protected ComparisonService $comparisonService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If user is authenticated, migrate their session comparison
        if (auth()->check()) {
            $this->comparisonService->migrateToUser(auth()->id());
        }

        return $next($request);
    }
}


