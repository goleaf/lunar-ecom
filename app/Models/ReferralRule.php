<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReferralRule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'referral_program_id',
        'trigger_event',
        'nth_order',
        'referee_reward_type',
        'referee_reward_value',
        'referrer_reward_type',
        'referrer_reward_value',
        'min_order_total',
        'eligible_product_ids',
        'eligible_category_ids',
        'eligible_collection_ids',
        'max_redemptions_total',
        'max_redemptions_per_referrer',
        'max_redemptions_per_referee',
        'cooldown_days',
        'stacking_mode',
        'priority',
        'validation_window_days',
        'fraud_policy_id',
        'tiered_rewards',
        'coupon_validity_days',
        'is_active',
    ];

    protected $casts = [
        'nth_order' => 'integer',
        'referee_reward_value' => 'decimal:2',
        'referrer_reward_value' => 'decimal:2',
        'min_order_total' => 'decimal:2',
        'eligible_product_ids' => 'array',
        'eligible_category_ids' => 'array',
        'eligible_collection_ids' => 'array',
        'max_redemptions_total' => 'integer',
        'max_redemptions_per_referrer' => 'integer',
        'max_redemptions_per_referee' => 'integer',
        'cooldown_days' => 'integer',
        'priority' => 'integer',
        'validation_window_days' => 'integer',
        'tiered_rewards' => 'array',
        'coupon_validity_days' => 'integer',
        'is_active' => 'boolean',
    ];

    // Trigger events
    const TRIGGER_SIGNUP = 'signup';
    const TRIGGER_FIRST_ORDER_PAID = 'first_order_paid';
    const TRIGGER_NTH_ORDER_PAID = 'nth_order_paid';
    const TRIGGER_SUBSCRIPTION_STARTED = 'subscription_started';

    // Reward types
    const REWARD_COUPON = 'coupon';
    const REWARD_PERCENTAGE_DISCOUNT = 'percentage_discount';
    const REWARD_FIXED_DISCOUNT = 'fixed_discount';
    const REWARD_FREE_SHIPPING = 'free_shipping';
    const REWARD_STORE_CREDIT = 'store_credit';
    const REWARD_PERCENTAGE_DISCOUNT_NEXT_ORDER = 'percentage_discount_next_order';
    const REWARD_FIXED_AMOUNT = 'fixed_amount';

    // Stacking modes
    const STACKING_EXCLUSIVE = 'exclusive';
    const STACKING_STACKABLE = 'stackable';
    const STACKING_BEST_OF = 'best_of';

    /**
     * Get the referral program.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(ReferralProgram::class, 'referral_program_id');
    }

    /**
     * Get the fraud policy.
     */
    public function fraudPolicy(): BelongsTo
    {
        return $this->belongsTo(FraudPolicy::class);
    }

    /**
     * Get coupons created by this rule.
     */
    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class, 'created_by_rule_id');
    }
}

