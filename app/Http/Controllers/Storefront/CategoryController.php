<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Lunar\Categories\CategoryHelper;
use App\Lunar\Categories\CategorySEO;
use App\Models\Category;
use App\Models\Product;
use App\Repositories\CategoryRepository;
use App\Services\CategoryFilterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Storefront category controller.
 */
class CategoryController extends Controller
{
    public function __construct(
        protected CategoryRepository $categoryRepository,
        protected CategoryFilterService $filterService
    ) {}

    /**
     * Show category page.
     */
    public function show(string $path, Request $request)
    {
        // Find category by path
        $category = $this->categoryRepository->findByPath($path);
        
        if (!$category) {
            $category = $this->categoryRepository->findBySlug($path);
        }

        if (!$category || !$category->is_active) {
            abort(404, 'Category not found');
        }

        $this->authorize('view', $category);

        // Load relationships
        $category->load(['children', 'parent']);

        // Get products with filters
        $productsQuery = Product::query()
            ->with(['variants.prices', 'media', 'brand', 'attributeValues.attribute'])
            ->where('status', 'published');

        // Include products from descendant categories
        $categoryIds = $category->descendants()->pluck('id')->push($category->id);
        $productsQuery->whereHas('categories', function ($q) use ($categoryIds) {
            $q->whereIn('categories.id', $categoryIds);
        });

        // Apply attribute filters
        $filterService = app(\App\Services\ProductAttributeFilterService::class);
        $activeFilters = \App\Lunar\Attributes\AttributeFilterHelper::getActiveFilters($request);
        if (!empty($activeFilters)) {
            $productsQuery = $filterService->applyFilters($productsQuery, $activeFilters, 'and');
        }

        // Apply additional filters (price, brand, sort)
        $productsQuery = $this->applyProductFilters($productsQuery, $request);

        // Pagination
        $perPage = $request->input('per_page', 24);
        $products = $productsQuery->paginate($perPage)->withQueryString();

        $availableFilters = $this->getAvailableFilters($category);

        $data = [
            'category' => $category,
            'breadcrumb' => $category->getBreadcrumb(),
            'full_path' => $category->getFullPath(),
            'products' => $products,
            'children' => $category->getChildren(),
            'filters' => $availableFilters,
            'activeFilters' => $activeFilters,
            'meta' => CategorySEO::getMetaTags($category),
            'structured_data' => CategorySEO::getStructuredData($category),
        ];

        if ($request->expectsJson()) {
            return response()->json(['data' => $data]);
        }

        return view('storefront.categories.show', $data);
    }

    /**
     * Apply product filters from request.
     */
    protected function applyProductFilters($query, Request $request)
    {
        // Price range filter
        if ($request->has('min_price')) {
            $query->whereHas('variants.prices', function ($q) use ($request) {
                $q->where('price', '>=', $request->input('min_price') * 100);
            });
        }

        if ($request->has('max_price')) {
            $query->whereHas('variants.prices', function ($q) use ($request) {
                $q->where('price', '<=', $request->input('max_price') * 100);
            });
        }

        // Brand filter
        if ($request->has('brand_id')) {
            $query->where('brand_id', $request->input('brand_id'));
        }

        // Sort
        $sort = $request->input('sort', 'default');
        switch ($sort) {
            case 'price_asc':
                $query->orderByRaw('(SELECT MIN(price) FROM ' . config('lunar.database.table_prefix') . 'prices WHERE priceable_type = ? AND priceable_id IN (SELECT id FROM ' . config('lunar.database.table_prefix') . 'product_variants WHERE product_id = products.id))', [\Lunar\Models\ProductVariant::class]);
                break;
            case 'price_desc':
                $query->orderByRaw('(SELECT MAX(price) FROM ' . config('lunar.database.table_prefix') . 'prices WHERE priceable_type = ? AND priceable_id IN (SELECT id FROM ' . config('lunar.database.table_prefix') . 'product_variants WHERE product_id = products.id)) DESC', [\Lunar\Models\ProductVariant::class]);
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            default:
                $query->orderBy('id');
        }

        return $query;
    }

    /**
     * Get available filters for category.
     */
    protected function getAvailableFilters(Category $category): array
    {
        $products = $category->getAllProducts();
        
        // Get unique brands
        $brandIds = $products->pluck('brand_id')->filter()->unique();
        
        // Get price range
        $prices = collect();
        foreach ($products as $product) {
            $variant = $product->variants->first();
            if ($variant) {
                $pricingResponse = \Lunar\Facades\Pricing::for($variant)->get();
                if ($pricingResponse->matched) {
                    $priceModel = $pricingResponse->matched;
                    // Price model has a 'price' property that returns PriceDataType
                    // Access the raw value via ->value
                    $priceValue = $priceModel->price?->value ?? null;
                    if ($priceValue) {
                        $prices->push($priceValue);
                    }
                }
            }
        }

        // Get filterable attributes with options
        $attributeService = app(\App\Services\AttributeService::class);
        $productQuery = $category->products()->getQuery();
        $filterableAttributes = $attributeService->getFilterableAttributes(null, $category->id);
        $filterOptions = $attributeService->getFilterOptions($filterableAttributes, $productQuery);

        // Group attributes by group
        $groupedAttributes = \App\Lunar\Attributes\AttributeFilterHelper::getGroupedFilterableAttributes(null, $category->id);

        return [
            'brands' => \Lunar\Models\Brand::whereIn('id', $brandIds)->get(),
            'price_range' => [
                'min' => $prices->min() ? round($prices->min() / 100, 2) : 0,
                'max' => $prices->max() ? round($prices->max() / 100, 2) : 0,
            ],
            'attributes' => $filterOptions,
            'grouped_attributes' => $groupedAttributes,
        ];
    }
}

