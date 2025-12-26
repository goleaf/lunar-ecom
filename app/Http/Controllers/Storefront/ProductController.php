<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Lunar\Facades\CartSession;
use Lunar\Models\Product;
use Lunar\Models\Url;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index(Request $request)
    {
        // Load products with media and URLs eager loaded
        // See: https://docs.lunarphp.com/1.x/reference/media
        // See: https://docs.lunarphp.com/1.x/reference/urls
        $query = Product::with(['variants.prices', 'media', 'urls', 'brand', 'attributeValues.attribute'])
            ->where('status', 'published');

        // Filter by brand if provided
        if ($request->has('brand') && $request->brand) {
            $query->whereHas('brand', function ($q) use ($request) {
                $q->where('id', $request->brand)
                  ->orWhere('name', 'like', '%' . $request->brand . '%');
            });
        }

        // Filter by brand ID if provided
        if ($request->has('brand_id') && $request->brand_id) {
            $query->where('brand_id', $request->brand_id);
        }

        // Apply attribute filters
        $filterService = app(\App\Services\ProductAttributeFilterService::class);
        $activeFilters = \App\Lunar\Attributes\AttributeFilterHelper::getActiveFilters($request);
        if (!empty($activeFilters)) {
            $query = $filterService->applyFilters($query, $activeFilters, 'and');
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
                $query->latest();
        }

        $products = $query->paginate(12)->withQueryString();

        // Get all brands for filter dropdown
        $brands = \Lunar\Models\Brand::orderBy('name')->get();

        // Get filterable attributes with options
        $attributeService = app(\App\Services\AttributeService::class);
        $filterableAttributes = $attributeService->getFilterableAttributes();
        $filterOptions = $attributeService->getFilterOptions($filterableAttributes, $query->getQuery());

        // Group attributes by group
        $groupedAttributes = \App\Lunar\Attributes\AttributeFilterHelper::getGroupedFilterableAttributes();

        // Get SEO data
        $metaTags = \App\Services\SEOService::getDefaultMetaTags(
            'Products',
            'Browse our complete product catalog. Find the best deals and latest items.',
            null,
            request()->url()
        );

        return view('storefront.products.index', compact(
            'products', 
            'brands', 
            'filterableAttributes',
            'filterOptions',
            'groupedAttributes',
            'activeFilters',
            'metaTags'
        ));
    }

    /**
     * Display the specified product.
     * 
     * Uses URL slug to find products instead of IDs.
     * See: https://docs.lunarphp.com/1.x/reference/urls
     */
    public function show(string $slug)
    {
        // Find product by URL slug
        // See: https://docs.lunarphp.com/1.x/reference/urls
        $url = Url::where('slug', $slug)
            ->where('element_type', Product::morphName())
            ->firstOrFail();

        // Load product with media eager loaded
        // See: https://docs.lunarphp.com/1.x/reference/media
        $product = Product::with([
            'variants.prices',
            'media', // Eager load media for better performance
            'collections',
            'associations.target',
            'tags',
            'urls', // Eager load URLs for link generation
            'reviews.customer', // Eager load reviews for rating display
            'digitalProduct', // Eager load digital product info
        ])->findOrFail($url->element_id);

        // Check if user can view this product
        $this->authorize('view', $product);

        // Get product relations using ProductRelationService
        $relationService = app(\App\Services\ProductRelationService::class);
        $crossSell = $relationService->getCrossSell($product, 10);
        $upSell = $relationService->getUpSell($product, 10);
        $alternate = $relationService->getReplacements($product, 10);
        $accessories = $relationService->getAccessories($product, 10);
        $related = $relationService->getRelated($product, 10);
        $customersAlsoBought = $relationService->getCustomersAlsoBought($product, 10);

        // Get attribute values for display
        // See: https://docs.lunarphp.com/1.x/reference/attributes
        $description = $product->translateAttribute('description');
        $material = $product->translateAttribute('material');
        $weight = $product->translateAttribute('weight');
        $metaTitle = $product->translateAttribute('meta_title');
        $metaDescription = $product->translateAttribute('meta_description');

        // Get SEO data
        $metaTags = \App\Lunar\Products\ProductSEO::getMetaTags($product);
        $structuredData = \App\Lunar\Products\ProductSEO::getStructuredData($product);
        $robotsMeta = \App\Lunar\Products\ProductSEO::getRobotsMeta($product);

        return view('storefront.products.show', compact(
            'product',
            'crossSell',
            'upSell',
            'alternate',
            'accessories',
            'related',
            'customersAlsoBought',
            'description',
            'material',
            'weight',
            'metaTitle',
            'metaDescription',
            'metaTags',
            'structuredData',
            'robotsMeta'
        ));
    }
}

