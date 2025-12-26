<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lunar\Models\Order;
use Lunar\Models\Cart;

class ReferralDiscountApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'cart_id',
        'user_id',
        'referral_attribution_id',
        'referral_program_id',
        'applied_rule_ids',
        'applied_discounts',
        'total_discount_amount',
        'stage',
        'audit_snapshot',
    ];

    protected $casts = [
        'applied_rule_ids' => 'array',
        'applied_discounts' => 'array',
        'audit_snapshot' => 'array',
        'total_discount_amount' => 'decimal:2',
    ];

    /**
     * Get the order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the cart.
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Get the user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the referral attribution.
     */
    public function attribution(): BelongsTo
    {
        return $this->belongsTo(ReferralAttribution::class, 'referral_attribution_id');
    }

    /**
     * Get the referral program.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(ReferralProgram::class, 'referral_program_id');
    }
}


