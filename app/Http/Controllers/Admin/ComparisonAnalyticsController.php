<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ComparisonService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Admin controller for comparison analytics.
 */
class ComparisonAnalyticsController extends Controller
{
    public function __construct(
        protected ComparisonService $comparisonService
    ) {}

    /**
     * Get comparison analytics.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Admin-only: require staff authentication
        if (!auth('staff')->check()) {
            abort(403, 'Unauthorized');
        }

        $mostCompared = $this->comparisonService->getMostComparedPairs(20);

        return response()->json([
            'most_compared_pairs' => $mostCompared,
        ]);
    }
}
