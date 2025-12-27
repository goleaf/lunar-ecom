<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\SizeGuideService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SizeGuideController extends Controller
{
    public function __construct(
        protected SizeGuideService $sizeGuideService
    ) {}

    /**
     * Display size guide for a product.
     */
    public function show(Product $product, Request $request)
    {
        $region = $request->input('region');
        $sizeGuide = $this->sizeGuideService->getSizeGuide($product, $region);

        if (!$sizeGuide) {
            return response()->json([
                'success' => false,
                'message' => 'No size guide available for this product.',
            ], 404);
        }

        $sizeGuide->load('sizeCharts');
        $fitStats = $this->sizeGuideService->getFitStatistics($product);

        return response()->json([
            'success' => true,
            'size_guide' => $sizeGuide,
            'fit_statistics' => $fitStats,
        ]);
    }

    /**
     * Get size recommendation.
     */
    public function recommend(Product $product, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'measurements' => 'required|array',
            'measurements.chest' => 'nullable|numeric|min:0',
            'measurements.waist' => 'nullable|numeric|min:0',
            'measurements.hips' => 'nullable|numeric|min:0',
            'measurements.length' => 'nullable|numeric|min:0',
            'measurements.shoulder' => 'nullable|numeric|min:0',
            'measurements.sleeve' => 'nullable|numeric|min:0',
            'measurements.inseam' => 'nullable|numeric|min:0',
            'measurements.neck' => 'nullable|numeric|min:0',
            'measurements.bust' => 'nullable|numeric|min:0',
            'body_info' => 'nullable|array',
            'body_info.height_cm' => 'nullable|integer|min:0',
            'body_info.weight_kg' => 'nullable|numeric|min:0',
            'body_info.body_type' => 'nullable|string|in:slim,regular,athletic,plus,petite,tall',
        ]);

        $recommendations = $this->sizeGuideService->getSizeRecommendationWithReviews(
            $product,
            $validated['measurements'],
            $validated['body_info'] ?? []
        );

        return response()->json([
            'success' => true,
            'recommendations' => $recommendations,
            'top_recommendation' => $recommendations[0] ?? null,
        ]);
    }

    /**
     * Submit fit review.
     */
    public function submitFitReview(Product $product, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_variant_id' => 'nullable|exists:product_variants,id',
            'purchased_size' => 'required|string|max:50',
            'recommended_size' => 'nullable|string|max:50',
            'height_cm' => 'nullable|integer|min:0|max:300',
            'weight_kg' => 'nullable|numeric|min:0|max:500',
            'body_type' => 'nullable|string|in:slim,regular,athletic,plus,petite,tall',
            'fit_rating' => 'required|in:too_small,slightly_small,perfect,slightly_large,too_large',
            'would_recommend_size' => 'boolean',
            'fit_notes' => 'nullable|string|max:1000',
            'fit_by_area' => 'nullable|array',
            'order_id' => 'nullable|exists:orders,id',
        ]);

        $review = $this->sizeGuideService->recordFitReview($product, array_merge($validated, [
            'customer_id' => auth()->user()?->customer?->id,
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Thank you for your fit feedback!',
            'review' => $review,
        ]);
    }

    /**
     * Get fit statistics.
     */
    public function fitStatistics(Product $product, Request $request): JsonResponse
    {
        $size = $request->input('size');
        
        $stats = $this->sizeGuideService->getFitStatistics($product, $size);
        $distribution = $this->sizeGuideService->getFitDistributionBySize($product);

        return response()->json([
            'success' => true,
            'statistics' => $stats,
            'distribution_by_size' => $distribution,
        ]);
    }
}



