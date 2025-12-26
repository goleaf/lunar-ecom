<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Bundle;
use App\Services\BundleService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Lunar\Facades\CartSession;
use Lunar\Facades\Currency;
use Lunar\Facades\StorefrontSession;

/**
 * Controller for storefront bundle functionality.
 */
class BundleController extends Controller
{
    public function __construct(
        protected BundleService $bundleService
    ) {}

    /**
     * Display a list of active bundles.
     */
    public function index(Request $request)
    {
        $bundles = Bundle::with(['product.media', 'items'])
            ->active()
            ->orderBy('display_order')
            ->paginate(12);

        if ($request->wantsJson()) {
            return response()->json($bundles);
        }

        return view('frontend.bundles.index', compact('bundles'));
    }

    /**
     * Display bundle product page.
     *
     * @param  Request  $request
     * @param  Bundle  $bundle
     * @return \Illuminate\View\View|JsonResponse
     */
    public function show(Request $request, Bundle $bundle)
    {
        $bundle->load(['product.media', 'items.product.variants', 'items.productVariant']);

        // Track view
        $bundle->incrementView();
        $this->bundleService->trackBundleEvent($bundle, 'view');

        $selectedItems = $request->input('selected_items');
        $quantity = (int) $request->input('quantity', 1);

        // Calculate pricing
        $pricing = $this->bundleService->calculateBundlePrice($bundle, $selectedItems, $quantity);

        // Check availability
        $availability = $this->bundleService->validateBundleAvailability($bundle, $selectedItems, $quantity);

        $currency = Currency::getDefault();
        $customerGroupId = StorefrontSession::getCustomerGroup()?->id;
        $individualTotal = $pricing['original_price'] ?? $bundle->calculateIndividualTotal($currency, $customerGroupId);
        $bundlePrice = $pricing['bundle_price'] ?? $bundle->calculatePrice($currency, $customerGroupId, $quantity);
        $savings = $pricing['savings_amount'] ?? $bundle->calculateSavings($currency, $customerGroupId);
        $availableStock = $bundle->getAvailableStock();

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

        return view('frontend.bundles.show', compact(
            'bundle',
            'currency',
            'individualTotal',
            'bundlePrice',
            'savings',
            'availableStock',
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
        [$selectedItems, $quantity] = $this->validateSelectedItems($request, $bundle, true);

        $pricing = $this->bundleService->calculateBundlePrice($bundle, $selectedItems, $quantity);

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
        [$selectedItems, $quantity] = $this->validateSelectedItems($request, $bundle, true);

        $availability = $this->bundleService->validateBundleAvailability($bundle, $selectedItems, $quantity);

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
        [$selectedItems, $quantity] = $this->validateSelectedItems($request, $bundle, true);

        try {
            $cart = CartSession::current();
            if (!$cart) {
                $cart = CartSession::create();
            }

            $result = $this->bundleService->addBundleToCart(
                $bundle,
                $cart,
                $quantity,
                $selectedItems
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

    /**
     * Validate selected item payloads for both fixed and dynamic bundles.
     *
     * @return array{0: array|null, 1?: int}
     */
    protected function validateSelectedItems(Request $request, Bundle $bundle, bool $withQuantity = false): array
    {
        $selectedItemsInput = $request->input('selected_items');

        $rules = [
            'selected_items' => 'nullable|array',
        ];

        if ($withQuantity) {
            $rules['quantity'] = 'integer|min:1';
        }

        if ($this->isDynamicSelection($bundle, $selectedItemsInput)) {
            $rules['selected_items.*.product_id'] = 'required|exists:lunar_products,id';
            $rules['selected_items.*.product_variant_id'] = 'nullable|exists:lunar_product_variants,id';
            $rules['selected_items.*.quantity'] = 'integer|min:1';
        } else {
            $rules['selected_items.*'] = 'nullable|integer|min:0';
        }

        $validated = $request->validate($rules);

        $selectedItems = $validated['selected_items'] ?? null;
        $quantity = $validated['quantity'] ?? 1;

        return $withQuantity ? [$selectedItems, $quantity] : [$selectedItems];
    }

    /**
     * Determine if the incoming selected items payload should be treated as dynamic.
     *
     * @param  mixed  $selectedItems
     */
    protected function isDynamicSelection(Bundle $bundle, $selectedItems): bool
    {
        return $bundle->isDynamic()
            || (is_array($selectedItems)
                && array_is_list($selectedItems)
                && isset($selectedItems[0]['product_id']));
    }
}


