<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bundle;
use App\Models\BundleItem;
use App\Services\BundleService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Admin controller for bundle management.
 */
class BundleController extends Controller
{
    public function __construct(
        protected BundleService $bundleService
    ) {}

    /**
     * Display bundle management index.
     *
     * @param  Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = Bundle::with(['product', 'items']);

        if ($request->has('status')) {
            if ($request->get('status') === 'active') {
                $query->where('is_active', true);
            } elseif ($request->get('status') === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if ($request->has('featured')) {
            $query->where('is_featured', true);
        }

        $bundles = $query->orderBy('display_order')->paginate(20);

        return view('admin.bundles.index', compact('bundles'));
    }

    /**
     * Show bundle creation form.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $products = \App\Models\Product::published()->get();
        $currencies = \Lunar\Models\Currency::where('enabled', true)->get();
        $customerGroups = \Lunar\Models\CustomerGroup::all();

        return view('admin.bundles.create', compact('products', 'currencies', 'customerGroups'));
    }

    /**
     * Store a new bundle.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:lunar_products,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'slug' => 'nullable|string|unique:lunar_bundles,slug',
            'sku' => 'nullable|string|unique:lunar_bundles,sku',
            'pricing_type' => 'required|in:fixed,percentage,dynamic',
            'discount_amount' => 'nullable|integer|min:0',
            'bundle_price' => 'nullable|integer|min:0',
            'inventory_type' => 'required|in:component,independent,unlimited',
            'stock' => 'nullable|integer|min:0',
            'min_quantity' => 'nullable|integer|min:1',
            'max_quantity' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'display_order' => 'nullable|integer',
            'allow_customization' => 'boolean',
            'show_individual_prices' => 'boolean',
            'show_savings' => 'boolean',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:lunar_products,id',
            'items.*.product_variant_id' => 'nullable|exists:lunar_product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.is_required' => 'boolean',
            'items.*.min_quantity' => 'nullable|integer|min:1',
            'items.*.max_quantity' => 'nullable|integer|min:1',
            'items.*.is_default' => 'boolean',
            'items.*.price_override' => 'nullable|integer|min:0',
            'items.*.discount_amount' => 'nullable|integer|min:0',
            'items.*.display_order' => 'nullable|integer|min:0',
            'items.*.notes' => 'nullable|string',
            'prices' => 'nullable|array',
            'prices.*.currency_id' => 'required_with:prices|exists:lunar_currencies,id',
            'prices.*.customer_group_id' => 'nullable|exists:lunar_customer_groups,id',
            'prices.*.price' => 'required_with:prices|integer|min:0',
            'prices.*.compare_at_price' => 'nullable|integer|min:0',
            'prices.*.min_quantity' => 'nullable|integer|min:1',
            'prices.*.max_quantity' => 'nullable|integer|min:1',
        ]);

        $bundle = $this->bundleService->createBundle($validated);

        return response()->json([
            'message' => 'Bundle created successfully',
            'bundle' => $bundle,
        ], 201);
    }

    /**
     * Show bundle edit form.
     *
     * @param  Bundle  $bundle
     * @return \Illuminate\View\View
     */
    public function edit(Bundle $bundle)
    {
        $bundle->load(['items.product', 'items.productVariant', 'prices']);
        $products = \App\Models\Product::published()->get();
        $currencies = \Lunar\Models\Currency::where('enabled', true)->get();
        $customerGroups = \Lunar\Models\CustomerGroup::all();

        return view('admin.bundles.edit', compact('bundle', 'products', 'currencies', 'customerGroups'));
    }

    /**
     * Update a bundle.
     *
     * @param  Request  $request
     * @param  Bundle  $bundle
     * @return JsonResponse
     */
    public function update(Request $request, Bundle $bundle): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'slug' => 'sometimes|string|unique:lunar_bundles,slug,' . $bundle->id,
            'sku' => 'nullable|string|unique:lunar_bundles,sku,' . $bundle->id,
            'pricing_type' => 'sometimes|required|in:fixed,percentage,dynamic',
            'discount_amount' => 'nullable|integer|min:0',
            'bundle_price' => 'nullable|integer|min:0',
            'inventory_type' => 'sometimes|required|in:component,independent,unlimited',
            'stock' => 'nullable|integer|min:0',
            'min_quantity' => 'nullable|integer|min:1',
            'max_quantity' => 'nullable|integer|min:1',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'display_order' => 'nullable|integer',
            'allow_customization' => 'boolean',
            'show_individual_prices' => 'boolean',
            'show_savings' => 'boolean',
            'items' => 'sometimes|array|min:1',
            'items.*.product_id' => 'required_with:items|exists:lunar_products,id',
            'items.*.product_variant_id' => 'nullable|exists:lunar_product_variants,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.is_required' => 'boolean',
            'items.*.min_quantity' => 'nullable|integer|min:1',
            'items.*.max_quantity' => 'nullable|integer|min:1',
            'items.*.is_default' => 'boolean',
            'items.*.price_override' => 'nullable|integer|min:0',
            'items.*.discount_amount' => 'nullable|integer|min:0',
            'items.*.display_order' => 'nullable|integer|min:0',
            'items.*.notes' => 'nullable|string',
            'prices' => 'sometimes|array',
            'prices.*.currency_id' => 'required_with:prices|exists:lunar_currencies,id',
            'prices.*.customer_group_id' => 'nullable|exists:lunar_customer_groups,id',
            'prices.*.price' => 'required_with:prices|integer|min:0',
            'prices.*.compare_at_price' => 'nullable|integer|min:0',
            'prices.*.min_quantity' => 'nullable|integer|min:1',
            'prices.*.max_quantity' => 'nullable|integer|min:1',
        ]);

        $bundle = $this->bundleService->updateBundle($bundle, $validated);

        return response()->json([
            'message' => 'Bundle updated successfully',
            'bundle' => $bundle,
        ]);
    }

    /**
     * Delete a bundle.
     *
     * @param  Bundle  $bundle
     * @return JsonResponse
     */
    public function destroy(Bundle $bundle): JsonResponse
    {
        $bundle->delete();

        return response()->json([
            'message' => 'Bundle deleted successfully',
        ]);
    }

    /**
     * Return a bundle with related data for admin tools.
     */
    public function show(Bundle $bundle): JsonResponse
    {
        $bundle->load(['items.product', 'items.productVariant', 'prices']);

        return response()->json($bundle);
    }

    /**
     * Bundle analytics snapshot.
     */
    public function analytics(Bundle $bundle): JsonResponse
    {
        $data = $this->bundleService->getBundleAnalytics($bundle);

        return response()->json($data);
    }

    /**
     * Add an item to the bundle.
     */
    public function addItem(Request $request, Bundle $bundle): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:lunar_products,id',
            'product_variant_id' => 'nullable|exists:lunar_product_variants,id',
            'quantity' => 'integer|min:1',
            'min_quantity' => 'integer|min:1',
            'max_quantity' => 'nullable|integer|min:1',
            'is_required' => 'boolean',
            'is_default' => 'boolean',
            'price_override' => 'nullable|integer|min:0',
            'discount_amount' => 'nullable|integer|min:0',
            'display_order' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        $item = $this->bundleService->addBundleItem($bundle, $validated);

        return response()->json([
            'message' => 'Bundle item added',
            'item' => $item,
        ], 201);
    }

    /**
     * Update a bundle item.
     */
    public function updateItem(Request $request, Bundle $bundle, BundleItem $item): JsonResponse
    {
        if ($item->bundle_id !== $bundle->id) {
            abort(404);
        }

        $validated = $request->validate([
            'quantity' => 'integer|min:1',
            'min_quantity' => 'integer|min:1',
            'max_quantity' => 'nullable|integer|min:1',
            'is_required' => 'boolean',
            'is_default' => 'boolean',
            'price_override' => 'nullable|integer|min:0',
            'discount_amount' => 'nullable|integer|min:0',
            'display_order' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        $item->fill($validated);
        $item->save();

        return response()->json([
            'message' => 'Bundle item updated',
            'item' => $item->fresh(['product', 'productVariant']),
        ]);
    }

    /**
     * Remove an item from a bundle.
     */
    public function removeItem(Bundle $bundle, BundleItem $item): JsonResponse
    {
        if ($item->bundle_id !== $bundle->id) {
            abort(404);
        }

        $item->delete();

        return response()->json([
            'message' => 'Bundle item removed',
        ]);
    }
}
