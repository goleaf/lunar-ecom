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
        'product_variant_id',
        'currency_id',
        'price',
        'compare_at_price',
        'channel_id',
        'customer_group_id',
        'pricing_layer',
        'pricing_rule_id',
        'changed_by',
        'change_reason',
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
        'price' => 'integer',
        'compare_at_price' => 'integer',
        'change_metadata' => 'array',
        'effective_from' => 'datetime',
        'effective_to' => 'datetime',
    ];

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
