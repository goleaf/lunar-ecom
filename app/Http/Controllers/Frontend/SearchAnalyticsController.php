<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Lunar\Search\SearchAnalyticsHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller for search analytics endpoints.
 */
class SearchAnalyticsController extends Controller
{
    /**
     * Get search statistics.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function statistics(Request $request): JsonResponse
    {
        $period = $request->input('period', 'week');
        $stats = SearchAnalyticsHelper::getStatistics($period);

        return response()->json([
            'data' => $stats,
        ]);
    }

    /**
     * Get zero-result queries.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function zeroResults(Request $request): JsonResponse
    {
        $limit = (int) $request->input('limit', 20);
        $period = $request->input('period', 'week');

        $queries = SearchAnalyticsHelper::getZeroResultQueries($limit, $period);

        return response()->json([
            'data' => $queries,
        ]);
    }

    /**
     * Get search trends.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function trends(Request $request): JsonResponse
    {
        $period = $request->input('period', 'week');
        $interval = $request->input('interval', 'day');

        $trends = SearchAnalyticsHelper::getSearchTrends($period, $interval);

        return response()->json([
            'data' => $trends,
        ]);
    }

    /**
     * Get most clicked products from search.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function mostClicked(Request $request): JsonResponse
    {
        $limit = (int) $request->input('limit', 10);
        $period = $request->input('period', 'week');

        $products = SearchAnalyticsHelper::getMostClickedProducts($limit, $period);

        return response()->json([
            'data' => $products,
        ]);
    }
}


