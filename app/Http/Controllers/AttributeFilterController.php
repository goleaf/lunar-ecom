<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Services\AttributeService;
use App\Services\ProductAttributeFilterService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Controller for product attribute filtering.
 */
class AttributeFilterController extends Controller
{
    public function __construct(
        protected AttributeService $attributeService,
        protected ProductAttributeFilterService $filterService
    ) {}

    /**
     * Get filterable attributes for a category or product type.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function getFilters(Request $request): JsonResponse
    {
        $categoryId = $request->input('category_id');
        $productTypeId = $request->input('product_type_id');

        // Build base product query if category provided
        $productQuery = null;
        if ($categoryId) {
            $category = Category::find($categoryId);
            if ($category) {
                $productQuery = $category->products()->getQuery();
            }
        }

        // Get filterable attributes
        $attributes = $this->attributeService->getFilterableAttributes($productTypeId, $categoryId);
        
        // Get filter options with counts
        $filterOptions = $this->attributeService->getFilterOptions($attributes, $productQuery);

        return response()->json([
            'data' => $filterOptions,
        ]);
    }

    /**
     * Apply filters and return filtered products.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function applyFilters(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'filters' => 'array',
            'filters.*' => 'required',
            'category_id' => 'nullable|exists:categories,id',
            'product_type_id' => 'nullable|exists:product_types,id',
            'logic' => 'in:and,or',
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100',
        ]);

        // Build base query
        $query = Product::query()->with(['variants.prices', 'media', 'brand']);

        // Apply category filter
        if (!empty($validated['category_id'])) {
            $category = Category::find($validated['category_id']);
            if ($category) {
                $categoryIds = $category->descendants()->pluck('id')->push($category->id);
                $query->whereHas('categories', function ($q) use ($categoryIds) {
                    $q->whereIn('categories.id', $categoryIds);
                });
            }
        }

        // Apply product type filter
        if (!empty($validated['product_type_id'])) {
            $query->where('product_type_id', $validated['product_type_id']);
        }

        // Apply attribute filters
        if (!empty($validated['filters'])) {
            $logic = $validated['logic'] ?? 'and';
            $query = $this->filterService->applyFilters($query, $validated['filters'], $logic);
        }

        // Paginate results
        $perPage = $validated['per_page'] ?? 24;
        $products = $query->paginate($perPage);

        return response()->json([
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    /**
     * Get product count for a specific filter value.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function getFilterCount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'attribute_handle' => 'required|string',
            'value' => 'required',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        $attribute = \App\Models\Attribute::where('handle', $validated['attribute_handle'])
            ->where('attribute_type', 'product')
            ->first();

        if (!$attribute) {
            return response()->json(['count' => 0]);
        }

        // Build base query if category provided
        $baseQuery = null;
        if (!empty($validated['category_id'])) {
            $category = Category::find($validated['category_id']);
            if ($category) {
                $baseQuery = $category->products()->getQuery();
            }
        }

        $count = $this->filterService->getProductCountForFilter(
            $attribute,
            $validated['value'],
            $baseQuery
        );

        return response()->json(['count' => $count]);
    }
}


