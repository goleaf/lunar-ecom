<?php

namespace App\Livewire\Frontend\Pages;

use App\Lunar\Attributes\AttributeFilterHelper;
use App\Lunar\Categories\CategorySEO;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Repositories\CategoryRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class CategoryShow extends Component
{
    public string $path;

    public function mount(string $path): void
    {
        $this->path = $path;
    }

    public function render()
    {
        $request = request();
        $categoryRepository = app(CategoryRepository::class);

        $category = $categoryRepository->findByPath($this->path)
            ?? $categoryRepository->findBySlug($this->path);

        if (!$category || !$category->is_active) {
            abort(404, 'Category not found');
        }

        Gate::authorize('view', $category);
        $category->load(['children', 'parent']);

        $productsQuery = Product::query()
            ->with(['variants.prices', 'media', 'brand', 'attributeValues.attribute'])
            ->published();

        $categoryIds = $category->descendants()->pluck('id')->push($category->id);
        $productsQuery->whereHas('categories', function ($q) use ($categoryIds) {
            $q->whereIn('categories.id', $categoryIds);
        });

        $attributeFilterService = app(\App\Services\ProductAttributeFilterService::class);
        $activeFilters = AttributeFilterHelper::getActiveFilters($request);
        if (!empty($activeFilters)) {
            $productsQuery = $attributeFilterService->applyFilters($productsQuery, $activeFilters, 'and');
        }

        $productsQuery = $this->applyProductFilters($productsQuery, $request);

        $perPage = $request->input('per_page', 24);
        $products = $productsQuery->paginate($perPage)->withQueryString();

        $availableFilters = $this->getAvailableFilters($category);

        return view('frontend.categories.show', [
            'category' => $category,
            'breadcrumb' => $category->getBreadcrumb(),
            'full_path' => $category->getFullPath(),
            'products' => $products,
            'children' => $category->getChildren(),
            'filters' => $availableFilters,
            'activeFilters' => $activeFilters,
            'meta' => CategorySEO::getMetaTags($category),
            'structured_data' => CategorySEO::getStructuredData($category),
        ]);
    }

    /**
     * Apply product filters from request.
     */
    protected function applyProductFilters($query, Request $request)
    {
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

        if ($request->has('brand_id')) {
            $query->where('brand_id', $request->input('brand_id'));
        }

        $sort = $request->input('sort', 'default');
        switch ($sort) {
            case 'price_asc':
                $query->orderByRaw(
                    '(SELECT MIN(price) FROM ' . config('lunar.database.table_prefix') . 'prices WHERE priceable_type = ? AND priceable_id IN (SELECT id FROM ' . config('lunar.database.table_prefix') . 'product_variants WHERE product_id = products.id))',
                    [ProductVariant::morphName()]
                );
                break;
            case 'price_desc':
                $query->orderByRaw(
                    '(SELECT MAX(price) FROM ' . config('lunar.database.table_prefix') . 'prices WHERE priceable_type = ? AND priceable_id IN (SELECT id FROM ' . config('lunar.database.table_prefix') . 'product_variants WHERE product_id = products.id)) DESC',
                    [ProductVariant::morphName()]
                );
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

        $brandIds = $products->pluck('brand_id')->filter()->unique();

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

        $attributeService = app(\App\Services\AttributeService::class);
        $productQuery = $category->products()->getQuery();
        $filterableAttributes = $attributeService->getFilterableAttributes(null, $category->id);
        $filterOptions = $attributeService->getFilterOptions($filterableAttributes, $productQuery);

        $groupedAttributes = AttributeFilterHelper::getGroupedFilterableAttributes(null, $category->id);

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


