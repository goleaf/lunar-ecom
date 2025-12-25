<?php

namespace App\Http\Middleware;

use App\Models\Product;
use App\Services\RecommendationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to track product views for recommendations.
 */
class TrackProductView
{
    public function __construct(
        protected RecommendationService $recommendationService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only track on product show pages
        if ($request->routeIs('storefront.products.show') && $request->route('product')) {
            $product = $request->route('product');
            
            if ($product instanceof Product) {
                $userId = auth()->id();
                $sessionId = session()->getId();
                
                // Track view asynchronously to avoid blocking the response
                dispatch(function () use ($product, $userId, $sessionId) {
                    $this->recommendationService->trackView($product, $userId, $sessionId);
                })->afterResponse();
            }
        }

        return $response;
    }
}

