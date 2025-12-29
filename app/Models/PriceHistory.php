<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model for historical price tracking (legal compliance).
 */
class PriceHistory extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'price_history';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        // Legacy price change tracking (MatrixPricingService + early UI)
        'product_id',
        'product_variant_id',
        'price_matrix_id',
        'old_price',
        'new_price',
        'currency_code',
        'change_type',
        'change_reason',
        'change_notes',
        'context',
        'changed_by',
        'changed_at',

        // Advanced pricing / normalized history (AdvancedPricingService + compliance)
        'currency_id',
        'price',
        'compare_at_price',
        'channel_id',
        'customer_group_id',
        'pricing_layer',
        'pricing_rule_id',
        'change_metadata',
        'effective_from',
        'effective_to',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'old_price' => 'decimal:2',
        'new_price' => 'decimal:2',
        'context' => 'array',
        'changed_at' => 'datetime',
        'price' => 'integer',
        'compare_at_price' => 'integer',
        'change_metadata' => 'array',
        'effective_from' => 'datetime',
        'effective_to' => 'datetime',
    ];

    /**
     * Product relationship.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Variant relationship.
     *
     * @return BelongsTo
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Product variant relationship (alias for legacy controller expectations).
     */
    public function productVariant(): BelongsTo
    {
        return $this->variant();
    }

    /**
     * Price matrix relationship (optional).
     */
    public function priceMatrix(): BelongsTo
    {
        return $this->belongsTo(PriceMatrix::class, 'price_matrix_id');
    }

    /**
     * Currency relationship.
     *
     * @return BelongsTo
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(\Lunar\Models\Currency::class);
    }

    /**
     * Channel relationship.
     *
     * @return BelongsTo
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(\Lunar\Models\Channel::class);
    }

    /**
     * Customer group relationship.
     *
     * @return BelongsTo
     */
    public function customerGroup(): BelongsTo
    {
        return $this->belongsTo(\Lunar\Models\CustomerGroup::class);
    }

    /**
     * Pricing rule relationship.
     *
     * @return BelongsTo
     */
    public function pricingRule(): BelongsTo
    {
        return $this->belongsTo(PricingRule::class);
    }

    /**
     * Changed by user relationship.
     *
     * @return BelongsTo
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'changed_by');
    }

    /**
     * Scope for date range.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \DateTimeInterface|string  $from
     * @param  \DateTimeInterface|string  $to
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('effective_from', [$from, $to]);
    }

    /**
     * Scope for variant.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $variantId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForVariant($query, int $variantId)
    {
        return $query->where('product_variant_id', $variantId);
    }
}
