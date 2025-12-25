<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Bundle;
use App\Services\BundleService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Lunar\Facades\CartSession;

/**
 * Controller for storefront bundle functionality.
 */
class BundleController extends Controller
{
    public function __construct(
        protected BundleService $bundleService
    ) {}

    /**
     * Display bundle product page.
     *
     * @param  Request  $request
     * @param  Bundle  $bundle
     * @return \Illuminate\View\View|JsonResponse
     */
    public function show(Request $request, Bundle $bundle)
    {
        $bundle->load(['product', 'items.product', 'items.productVariant', 'category']);

        // Track view
        $bundle->incrementView();
        $this->bundleService->trackBundleEvent($bundle, 'view');

        // Calculate pricing
        $selectedItems = $request->get('selected_items');
        $pricing = $this->bundleService->calculateBundlePrice($bundle, $selectedItems);

        // Check availability
        $availability = $this->bundleService->validateBundleAvailability($bundle, $selectedItems);

        // Get available products for dynamic bundles
        $availableProducts = null;
        if ($bundle->isDynamic() && $bundle->category_id) {
            $availableProducts = $this->bundleService->getAvailableProductsForBundle($bundle);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'bundle' => $bundle,
                'pricing' => $pricing,
                'availability' => $availability,
                'available_products' => $availableProducts,
            ]);
        }

        return view('storefront.bundles.show', compact(
            'bundle',
            'pricing',
            'availability',
            'availableProducts'
        ));
    }

    /**
     * Calculate bundle price (AJAX endpoint).
     *
     * @param  Request  $request
     * @param  Bundle  $bundle
     * @return JsonResponse
     */
    public function calculatePrice(Request $request, Bundle $bundle): JsonResponse
    {
        $validated = $request->validate([
            'selected_items' => 'nullable|array',
            'selected_items.*.product_id' => 'required|exists:lunar_products,id',
            'selected_items.*.product_variant_id' => 'nullable|exists:lunar_product_variants,id',
            'selected_items.*.quantity' => 'integer|min:1',
        ]);

        $pricing = $this->bundleService->calculateBundlePrice($bundle, $validated['selected_items'] ?? null);

        return response()->json($pricing);
    }

    /**
     * Validate bundle availability.
     *
     * @param  Request  $request
     * @param  Bundle  $bundle
     * @return JsonResponse
     */
    public function checkAvailability(Request $request, Bundle $bundle): JsonResponse
    {
        $validated = $request->validate([
            'selected_items' => 'nullable|array',
            'quantity' => 'integer|min:1',
        ]);

        $availability = $this->bundleService->validateBundleAvailability(
            $bundle,
            $validated['selected_items'] ?? null,
            $validated['quantity'] ?? 1
        );

        return response()->json($availability);
    }

    /**
     * Validate dynamic bundle selection.
     *
     * @param  Request  $request
     * @param  Bundle  $bundle
     * @return JsonResponse
     */
    public function validateSelection(Request $request, Bundle $bundle): JsonResponse
    {
        $validated = $request->validate([
            'selected_items' => 'required|array',
            'selected_items.*.product_id' => 'required|exists:lunar_products,id',
            'selected_items.*.product_variant_id' => 'nullable|exists:lunar_product_variants,id',
            'selected_items.*.quantity' => 'integer|min:1',
            'selected_items.*.group_name' => 'nullable|string',
        ]);

        $validation = $this->bundleService->validateDynamicBundleSelection($bundle, $validated['selected_items']);

        return response()->json($validation);
    }

    /**
     * Add bundle to cart.
     *
     * @param  Request  $request
     * @param  Bundle  $bundle
     * @return JsonResponse
     */
    public function addToCart(Request $request, Bundle $bundle): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'integer|min:1',
            'selected_items' => 'nullable|array',
            'selected_items.*.product_id' => 'required|exists:lunar_products,id',
            'selected_items.*.product_variant_id' => 'nullable|exists:lunar_product_variants,id',
            'selected_items.*.quantity' => 'integer|min:1',
        ]);

        try {
            $cart = CartSession::current();
            if (!$cart) {
                $cart = CartSession::create();
            }

            $result = $this->bundleService->addBundleToCart(
                $bundle,
                $cart,
                $validated['quantity'] ?? 1,
                $validated['selected_items'] ?? null
            );

            return response()->json([
                'message' => 'Bundle added to cart successfully',
                'cart' => $cart->fresh(),
                'pricing' => $result['pricing'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get available products for "Build Your Own Bundle".
     *
     * @param  Request  $request
     * @param  Bundle  $bundle
     * @return JsonResponse
     */
    public function getAvailableProducts(Request $request, Bundle $bundle): JsonResponse
    {
        $products = $this->bundleService->getAvailableProductsForBundle($bundle);

        return response()->json([
            'products' => $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->translateAttribute('name'),
                    'variants' => $product->variants->map(function ($variant) {
                        return [
                            'id' => $variant->id,
                            'sku' => $variant->sku,
                            'price' => $variant->base_price ?? $variant->price ?? 0,
                        ];
                    }),
                ];
            }),
        ]);
    }
}
