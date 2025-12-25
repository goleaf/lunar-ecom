<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rate limiting middleware for checkout endpoints.
 * 
 * Protects checkout from abuse and overload.
 */
class RateLimitCheckout
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = 'checkout:' . ($request->user()?->id ?? $request->ip());

        // Limit to 10 checkout attempts per minute per user/IP
        $executed = RateLimiter::attempt(
            $key,
            $perMinute = 10,
            function () use ($next, $request) {
                return $next($request);
            },
            $decaySeconds = 60
        );

        if (!$executed) {
            return response()->json([
                'message' => 'Too many checkout attempts. Please try again later.',
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        return $executed;
    }
}

