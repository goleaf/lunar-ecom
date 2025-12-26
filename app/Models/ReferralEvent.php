<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\User;
use Lunar\Models\Customer;
use Lunar\Models\Order;
use Lunar\Models\Currency;

/**
 * Referral Event Model
 * 
 * Tracks referral events (signup, purchase, etc.) and their processing status.
 */
class ReferralEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'referral_program_id',
        'referral_code_id',
        'event_type',
        'status',
        'referrer_id',
        'referrer_customer_id',
        'referee_id',
        'referee_customer_id',
        'order_id',
        'order_reference',
        'reward_config',
        'reward_value',
        'reward_currency_id',
        'reward_type',
        'processed_at',
        'processing_notes',
        'error_message',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'reward_config' => 'array',
        'reward_value' => 'decimal:2',
        'processed_at' => 'datetime',
        'metadata' => 'array',
    ];

    // Event types
    const EVENT_SIGNUP = 'signup';
    const EVENT_FIRST_PURCHASE = 'first_purchase';
    const EVENT_REPEAT_PURCHASE = 'repeat_purchase';
    const EVENT_CUSTOM = 'custom';

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSED = 'processed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the referral program.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(ReferralProgram::class, 'referral_program_id');
    }

    /**
     * Get the referral code.
     */
    public function referralCode(): BelongsTo
    {
        return $this->belongsTo(ReferralCode::class, 'referral_code_id');
    }

    /**
     * Get the referrer user.
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    /**
     * Get the referrer customer.
     */
    public function referrerCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'referrer_customer_id');
    }

    /**
     * Get the referee user.
     */
    public function referee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referee_id');
    }

    /**
     * Get the referee customer.
     */
    public function refereeCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'referee_customer_id');
    }

    /**
     * Get the related order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Get the reward currency.
     */
    public function rewardCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'reward_currency_id');
    }

    /**
     * Get the reward issued for this event.
     */
    public function reward(): HasOne
    {
        return $this->hasOne(ReferralReward::class, 'referral_event_id');
    }

    /**
     * Mark event as processed.
     */
    public function markAsProcessed(?string $notes = null): bool
    {
        return $this->update([
            'status' => self::STATUS_PROCESSED,
            'processed_at' => now(),
            'processing_notes' => $notes,
        ]);
    }

    /**
     * Mark event as failed.
     */
    public function markAsFailed(string $errorMessage): bool
    {
        return $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Scope to get pending events.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get processed events.
     */
    public function scopeProcessed($query)
    {
        return $query->where('status', self::STATUS_PROCESSED);
    }

    /**
     * Scope to get events by type.
     */
    public function scopeByType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }
}


