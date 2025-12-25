<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ComparisonService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Controller for product comparison functionality.
 */
class ComparisonController extends Controller
{
    public function __construct(
        protected ComparisonService $comparisonService
    ) {}

    /**
     * Display comparison page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $data = $this->comparisonService->getComparisonData();

        return view('storefront.comparison.index', $data);
    }

    /**
     * Add product to comparison.
     *
     * @param  Request  $request
     * @param  Product  $product
     * @return JsonResponse
     */
    public function add(Request $request, Product $product): JsonResponse
    {
        if ($this->comparisonService->isFull()) {
            return response()->json([
                'success' => false,
                'message' => 'Maximum ' . ComparisonService::MAX_COMPARISON_ITEMS . ' products can be compared at once.',
            ], 422);
        }

        $added = $this->comparisonService->addProduct($product);

        if (!$added) {
            return response()->json([
                'success' => false,
                'message' => 'Product is already in comparison.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Product added to comparison.',
            'count' => $this->comparisonService->getComparisonCount(),
        ]);
    }

    /**
     * Remove product from comparison.
     *
     * @param  Request  $request
     * @param  Product  $product
     * @return JsonResponse
     */
    public function remove(Request $request, Product $product): JsonResponse
    {
        $this->comparisonService->removeProduct($product);

        return response()->json([
            'success' => true,
            'message' => 'Product removed from comparison.',
            'count' => $this->comparisonService->getComparisonCount(),
        ]);
    }

    /**
     * Clear comparison.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function clear(Request $request): JsonResponse
    {
        $this->comparisonService->clearComparison();

        return response()->json([
            'success' => true,
            'message' => 'Comparison cleared.',
        ]);
    }

    /**
     * Get comparison count.
     *
     * @return JsonResponse
     */
    public function count(): JsonResponse
    {
        return response()->json([
            'count' => $this->comparisonService->getComparisonCount(),
            'max' => ComparisonService::MAX_COMPARISON_ITEMS,
        ]);
    }

    /**
     * Check if product is in comparison.
     *
     * @param  Product  $product
     * @return JsonResponse
     */
    public function check(Product $product): JsonResponse
    {
        return response()->json([
            'in_comparison' => $this->comparisonService->isInComparison($product),
        ]);
    }
}
