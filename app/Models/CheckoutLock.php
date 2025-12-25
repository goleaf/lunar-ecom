<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Lunar\Models\Cart;

/**
 * CheckoutLock model for managing checkout state and preventing concurrent modifications.
 */
class CheckoutLock extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'checkout_locks';
    }

    /**
     * Checkout states.
     */
    public const STATE_PENDING = 'pending';
    public const STATE_VALIDATING = 'validating';
    public const STATE_RESERVING = 'reserving';
    public const STATE_LOCKING_PRICES = 'locking_prices';
    public const STATE_AUTHORIZING = 'authorizing';
    public const STATE_CREATING_ORDER = 'creating_order';
    public const STATE_CAPTURING = 'capturing';
    public const STATE_COMMITTING = 'committing';
    public const STATE_COMPLETED = 'completed';
    public const STATE_FAILED = 'failed';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'cart_id',
        'session_id',
        'user_id',
        'state',
        'phase',
        'failure_reason',
        'locked_at',
        'expires_at',
        'completed_at',
        'failed_at',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'failure_reason' => 'array',
        'metadata' => 'array',
        'locked_at' => 'datetime',
        'expires_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    /**
     * Cart relationship.
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * User relationship.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Price snapshots relationship.
     */
    public function priceSnapshots(): HasMany
    {
        return $this->hasMany(PriceSnapshot::class);
    }

    /**
     * Stock reservations relationship.
     */
    public function stockReservations(): HasMany
    {
        return $this->hasMany(StockReservation::class, 'reference_id')
            ->where('reference_type', self::class);
    }

    /**
     * Check if lock is active.
     */
    public function isActive(): bool
    {
        return !$this->isCompleted() 
            && !$this->isFailed() 
            && $this->expires_at->isFuture();
    }

    /**
     * Check if lock is completed.
     */
    public function isCompleted(): bool
    {
        return $this->state === self::STATE_COMPLETED;
    }

    /**
     * Check if lock is failed.
     */
    public function isFailed(): bool
    {
        return $this->state === self::STATE_FAILED;
    }

    /**
     * Check if lock is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Mark as completed.
     */
    public function markCompleted(): void
    {
        $this->update([
            'state' => self::STATE_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark as failed.
     */
    public function markFailed(string $phase, array $reason): void
    {
        $this->update([
            'state' => self::STATE_FAILED,
            'phase' => $phase,
            'failure_reason' => $reason,
            'failed_at' => now(),
        ]);
    }

    /**
     * Update state.
     */
    public function updateState(string $state, ?string $phase = null): void
    {
        $this->update([
            'state' => $state,
            'phase' => $phase,
        ]);
    }

    /**
     * Scope for active locks.
     */
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now())
            ->whereNotIn('state', [self::STATE_COMPLETED, self::STATE_FAILED]);
    }

    /**
     * Scope for expired locks.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now())
            ->whereNotIn('state', [self::STATE_COMPLETED, self::STATE_FAILED]);
    }

    /**
     * Get the order associated with this checkout lock.
     */
    public function getOrder(): ?\Lunar\Models\Order
    {
        $orderId = $this->metadata['order_id'] ?? null;
        
        if (!$orderId) {
            return null;
        }

        return \Lunar\Models\Order::find($orderId);
    }

    /**
     * Get cart-level price snapshot.
     */
    public function getCartSnapshot(): ?PriceSnapshot
    {
        return $this->priceSnapshots()
            ->whereNull('cart_line_id')
            ->first();
    }

    /**
     * Get all line-level price snapshots.
     */
    public function getLineSnapshots()
    {
        return $this->priceSnapshots()
            ->whereNotNull('cart_line_id')
            ->get();
    }

    /**
     * Check if checkout can be resumed.
     */
    public function canResume(): bool
    {
        return $this->isActive() 
            && !in_array($this->state, [self::STATE_COMPLETED, self::STATE_FAILED])
            && $this->expires_at->isFuture();
    }
}

