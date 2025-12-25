<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ProductAnalytics model for aggregated product analytics.
 */
class ProductAnalytics extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'product_analytics';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'date',
        'period',
        'views',
        'unique_views',
        'orders',
        'quantity_sold',
        'conversion_rate',
        'revenue',
        'revenue_discounted',
        'average_order_value',
        'cart_additions',
        'cart_removals',
        'abandoned_carts',
        'abandoned_cart_rate',
        'stock_turnover',
        'stock_turnover_rate',
        'stock_level_start',
        'stock_level_end',
        'average_price',
        'min_price',
        'max_price',
        'price_changes',
        'wishlist_additions',
        'reviews_count',
        'average_rating',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'views' => 'integer',
        'unique_views' => 'integer',
        'orders' => 'integer',
        'quantity_sold' => 'integer',
        'conversion_rate' => 'decimal:4',
        'revenue' => 'integer',
        'revenue_discounted' => 'integer',
        'average_order_value' => 'decimal:2',
        'cart_additions' => 'integer',
        'cart_removals' => 'integer',
        'abandoned_carts' => 'integer',
        'abandoned_cart_rate' => 'decimal:4',
        'stock_turnover' => 'integer',
        'stock_turnover_rate' => 'decimal:4',
        'stock_level_start' => 'integer',
        'stock_level_end' => 'integer',
        'average_price' => 'integer',
        'min_price' => 'integer',
        'max_price' => 'integer',
        'price_changes' => 'integer',
        'wishlist_additions' => 'integer',
        'reviews_count' => 'integer',
        'average_rating' => 'decimal:2',
    ];

    /**
     * Product relationship.
     *
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope to get analytics for date range.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \DateTimeInterface|string  $startDate
     * @param  \DateTimeInterface|string  $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope to get analytics by period.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $period
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePeriod($query, string $period)
    {
        return $query->where('period', $period);
    }
}

