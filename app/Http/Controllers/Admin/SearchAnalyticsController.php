<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SearchAnalytic;
use App\Models\SearchSynonym;
use App\Services\SearchService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Admin controller for search analytics and synonym management.
 */
class SearchAnalyticsController extends Controller
{
    public function __construct(
        protected SearchService $searchService
    ) {}

    /**
     * Display search analytics dashboard.
     *
     * @param  Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // Admin-only: require staff authentication
        if (!auth('staff')->check()) {
            abort(403, 'Unauthorized');
        }

        $period = $request->get('period', 'week');
        $limit = (int) $request->get('limit', 50);

        // Get popular searches
        $popularSearches = $this->searchService->popularSearches($limit, $period);

        // Get zero-result searches
        $zeroResults = SearchAnalytic::where('zero_results', true)
            ->where('searched_at', '>=', $this->getPeriodStart($period))
            ->select('search_term')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('search_term')
            ->orderByDesc('count')
            ->limit(20)
            ->get();

        // Get search statistics
        $stats = $this->getSearchStatistics($period);

        // Get recent searches
        $recentSearches = SearchAnalytic::where('searched_at', '>=', $this->getPeriodStart($period))
            ->orderByDesc('searched_at')
            ->limit(50)
            ->get();

        return view('admin.search-analytics.index', compact(
            'popularSearches',
            'zeroResults',
            'stats',
            'recentSearches',
            'period'
        ));
    }

    /**
     * Get search statistics.
     *
     * @param  string  $period
     * @return array
     */
    protected function getSearchStatistics(string $period): array
    {
        $startDate = $this->getPeriodStart($period);

        $totalSearches = SearchAnalytic::where('searched_at', '>=', $startDate)->count();
        $zeroResultSearches = SearchAnalytic::where('searched_at', '>=', $startDate)
            ->where('zero_results', true)
            ->count();
        $uniqueSearches = SearchAnalytic::where('searched_at', '>=', $startDate)
            ->distinct('search_term')
            ->count('search_term');
        $clickedResults = SearchAnalytic::where('searched_at', '>=', $startDate)
            ->whereNotNull('clicked_product_id')
            ->count();

        return [
            'total_searches' => $totalSearches,
            'zero_result_searches' => $zeroResultSearches,
            'zero_result_percentage' => $totalSearches > 0 
                ? round(($zeroResultSearches / $totalSearches) * 100, 2) 
                : 0,
            'unique_searches' => $uniqueSearches,
            'clicked_results' => $clickedResults,
            'click_through_rate' => $totalSearches > 0 
                ? round(($clickedResults / $totalSearches) * 100, 2) 
                : 0,
        ];
    }

    /**
     * Get start date for period.
     *
     * @param  string  $period
     * @return \Carbon\Carbon
     */
    protected function getPeriodStart(string $period)
    {
        return match ($period) {
            'day' => now()->subDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'year' => now()->subYear(),
            default => now()->subWeek(),
        };
    }

    /**
     * Manage search synonyms.
     *
     * @return \Illuminate\View\View
     */
    public function synonyms()
    {
        $synonyms = SearchSynonym::ordered()->get();

        return view('admin.search-analytics.synonyms', compact('synonyms'));
    }

    /**
     * Store a new synonym.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function storeSynonym(Request $request): JsonResponse
    {
        // Admin-only: require staff authentication
        if (!auth('staff')->check()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'term' => 'required|string|max:255|unique:lunar_search_synonyms,term',
            'synonyms' => 'required|array|min:1',
            'synonyms.*' => 'required|string|max:255',
            'priority' => 'integer|min:0|max:100',
            'notes' => 'nullable|string',
        ]);

        $synonym = SearchSynonym::create([
            'term' => $validated['term'],
            'synonyms' => $validated['synonyms'],
            'priority' => $validated['priority'] ?? 0,
            'notes' => $validated['notes'] ?? null,
            'is_active' => true,
        ]);

        // Clear search cache to apply new synonyms
        Cache::flush();

        return response()->json([
            'message' => 'Synonym created successfully',
            'data' => $synonym,
        ], 201);
    }

    /**
     * Update a synonym.
     *
     * @param  Request  $request
     * @param  SearchSynonym  $synonym
     * @return JsonResponse
     */
    public function updateSynonym(Request $request, SearchSynonym $synonym): JsonResponse
    {
        // Admin-only: require staff authentication
        if (!auth('staff')->check()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'term' => 'required|string|max:255|unique:lunar_search_synonyms,term,' . $synonym->id,
            'synonyms' => 'required|array|min:1',
            'synonyms.*' => 'required|string|max:255',
            'priority' => 'integer|min:0|max:100',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $synonym->update($validated);

        // Clear search cache to apply updated synonyms
        Cache::flush();

        return response()->json([
            'message' => 'Synonym updated successfully',
            'data' => $synonym->fresh(),
        ]);
    }

    /**
     * Delete a synonym.
     *
     * @param  SearchSynonym  $synonym
     * @return JsonResponse
     */
    public function deleteSynonym(SearchSynonym $synonym): JsonResponse
    {
        // Admin-only: require staff authentication
        if (!auth('staff')->check()) {
            abort(403, 'Unauthorized');
        }

        $synonym->delete();

        // Clear search cache to apply synonym removal
        Cache::flush();

        return response()->json([
            'message' => 'Synonym deleted successfully',
        ]);
    }

    /**
     * Get search analytics data for charts.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function analyticsData(Request $request): JsonResponse
    {
        $period = $request->get('period', 'week');
        $startDate = $this->getPeriodStart($period);

        // Get daily search counts
        $dailySearches = SearchAnalytic::where('searched_at', '>=', $startDate)
            ->selectRaw('DATE(searched_at) as date')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Get top search terms
        $topSearches = SearchAnalytic::where('searched_at', '>=', $startDate)
            ->select('search_term')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(CASE WHEN zero_results = 0 THEN 1 ELSE 0 END) as success_count')
            ->groupBy('search_term')
            ->orderByDesc('count')
            ->limit(20)
            ->get();

        return response()->json([
            'daily_searches' => $dailySearches,
            'top_searches' => $topSearches,
        ]);
    }
}

