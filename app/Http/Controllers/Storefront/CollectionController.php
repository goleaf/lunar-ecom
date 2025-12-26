<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Lunar\Models\Collection;
use Lunar\Models\Url;

class CollectionController extends Controller
{
    /**
     * Display a listing of collections.
     */
    public function index()
    {
        // Load collections with URLs for link generation
        // See: https://docs.lunarphp.com/1.x/reference/urls
        $collections = Collection::with(['group', 'urls'])
            ->whereHas('products')
            ->latest()
            ->paginate(12);

        // Get SEO data
        $metaTags = \App\Services\SEOService::getDefaultMetaTags(
            'Collections',
            'Browse our product collections. Discover curated selections of products.',
            null,
            request()->url()
        );

        return view('storefront.collections.index', compact('collections', 'metaTags'));
    }

    /**
     * Display the specified collection with its products.
     * 
     * Uses URL slug to find collections instead of IDs.
     * Products are sorted according to the collection's sort type:
     * - min_price:asc/desc - by minimum variant price
     * - sku:asc/desc - by SKU
     * - custom - by manual position (default)
     * 
     * See: https://docs.lunarphp.com/1.x/reference/collections
     * See: https://docs.lunarphp.com/1.x/reference/urls
     */
    public function show(string $slug, Request $request)
    {
        // Find collection by URL slug
        // See: https://docs.lunarphp.com/1.x/reference/urls
        $url = Url::where('slug', $slug)
            ->where('element_type', Collection::morphName())
            ->firstOrFail();

        // Load collection with media eager loaded
        // See: https://docs.lunarphp.com/1.x/reference/media
        $collection = Collection::with(['group', 'children', 'media', 'urls'])->findOrFail($url->element_id);

        // Check if user can view this collection
        $this->authorize('view', $collection);

        // Get products with proper sorting based on collection's sort type
        $products = \App\Lunar\Collections\CollectionHelper::getSortedProducts($collection);
        
        // Paginate the sorted products
        $perPage = 12;
        $currentPage = $request->get('page', 1);
        $items = $products->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $products = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $products->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Get breadcrumb for navigation
        $breadcrumb = $collection->breadcrumb;

        // Get SEO data
        $defaultUrl = $collection->urls->where('default', true)->first();
        $canonicalUrl = $defaultUrl 
            ? route('storefront.collections.show', $defaultUrl->slug)
            : url('/collections/' . $collection->id);

        $metaTags = [
            'title' => $collection->translate('name') . ' - Collections',
            'description' => $collection->translate('description') 
                ? mb_substr(strip_tags($collection->translate('description')), 0, 160)
                : "Browse products in {$collection->translate('name')} collection.",
            'og:title' => $collection->translate('name'),
            'og:description' => $collection->translate('description') 
                ? mb_substr(strip_tags($collection->translate('description')), 0, 160)
                : "Browse products in {$collection->translate('name')} collection.",
            'og:image' => $collection->getFirstMediaUrl('images', 'large'),
            'og:type' => 'website',
            'og:url' => $canonicalUrl,
            'canonical' => $canonicalUrl,
        ];

        // Get filter options for the new filtering system
        $filterController = new \App\Http\Controllers\Storefront\CollectionFilterController();
        $filterOptions = $filterController->getFilterOptions($collection, $request);

        // Get filter options for the new filtering system
        $filterController = new \App\Http\Controllers\Storefront\CollectionFilterController();
        $filterOptions = $filterController->getFilterOptions($collection, $request);

        return view('storefront.collections.show', compact(
            'collection', 
            'products', 
            'breadcrumb',
            'metaTags',
            'filterOptions'
        ));
    }

    /**
     * Get filter options for collection (helper method).
     *
     * @param  Collection  $collection
     * @param  Request  $request
     * @return array
     */
    protected function getFilterOptionsForCollection(Collection $collection, Request $request)
    {
        $baseQuery = $collection->products()->published();
        
        // Get price range
        $productIds = $baseQuery->pluck('id');
        $priceRange = ['min' => 0, 'max' => 0];
        
        if ($productIds->isNotEmpty()) {
            $minPrice = \DB::table('lunar_product_variants')
                ->join('lunar_prices', function($join) {
                    $join->on('lunar_product_variants.id', '=', 'lunar_prices.priceable_id')
                         ->where('lunar_prices.priceable_type', '=', \Lunar\Models\ProductVariant::class);
                })
                ->whereIn('lunar_product_variants.product_id', $productIds)
                ->min('lunar_prices.price');
            
            $maxPrice = \DB::table('lunar_product_variants')
                ->join('lunar_prices', function($join) {
                    $join->on('lunar_product_variants.id', '=', 'lunar_prices.priceable_id')
                         ->where('lunar_prices.priceable_type', '=', \Lunar\Models\ProductVariant::class);
                })
                ->whereIn('lunar_product_variants.product_id', $productIds)
                ->max('lunar_prices.price');
            
            $priceRange = [
                'min' => $minPrice ? ($minPrice / 100) : 0,
                'max' => $maxPrice ? ($maxPrice / 100) : 0,
            ];
        }

        return [
            'price_range' => $priceRange,
            'brands' => [],
            'categories' => [],
            'attributes' => [],
            'availability' => [
                'in_stock' => 0,
                'out_of_stock' => 0,
                'low_stock' => 0,
            ],
        ];
    }
}
