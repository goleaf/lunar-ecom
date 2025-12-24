<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Lunar\Models\Product;

class SearchController extends Controller
{
    /**
     * Display search results.
     */
    public function index(Request $request)
    {
        $query = $request->get('q', '');
        $products = collect();

        if ($query) {
            // Simple search - can be enhanced with Lunar's search driver
            $products = Product::with(['variants.prices', 'images'])
                ->where('status', 'published')
                ->whereHas('variants', function ($q) use ($query) {
                    $q->where('sku', 'like', "%{$query}%");
                })
                ->orWhereHas('urls', function ($q) use ($query) {
                    $q->where('slug', 'like', "%{$query}%");
                })
                ->latest()
                ->paginate(12)
                ->appends($request->query());
        }

        return view('storefront.search.index', compact('products', 'query'));
    }
}

