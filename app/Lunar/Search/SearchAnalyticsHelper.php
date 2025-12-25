<?php

namespace App\Lunar\Search;

use App\Models\SearchAnalytic;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Helper class for search analytics operations.
 * 
 * Provides methods for analyzing search performance, popular searches,
 * zero-result queries, and search trends.
 */
class SearchAnalyticsHelper
{
    /**
     * Get search statistics.
     * 
     * @param string $period 'day', 'week', 'month', 'all'
     * @return array
     */
    public static function getStatistics(string $period = 'week'): array
    {
        $cacheKey = "search.stats.{$period}";

        return Cache::remember($cacheKey, 3600, function () use ($period) {
            $query = SearchAnalytic::query();

            // Filter by period
            switch ($period) {
                case 'day':
                    $query->where('searched_at', '>=', now()->subDay());
                    break;
                case 'week':
                    $query->where('searched_at', '>=', now()->subWeek());
                    break;
                case 'month':
                    $query->where('searched_at', '>=', now()->subMonth());
                    break;
            }

            $total = $query->count();
            $zeroResults = $query->where('zero_results', true)->count();
            $withResults = $total - $zeroResults;
            $withClicks = $query->whereNotNull('clicked_product_id')->count();

            return [
                'total_searches' => $total,
                'searches_with_results' => $withResults,
                'zero_result_searches' => $zeroResults,
                'searches_with_clicks' => $withClicks,
                'zero_result_rate' => $total > 0 ? round(($zeroResults / $total) * 100, 2) : 0,
                'click_through_rate' => $total > 0 ? round(($withClicks / $total) * 100, 2) : 0,
                'average_results_per_search' => $query->avg('result_count') ?? 0,
            ];
        });
    }

    /**
     * Get zero-result queries (queries that returned no results).
     * 
     * @param int $limit
     * @param string $period
     * @return Collection
     */
    public static function getZeroResultQueries(int $limit = 20, string $period = 'week'): Collection
    {
        $cacheKey = "search.zero_results.{$period}.{$limit}";

        return Cache::remember($cacheKey, 3600, function () use ($limit, $period) {
            $query = SearchAnalytic::where('zero_results', true)
                ->select('search_term')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('search_term')
                ->orderByDesc('count')
                ->limit($limit);

            switch ($period) {
                case 'day':
                    $query->where('searched_at', '>=', now()->subDay());
                    break;
                case 'week':
                    $query->where('searched_at', '>=', now()->subWeek());
                    break;
                case 'month':
                    $query->where('searched_at', '>=', now()->subMonth());
                    break;
            }

            return $query->get();
        });
    }

    /**
     * Get search trends (searches over time).
     * 
     * @param string $period 'day', 'week', 'month'
     * @param string $interval 'hour', 'day', 'week'
     * @return Collection
     */
    public static function getSearchTrends(string $period = 'week', string $interval = 'day'): Collection
    {
        $cacheKey = "search.trends.{$period}.{$interval}";

        return Cache::remember($cacheKey, 1800, function () use ($period, $interval) {
            $query = SearchAnalytic::query();

            // Filter by period
            switch ($period) {
                case 'day':
                    $query->where('searched_at', '>=', now()->subDay());
                    break;
                case 'week':
                    $query->where('searched_at', '>=', now()->subWeek());
                    break;
                case 'month':
                    $query->where('searched_at', '>=', now()->subMonth());
                    break;
            }

            // Group by interval
            switch ($interval) {
                case 'hour':
                    $query->selectRaw('DATE_FORMAT(searched_at, "%Y-%m-%d %H:00:00") as period')
                        ->selectRaw('COUNT(*) as count')
                        ->groupBy('period')
                        ->orderBy('period');
                    break;
                case 'day':
                    $query->selectRaw('DATE(searched_at) as period')
                        ->selectRaw('COUNT(*) as count')
                        ->groupBy('period')
                        ->orderBy('period');
                    break;
                case 'week':
                    $query->selectRaw('YEARWEEK(searched_at) as period')
                        ->selectRaw('COUNT(*) as count')
                        ->groupBy('period')
                        ->orderBy('period');
                    break;
            }

            return $query->get();
        });
    }

    /**
     * Get most clicked products from search.
     * 
     * @param int $limit
     * @param string $period
     * @return Collection
     */
    public static function getMostClickedProducts(int $limit = 10, string $period = 'week'): Collection
    {
        $cacheKey = "search.most_clicked.{$period}.{$limit}";

        return Cache::remember($cacheKey, 3600, function () use ($limit, $period) {
            $query = SearchAnalytic::whereNotNull('clicked_product_id')
                ->select('clicked_product_id')
                ->selectRaw('COUNT(*) as click_count')
                ->groupBy('clicked_product_id')
                ->orderByDesc('click_count')
                ->limit($limit);

            switch ($period) {
                case 'day':
                    $query->where('searched_at', '>=', now()->subDay());
                    break;
                case 'week':
                    $query->where('searched_at', '>=', now()->subWeek());
                    break;
                case 'month':
                    $query->where('searched_at', '>=', now()->subMonth());
                    break;
            }

            return $query->get()->map(function ($item) {
                return [
                    'product_id' => $item->clicked_product_id,
                    'click_count' => $item->click_count,
                ];
            });
        });
    }

    /**
     * Get search query suggestions based on analytics.
     * 
     * @param string $query
     * @param int $limit
     * @return Collection
     */
    public static function getQuerySuggestions(string $query, int $limit = 5): Collection
    {
        if (strlen($query) < 2) {
            return collect();
        }

        return SearchAnalytic::where('search_term', 'like', $query . '%')
            ->where('zero_results', false)
            ->select('search_term')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('search_term')
            ->orderByDesc('count')
            ->limit($limit)
            ->pluck('search_term');
    }
}

