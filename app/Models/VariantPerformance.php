<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * VariantPerformance model for variant analytics.
 */
class VariantPerformance extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'variant_performance';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'variant_id',
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
        'stock_turnover',
        'stock_turnover_rate',
        'average_price',
        'price_changes',
        'returns_count',
        'return_rate',
        'return_revenue',
        'discount_applied_count',
        'discount_amount_total',
        'discount_impact_revenue',
        'popularity_score',
        'popularity_rank',
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
        'stock_turnover' => 'integer',
        'stock_turnover_rate' => 'decimal:4',
        'average_price' => 'integer',
        'price_changes' => 'integer',
        'returns_count' => 'integer',
        'return_rate' => 'decimal:4',
        'return_revenue' => 'integer',
        'discount_applied_count' => 'integer',
        'discount_amount_total' => 'integer',
        'discount_impact_revenue' => 'integer',
        'popularity_score' => 'decimal:2',
        'popularity_rank' => 'integer',
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
     * Variant relationship.
     *
     * @return BelongsTo
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ProductVariant::class, 'variant_id');
    }

    /**
     * Scope by date range.
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
     * Scope by period.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $period
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePeriod($query, string $period)
    {
        return $query->where('period', $period);
    }

    /**
     * Scope ordered by popularity.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderedByPopularity($query)
    {
        return $query->orderByDesc('popularity_score')->orderBy('popularity_rank');
    }
}

