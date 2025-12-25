<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use Lunar\Models\Customer;
use Lunar\Models\Order;

/**
 * Referral Tracking Model
 * 
 * Tracks individual referral link clicks, signups, and conversions.
 */
class ReferralTracking extends Model
{
    use HasFactory;

    protected $fillable = [
        'referral_code_id',
        'session_id',
        'ip_address',
        'user_agent',
        'referrer_url',
        'landing_page',
        'user_id',
        'customer_id',
        'event_type',
        'event_data',
        'converted',
        'converted_at',
        'conversion_order_id',
        'metadata',
    ];

    protected $casts = [
        'event_data' => 'array',
        'converted' => 'boolean',
        'converted_at' => 'datetime',
        'metadata' => 'array',
    ];

    // Event types
    const EVENT_CLICK = 'click';
    const EVENT_SIGNUP = 'signup';
    const EVENT_PURCHASE = 'purchase';
    const EVENT_CUSTOM = 'custom';

    /**
     * Get the referral code.
     */
    public function referralCode(): BelongsTo
    {
        return $this->belongsTo(ReferralCode::class, 'referral_code_id');
    }

    /**
     * Get the user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the customer.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the conversion order.
     */
    public function conversionOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'conversion_order_id');
    }

    /**
     * Mark as converted.
     */
    public function markAsConverted(?Order $order = null): bool
    {
        return $this->update([
            'converted' => true,
            'converted_at' => now(),
            'conversion_order_id' => $order?->id,
        ]);
    }
}

