<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Observability\PricingMetricsService;
use Illuminate\Http\JsonResponse;

/**
 * API Controller for pricing metrics and observability.
 */
class MetricsController extends Controller
{
    public function __construct(
        protected PricingMetricsService $metricsService
    ) {}

    /**
     * Get pricing metrics summary.
     */
    public function pricing(): JsonResponse
    {
        return response()->json([
            'metrics' => $this->metricsService->getMetricsSummary(),
        ]);
    }

    /**
     * Get cache hit ratio.
     */
    public function cacheHitRatio(string $type = 'total'): JsonResponse
    {
        return response()->json([
            'type' => $type,
            'hit_ratio' => $this->metricsService->getCacheHitRatio($type),
        ]);
    }
}

