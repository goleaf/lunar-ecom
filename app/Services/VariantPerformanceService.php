<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\VariantPerformance;
use App\Models\ProductView;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Service for calculating variant performance analytics.
 */
class VariantPerformanceService
{
    /**
     * Calculate performance for a variant for a specific date.
     *
     * @param  ProductVariant  $variant
     * @param  Carbon|string  $date
     * @param  string  $period
     * @return VariantPerformance
     */
    public function calculateForDate(ProductVariant $variant, $date, string $period = 'daily'): VariantPerformance
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);
        
        // Get views (approximate - views are tracked at product level)
        $views = ProductView::where('product_id', $variant->product_id)
            ->whereDate('viewed_at', $date)
            ->count();
        
        $uniqueViews = ProductView::where('product_id', $variant->product_id)
            ->whereDate('viewed_at', $date)
            ->distinct('session_id')
            ->count('session_id');
        
        // Get orders for this variant
        $orders = \Lunar\Models\OrderLine::where('purchasable_type', ProductVariant::class)
            ->where('purchasable_id', $variant->id)
            ->whereHas('order', function ($q) use ($date) {
                $q->whereDate('placed_at', $date)
                  ->whereIn('status', ['placed', 'completed']);
            })
            ->get();
        
        $quantitySold = $orders->sum('quantity');
        $revenue = $orders->sum(function ($orderLine) {
            return $orderLine->sub_total->value ?? 0;
        });
        $revenueDiscounted = $orders->sum(function ($orderLine) {
            return ($orderLine->sub_total->value ?? 0) - ($orderLine->discount_total->value ?? 0);
        });
        
        // Calculate conversion rate
        $conversionRate = $views > 0 ? ($orders->count() / $views) : 0;
        
        // Calculate average order value
        $averageOrderValue = $orders->count() > 0 ? ($revenue / $orders->count()) : 0;
        
        // Stock turnover
        $stockTurnover = $quantitySold;
        $averageStock = $variant->stock;
        $stockTurnoverRate = $averageStock > 0 ? ($stockTurnover / $averageStock) : 0;
        
        // Price metrics
        $price = $variant->prices()->first();
        $averagePrice = $price?->price->value ?? 0;
        $priceChanges = 0; // Would need to track price change history
        
        return VariantPerformance::updateOrCreate(
            [
                'variant_id' => $variant->id,
                'date' => $date,
                'period' => $period,
            ],
            [
                'product_id' => $variant->product_id,
                'views' => $views,
                'unique_views' => $uniqueViews,
                'orders' => $orders->count(),
                'quantity_sold' => $quantitySold,
                'conversion_rate' => $conversionRate,
                'revenue' => $revenue,
                'revenue_discounted' => $revenueDiscounted,
                'average_order_value' => $averageOrderValue,
                'stock_turnover' => $stockTurnover,
                'stock_turnover_rate' => $stockTurnoverRate,
                'average_price' => $averagePrice,
                'price_changes' => $priceChanges,
            ]
        );
    }

    /**
     * Get top performing variants for a product.
     *
     * @param  Product  $product
     * @param  Carbon|null  $startDate
     * @param  Carbon|null  $endDate
     * @param  int  $limit
     * @return Collection
     */
    public function getTopPerformingVariants(
        Product $product,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        int $limit = 10
    ) {
        $query = VariantPerformance::where('product_id', $product->id);
        
        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('date', '<=', $endDate);
        }
        
        return $query->select('variant_id', DB::raw('
            SUM(orders) as total_orders,
            SUM(quantity_sold) as total_quantity_sold,
            SUM(revenue) as total_revenue,
            AVG(conversion_rate) as avg_conversion_rate
        '))
        ->groupBy('variant_id')
        ->orderByDesc('total_revenue')
        ->limit($limit)
        ->get();
    }

    /**
     * Get variant performance summary.
     *
     * @param  ProductVariant  $variant
     * @param  Carbon|null  $startDate
     * @param  Carbon|null  $endDate
     * @return array
     */
    public function getPerformanceSummary(
        ProductVariant $variant,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): array {
        $query = VariantPerformance::where('variant_id', $variant->id);
        
        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('date', '<=', $endDate);
        }
        
        $stats = $query->select(DB::raw('
            SUM(views) as total_views,
            SUM(unique_views) as total_unique_views,
            SUM(orders) as total_orders,
            SUM(quantity_sold) as total_quantity_sold,
            SUM(revenue) as total_revenue,
            AVG(conversion_rate) as avg_conversion_rate,
            AVG(average_order_value) as avg_order_value,
            SUM(stock_turnover) as total_stock_turnover,
            AVG(stock_turnover_rate) as avg_stock_turnover_rate
        '))->first();
        
        return [
            'views' => $stats->total_views ?? 0,
            'unique_views' => $stats->total_unique_views ?? 0,
            'orders' => $stats->total_orders ?? 0,
            'quantity_sold' => $stats->total_quantity_sold ?? 0,
            'revenue' => $stats->total_revenue ?? 0,
            'conversion_rate' => $stats->avg_conversion_rate ?? 0,
            'average_order_value' => $stats->avg_order_value ?? 0,
            'stock_turnover' => $stats->total_stock_turnover ?? 0,
            'stock_turnover_rate' => $stats->avg_stock_turnover_rate ?? 0,
        ];
    }
}

