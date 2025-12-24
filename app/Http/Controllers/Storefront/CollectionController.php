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
        $collections = Collection::with('group')
            ->latest()
            ->paginate(12);

        return view('storefront.collections.index', compact('collections'));
    }

    /**
     * Display the specified collection with its products.
     * 
     * Products are sorted according to the collection's sort type:
     * - min_price:asc/desc - by minimum variant price
     * - sku:asc/desc - by SKU
     * - custom - by manual position (default)
     * 
     * See: https://docs.lunarphp.com/1.x/reference/collections
     */
    public function show(string $slug, Request $request)
    {
        $url = Url::where('slug', $slug)
            ->where('element_type', Collection::class)
            ->firstOrFail();

        $collection = Collection::with(['group', 'children'])->findOrFail($url->element_id);

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

        return view('storefront.collections.show', compact('collection', 'products', 'breadcrumb'));
    }
}

