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
        $products = Product::with(['variants.prices', 'media', 'urls'])
            ->where('status', 'published')
            ->latest()
            ->paginate(12);

        return view('storefront.products.index', compact('products'));
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
            ->where('element_type', Product::class)
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
        ])->findOrFail($url->element_id);

        // Get cross-sell, up-sell, and alternate products using relationship scopes
        // See: https://docs.lunarphp.com/1.x/reference/associations
        $crossSell = $product->associations()
            ->crossSell()
            ->with('target.variants.prices', 'target.images')
            ->get()
            ->pluck('target');

        $upSell = $product->associations()
            ->upSell()
            ->with('target.variants.prices', 'target.images')
            ->get()
            ->pluck('target');

        $alternate = $product->associations()
            ->alternate()
            ->with('target.variants.prices', 'target.images')
            ->get()
            ->pluck('target');

        // Get attribute values for display
        // See: https://docs.lunarphp.com/1.x/reference/attributes
        $description = $product->translateAttribute('description');
        $material = $product->translateAttribute('material');
        $weight = $product->translateAttribute('weight');
        $metaTitle = $product->translateAttribute('meta_title');
        $metaDescription = $product->translateAttribute('meta_description');

        return view('storefront.products.show', compact(
            'product',
            'crossSell',
            'upSell',
            'alternate',
            'description',
            'material',
            'weight',
            'metaTitle',
            'metaDescription'
        ));
    }
}

