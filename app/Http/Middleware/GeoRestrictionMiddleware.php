<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\GeoRestrictionService;
use Lunar\Facades\StorefrontSession;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to enforce geo-restrictions based on channel and country.
 */
class GeoRestrictionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $channel = StorefrontSession::getChannel();
        
        if (!$channel) {
            return $next($request);
        }

        $geoService = app(GeoRestrictionService::class);
        $countryCode = $geoService->getCountryFromRequest();
        
        if ($countryCode && !$geoService->isCountryAllowed($channel, $countryCode)) {
            // Country is blocked for this channel
            abort(403, 'This content is not available in your country.');
        }

        return $next($request);
    }
}

