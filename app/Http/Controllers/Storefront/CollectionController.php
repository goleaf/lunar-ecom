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
     */
    public function show(string $slug, Request $request)
    {
        $url = Url::where('slug', $slug)
            ->where('element_type', Collection::class)
            ->firstOrFail();

        $collection = Collection::with('group')->findOrFail($url->element_id);

        $products = $collection->products()
            ->with(['variants.prices', 'images'])
            ->where('status', 'published')
            ->latest()
            ->paginate(12);

        return view('storefront.collections.show', compact('collection', 'products'));
    }
}

