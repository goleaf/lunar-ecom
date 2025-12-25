<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductAnalytics;
use App\Models\ProductView;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Service for calculating and managing product analytics.
 */
class ProductAnalyticsService
{
    /**
     * Calculate analytics for a product for a specific date.
     *
     * @param  Product  $product
     * @param  Carbon|string  $date
     * @param  string  $period
     * @return ProductAnalytics
     */
    public function calculateForDate(Product $product, $date, string $period = 'daily'): ProductAnalytics
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);
        
        // Get views
        $views = ProductView::where('product_id', $product->id)
            ->whereDate('viewed_at', $date)
            ->count();
        
        $uniqueViews = ProductView::where('product_id', $product->id)
            ->whereDate('viewed_at', $date)
            ->distinct('session_id')
            ->count('session_id');
        
        // Get orders
        $orders = \Lunar\Models\OrderLine::whereHas('purchasable.product', function ($q) use ($product) {
            $q->where('id', $product->id);
        })
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
        
        // Get cart metrics
        $cartAdditions = $this->getCartAdditions($product, $date);
        $cartRemovals = $this->getCartRemovals($product, $date);
        $abandonedCarts = $this->getAbandonedCarts($product, $date);
        $abandonedCartRate = $cartAdditions > 0 ? ($abandonedCarts / $cartAdditions) : 0;
        
        // Get stock metrics
        $stockMetrics = $this->getStockMetrics($product, $date);
        
        // Get price metrics
        $priceMetrics = $this->getPriceMetrics($product, $date);
        
        // Get engagement metrics
        $engagementMetrics = $this->getEngagementMetrics($product, $date);
        
        return ProductAnalytics::updateOrCreate(
            [
                'product_id' => $product->id,
                'date' => $date,
                'period' => $period,
            ],
            [
                'views' => $views,
                'unique_views' => $uniqueViews,
                'orders' => $orders->count(),
                'quantity_sold' => $quantitySold,
                'conversion_rate' => $conversionRate,
                'revenue' => $revenue,
                'revenue_discounted' => $revenueDiscounted,
                'average_order_value' => $averageOrderValue,
                'cart_additions' => $cartAdditions,
                'cart_removals' => $cartRemovals,
                'abandoned_carts' => $abandonedCarts,
                'abandoned_cart_rate' => $abandonedCartRate,
                'stock_turnover' => $stockMetrics['turnover'],
                'stock_turnover_rate' => $stockMetrics['turnover_rate'],
                'stock_level_start' => $stockMetrics['start'],
                'stock_level_end' => $stockMetrics['end'],
                'average_price' => $priceMetrics['average'],
                'min_price' => $priceMetrics['min'],
                'max_price' => $priceMetrics['max'],
                'price_changes' => $priceMetrics['changes'],
                'wishlist_additions' => $engagementMetrics['wishlist'],
                'reviews_count' => $engagementMetrics['reviews'],
                'average_rating' => $engagementMetrics['rating'],
            ]
        );
    }

    /**
     * Get conversion rate for product.
     *
     * @param  Product  $product
     * @param  Carbon|null  $startDate
     * @param  Carbon|null  $endDate
     * @return float
     */
    public function getConversionRate(Product $product, ?Carbon $startDate = null, ?Carbon $endDate = null): float
    {
        $views = ProductView::where('product_id', $product->id);
        $orders = \Lunar\Models\OrderLine::whereHas('purchasable.product', function ($q) use ($product) {
            $q->where('id', $product->id);
        })->whereHas('order', function ($q) {
            $q->whereIn('status', ['placed', 'completed']);
        });
        
        if ($startDate) {
            $views->where('viewed_at', '>=', $startDate);
            $orders->whereHas('order', function ($q) use ($startDate) {
                $q->where('placed_at', '>=', $startDate);
            });
        }
        
        if ($endDate) {
            $views->where('viewed_at', '<=', $endDate);
            $orders->whereHas('order', function ($q) use ($endDate) {
                $q->where('placed_at', '<=', $endDate);
            });
        }
        
        $viewCount = $views->count();
        $orderCount = $orders->distinct('order_id')->count('order_id');
        
        return $viewCount > 0 ? ($orderCount / $viewCount) : 0;
    }

    /**
     * Get revenue for product.
     *
     * @param  Product  $product
     * @param  Carbon|null  $startDate
     * @param  Carbon|null  $endDate
     * @return int
     */
    public function getRevenue(Product $product, ?Carbon $startDate = null, ?Carbon $endDate = null): int
    {
        $orders = \Lunar\Models\OrderLine::whereHas('purchasable.product', function ($q) use ($product) {
            $q->where('id', $product->id);
        })
        ->whereHas('order', function ($q) use ($startDate, $endDate) {
            $q->whereIn('status', ['placed', 'completed']);
            if ($startDate) {
                $q->where('placed_at', '>=', $startDate);
            }
            if ($endDate) {
                $q->where('placed_at', '<=', $endDate);
            }
        })
        ->get();
        
        return $orders->sum(function ($orderLine) {
            return $orderLine->sub_total->value ?? 0;
        });
    }

    /**
     * Get cart additions count.
     *
     * @param  Product  $product
     * @param  Carbon  $date
     * @return int
     */
    protected function getCartAdditions(Product $product, Carbon $date): int
    {
        // This would track cart additions - implement based on your cart tracking
        return 0;
    }

    /**
     * Get cart removals count.
     *
     * @param  Product  $product
     * @param  Carbon  $date
     * @return int
     */
    protected function getCartRemovals(Product $product, Carbon $date): int
    {
        // This would track cart removals - implement based on your cart tracking
        return 0;
    }

    /**
     * Get abandoned carts count.
     *
     * @param  Product  $product
     * @param  Carbon  $date
     * @return int
     */
    protected function getAbandonedCarts(Product $product, Carbon $date): int
    {
        return \App\Models\AbandonedCart::where('product_id', $product->id)
            ->whereDate('abandoned_at', $date)
            ->where('status', 'abandoned')
            ->count();
    }

    /**
     * Get stock metrics.
     *
     * @param  Product  $product
     * @param  Carbon  $date
     * @return array
     */
    protected function getStockMetrics(Product $product, Carbon $date): array
    {
        $startStock = $product->variants->sum('stock');
        $endStock = $startStock; // Would need to track historical stock
        
        $sold = \Lunar\Models\OrderLine::whereHas('purchasable.product', function ($q) use ($product) {
            $q->where('id', $product->id);
        })
        ->whereHas('order', function ($q) use ($date) {
            $q->whereDate('placed_at', $date)
              ->whereIn('status', ['placed', 'completed']);
        })
        ->sum('quantity');
        
        $turnover = $sold;
        $averageStock = ($startStock + $endStock) / 2;
        $turnoverRate = $averageStock > 0 ? ($turnover / $averageStock) : 0;
        
        return [
            'start' => $startStock,
            'end' => $endStock,
            'turnover' => $turnover,
            'turnover_rate' => $turnoverRate,
        ];
    }

    /**
     * Get price metrics.
     *
     * @param  Product  $product
     * @param  Carbon  $date
     * @return array
     */
    protected function getPriceMetrics(Product $product, Carbon $date): array
    {
        $prices = $product->variants->flatMap(function ($variant) {
            return $variant->prices->map(function ($price) {
                return $price->price->value ?? 0;
            });
        })->filter()->values();
        
        return [
            'average' => $prices->avg() ?? 0,
            'min' => $prices->min() ?? 0,
            'max' => $prices->max() ?? 0,
            'changes' => 0, // Would need to track price change history
        ];
    }

    /**
     * Get engagement metrics.
     *
     * @param  Product  $product
     * @param  Carbon  $date
     * @return array
     */
    protected function getEngagementMetrics(Product $product, Carbon $date): array
    {
        $reviews = $product->reviews()
            ->whereDate('created_at', $date)
            ->get();
        
        return [
            'wishlist' => 0, // Would need wishlist tracking
            'reviews' => $reviews->count(),
            'rating' => $reviews->avg('rating') ?? 0,
        ];
    }

    /**
     * Aggregate analytics for date range.
     *
     * @param  Product  $product
     * @param  Carbon  $startDate
     * @param  Carbon  $endDate
     * @param  string  $period
     * @return Collection
     */
    public function aggregateForDateRange(
        Product $product,
        Carbon $startDate,
        Carbon $endDate,
        string $period = 'daily'
    ) {
        $current = $startDate->copy();
        $analytics = collect();
        
        while ($current->lte($endDate)) {
            $analytics->push($this->calculateForDate($product, $current, $period));
            $current->addDay();
        }
        
        return $analytics;
    }
}

