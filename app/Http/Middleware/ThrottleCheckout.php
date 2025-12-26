<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Rate limiting middleware for checkout endpoints.
 */
class ThrottleCheckout
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $key = 'checkout:' . ($request->user()?->id ?? $request->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            
            return response()->json([
                'message' => 'Too many checkout attempts. Please try again later.',
                'retry_after' => $seconds,
            ], 429)->withHeaders([
                'Retry-After' => $seconds,
                'X-RateLimit-Limit' => 5,
                'X-RateLimit-Remaining' => 0,
            ]);
        }

        RateLimiter::hit($key, 60); // 5 attempts per minute

        $response = $next($request);

        $response->headers->set('X-RateLimit-Limit', 5);
        $response->headers->set('X-RateLimit-Remaining', RateLimiter::remaining($key, 5));

        return $response;
    }
}


