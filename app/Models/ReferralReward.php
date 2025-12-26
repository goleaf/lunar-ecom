<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use Lunar\Models\Customer;
use Lunar\Models\Discount;
use Lunar\Models\Order;
use Lunar\Models\Currency;

/**
 * Referral Reward Model
 * 
 * Represents rewards issued to referrers or referees.
 */
class ReferralReward extends Model
{
    use HasFactory;

    protected $fillable = [
        'referral_program_id',
        'referral_event_id',
        'user_id',
        'customer_id',
        'reward_type',
        'reward_value',
        'currency_id',
        'status',
        'delivery_method',
        'discount_id',
        'discount_code',
        'issued_at',
        'expires_at',
        'redeemed_at',
        'times_used',
        'max_uses',
        'redeemed_order_id',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'reward_value' => 'decimal:2',
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
        'redeemed_at' => 'datetime',
        'times_used' => 'integer',
        'max_uses' => 'integer',
        'metadata' => 'array',
    ];

    // Reward types
    const TYPE_DISCOUNT_CODE = 'discount_code';
    const TYPE_CREDIT = 'credit';
    const TYPE_PERCENTAGE = 'percentage';
    const TYPE_FIXED_AMOUNT = 'fixed_amount';

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_ISSUED = 'issued';
    const STATUS_REDEEMED = 'redeemed';
    const STATUS_EXPIRED = 'expired';
    const STATUS_CANCELLED = 'cancelled';

    // Delivery methods
    const DELIVERY_AUTOMATIC = 'automatic';
    const DELIVERY_MANUAL = 'manual';
    const DELIVERY_EMAIL = 'email';

    /**
     * Get the referral program.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(ReferralProgram::class, 'referral_program_id');
    }

    /**
     * Get the referral event.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(ReferralEvent::class, 'referral_event_id');
    }

    /**
     * Get the user recipient.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the customer recipient.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the discount (if reward is a discount code).
     */
    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    /**
     * Get the currency.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the order where this reward was redeemed.
     */
    public function redeemedOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'redeemed_order_id');
    }

    /**
     * Check if reward is valid.
     */
    public function isValid(): bool
    {
        if ($this->status !== self::STATUS_ISSUED) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_uses && $this->times_used >= $this->max_uses) {
            return false;
        }

        return true;
    }

    /**
     * Mark reward as issued.
     */
    public function markAsIssued(): bool
    {
        return $this->update([
            'status' => self::STATUS_ISSUED,
            'issued_at' => now(),
        ]);
    }

    /**
     * Mark reward as redeemed.
     */
    public function markAsRedeemed(?Order $order = null): bool
    {
        return $this->update([
            'status' => self::STATUS_REDEEMED,
            'redeemed_at' => now(),
            'redeemed_order_id' => $order?->id,
            'times_used' => $this->times_used + 1,
        ]);
    }

    /**
     * Mark reward as expired.
     */
    public function markAsExpired(): bool
    {
        return $this->update([
            'status' => self::STATUS_EXPIRED,
        ]);
    }

    /**
     * Scope to get valid rewards.
     */
    public function scopeValid($query)
    {
        return $query->where('status', self::STATUS_ISSUED)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->where(function ($q) {
                $q->whereColumn('times_used', '<', 'max_uses')
                    ->orWhereNull('max_uses');
            });
    }

    /**
     * Scope to get rewards for a user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get rewards for a customer.
     */
    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }
}


