<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Models\Product;
use App\Services\CollectionManagementService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Admin controller for collection management.
 */
class CollectionManagementController extends Controller
{
    public function __construct(
        protected CollectionManagementService $collectionService
    ) {}

    /**
     * Display collection management page.
     *
     * @param  Collection  $collection
     * @return \Illuminate\View\View
     */
    public function show(Collection $collection)
    {
        $products = $collection->productsWithMetadata()
            ->paginate(20);

        $availableProducts = Product::published()
            ->whereDoesntHave('collections', function ($q) use ($collection) {
                $q->where('collections.id', $collection->id);
            })
            ->limit(50)
            ->get();

        return view('admin.collections.manage', compact('collection', 'products', 'availableProducts'));
    }

    /**
     * Update collection settings.
     *
     * @param  Request  $request
     * @param  Collection  $collection
     * @return JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function updateSettings(Request $request, Collection $collection)
    {
        $validated = $request->validate([
            'collection_type' => 'required|in:manual,bestsellers,new_arrivals,featured,seasonal,custom',
            'auto_assign' => 'boolean',
            'assignment_rules' => 'nullable|array',
            'max_products' => 'nullable|integer|min:1',
            'sort_by' => 'required|in:created_at,price,name,popularity,sales_count,rating',
            'sort_direction' => 'required|in:asc,desc',
            'show_on_homepage' => 'boolean',
            'homepage_position' => 'nullable|integer',
            'display_style' => 'required|in:grid,list,carousel',
            'products_per_row' => 'required|integer|min:1|max:6',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
        ]);

        try {
            $collection->update($validated);

            // If auto-assign is enabled, process immediately
            if ($collection->auto_assign) {
                $this->collectionService->processAutoAssignment($collection);
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Collection settings updated successfully',
                    'collection' => $collection->fresh(),
                ]);
            }

            return back()->with('success', 'Collection settings updated successfully');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update collection: ' . $e->getMessage(),
                ], 500);
            }

            return back()->withErrors(['error' => 'Failed to update collection: ' . $e->getMessage()]);
        }
    }

    /**
     * Add product to collection.
     *
     * @param  Request  $request
     * @param  Collection  $collection
     * @return JsonResponse
     */
    public function addProduct(Request $request, Collection $collection): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'position' => 'nullable|integer',
            'expires_at' => 'nullable|date',
        ]);

        $product = Product::findOrFail($validated['product_id']);

        $this->collectionService->assignProduct($collection, $product, [
            'position' => $validated['position'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product added to collection',
        ]);
    }

    /**
     * Remove product from collection.
     *
     * @param  Request  $request
     * @param  Collection  $collection
     * @return JsonResponse
     */
    public function removeProduct(Request $request, Collection $collection): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $product = Product::findOrFail($validated['product_id']);

        $this->collectionService->removeProduct($collection, $product);

        return response()->json([
            'success' => true,
            'message' => 'Product removed from collection',
        ]);
    }

    /**
     * Reorder products in collection.
     *
     * @param  Request  $request
     * @param  Collection  $collection
     * @return JsonResponse
     */
    public function reorderProducts(Request $request, Collection $collection): JsonResponse
    {
        $validated = $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id',
        ]);

        $this->collectionService->reorderProducts($collection, $validated['product_ids']);

        return response()->json([
            'success' => true,
            'message' => 'Products reordered successfully',
        ]);
    }

    /**
     * Process auto-assignment for collection.
     *
     * @param  Collection  $collection
     * @return JsonResponse
     */
    public function processAutoAssignment(Collection $collection): JsonResponse
    {
        try {
            $assigned = $this->collectionService->processAutoAssignment($collection);

            return response()->json([
                'success' => true,
                'message' => "Assigned {$assigned} products to collection",
                'assigned' => $assigned,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process auto-assignment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get collection statistics.
     *
     * @param  Collection  $collection
     * @return JsonResponse
     */
    public function statistics(Collection $collection): JsonResponse
    {
        $stats = [
            'total_products' => $collection->products()->count(),
            'auto_assigned' => $collection->productMetadata()->where('is_auto_assigned', true)->count(),
            'manual_assigned' => $collection->productMetadata()->where('is_auto_assigned', false)->count(),
            'last_updated' => $collection->last_updated_at?->toIso8601String(),
        ];

        return response()->json($stats);
    }
}

