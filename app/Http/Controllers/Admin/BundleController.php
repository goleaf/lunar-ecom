<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bundle;
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
        $products = \Lunar\Models\Product::where('status', 'published')->get();
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
            'prices' => 'nullable|array',
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
        $products = \Lunar\Models\Product::where('status', 'published')->get();
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
            'is_active' => 'boolean',
            'items' => 'sometimes|array',
            'prices' => 'nullable|array',
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
}
