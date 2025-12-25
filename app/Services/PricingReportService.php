<?php

namespace App\Services;

use App\Models\PriceMatrix;
use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\ProductVariant;
use Lunar\Models\CustomerGroup;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * PricingReportService - Generate pricing reports.
 */
class PricingReportService
{
    /**
     * Generate report by product.
     *
     * @param int|null $productId
     * @param array $filters
     * @return array
     */
    public function reportByProduct(?int $productId = null, array $filters = []): array
    {
        $query = PriceMatrix::with('product');

        if ($productId) {
            $query->where('product_id', $productId);
        }

        if (isset($filters['matrix_type'])) {
            $query->where('matrix_type', $filters['matrix_type']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        $matrices = $query->get();

        return [
            'total_matrices' => $matrices->count(),
            'by_type' => $matrices->groupBy('matrix_type')->map->count(),
            'active' => $matrices->where('is_active', true)->count(),
            'inactive' => $matrices->where('is_active', false)->count(),
            'matrices' => $matrices->map(function ($matrix) {
                return [
                    'id' => $matrix->id,
                    'product_id' => $matrix->product_id,
                    'product_name' => $matrix->product->translateAttribute('name') ?? 'N/A',
                    'matrix_type' => $matrix->matrix_type,
                    'is_active' => $matrix->is_active,
                    'priority' => $matrix->priority,
                    'starts_at' => $matrix->starts_at?->toIso8601String(),
                    'ends_at' => $matrix->ends_at?->toIso8601String(),
                ];
            }),
        ];
    }

    /**
     * Generate report by customer group.
     *
     * @param string|null $customerGroupHandle
     * @return array
     */
    public function reportByCustomerGroup(?string $customerGroupHandle = null): array
    {
        $query = PriceMatrix::with('product');

        if ($customerGroupHandle) {
            $query->whereJsonContains('rules->customer_groups', [$customerGroupHandle => true]);
        } else {
            $query->where('matrix_type', PriceMatrix::TYPE_CUSTOMER_GROUP);
        }

        $matrices = $query->get();

        $customerGroups = CustomerGroup::all();
        $report = [];

        foreach ($customerGroups as $group) {
            $groupMatrices = $matrices->filter(function ($matrix) use ($group) {
                $rules = $matrix->rules;
                return isset($rules['customer_groups'][$group->handle]);
            });

            $report[$group->handle] = [
                'name' => $group->name,
                'handle' => $group->handle,
                'total_matrices' => $groupMatrices->count(),
                'products' => $groupMatrices->map(function ($matrix) {
                    return [
                        'product_id' => $matrix->product_id,
                        'product_name' => $matrix->product->translateAttribute('name') ?? 'N/A',
                        'price' => $matrix->rules['customer_groups'][$group->handle]['price'] ?? null,
                    ];
                }),
            ];
        }

        return [
            'total_customer_groups' => count($report),
            'groups' => $report,
        ];
    }

    /**
     * Generate report by region.
     *
     * @param string|null $region
     * @return array
     */
    public function reportByRegion(?string $region = null): array
    {
        $query = PriceMatrix::with('product');

        if ($region) {
            $query->whereJsonContains('rules->regions', [$region => true]);
        } else {
            $query->where('matrix_type', PriceMatrix::TYPE_REGION);
        }

        $matrices = $query->get();

        $regions = [];
        foreach ($matrices as $matrix) {
            $rules = $matrix->rules;
            if (isset($rules['regions'])) {
                foreach ($rules['regions'] as $regionCode => $regionData) {
                    if (!isset($regions[$regionCode])) {
                        $regions[$regionCode] = [
                            'code' => $regionCode,
                            'total_matrices' => 0,
                            'products' => [],
                        ];
                    }
                    $regions[$regionCode]['total_matrices']++;
                    $regions[$regionCode]['products'][] = [
                        'product_id' => $matrix->product_id,
                        'product_name' => $matrix->product->translateAttribute('name') ?? 'N/A',
                        'price' => $regionData['price'] ?? null,
                    ];
                }
            }
        }

        return [
            'total_regions' => count($regions),
            'regions' => $regions,
        ];
    }

    /**
     * Generate price change history report.
     *
     * @param array $filters
     * @return array
     */
    public function reportPriceHistory(array $filters = []): array
    {
        $query = PriceHistory::with(['product', 'variant', 'currency', 'customerGroup']);

        if (isset($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (isset($filters['variant_id'])) {
            $query->where('variant_id', $filters['variant_id']);
        }

        if (isset($filters['change_type'])) {
            $query->where('change_type', $filters['change_type']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['date_from']));
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['date_to']));
        }

        $histories = $query->orderBy('created_at', 'desc')->get();

        return [
            'total_changes' => $histories->count(),
            'by_type' => $histories->groupBy('change_type')->map->count(),
            'changes' => $histories->map(function ($history) {
                return [
                    'id' => $history->id,
                    'product_id' => $history->product_id,
                    'product_name' => $history->product->translateAttribute('name') ?? 'N/A',
                    'variant_id' => $history->variant_id,
                    'variant_sku' => $history->variant->sku ?? null,
                    'old_price' => $history->old_price,
                    'new_price' => $history->new_price,
                    'price_change' => $history->getPriceChangePercent(),
                    'change_type' => $history->change_type,
                    'change_reason' => $history->change_reason,
                    'changed_by' => $history->changedBy->name ?? null,
                    'created_at' => $history->created_at->toIso8601String(),
                ];
            }),
        ];
    }

    /**
     * Generate summary report.
     *
     * @return array
     */
    public function generateSummaryReport(): array
    {
        $totalMatrices = PriceMatrix::count();
        $activeMatrices = PriceMatrix::where('is_active', true)->count();
        $totalPriceChanges = PriceHistory::count();

        $byType = PriceMatrix::select('matrix_type', DB::raw('count(*) as count'))
            ->groupBy('matrix_type')
            ->pluck('count', 'matrix_type');

        $recentChanges = PriceHistory::with('product')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($history) {
                return [
                    'product_name' => $history->product->translateAttribute('name') ?? 'N/A',
                    'old_price' => $history->old_price,
                    'new_price' => $history->new_price,
                    'change_type' => $history->change_type,
                    'created_at' => $history->created_at->toIso8601String(),
                ];
            });

        return [
            'summary' => [
                'total_matrices' => $totalMatrices,
                'active_matrices' => $activeMatrices,
                'inactive_matrices' => $totalMatrices - $activeMatrices,
                'total_price_changes' => $totalPriceChanges,
            ],
            'by_type' => $byType,
            'recent_changes' => $recentChanges,
        ];
    }
}

