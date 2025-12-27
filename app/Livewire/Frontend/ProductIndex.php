<?php

namespace App\Livewire\Frontend;

use App\Lunar\Attributes\AttributeFilterHelper;
use App\Models\Category;
use App\Models\Product;
use App\Repositories\CategoryRepository;
use App\Services\AttributeService;
use App\Services\ProductAttributeFilterService;
use App\Services\SEOService;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use Livewire\WithPagination;
use Lunar\Models\Brand;

class ProductIndex extends Component
{
    use WithPagination;

    public ?int $categoryId = null;

    public ?int $brandId = null;

    public string $sort = 'default';

    public array $activeFilters = [];

    protected $queryString = [
        'categoryId' => ['as' => 'category_id', 'except' => null],
        'brandId' => ['as' => 'brand_id', 'except' => null],
        'sort' => ['except' => 'default'],
    ];

    public function mount(): void
    {
        $this->categoryId = request()->integer('category_id') ?: null;
        $this->brandId = request()->integer('brand_id') ?: null;
        $this->sort = request()->get('sort', 'default');
        $this->activeFilters = AttributeFilterHelper::getActiveFilters(request());
    }

    public function updatingCategoryId(): void
    {
        $this->resetPage();
    }

    public function updatingBrandId(): void
    {
        $this->resetPage();
    }

    public function updatingSort(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Product::with(['variants.prices', 'media', 'urls', 'brand', 'attributeValues.attribute'])
            ->published();

        $selectedCategory = null;
        if ($this->categoryId) {
            $selectedCategory = Category::query()->active()->find($this->categoryId);
            if ($selectedCategory) {
                $categoryIds = $selectedCategory->descendants()->pluck('id')->push($selectedCategory->id);
                $query->whereHas('categories', function ($q) use ($categoryIds) {
                    $q->whereIn($q->qualifyColumn('id'), $categoryIds);
                });
            }
        }

        if ($this->brandId) {
            $query->where('brand_id', $this->brandId);
        }

        $this->activeFilters = AttributeFilterHelper::getActiveFilters(request());
        if (!empty($this->activeFilters)) {
            $filterService = app(ProductAttributeFilterService::class);
            $query = $filterService->applyFilters($query, $this->activeFilters, 'and');
        }

        switch ($this->sort) {
            case 'price_asc':
                $query->orderByRaw(
                    '(SELECT MIN(price) FROM ' . config('lunar.database.table_prefix') . 'prices WHERE priceable_type = ? AND priceable_id IN (SELECT id FROM ' . config('lunar.database.table_prefix') . 'product_variants WHERE product_id = products.id))',
                    [\App\Models\ProductVariant::morphName()]
                );
                break;
            case 'price_desc':
                $query->orderByRaw(
                    '(SELECT MAX(price) FROM ' . config('lunar.database.table_prefix') . 'prices WHERE priceable_type = ? AND priceable_id IN (SELECT id FROM ' . config('lunar.database.table_prefix') . 'product_variants WHERE product_id = products.id)) DESC',
                    [\App\Models\ProductVariant::morphName()]
                );
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            default:
                $query->latest();
        }

        $products = $query->paginate(12)->withQueryString();
        $brands = Brand::orderBy('name')->get();

        $categoryTree = app(CategoryRepository::class)->getCategoryTree(null, 4);

        $attributeService = app(AttributeService::class);
        $filterableAttributes = $attributeService->getFilterableAttributes(null, $selectedCategory?->id);
        $filterOptions = $attributeService->getFilterOptions($filterableAttributes, $query->getQuery());
        $groupedAttributes = AttributeFilterHelper::getGroupedFilterableAttributes(null, $selectedCategory?->id, $query->getQuery());

        $metaTags = SEOService::getDefaultMetaTags(
            __('frontend.nav.products'),
            __('frontend.products_index.meta_description'),
            null,
            request()->url()
        );

        $pageMeta = new HtmlString(view('frontend.products._meta', [
            'metaTags' => $metaTags,
        ])->render());

        return view('livewire.frontend.product-index', compact(
            'products',
            'categoryTree',
            'selectedCategory',
            'brands',
            'filterableAttributes',
            'filterOptions',
            'groupedAttributes',
            'metaTags'
        ))->layout('frontend.layout', [
            'pageTitle' => $metaTags['title'] ?? __('frontend.nav.products'),
            'pageMeta' => $pageMeta,
        ]);
    }
}


