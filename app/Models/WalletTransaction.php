<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lunar\Models\Order;

class WalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'type',
        'amount',
        'reason',
        'related_order_id',
        'related_referral_id',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    // Transaction types
    const TYPE_CREDIT = 'credit';
    const TYPE_DEBIT = 'debit';
    const TYPE_HOLD = 'hold';
    const TYPE_RELEASE = 'release';

    // Reasons
    const REASON_REFERRAL_REWARD = 'referral_reward';
    const REASON_REFUND_ADJUSTMENT = 'refund_adjustment';
    const REASON_FRAUD_REVERSAL = 'fraud_reversal';
    const REASON_MANUAL_ADJUSTMENT = 'manual_adjustment';

    /**
     * Get the wallet.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Get the related order.
     */
    public function relatedOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'related_order_id');
    }

    /**
     * Get the related referral attribution.
     */
    public function relatedReferral(): BelongsTo
    {
        return $this->belongsTo(ReferralAttribution::class, 'related_referral_id');
    }
}

