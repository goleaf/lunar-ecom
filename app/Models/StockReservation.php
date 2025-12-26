<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * StockReservation model for reserving stock during checkout.
 */
class StockReservation extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'stock_reservations';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_variant_id',
        'warehouse_id',
        'inventory_level_id',
        'quantity',              // Requested quantity
        'reserved_quantity',     // Actually reserved quantity (for partial reservations)
        'status',                // cart, order_confirmed, manual, expired, released
        'reference_type',        // Order, Cart, etc.
        'reference_id',
        'session_id',
        'user_id',
        'lock_token',            // Race-condition safe lock token
        'locked_at',
        'lock_expires_at',
        'expires_at',
        'is_released',
        'released_at',
        'confirmed_at',
        'confirmed_by',
        'override_reason',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'is_released' => 'boolean',
        'expires_at' => 'datetime',
        'released_at' => 'datetime',
        'locked_at' => 'datetime',
        'lock_expires_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-release expired reservations
        static::saving(function ($reservation) {
            if ($reservation->expires_at && $reservation->expires_at->isPast() && !$reservation->is_released) {
                $reservation->is_released = true;
                $reservation->released_at = now();
            }
        });
    }

    /**
     * Product variant relationship.
     *
     * @return BelongsTo
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(\Lunar\Models\ProductVariant::class);
    }

    /**
     * Warehouse relationship.
     *
     * @return BelongsTo
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Inventory level relationship.
     *
     * @return BelongsTo
     */
    public function inventoryLevel(): BelongsTo
    {
        return $this->belongsTo(InventoryLevel::class);
    }

    /**
     * User relationship.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Confirmed by user relationship.
     *
     * @return BelongsTo
     */
    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'confirmed_by');
    }

    /**
     * Reference relationship (polymorphic).
     *
     * @return MorphTo
     */
    public function reference(): MorphTo
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }

    /**
     * Check if reservation is expired.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if reservation is partial (reserved_quantity < quantity).
     *
     * @return bool
     */
    public function isPartial(): bool
    {
        return $this->reserved_quantity > 0 && $this->reserved_quantity < $this->quantity;
    }

    /**
     * Check if reservation is fully reserved.
     *
     * @return bool
     */
    public function isFullyReserved(): bool
    {
        return $this->reserved_quantity >= $this->quantity;
    }

    /**
     * Check if reservation is locked (race-condition safe).
     *
     * @return bool
     */
    public function isLocked(): bool
    {
        return $this->lock_token !== null 
            && $this->lock_expires_at 
            && $this->lock_expires_at->isFuture();
    }

    /**
     * Check if lock is expired.
     *
     * @return bool
     */
    public function isLockExpired(): bool
    {
        return $this->lock_expires_at && $this->lock_expires_at->isPast();
    }

    /**
     * Check if reservation is confirmed (order-confirmed).
     *
     * @return bool
     */
    public function isConfirmed(): bool
    {
        return $this->status === 'order_confirmed' && $this->confirmed_at !== null;
    }

    /**
     * Check if reservation is manual override.
     *
     * @return bool
     */
    public function isManual(): bool
    {
        return $this->status === 'manual';
    }

    /**
     * Get remaining quantity to reserve.
     *
     * @return int
     */
    public function getRemainingQuantityAttribute(): int
    {
        return max(0, $this->quantity - $this->reserved_quantity);
    }

    /**
     * Scope to get active (non-released) reservations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_released', false)
            ->where('expires_at', '>', now())
            ->where('status', '!=', 'released');
    }

    /**
     * Scope to get expired reservations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpired($query)
    {
        return $query->where('is_released', false)
            ->where('expires_at', '<=', now());
    }

    /**
     * Scope to get cart-based reservations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCart($query)
    {
        return $query->where('status', 'cart');
    }

    /**
     * Scope to get order-confirmed reservations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderConfirmed($query)
    {
        return $query->where('status', 'order_confirmed');
    }

    /**
     * Scope to get manual reservations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeManual($query)
    {
        return $query->where('status', 'manual');
    }

    /**
     * Scope to get partial reservations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePartial($query)
    {
        return $query->whereColumn('reserved_quantity', '<', 'quantity')
            ->where('reserved_quantity', '>', 0);
    }
}

