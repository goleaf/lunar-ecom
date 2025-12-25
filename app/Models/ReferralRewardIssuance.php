<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lunar\Models\Order;

class ReferralRewardIssuance extends Model
{
    use HasFactory;

    protected $fillable = [
        'referral_rule_id',
        'referral_attribution_id',
        'referee_user_id',
        'referrer_user_id',
        'order_id',
        'referee_reward_type',
        'referee_reward_value',
        'referrer_reward_type',
        'referrer_reward_value',
        'status',
        'issued_at',
        'reversed_at',
        'reversal_reason',
        'metadata',
    ];

    protected $casts = [
        'referee_reward_value' => 'decimal:2',
        'referrer_reward_value' => 'decimal:2',
        'issued_at' => 'datetime',
        'reversed_at' => 'datetime',
        'metadata' => 'array',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_ISSUED = 'issued';
    const STATUS_REVERSED = 'reversed';

    /**
     * Get the referral rule.
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(ReferralRule::class, 'referral_rule_id');
    }

    /**
     * Get the attribution.
     */
    public function attribution(): BelongsTo
    {
        return $this->belongsTo(ReferralAttribution::class, 'referral_attribution_id');
    }

    /**
     * Get the referee user.
     */
    public function referee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referee_user_id');
    }

    /**
     * Get the referrer user.
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_user_id');
    }

    /**
     * Get the order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Mark as issued.
     */
    public function markAsIssued(): bool
    {
        return $this->update([
            'status' => self::STATUS_ISSUED,
            'issued_at' => now(),
        ]);
    }

    /**
     * Reverse the reward.
     */
    public function reverse(string $reason): bool
    {
        return $this->update([
            'status' => self::STATUS_REVERSED,
            'reversed_at' => now(),
            'reversal_reason' => $reason,
        ]);
    }
}

