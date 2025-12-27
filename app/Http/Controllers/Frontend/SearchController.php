<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\SearchService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    public function __construct(
        protected SearchService $searchService
    ) {}

    /**
     * Display search results with faceted search.
     * 
     * Uses local database queries for search functionality.
     */
    public function index(Request $request)
    {
        $query = $request->get('q', '');
        
        if (empty($query)) {
            return view('frontend.search.index', [
                'products' => collect(),
                'query' => '',
                'facets' => [],
            ]);
        }

        // Get filters from request
        $filters = $this->parseFilters($request);
        
        // Perform search with filters
        $result = $this->searchService->searchWithFilters($query, $filters, [
            'per_page' => $request->get('per_page', 24),
            'page' => $request->get('page', 1),
            'sort' => $request->get('sort', 'relevance'),
        ]);

        return view('frontend.search.index', [
            'products' => $result['results'],
            'query' => $query,
            'facets' => $result['facets'],
            'filters' => $filters,
        ]);
    }

    /**
     * Get autocomplete suggestions.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        $limit = (int) $request->get('limit', 10);

        if (strlen($query) < 2) {
            return response()->json(['data' => []]);
        }

        $suggestions = $this->searchService->searchSuggestions($query, $limit);
        $history = $this->searchService->getSearchHistory(5);

        return response()->json([
            'data' => $suggestions,
            'history' => $history,
        ]);
    }

    /**
     * Track product click from search results.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function trackClick(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'required|string',
            'product_id' => 'required|integer|exists:products,id',
        ]);

        $this->searchService->trackClick($validated['query'], $validated['product_id']);

        return response()->json(['success' => true]);
    }

    /**
     * Get popular searches.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function popularSearches(Request $request): JsonResponse
    {
        $period = $request->get('period', 'week');
        $limit = (int) $request->get('limit', 10);

        $searches = $this->searchService->popularSearches($limit, $period);

        return response()->json([
            'data' => $searches,
        ]);
    }

    /**
     * Get trending searches.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function trendingSearches(Request $request): JsonResponse
    {
        $limit = (int) $request->get('limit', 10);

        $searches = $this->searchService->trendingSearches($limit);

        return response()->json([
            'data' => $searches,
        ]);
    }

    /**
     * Parse filters from request.
     *
     * @param  Request  $request
     * @return array
     */
    protected function parseFilters(Request $request): array
    {
        $filters = [];

        // Category filter
        if ($request->has('category_id')) {
            $filters['category_ids'] = (array) $request->get('category_id');
        }

        // Brand filter
        if ($request->has('brand_id')) {
            $filters['brand_id'] = $request->get('brand_id');
        }

        // Price range filter
        if ($request->has('price_min')) {
            $filters['price_min'] = (int) ($request->get('price_min') * 100); // Convert to cents
        }
        if ($request->has('price_max')) {
            $filters['price_max'] = (int) ($request->get('price_max') * 100); // Convert to cents
        }

        // In stock filter
        if ($request->has('in_stock')) {
            $filters['in_stock'] = filter_var($request->get('in_stock'), FILTER_VALIDATE_BOOLEAN);
        }

        return $filters;
    }
}



