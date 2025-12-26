<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ProductVariant;

/**
 * Middleware to track variant views.
 */
class TrackVariantView
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Track variant view if variant is in route
        if ($request->route('variant') instanceof ProductVariant) {
            $variant = $request->route('variant');
            
            $variant->trackView([
                'session_id' => session()->getId(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'user_id' => auth()->id(),
                'channel_id' => $request->header('X-Channel-Id'),
                'referrer' => $request->header('referer'),
            ]);
        }

        return $response;
    }
}


