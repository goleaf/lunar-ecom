<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bundle;
use App\Models\BundleItem;
use App\Services\BundleService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Lunar\Models\Product;

/**
 * Admin controller for bundle management.
 */
class BundleController extends Controller
{
    public function __construct(
        protected BundleService $bundleService
    ) {}

    /**
     * Display bundle builder interface.
     *
     * @param  Request  $request
     * @return \Illuminate\View\View|JsonResponse
     */
    public function index(Request $request)
    {
        // Admin-only: require staff authentication
        if (!auth('staff')->check()) {
            abort(403, 'Unauthorized');
        }

        $bundles = Bundle::with(['product', 'items.product'])->paginate(20);

        if ($request->wantsJson()) {
            return response()->json($bundles);
        }

        return view('admin.bundles.index', compact('bundles'));
    }

    /**
     * Show bundle builder.
     *
     * @param  Bundle  $bundle
     * @return \Illuminate\View\View|JsonResponse
     */
    public function show(Bundle $bundle)
    {
        // Admin-only: require staff authentication
        if (!auth('staff')->check()) {
            abort(403, 'Unauthorized');
        }

        $bundle->load(['product', 'items.product', 'items.productVariant', 'category']);

        return response()->json($bundle);
    }

    /**
     * Store a new bundle.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // Admin-only: require staff authentication
        if (!auth('staff')->check()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'product_id' => 'required|exists:lunar_products,id',
            'bundle_type' => 'required|in:fixed,dynamic',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'min_items' => 'nullable|integer|min:1',
            'max_items' => 'nullable|integer|min:1',
            'category_id' => 'nullable|exists:lunar_categories,id',
            'show_individual_prices' => 'boolean',
            'show_savings' => 'boolean',
            'allow_individual_returns' => 'boolean',
        ]);

        $bundle = Bundle::create($validated);

        // Mark product as bundle
        $product = Product::find($validated['product_id']);
        $product->update(['is_bundle' => true]);

        return response()->json([
            'message' => 'Bundle created successfully',
            'bundle' => $bundle->load(['product', 'items']),
        ], 201);
    }

    /**
     * Update bundle.
     *
     * @param  Request  $request
     * @param  Bundle  $bundle
     * @return JsonResponse
     */
    public function update(Request $request, Bundle $bundle): JsonResponse
    {
        // Admin-only: require staff authentication
        if (!auth('staff')->check()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'bundle_type' => 'in:fixed,dynamic',
            'discount_type' => 'in:percentage,fixed',
            'discount_value' => 'numeric|min:0',
            'min_items' => 'nullable|integer|min:1',
            'max_items' => 'nullable|integer|min:1',
            'category_id' => 'nullable|exists:lunar_categories,id',
            'show_individual_prices' => 'boolean',
            'show_savings' => 'boolean',
            'allow_individual_returns' => 'boolean',
        ]);

        $bundle->update($validated);

        return response()->json([
            'message' => 'Bundle updated successfully',
            'bundle' => $bundle->fresh(['product', 'items']),
        ]);
    }

    /**
     * Add item to bundle.
     *
     * @param  Request  $request
     * @param  Bundle  $bundle
     * @return JsonResponse
     */
    public function addItem(Request $request, Bundle $bundle): JsonResponse
    {
        // Admin-only: require staff authentication
        if (!auth('staff')->check()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'product_id' => 'required|exists:lunar_products,id',
            'product_variant_id' => 'nullable|exists:lunar_product_variants,id',
            'quantity' => 'integer|min:1',
            'is_optional' => 'boolean',
            'custom_price_override' => 'nullable|numeric|min:0',
            'display_order' => 'integer|min:0',
            'group_name' => 'nullable|string',
            'group_min_selection' => 'nullable|integer|min:0',
            'group_max_selection' => 'nullable|integer|min:1',
        ]);

        $item = $bundle->items()->create($validated);

        return response()->json([
            'message' => 'Item added to bundle',
            'item' => $item->load(['product', 'productVariant']),
        ], 201);
    }

    /**
     * Update bundle item.
     *
     * @param  Request  $request
     * @param  Bundle  $bundle
     * @param  BundleItem  $item
     * @return JsonResponse
     */
    public function updateItem(Request $request, Bundle $bundle, BundleItem $item): JsonResponse
    {
        // Admin-only: require staff authentication
        if (!auth('staff')->check()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'quantity' => 'integer|min:1',
            'is_optional' => 'boolean',
            'custom_price_override' => 'nullable|numeric|min:0',
            'display_order' => 'integer|min:0',
            'group_name' => 'nullable|string',
            'group_min_selection' => 'nullable|integer|min:0',
            'group_max_selection' => 'nullable|integer|min:1',
        ]);

        $item->update($validated);

        return response()->json([
            'message' => 'Bundle item updated',
            'item' => $item->fresh(['product', 'productVariant']),
        ]);
    }

    /**
     * Remove item from bundle.
     *
     * @param  Bundle  $bundle
     * @param  BundleItem  $item
     * @return JsonResponse
     */
    public function removeItem(Bundle $bundle, BundleItem $item): JsonResponse
    {
        // Admin-only: require staff authentication
        if (!auth('staff')->check()) {
            abort(403, 'Unauthorized');
        }

        $item->delete();

        return response()->json([
            'message' => 'Item removed from bundle',
        ]);
    }

    /**
     * Get bundle analytics.
     *
     * @param  Request  $request
     * @param  Bundle  $bundle
     * @return JsonResponse
     */
    public function analytics(Request $request, Bundle $bundle): JsonResponse
    {
        // Admin-only: require staff authentication
        if (!auth('staff')->check()) {
            abort(403, 'Unauthorized');
        }

        $days = $request->get('days');

        $analytics = $this->bundleService->getBundleAnalytics($bundle, $days);

        return response()->json($analytics);
    }
}
