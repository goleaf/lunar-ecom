<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PriceElasticity model for tracking price elasticity data.
 */
class PriceElasticity extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'price_elasticity';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'variant_id',
        'old_price',
        'new_price',
        'price_change_percent',
        'price_changed_at',
        'sales_before',
        'sales_after',
        'sales_change_percent',
        'revenue_before',
        'revenue_after',
        'revenue_change_percent',
        'price_elasticity',
        'days_before',
        'days_after',
        'analysis_date',
        'context',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'old_price' => 'integer',
        'new_price' => 'integer',
        'price_change_percent' => 'decimal:4',
        'price_changed_at' => 'datetime',
        'sales_before' => 'integer',
        'sales_after' => 'integer',
        'sales_change_percent' => 'decimal:4',
        'revenue_before' => 'integer',
        'revenue_after' => 'integer',
        'revenue_change_percent' => 'decimal:4',
        'price_elasticity' => 'decimal:4',
        'days_before' => 'integer',
        'days_after' => 'integer',
        'analysis_date' => 'date',
        'context' => 'array',
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

