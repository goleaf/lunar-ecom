<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lunar\Models\Currency;
use Lunar\Models\Channel;
use Lunar\Models\ProductVariant;

/**
 * MAP (Minimum Advertised Price) model.
 * 
 * Enforces minimum advertised prices for product variants
 * by currency, channel, and time period.
 */
class MapPrice extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'map_prices';
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'product_variant_id',
        'currency_id',
        'channel_id',
        'min_price',
        'enforcement_level',
        'valid_from',
        'valid_to',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'min_price' => 'integer',
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
    ];

    /**
     * Product variant relationship.
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Currency relationship.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Channel relationship.
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    /**
     * Scope: Active MAP prices (within validity period).
     */
    public function scopeActive($query)
    {
        return $query->where(function($q) {
            $q->whereNull('valid_from')
                ->orWhere('valid_from', '<=', now());
        })
        ->where(function($q) {
            $q->whereNull('valid_to')
                ->orWhere('valid_to', '>=', now());
        });
    }

    /**
     * Scope: Strict enforcement level.
     */
    public function scopeStrict($query)
    {
        return $query->where('enforcement_level', 'strict');
    }

    /**
     * Scope: Warning enforcement level.
     */
    public function scopeWarning($query)
    {
        return $query->where('enforcement_level', 'warning');
    }
}

