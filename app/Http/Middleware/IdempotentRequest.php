<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Idempotent request middleware.
 * 
 * Ensures operations can be safely retried without side effects.
 * Uses idempotency keys to prevent duplicate processing.
 */
class IdempotentRequest
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only apply to POST/PUT/PATCH requests
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            return $next($request);
        }

        // Get idempotency key from header
        $idempotencyKey = $request->header('Idempotency-Key');

        if (!$idempotencyKey) {
            return $next($request);
        }

        $cacheKey = "idempotent:{$idempotencyKey}";

        // Check if we've seen this key before
        $cachedResponse = Cache::get($cacheKey);

        if ($cachedResponse) {
            // Return cached response
            return response()->json(
                json_decode($cachedResponse['body'], true),
                $cachedResponse['status'],
                $cachedResponse['headers'] ?? []
            );
        }

        // Process request
        $response = $next($request);

        // Cache successful responses (2xx status codes)
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            Cache::put($cacheKey, [
                'body' => $response->getContent(),
                'status' => $response->getStatusCode(),
                'headers' => $response->headers->all(),
            ], now()->addHours(24)); // Cache for 24 hours
        }

        return $response;
    }
}

