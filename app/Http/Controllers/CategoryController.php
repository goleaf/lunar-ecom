<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Repositories\CategoryRepository;
use App\Services\CategoryFilterService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Controller for category operations.
 */
class CategoryController extends Controller
{
    public function __construct(
        protected CategoryRepository $categoryRepository,
        protected CategoryFilterService $filterService
    ) {}

    /**
     * Get category by slug or full path.
     *
     * @param  string  $path  Can be a single slug or full path like "parent/child/grandchild"
     * @return JsonResponse|\Illuminate\View\View
     */
    public function show(string $path, Request $request)
    {
        // Try to find by full path first, then by slug
        $category = $this->categoryRepository->findByPath($path);
        
        if (!$category) {
            $category = $this->categoryRepository->findBySlug($path);
        }

        if (!$category) {
            if (request()->expectsJson()) {
                return response()->json([
                    'error' => 'Category not found'
                ], 404);
            }
            abort(404, 'Category not found');
        }

        $this->authorize('view', $category);

        // Load relationships
        $category->load(['children', 'parent', 'products.variants.prices']);

        // Get products with filters
        $productsQuery = Product::query()
            ->whereHas('categories', function ($q) use ($category) {
                $categoryIds = $category->descendants()->pluck('id')->push($category->id);
                $q->whereIn('categories.id', $categoryIds);
            })
            ->with(['variants.prices', 'media', 'brand'])
            ->where('status', 'published');
        
        // Apply additional filters
        $productsQuery = $this->applyProductFilters($productsQuery, $request);
        
        // Additional filters from request
        $productsQuery = $this->applyProductFilters($productsQuery, $request);
        
        // Pagination
        $perPage = $request->input('per_page', 24);
        $products = $productsQuery->paginate($perPage);

        $data = [
            'category' => $category,
            'breadcrumb' => $category->getBreadcrumb(),
            'full_path' => $category->getFullPath(),
            'products' => $products,
            'children' => $category->getChildren(),
            'filters' => $this->getAvailableFilters($category),
        ];

        if (request()->expectsJson()) {
            return response()->json(['data' => $data]);
        }

        // Return view for web requests
        return view('storefront.categories.show', $data);
    }

    /**
     * Apply product filters from request.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  Request  $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyProductFilters($query, Request $request)
    {
        // Price range filter
        if ($request->has('min_price')) {
            // Filter by variant prices
            $query->whereHas('variants.prices', function ($q) use ($request) {
                $q->where('price', '>=', $request->input('min_price') * 100); // Convert to cents
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
                $query->orderByRaw('(SELECT MIN(price) FROM ' . config('lunar.database.table_prefix') . 'prices WHERE priceable_type = ? AND priceable_id = products.id)', [\Lunar\Models\ProductVariant::class]);
                break;
            case 'price_desc':
                $query->orderByRaw('(SELECT MAX(price) FROM ' . config('lunar.database.table_prefix') . 'prices WHERE priceable_type = ? AND priceable_id = products.id) DESC', [\Lunar\Models\ProductVariant::class]);
                break;
            case 'name_asc':
                // This would need to use attribute_data, simplified here
                $query->orderBy('id');
                break;
            case 'name_desc':
                $query->orderBy('id', 'desc');
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
     *
     * @param  Category  $category
     * @return array
     */
    protected function getAvailableFilters(Category $category): array
    {
        $products = $category->getAllProducts();
        
        // Get unique brands
        $brands = $products->pluck('brand_id')->filter()->unique();
        
        // Get price range
        $prices = collect();
        foreach ($products as $product) {
            $variant = $product->variants->first();
            if ($variant) {
                $pricingResponse = \Lunar\Facades\Pricing::for($variant)->get();
                if ($pricingResponse->matched) {
                    $priceModel = $pricingResponse->matched;
                    $priceValue = $priceModel->price?->value ?? null;
                    if ($priceValue) {
                        $prices->push($priceValue);
                    }
                }
            }
        }

        return [
            'brands' => \Lunar\Models\Brand::whereIn('id', $brands)->get(),
            'price_range' => [
                'min' => $prices->min() ? $prices->min() / 100 : 0,
                'max' => $prices->max() ? $prices->max() / 100 : 0,
            ],
        ];
    }

    /**
     * Get category tree.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function tree(Request $request): JsonResponse
    {
        $rootId = $request->input('root_id');
        $depth = (int) $request->input('depth', 10);

        $root = $rootId ? Category::find($rootId) : null;
        $tree = $this->categoryRepository->getCategoryTree($root, $depth);

        return response()->json([
            'data' => $tree
        ]);
    }

    /**
     * Get root categories.
     *
     * @return JsonResponse
     */
    public function roots(): JsonResponse
    {
        $roots = $this->categoryRepository->getRootCategories();

        return response()->json([
            'data' => $roots
        ]);
    }

    /**
     * Get flat category list.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function flatList(Request $request): JsonResponse
    {
        $rootId = $request->input('root_id');
        $root = $rootId ? Category::find($rootId) : null;

        $list = $this->categoryRepository->getFlatList($root);

        return response()->json([
            'data' => $list
        ]);
    }

    /**
     * Get navigation categories.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function navigation(Request $request): JsonResponse
    {
        $maxDepth = (int) $request->input('max_depth', 3);
        $categories = $this->categoryRepository->getNavigationCategories($maxDepth);

        return response()->json([
            'data' => $categories
        ]);
    }

    /**
     * Get category breadcrumb.
     *
     * @param  Category  $category
     * @return JsonResponse
     */
    public function breadcrumb(Category $category): JsonResponse
    {
        return response()->json([
            'data' => $category->getBreadcrumb()
        ]);
    }
}

