<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lunar\Models\Cart;
use Lunar\Models\CartLine;

/**
 * PriceSnapshot model for storing frozen prices, discounts, tax, and currency during checkout.
 */
class PriceSnapshot extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'price_snapshots';
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'checkout_lock_id',
        'cart_id',
        'cart_line_id',
        'unit_price',
        'sub_total',
        'discount_total',
        'tax_total',
        'total',
        'discount_breakdown',
        'applied_discounts',
        'tax_breakdown',
        'tax_rate',
        'currency_code',
        'compare_currency_code',
        'exchange_rate',
        'coupon_code',
        'promotion_details',
        'snapshot_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'discount_breakdown' => 'array',
        'applied_discounts' => 'array',
        'tax_breakdown' => 'array',
        'tax_rate' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'promotion_details' => 'array',
        'snapshot_at' => 'datetime',
        'unit_price' => 'integer',
        'sub_total' => 'integer',
        'discount_total' => 'integer',
        'tax_total' => 'integer',
        'total' => 'integer',
    ];

    /**
     * Checkout lock relationship.
     */
    public function checkoutLock(): BelongsTo
    {
        return $this->belongsTo(CheckoutLock::class);
    }

    /**
     * Cart relationship.
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Cart line relationship.
     */
    public function cartLine(): BelongsTo
    {
        return $this->belongsTo(CartLine::class);
    }
}

