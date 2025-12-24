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
        $products = Product::with(['variants.prices', 'images'])
            ->where('status', 'published')
            ->latest()
            ->paginate(12);

        return view('storefront.products.index', compact('products'));
    }

    /**
     * Display the specified product.
     */
    public function show(string $slug)
    {
        $url = Url::where('slug', $slug)
            ->where('element_type', Product::class)
            ->firstOrFail();

        $product = Product::with([
            'variants.prices',
            'images',
            'collections',
            'associations.target',
            'tags',
        ])->findOrFail($url->element_id);

        // Get cross-sell, up-sell, and alternate products
        $crossSell = $product->associations()
            ->where('type', 'cross-sell')
            ->with('target.variants.prices')
            ->get()
            ->pluck('target');

        $upSell = $product->associations()
            ->where('type', 'up-sell')
            ->with('target.variants.prices')
            ->get()
            ->pluck('target');

        $alternate = $product->associations()
            ->where('type', 'alternate')
            ->with('target.variants.prices')
            ->get()
            ->pluck('target');

        return view('storefront.products.show', compact(
            'product',
            'crossSell',
            'upSell',
            'alternate'
        ));
    }
}

