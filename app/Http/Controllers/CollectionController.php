<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Services\CollectionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Lunar\Models\CollectionGroup;

class CollectionController extends Controller
{
    public function __construct(
        protected CollectionService $collectionService
    ) {}

    /**
     * List all collections
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['collection_group_id', 'attributes']);
        $collections = $this->collectionService->searchCollections($filters);

        return response()->json([
            'data' => $collections->load(['products', 'group']),
            'total' => $collections->count()
        ]);
    }

    /**
     * Create a new collection
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Collection::class);
        
        $validated = $request->validate([
            'collection_group_id' => 'required|exists:lunar_collection_groups,id',
            'sort' => 'integer|min:0',
            'attributes' => 'array',
            'attributes.*' => 'string',
        ]);

        $collection = $this->collectionService->createCollection($validated);

        return response()->json([
            'data' => $collection->load(['products', 'group']),
            'message' => 'Collection created successfully'
        ], 201);
    }

    /**
     * Show a specific collection
     */
    public function show(Collection $collection): JsonResponse
    {
        $this->authorize('view', $collection);
        
        return response()->json([
            'data' => $collection->load(['products.variants', 'group'])
        ]);
    }

    /**
     * Update a collection
     */
    public function update(Request $request, Collection $collection): JsonResponse
    {
        $this->authorize('update', $collection);
        
        $validated = $request->validate([
            'collection_group_id' => 'exists:lunar_collection_groups,id',
            'sort' => 'integer|min:0',
            'attributes' => 'array',
            'attributes.*' => 'string',
        ]);

        $collection->update($validated);

        if (isset($validated['attributes'])) {
            // Update attributes would require proper implementation
            // For now, we'll skip this complex operation
        }

        return response()->json([
            'data' => $collection->fresh()->load(['products', 'group']),
            'message' => 'Collection updated successfully'
        ]);
    }

    /**
     * Delete a collection
     */
    public function destroy(Collection $collection): JsonResponse
    {
        $this->authorize('delete', $collection);
        
        $collection->delete();

        return response()->json([
            'message' => 'Collection deleted successfully'
        ]);
    }

    /**
     * Add products to collection
     */
    public function addProducts(Request $request, Collection $collection): JsonResponse
    {
        $this->authorize('update', $collection);
        
        $validated = $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:lunar_products,id',
        ]);

        $updatedCollection = $this->collectionService->addProducts($collection, $validated['product_ids']);

        return response()->json([
            'data' => $updatedCollection->load(['products']),
            'message' => 'Products added to collection successfully'
        ]);
    }

    /**
     * Remove products from collection
     */
    public function removeProducts(Request $request, Collection $collection): JsonResponse
    {
        $this->authorize('update', $collection);
        
        $validated = $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:lunar_products,id',
        ]);

        $updatedCollection = $this->collectionService->removeProducts($collection, $validated['product_ids']);

        return response()->json([
            'data' => $updatedCollection->load(['products']),
            'message' => 'Products removed from collection successfully'
        ]);
    }

    /**
     * Get products in collection
     */
    public function getProducts(Collection $collection): JsonResponse
    {
        $products = $this->collectionService->getProducts($collection);

        return response()->json([
            'data' => $products,
            'total' => $products->count()
        ]);
    }

    /**
     * Get collection hierarchy (if using nested collections)
     */
    public function getHierarchy(Collection $collection): JsonResponse
    {
        // Load the collection with its children and parent
        $collection->load(['children', 'parent']);

        return response()->json([
            'data' => [
                'collection' => $collection,
                'children' => $collection->children,
                'parent' => $collection->parent,
                'ancestors' => $collection->ancestors ?? [],
                'descendants' => $collection->descendants ?? [],
            ]
        ]);
    }
}