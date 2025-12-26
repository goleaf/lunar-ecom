<?php

namespace App\Livewire\Frontend;

use App\Lunar\Attributes\AttributeFilterHelper;
use App\Models\Product;
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

    public ?int $brandId = null;

    public string $sort = 'default';

    public array $activeFilters = [];

    protected $queryString = [
        'brandId' => ['as' => 'brand_id', 'except' => null],
        'sort' => ['except' => 'default'],
    ];

    public function mount(): void
    {
        $this->brandId = request()->integer('brand_id') ?: null;
        $this->sort = request()->get('sort', 'default');
        $this->activeFilters = AttributeFilterHelper::getActiveFilters(request());
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
                    [\App\Models\ProductVariant::class]
                );
                break;
            case 'price_desc':
                $query->orderByRaw(
                    '(SELECT MAX(price) FROM ' . config('lunar.database.table_prefix') . 'prices WHERE priceable_type = ? AND priceable_id IN (SELECT id FROM ' . config('lunar.database.table_prefix') . 'product_variants WHERE product_id = products.id)) DESC',
                    [\App\Models\ProductVariant::class]
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

        $attributeService = app(AttributeService::class);
        $filterableAttributes = $attributeService->getFilterableAttributes();
        $filterOptions = $attributeService->getFilterOptions($filterableAttributes, $query->getQuery());
        $groupedAttributes = AttributeFilterHelper::getGroupedFilterableAttributes();

        $metaTags = SEOService::getDefaultMetaTags(
            'Products',
            'Browse our complete product catalog. Find the best deals and latest items.',
            null,
            request()->url()
        );

        $pageMeta = new HtmlString(view('frontend.products._meta', [
            'metaTags' => $metaTags,
        ])->render());

        return view('livewire.frontend.product-index', compact(
            'products',
            'brands',
            'filterableAttributes',
            'filterOptions',
            'groupedAttributes',
            'metaTags'
        ))->layout('frontend.layout', [
            'pageTitle' => $metaTags['title'] ?? 'Products',
            'pageMeta' => $pageMeta,
        ]);
    }
}


