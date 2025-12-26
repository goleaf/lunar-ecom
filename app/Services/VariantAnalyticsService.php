<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\VariantView;
use App\Models\VariantReturn;
use App\Models\VariantPerformance;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Service for variant analytics and performance tracking.
 * 
 * Handles:
 * - Views per variant
 * - Conversion rate
 * - Revenue per variant
 * - Stock turnover
 * - Return rate per variant
 * - Discount impact
 * - Variant popularity ranking
 */
class VariantAnalyticsService
{
    /**
     * Track variant view.
     *
     * @param  ProductVariant  $variant
     * @param  array  $context
     * @return VariantView
     */
    public function trackView(ProductVariant $variant, array $context = []): VariantView
    {
        return VariantView::create([
            'product_variant_id' => $variant->id,
            'session_id' => $context['session_id'] ?? session()->getId(),
            'ip_address' => $context['ip_address'] ?? request()->ip(),
            'user_agent' => $context['user_agent'] ?? request()->userAgent(),
            'user_id' => $context['user_id'] ?? auth()->id(),
            'channel_id' => $context['channel_id'] ?? null,
            'referrer' => $context['referrer'] ?? request()->header('referer'),
            'viewed_at' => now(),
        ]);
    }

    /**
     * Get views for variant.
     *
     * @param  ProductVariant  $variant
     * @param  Carbon|null  $startDate
     * @param  Carbon|null  $endDate
     * @return array
     */
    public function getViews(ProductVariant $variant, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = VariantView::where('product_variant_id', $variant->id);

        if ($startDate) {
            $query->where('viewed_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('viewed_at', '<=', $endDate);
        }

        $totalViews = $query->count();
        $uniqueViews = $query->distinct('session_id')->count('session_id');
        $uniqueUsers = $query->whereNotNull('user_id')->distinct('user_id')->count('user_id');

        return [
            'total_views' => $totalViews,
            'unique_views' => $uniqueViews,
            'unique_users' => $uniqueUsers,
        ];
    }

    /**
     * Calculate conversion rate for variant.
     *
     * @param  ProductVariant  $variant
     * @param  Carbon|null  $startDate
     * @param  Carbon|null  $endDate
     * @return float
     */
    public function calculateConversionRate(ProductVariant $variant, ?Carbon $startDate = null, ?Carbon $endDate = null): float
    {
        $views = $this->getViews($variant, $startDate, $endDate);
        $totalViews = $views['total_views'];

        if ($totalViews === 0) {
            return 0.0;
        }

        $orders = $this->getOrdersCount($variant, $startDate, $endDate);

        return round(($orders / $totalViews) * 100, 2);
    }

    /**
     * Get orders count for variant.
     *
     * @param  ProductVariant  $variant
     * @param  Carbon|null  $startDate
     * @param  Carbon|null  $endDate
     * @return int
     */
    protected function getOrdersCount(ProductVariant $variant, ?Carbon $startDate = null, ?Carbon $endDate = null): int
    {
        $prefix = config('lunar.database.table_prefix');
        
        $query = DB::table($prefix . 'order_lines')
            ->join($prefix . 'orders', $prefix . 'order_lines.order_id', '=', $prefix . 'orders.id')
            ->where($prefix . 'order_lines.purchasable_type', ProductVariant::class)
            ->where($prefix . 'order_lines.purchasable_id', $variant->id)
            ->whereIn($prefix . 'orders.status', ['placed', 'completed']);

        if ($startDate) {
            $query->where($prefix . 'orders.placed_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where($prefix . 'orders.placed_at', '<=', $endDate);
        }

        return $query->distinct($prefix . 'orders.id')->count($prefix . 'orders.id');
    }

    /**
     * Calculate revenue for variant.
     *
     * @param  ProductVariant  $variant
     * @param  Carbon|null  $startDate
     * @param  Carbon|null  $endDate
     * @return array
     */
    public function calculateRevenue(ProductVariant $variant, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = DB::table(config('lunar.database.table_prefix') . 'order_lines')
            ->where('purchasable_type', ProductVariant::class)
            ->where('purchasable_id', $variant->id)
            ->join(
                config('lunar.database.table_prefix') . 'orders',
                config('lunar.database.table_prefix') . 'order_lines.order_id',
                '=',
                config('lunar.database.table_prefix') . 'orders.id'
            )
            ->whereIn(config('lunar.database.table_prefix') . 'orders.status', ['placed', 'completed']);

        if ($startDate) {
            $query->where(config('lunar.database.table_prefix') . 'orders.placed_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where(config('lunar.database.table_prefix') . 'orders.placed_at', '<=', $endDate);
        }

        $results = $query->select(
            DB::raw('SUM(' . config('lunar.database.table_prefix') . 'order_lines.quantity) as quantity_sold'),
            DB::raw('SUM(' . config('lunar.database.table_prefix') . 'order_lines.sub_total) as revenue'),
            DB::raw('SUM(' . config('lunar.database.table_prefix') . 'order_lines.discount_total) as discount_total')
        )->first();

        $revenue = (int)($results->revenue ?? 0);
        $discountTotal = (int)($results->discount_total ?? 0);
        $revenueAfterDiscount = $revenue - $discountTotal;
        $quantitySold = (int)($results->quantity_sold ?? 0);
        $averageOrderValue = $quantitySold > 0 ? round($revenue / $quantitySold) : 0;

        return [
            'revenue' => $revenue,
            'revenue_after_discount' => $revenueAfterDiscount,
            'discount_total' => $discountTotal,
            'quantity_sold' => $quantitySold,
            'average_order_value' => $averageOrderValue,
            'orders_count' => $this->getOrdersCount($variant, $startDate, $endDate),
        ];
    }

    /**
     * Calculate stock turnover for variant.
     *
     * @param  ProductVariant  $variant
     * @param  Carbon|null  $startDate
     * @param  Carbon|null  $endDate
     * @return array
     */
    public function calculateStockTurnover(ProductVariant $variant, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? Carbon::now()->subMonth();
        $endDate = $endDate ?? Carbon::now();

        $revenue = $this->calculateRevenue($variant, $startDate, $endDate);
        $quantitySold = $revenue['quantity_sold'];

        // Get average stock level (simplified - would need stock history for accurate calculation)
        $averageStock = $variant->stock ?? 0;

        $turnoverRate = $averageStock > 0 ? round(($quantitySold / $averageStock) * 100, 2) : 0;

        return [
            'quantity_sold' => $quantitySold,
            'average_stock' => $averageStock,
            'turnover_rate' => $turnoverRate,
            'days_to_turnover' => $turnoverRate > 0 ? round(365 / $turnoverRate, 1) : null,
        ];
    }

    /**
     * Track variant return.
     *
     * @param  ProductVariant  $variant
     * @param  int  $orderId
     * @param  int  $orderLineId
     * @param  array  $data
     * @return VariantReturn
     */
    public function trackReturn(ProductVariant $variant, int $orderId, int $orderLineId, array $data): VariantReturn
    {
        return VariantReturn::create([
            'product_variant_id' => $variant->id,
            'order_id' => $orderId,
            'order_line_id' => $orderLineId,
            'quantity_returned' => $data['quantity'] ?? 1,
            'refund_amount' => $data['refund_amount'] ?? 0,
            'return_reason' => $data['reason'] ?? null,
            'return_notes' => $data['notes'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'returned_at' => $data['returned_at'] ?? now(),
        ]);
    }

    /**
     * Calculate return rate for variant.
     *
     * @param  ProductVariant  $variant
     * @param  Carbon|null  $startDate
     * @param  Carbon|null  $endDate
     * @return array
     */
    public function calculateReturnRate(ProductVariant $variant, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = VariantReturn::where('product_variant_id', $variant->id)
            ->whereIn('status', ['approved', 'refunded', 'completed']);

        if ($startDate) {
            $query->where('returned_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('returned_at', '<=', $endDate);
        }

        $returns = $query->get();

        $returnsCount = $returns->count();
        $quantityReturned = $returns->sum('quantity_returned');
        $returnRevenue = $returns->sum('refund_amount');

        // Get quantity sold in same period
        $revenue = $this->calculateRevenue($variant, $startDate, $endDate);
        $quantitySold = $revenue['quantity_sold'];

        $returnRate = $quantitySold > 0 ? round(($quantityReturned / $quantitySold) * 100, 2) : 0;

        return [
            'returns_count' => $returnsCount,
            'quantity_returned' => $quantityReturned,
            'return_revenue' => $returnRevenue,
            'quantity_sold' => $quantitySold,
            'return_rate' => $returnRate,
        ];
    }

    /**
     * Calculate discount impact for variant.
     *
     * @param  ProductVariant  $variant
     * @param  Carbon|null  $startDate
     * @param  Carbon|null  $endDate
     * @return array
     */
    public function calculateDiscountImpact(ProductVariant $variant, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = DB::table(config('lunar.database.table_prefix') . 'order_lines')
            ->where('purchasable_type', ProductVariant::class)
            ->where('purchasable_id', $variant->id)
            ->join(
                config('lunar.database.table_prefix') . 'orders',
                config('lunar.database.table_prefix') . 'order_lines.order_id',
                '=',
                config('lunar.database.table_prefix') . 'orders.id'
            )
            ->whereIn(config('lunar.database.table_prefix') . 'orders.status', ['placed', 'completed'])
            ->where(config('lunar.database.table_prefix') . 'order_lines.discount_total', '>', 0);

        if ($startDate) {
            $query->where(config('lunar.database.table_prefix') . 'orders.placed_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where(config('lunar.database.table_prefix') . 'orders.placed_at', '<=', $endDate);
        }

        $results = $query->select(
            DB::raw('COUNT(*) as discount_applied_count'),
            DB::raw('SUM(' . config('lunar.database.table_prefix') . 'order_lines.discount_total) as discount_amount_total'),
            DB::raw('SUM(' . config('lunar.database.table_prefix') . 'order_lines.quantity) as quantity_with_discount')
        )->first();

        $discountAppliedCount = (int)($results->discount_applied_count ?? 0);
        $discountAmountTotal = (int)($results->discount_amount_total ?? 0);
        $quantityWithDiscount = (int)($results->quantity_with_discount ?? 0);

        // Calculate revenue impact (additional revenue from discount-driven sales)
        $totalRevenue = $this->calculateRevenue($variant, $startDate, $endDate);
        $totalOrders = $totalRevenue['orders_count'];
        
        // Estimate impact (simplified - would need A/B testing for accurate measurement)
        $discountImpactRevenue = $discountAppliedCount > 0 ? 
            round($totalRevenue['revenue'] * ($discountAppliedCount / max($totalOrders, 1)) * 0.3) : 0; // 30% attribution

        return [
            'discount_applied_count' => $discountAppliedCount,
            'discount_amount_total' => $discountAmountTotal,
            'quantity_with_discount' => $quantityWithDiscount,
            'discount_impact_revenue' => $discountImpactRevenue,
            'average_discount_per_order' => $discountAppliedCount > 0 ? round($discountAmountTotal / $discountAppliedCount) : 0,
        ];
    }

    /**
     * Calculate popularity score for variant.
     *
     * @param  ProductVariant  $variant
     * @return float
     */
    public function calculatePopularityScore(ProductVariant $variant): float
    {
        // Get metrics for last 30 days
        $startDate = Carbon::now()->subDays(30);
        $endDate = Carbon::now();

        $views = $this->getViews($variant, $startDate, $endDate);
        $revenue = $this->calculateRevenue($variant, $startDate, $endDate);
        $conversionRate = $this->calculateConversionRate($variant, $startDate, $endDate);
        $returnRate = $this->calculateReturnRate($variant, $startDate, $endDate);

        // Calculate popularity score
        // Formula: (views * 0.1) + (revenue * 0.001) + (conversion_rate * 100) - (return_rate * 50)
        $score = 
            ($views['total_views'] * 0.1) +
            ($revenue['revenue'] * 0.001) +
            ($conversionRate * 100) -
            ($returnRate['return_rate'] * 50);

        return max(0, round($score, 2)); // Ensure non-negative
    }

    /**
     * Calculate and update popularity ranking for all variants.
     *
     * @return int Number of variants updated
     */
    public function updatePopularityRankings(): int
    {
        $variants = ProductVariant::where('status', 'active')->get();
        $scores = [];

        foreach ($variants as $variant) {
            $score = $this->calculatePopularityScore($variant);
            $scores[$variant->id] = $score;
        }

        // Sort by score descending
        arsort($scores);

        // Update rankings
        $rank = 1;
        $updated = 0;

        foreach ($scores as $variantId => $score) {
            VariantPerformance::where('product_variant_id', $variantId)
                ->update([
                    'popularity_score' => $score,
                    'popularity_rank' => $rank++,
                ]);
            $updated++;
        }

        return $updated;
    }

    /**
     * Calculate complete analytics for variant.
     *
     * @param  ProductVariant  $variant
     * @param  Carbon|null  $startDate
     * @param  Carbon|null  $endDate
     * @return array
     */
    public function calculateAnalytics(ProductVariant $variant, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? Carbon::now()->subMonth();
        $endDate = $endDate ?? Carbon::now();

        $views = $this->getViews($variant, $startDate, $endDate);
        $conversionRate = $this->calculateConversionRate($variant, $startDate, $endDate);
        $revenue = $this->calculateRevenue($variant, $startDate, $endDate);
        $stockTurnover = $this->calculateStockTurnover($variant, $startDate, $endDate);
        $returnRate = $this->calculateReturnRate($variant, $startDate, $endDate);
        $discountImpact = $this->calculateDiscountImpact($variant, $startDate, $endDate);
        $popularityScore = $this->calculatePopularityScore($variant);

        // Get popularity rank
        $popularityRank = VariantPerformance::where('product_variant_id', $variant->id)
            ->value('popularity_rank');

        return [
            'views' => $views,
            'conversion_rate' => $conversionRate,
            'revenue' => $revenue,
            'stock_turnover' => $stockTurnover,
            'return_rate' => $returnRate,
            'discount_impact' => $discountImpact,
            'popularity_score' => $popularityScore,
            'popularity_rank' => $popularityRank,
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
        ];
    }

    /**
     * Store analytics in VariantPerformance table.
     *
     * @param  ProductVariant  $variant
     * @param  Carbon|string  $date
     * @param  string  $period
     * @return VariantPerformance
     */
    public function storeAnalytics(ProductVariant $variant, $date, string $period = 'daily'): VariantPerformance
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);
        $startDate = $date->copy()->startOfDay();
        $endDate = $date->copy()->endOfDay();

        if ($period === 'weekly') {
            $startDate = $date->copy()->startOfWeek();
            $endDate = $date->copy()->endOfWeek();
        } elseif ($period === 'monthly') {
            $startDate = $date->copy()->startOfMonth();
            $endDate = $date->copy()->endOfMonth();
        }

        $analytics = $this->calculateAnalytics($variant, $startDate, $endDate);

        return VariantPerformance::updateOrCreate(
            [
                'product_variant_id' => $variant->id,
                'date' => $date,
                'period' => $period,
            ],
            [
                'views' => $analytics['views']['total_views'],
                'unique_views' => $analytics['views']['unique_views'],
                'orders' => $analytics['revenue']['orders_count'],
                'quantity_sold' => $analytics['revenue']['quantity_sold'],
                'conversion_rate' => $analytics['conversion_rate'],
                'revenue' => $analytics['revenue']['revenue'],
                'revenue_discounted' => $analytics['revenue']['revenue_after_discount'],
                'average_order_value' => $analytics['revenue']['average_order_value'],
                'returns_count' => $analytics['return_rate']['returns_count'],
                'return_rate' => $analytics['return_rate']['return_rate'],
                'return_revenue' => $analytics['return_rate']['return_revenue'],
                'discount_applied_count' => $analytics['discount_impact']['discount_applied_count'],
                'discount_amount_total' => $analytics['discount_impact']['discount_amount_total'],
                'discount_impact_revenue' => $analytics['discount_impact']['discount_impact_revenue'],
                'popularity_score' => $analytics['popularity_score'],
                'popularity_rank' => $analytics['popularity_rank'],
            ]
        );
    }
}


