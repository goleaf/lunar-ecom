<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\RecommendationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Controller for product recommendations.
 */
class RecommendationController extends Controller
{
    public function __construct(
        protected RecommendationService $recommendationService
    ) {}

    /**
     * Get recommendations for a product.
     *
     * @param  Request  $request
     * @param  Product  $product
     * @return JsonResponse
     */
    public function index(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'algorithm' => 'in:related,frequently_bought_together,cross_sell,personalized,collaborative,hybrid',
            'location' => 'in:product_page,cart,checkout,post_purchase',
            'limit' => 'integer|min:1|max:50',
        ]);

        $algorithm = $validated['algorithm'] ?? 'hybrid';
        $location = $validated['location'] ?? 'product_page';
        $limit = $validated['limit'] ?? 10;

        $userId = auth()->id();
        $sessionId = session()->getId();

        $recommendations = $this->recommendationService->getRecommendations(
            $product,
            $algorithm,
            $location,
            $userId,
            $sessionId,
            $limit
        );

        return response()->json([
            'recommendations' => $recommendations,
            'algorithm' => $algorithm,
            'location' => $location,
        ]);
    }

    /**
     * Track product view.
     *
     * @param  Request  $request
     * @param  Product  $product
     * @return JsonResponse
     */
    public function trackView(Request $request, Product $product): JsonResponse
    {
        $userId = auth()->id();
        $sessionId = session()->getId();

        $this->recommendationService->trackView($product, $userId, $sessionId);

        return response()->json(['message' => 'View tracked']);
    }

    /**
     * Track recommendation click.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function trackClick(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source_product_id' => 'required|exists:products,id',
            'recommended_product_id' => 'required|exists:products,id',
            'recommendation_type' => 'required|string',
            'display_location' => 'required|string',
            'recommendation_algorithm' => 'nullable|string',
        ]);

        $sourceProduct = Product::findOrFail($validated['source_product_id']);
        $recommendedProduct = Product::findOrFail($validated['recommended_product_id']);

        $userId = auth()->id();
        $sessionId = session()->getId();

        $this->recommendationService->trackClick(
            $sourceProduct,
            $recommendedProduct,
            $validated['recommendation_type'],
            $validated['display_location'],
            $validated['recommendation_algorithm'] ?? null,
            $userId,
            $sessionId
        );

        return response()->json(['message' => 'Click tracked']);
    }

    /**
     * Get frequently bought together products.
     *
     * @param  Request  $request
     * @param  Product  $product
     * @return JsonResponse
     */
    public function frequentlyBoughtTogether(Request $request, Product $product): JsonResponse
    {
        $limit = $request->get('limit', 10);
        
        $products = $this->recommendationService->getFrequentlyBoughtTogether($product, $limit);

        return response()->json(['products' => $products]);
    }

    /**
     * Get personalized recommendations.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function personalized(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        $userId = auth()->id();
        $sessionId = session()->getId();

        $products = $this->recommendationService->getPersonalizedRecommendations($userId, $sessionId, $limit);

        return response()->json(['products' => $products]);
    }
}

