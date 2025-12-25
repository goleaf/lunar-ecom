<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PricingReportService;
use Illuminate\Http\JsonResponse;

class PricingReportController extends Controller
{
    protected PricingReportService $reportService;

    public function __construct(PricingReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Get report by product.
     */
    public function byProduct(Request $request): JsonResponse
    {
        $productId = $request->input('product_id');
        $filters = $request->only(['matrix_type', 'is_active']);

        $report = $this->reportService->reportByProduct($productId, $filters);

        return response()->json($report);
    }

    /**
     * Get report by customer group.
     */
    public function byCustomerGroup(Request $request): JsonResponse
    {
        $customerGroupHandle = $request->input('customer_group');

        $report = $this->reportService->reportByCustomerGroup($customerGroupHandle);

        return response()->json($report);
    }

    /**
     * Get report by region.
     */
    public function byRegion(Request $request): JsonResponse
    {
        $region = $request->input('region');

        $report = $this->reportService->reportByRegion($region);

        return response()->json($report);
    }

    /**
     * Get price history report.
     */
    public function priceHistory(Request $request): JsonResponse
    {
        $filters = $request->only([
            'product_id',
            'variant_id',
            'change_type',
            'date_from',
            'date_to',
        ]);

        $report = $this->reportService->reportPriceHistory($filters);

        return response()->json($report);
    }

    /**
     * Get summary report.
     */
    public function summary(): JsonResponse
    {
        $report = $this->reportService->generateSummaryReport();

        return response()->json($report);
    }
}
