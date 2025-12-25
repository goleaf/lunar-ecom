<?php

namespace App\Services;

use App\Models\ReferralAttribution;
use App\Models\ReferralRule;
use App\Models\ReferralProgram;
use App\Models\User;
use App\Models\Coupon;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\ReferralRewardIssuance;
use Lunar\Models\Order;
use Lunar\Models\Discount;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Referral Reward Service
 * 
 * Handles issuance of rewards to both referees and referrers
 * based on configured rules and fraud prevention checks.
 */
class ReferralRewardService
{
    protected ReferralAttributionService $attributionService;
    protected ReferralFraudService $fraudService;

    public function __construct(
        ReferralAttributionService $attributionService,
        ReferralFraudService $fraudService
    ) {
        $this->attributionService = $attributionService;
        $this->fraudService = $fraudService;
    }

    /**
     * Process reward for a trigger event.
     * 
     * @param User $referee The user who triggered the event
     * @param string $triggerEvent The event type (signup, first_order_paid, etc.)
     * @param Order|null $order The order if applicable
     */
    public function processReward(User $referee, string $triggerEvent, ?Order $order = null): void
    {
        // Get confirmed attribution for this user
        $attribution = ReferralAttribution::where('referee_user_id', $referee->id)
            ->where('status', ReferralAttribution::STATUS_CONFIRMED)
            ->first();

        if (!$attribution) {
            return; // No attribution, no reward
        }

        $referrer = $attribution->referrer;
        $program = $attribution->program;

        if (!$referrer || !$program) {
            return;
        }

        // Get applicable rules for this trigger
        $rules = ReferralRule::where('referral_program_id', $program->id)
            ->where('trigger_event', $triggerEvent)
            ->where('is_active', true)
            ->orderBy('priority', 'desc')
            ->get();

        foreach ($rules as $rule) {
            // Check if rule is applicable
            if (!$this->isRuleApplicable($rule, $referee, $referrer, $order)) {
                continue;
            }

            // Check fraud/abuse prevention
            if (!$this->fraudService->canIssueReward($rule, $referee, $referrer, $order)) {
                continue;
            }

            // Issue rewards
            $this->issueRewards($rule, $referee, $referrer, $order, $attribution);
        }
    }

    /**
     * Check if a rule is applicable.
     */
    protected function isRuleApplicable(ReferralRule $rule, User $referee, User $referrer, ?Order $order): bool
    {
        // Check minimum order total
        if ($rule->min_order_total && $order) {
            $orderTotal = $order->total->value;
            if ($orderTotal < $rule->min_order_total) {
                return false;
            }
        }

        // Check eligible products/categories/collections
        if ($order && ($rule->eligible_product_ids || $rule->eligible_category_ids || $rule->eligible_collection_ids)) {
            if (!$this->orderMatchesEligibility($order, $rule)) {
                return false;
            }
        }

        // Check redemption limits
        if (!$this->checkRedemptionLimits($rule, $referee, $referrer)) {
            return false;
        }

        // Check cooldown
        if ($rule->cooldown_days && !$this->checkCooldown($rule, $referrer)) {
            return false;
        }

        // Check validation window
        if ($rule->validation_window_days) {
            $attribution = ReferralAttribution::where('referee_user_id', $referee->id)
                ->where('referrer_user_id', $referrer->id)
                ->where('program_id', $rule->referral_program_id)
                ->first();

            if ($attribution && $attribution->attributed_at->addDays($rule->validation_window_days)->isPast()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if order matches eligibility criteria.
     */
    protected function orderMatchesEligibility(Order $order, ReferralRule $rule): bool
    {
        $orderProductIds = $order->lines->pluck('purchasable.product_id')->filter();
        $orderCategoryIds = $order->lines->pluck('purchasable.product.default_relation_id')->filter();

        // Check products
        if ($rule->eligible_product_ids) {
            $eligibleIds = is_array($rule->eligible_product_ids) 
                ? $rule->eligible_product_ids 
                : json_decode($rule->eligible_product_ids, true);
            
            if (!empty($eligibleIds) && !$orderProductIds->intersect($eligibleIds)->isNotEmpty()) {
                return false;
            }
        }

        // Check categories
        if ($rule->eligible_category_ids) {
            $eligibleIds = is_array($rule->eligible_category_ids) 
                ? $rule->eligible_category_ids 
                : json_decode($rule->eligible_category_ids, true);
            
            if (!empty($eligibleIds) && !$orderCategoryIds->intersect($eligibleIds)->isNotEmpty()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check redemption limits.
     */
    protected function checkRedemptionLimits(ReferralRule $rule, User $referee, User $referrer): bool
    {
        // Check total redemptions
        if ($rule->max_redemptions_total) {
            $totalRedemptions = ReferralRewardIssuance::where('referral_rule_id', $rule->id)
                ->where('status', ReferralRewardIssuance::STATUS_ISSUED)
                ->count();

            if ($totalRedemptions >= $rule->max_redemptions_total) {
                return false;
            }
        }

        // Check per referrer limit
        if ($rule->max_redemptions_per_referrer) {
            $referrerRedemptions = ReferralRewardIssuance::where('referral_rule_id', $rule->id)
                ->where('referrer_user_id', $referrer->id)
                ->where('status', ReferralRewardIssuance::STATUS_ISSUED)
                ->count();

            if ($referrerRedemptions >= $rule->max_redemptions_per_referrer) {
                return false;
            }
        }

        // Check per referee limit
        if ($rule->max_redemptions_per_referee) {
            $refereeRedemptions = ReferralRewardIssuance::where('referral_rule_id', $rule->id)
                ->where('referee_user_id', $referee->id)
                ->where('status', ReferralRewardIssuance::STATUS_ISSUED)
                ->count();

            if ($refereeRedemptions >= $rule->max_redemptions_per_referee) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check cooldown period.
     */
    protected function checkCooldown(ReferralRule $rule, User $referrer): bool
    {
        $lastReward = ReferralRewardIssuance::where('referral_rule_id', $rule->id)
            ->where('referrer_user_id', $referrer->id)
            ->where('status', ReferralRewardIssuance::STATUS_ISSUED)
            ->orderBy('issued_at', 'desc')
            ->first();

        if ($lastReward && $lastReward->issued_at->addDays($rule->cooldown_days)->isFuture()) {
            return false;
        }

        return true;
    }

    /**
     * Issue rewards to both referee and referrer.
     */
    protected function issueRewards(
        ReferralRule $rule,
        User $referee,
        User $referrer,
        ?Order $order,
        ReferralAttribution $attribution
    ): void {
        DB::transaction(function () use ($rule, $referee, $referrer, $order, $attribution) {
            // Issue referee reward
            if ($rule->referee_reward_type && $rule->referee_reward_value) {
                $this->issueRefereeReward($rule, $referee, $order);
            }

            // Issue referrer reward
            if ($rule->referrer_reward_type && $rule->referrer_reward_value) {
                $this->issueReferrerReward($rule, $referrer, $referee, $order);
            }

            // Record issuance
            ReferralRewardIssuance::create([
                'referral_rule_id' => $rule->id,
                'referral_attribution_id' => $attribution->id,
                'referee_user_id' => $referee->id,
                'referrer_user_id' => $referrer->id,
                'order_id' => $order?->id,
                'referee_reward_type' => $rule->referee_reward_type,
                'referee_reward_value' => $rule->referee_reward_value,
                'referrer_reward_type' => $rule->referrer_reward_type,
                'referrer_reward_value' => $rule->referrer_reward_value,
                'status' => ReferralRewardIssuance::STATUS_ISSUED,
                'issued_at' => now(),
            ]);
        });
    }

    /**
     * Issue reward to referee.
     */
    protected function issueRefereeReward(ReferralRule $rule, User $referee, ?Order $order): void
    {
        switch ($rule->referee_reward_type) {
            case ReferralRule::REWARD_COUPON:
                $this->createRefereeCoupon($rule, $referee);
                break;

            case ReferralRule::REWARD_PERCENTAGE_DISCOUNT:
                $this->createRefereeDiscount($rule, $referee, 'percentage');
                break;

            case ReferralRule::REWARD_FIXED_DISCOUNT:
                $this->createRefereeDiscount($rule, $referee, 'fixed');
                break;

            case ReferralRule::REWARD_FREE_SHIPPING:
                $this->createRefereeFreeShipping($rule, $referee);
                break;

            case ReferralRule::REWARD_STORE_CREDIT:
                $this->addStoreCredit($referee, $rule->referee_reward_value, 'referee_reward');
                break;
        }
    }

    /**
     * Issue reward to referrer.
     */
    protected function issueReferrerReward(ReferralRule $rule, User $referrer, User $referee, ?Order $order): void
    {
        // Check for tiered rewards
        $tieredValue = $this->getTieredRewardValue($rule, $referrer);
        $rewardValue = $tieredValue ?? $rule->referrer_reward_value;

        switch ($rule->referrer_reward_type) {
            case ReferralRule::REWARD_COUPON:
                $this->createReferrerCoupon($rule, $referrer, $rewardValue);
                break;

            case ReferralRule::REWARD_STORE_CREDIT:
                $this->addStoreCredit($referrer, $rewardValue, 'referrer_reward', $order);
                break;

            case ReferralRule::REWARD_PERCENTAGE_DISCOUNT_NEXT_ORDER:
                $this->createReferrerDiscount($rule, $referrer, 'percentage', $rewardValue);
                break;

            case ReferralRule::REWARD_FIXED_AMOUNT:
                $this->addStoreCredit($referrer, $rewardValue, 'referrer_reward', $order);
                break;
        }
    }

    /**
     * Get tiered reward value based on referral count.
     */
    protected function getTieredRewardValue(ReferralRule $rule, User $referrer): ?float
    {
        // Check if rule has tiered rewards configured
        $tieredRewards = $rule->tiered_rewards ?? null;
        if (!$tieredRewards) {
            return null;
        }

        $tieredRewards = is_array($tieredRewards) ? $tieredRewards : json_decode($tieredRewards, true);

        if (empty($tieredRewards)) {
            return null;
        }

        // Count successful referrals for this referrer
        $referralCount = ReferralRewardIssuance::where('referrer_user_id', $referrer->id)
            ->where('referral_rule_id', $rule->id)
            ->where('status', ReferralRewardIssuance::STATUS_ISSUED)
            ->count();

        // Find matching tier (sorted by threshold descending)
        krsort($tieredRewards);
        foreach ($tieredRewards as $threshold => $value) {
            if ($referralCount >= (int)$threshold) {
                return (float)$value;
            }
        }

        return null;
    }

    /**
     * Create coupon for referee.
     */
    protected function createRefereeCoupon(ReferralRule $rule, User $referee): Coupon
    {
        $code = $this->generateCouponCode($referee);
        
        return Coupon::create([
            'code' => $code,
            'type' => Coupon::TYPE_FIXED,
            'value' => $rule->referee_reward_value,
            'start_at' => now(),
            'end_at' => now()->addDays($rule->coupon_validity_days ?? 30),
            'usage_limit' => 1,
            'per_user_limit' => 1,
            'eligible_product_ids' => $rule->eligible_product_ids,
            'eligible_category_ids' => $rule->eligible_category_ids,
            'stack_policy' => $rule->stacking_mode === ReferralRule::STACKING_STACKABLE ? 'stackable' : 'exclusive',
            'created_by_rule_id' => $rule->id,
            'assigned_to_user_id' => $referee->id,
        ]);
    }

    /**
     * Create discount for referee.
     */
    protected function createRefereeDiscount(ReferralRule $rule, User $referee, string $type): Discount
    {
        $discount = Discount::create([
            'name' => "Referral Reward - {$referee->email}",
            'handle' => 'referee-reward-' . $referee->id . '-' . Str::random(8),
            'type' => $type === 'percentage' ? 'percentage' : 'fixed',
            'starts_at' => now(),
            'ends_at' => now()->addDays($rule->coupon_validity_days ?? 30),
        ]);

        // Set discount value
        $discount->data = [
            'value' => $rule->referee_reward_value,
            'min_basket' => $rule->min_order_total ?? 0,
        ];

        $discount->save();

        // Assign to user (if Lunar supports user-specific discounts)
        // Otherwise, create a coupon code

        return $discount;
    }

    /**
     * Create free shipping discount for referee.
     */
    protected function createRefereeFreeShipping(ReferralRule $rule, User $referee): Discount
    {
        $discount = Discount::create([
            'name' => "Free Shipping - {$referee->email}",
            'handle' => 'referee-freeshipping-' . $referee->id . '-' . Str::random(8),
            'type' => 'shipping',
            'starts_at' => now(),
            'ends_at' => now()->addDays($rule->coupon_validity_days ?? 30),
        ]);

        $discount->save();

        return $discount;
    }

    /**
     * Create coupon for referrer.
     */
    protected function createReferrerCoupon(ReferralRule $rule, User $referrer, float $value): Coupon
    {
        $code = $this->generateCouponCode($referrer, 'REF');
        
        return Coupon::create([
            'code' => $code,
            'type' => Coupon::TYPE_FIXED,
            'value' => $value,
            'start' => now(),
            'end' => now()->addDays($rule->coupon_validity_days ?? 60),
            'usage_limit' => 1,
            'per_user_limit' => 1,
            'stack_policy' => 'exclusive',
            'created_by_rule_id' => $rule->id,
            'assigned_to_user_id' => $referrer->id,
        ]);
    }

    /**
     * Create discount for referrer next order.
     */
    protected function createReferrerDiscount(ReferralRule $rule, User $referrer, string $type, float $value): Discount
    {
        $discount = Discount::create([
            'name' => "Referrer Reward - {$referrer->email}",
            'handle' => 'referrer-reward-' . $referrer->id . '-' . Str::random(8),
            'type' => $type === 'percentage' ? 'percentage' : 'fixed',
            'starts_at' => now(),
            'ends_at' => now()->addDays($rule->coupon_validity_days ?? 60),
        ]);

        $discount->data = [
            'value' => $value,
        ];

        $discount->save();

        return $discount;
    }

    /**
     * Add store credit to user wallet.
     */
    protected function addStoreCredit(User $user, float $amount, string $reason, ?Order $order = null): void
    {
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0]
        );

        $wallet->increment('balance', $amount);

        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'type' => WalletTransaction::TYPE_CREDIT,
            'amount' => $amount,
            'reason' => $reason,
            'related_order_id' => $order?->id,
            'created_at' => now(),
        ]);
    }

    /**
     * Generate unique coupon code.
     */
    protected function generateCouponCode(User $user, string $prefix = 'REF'): string
    {
        do {
            $code = strtoupper($prefix . substr($user->referral_code, 0, 3) . Str::random(6));
        } while (Coupon::where('code', $code)->exists());

        return $code;
    }
}
