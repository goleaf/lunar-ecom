<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Lunar\Search\SearchHelper;
use Illuminate\Http\Request;
use Lunar\Models\Product;

class SearchController extends Controller
{
    /**
     * Display search results.
     * 
     * Uses Laravel Scout for search via Lunar's Searchable trait.
     * See: https://docs.lunarphp.com/1.x/reference/search
     */
    public function index(Request $request)
    {
        $query = $request->get('q', '');
        $products = collect();
        $currentPage = $request->get('page', 1);

        if ($query) {
            // Use Laravel Scout for search (configured via config/scout.php)
            // Products use the Searchable trait from Lunar which provides
            // integration with Scout and proper indexing via indexers
            try {
                $products = Product::search($query)
                    ->where('status', 'published')
                    ->with(['variants.prices', 'media']) // Eager load relationships
                    ->paginate(12, 'page', $currentPage)
                    ->appends($request->query());
            } catch (\Exception $e) {
                // Fallback to simple database search if Scout is not configured
                // This handles cases where Scout driver is not set up yet
                \Log::warning('Scout search failed, falling back to database search: ' . $e->getMessage());
                
                $products = Product::with(['variants.prices', 'media'])
                    ->where('status', 'published')
                    ->whereHas('urls', function ($q) use ($query) {
                        $q->where('slug', 'like', "%{$query}%");
                    })
                    ->orWhereHas('variants', function ($q) use ($query) {
                        $q->where('sku', 'like', "%{$query}%");
                    })
                    ->latest()
                    ->paginate(12)
                    ->appends($request->query());
            }
        }

        return view('storefront.search.index', compact('products', 'query'));
    }
}

