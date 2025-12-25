<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP Cache Middleware
 * 
 * Adds cache headers for API responses.
 * Used for read-only catalog endpoints.
 */
class HttpCache
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, int $maxAge = 300): Response
    {
        $response = $next($request);

        // Only cache GET requests with successful responses
        if ($request->isMethod('GET') && $response->getStatusCode() === 200) {
            $response->headers->set('Cache-Control', "public, max-age={$maxAge}, s-maxage={$maxAge}");
            $response->headers->set('Vary', 'Accept, Accept-Language');
            $response->headers->set('ETag', md5($response->getContent()));
        }

        return $response;
    }
}

