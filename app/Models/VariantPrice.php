<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * Model for variant-specific pricing.
 */
class VariantPrice extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'variant_prices';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'variant_id',
        'currency_id',
        'price',
        'compare_at_price',
        'channel_id',
        'customer_group_id',
        'customer_id',
        'contract_id',
        'min_quantity',
        'max_quantity',
        'starts_at',
        'ends_at',
        'tax_inclusive',
        'priority',
        'pricing_layer',
        'is_manual_override',
        'is_active',
        'scheduled_change_at',
        'scheduled_price',
        'is_flash_deal',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'integer',
        'compare_at_price' => 'integer',
        'min_quantity' => 'integer',
        'max_quantity' => 'integer',
        'tax_inclusive' => 'boolean',
        'priority' => 'integer',
        'pricing_layer' => 'string',
        'is_manual_override' => 'boolean',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /**
     * Variant relationship.
     *
     * @return BelongsTo
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
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
     * Customer relationship (for contract/customer-specific pricing).
     *
     * @return BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(\Lunar\Models\Customer::class);
    }

    /**
     * Contract relationship.
     *
     * @return BelongsTo
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(\App\Models\B2BContract::class, 'contract_id');
    }

    /**
     * Scope for pricing layer.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $layer
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForLayer($query, string $layer)
    {
        return $query->where('pricing_layer', $layer);
    }

    /**
     * Scope for customer.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int|null  $customerId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForCustomer($query, ?int $customerId)
    {
        return $query->where(function ($q) use ($customerId) {
            $q->whereNull('customer_id')
              ->orWhere('customer_id', $customerId);
        });
    }

    /**
     * Check if price is currently active (within time range).
     *
     * @return bool
     */
    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = Carbon::now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }

        return true;
    }

    /**
     * Check if price applies to quantity.
     *
     * @param  int  $quantity
     * @return bool
     */
    public function appliesToQuantity(int $quantity): bool
    {
        if ($quantity < $this->min_quantity) {
            return false;
        }

        if ($this->max_quantity !== null && $quantity > $this->max_quantity) {
            return false;
        }

        return true;
    }

    /**
     * Scope active prices.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')
                  ->orWhere('starts_at', '<=', Carbon::now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')
                  ->orWhere('ends_at', '>=', Carbon::now());
            });
    }

    /**
     * Scope for currency.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $currencyId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForCurrency($query, int $currencyId)
    {
        return $query->where('currency_id', $currencyId);
    }

    /**
     * Scope for channel.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int|null  $channelId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForChannel($query, ?int $channelId)
    {
        return $query->where(function ($q) use ($channelId) {
            $q->whereNull('channel_id')
              ->orWhere('channel_id', $channelId);
        });
    }

    /**
     * Scope for customer group.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int|null  $customerGroupId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForCustomerGroup($query, ?int $customerGroupId)
    {
        return $query->where(function ($q) use ($customerGroupId) {
            $q->whereNull('customer_group_id')
              ->orWhere('customer_group_id', $customerGroupId);
        });
    }

    /**
     * Scope for quantity.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $quantity
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForQuantity($query, int $quantity)
    {
        return $query->where('min_quantity', '<=', $quantity)
            ->where(function ($q) use ($quantity) {
                $q->whereNull('max_quantity')
                  ->orWhere('max_quantity', '>=', $quantity);
            });
    }
}

