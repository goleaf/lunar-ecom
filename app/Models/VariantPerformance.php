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
}

