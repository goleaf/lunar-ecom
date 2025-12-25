<?php

namespace App\Services;

use App\Models\ReferralEvent;
use App\Models\ReferralReward;
use App\Models\ReferralProgram;
use App\Services\DiscountService;
use Lunar\Models\Discount;
use Lunar\Models\Currency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Referral Reward Service
 * 
 * Handles issuing and managing referral rewards.
 */
class ReferralRewardService
{
    protected DiscountService $discountService;

    public function __construct(DiscountService $discountService)
    {
        $this->discountService = $discountService;
    }

    /**
     * Issue a reward based on event and configuration.
     */
    public function issueReward(ReferralEvent $event, array $rewardConfig): ReferralReward
    {
        $program = $event->program;
        $rewardType = $rewardConfig['type'] ?? 'discount';
        $rewardValue = $rewardConfig['value'] ?? 0;
        $currencyId = $rewardConfig['currency_id'] ?? null;

        // Determine recipient
        $userId = $event->referrer_id;
        $customerId = $event->referrer_customer_id;

        // Create reward record
        $reward = ReferralReward::create([
            'referral_program_id' => $program->id,
            'referral_event_id' => $event->id,
            'user_id' => $userId,
            'customer_id' => $customerId,
            'reward_type' => $this->mapRewardType($rewardType),
            'reward_value' => $rewardValue,
            'currency_id' => $currencyId,
            'status' => ReferralReward::STATUS_PENDING,
            'delivery_method' => $rewardConfig['delivery_method'] ?? ReferralReward::DELIVERY_AUTOMATIC,
        ]);

        // Process based on reward type
        switch ($rewardType) {
            case 'discount':
            case 'discount_code':
                $this->issueDiscountReward($reward, $rewardConfig, $program);
                break;
            
            case 'credit':
                $this->issueCreditReward($reward, $rewardConfig);
                break;
            
            case 'percentage':
            case 'fixed_amount':
                // These are typically discount codes
                $this->issueDiscountReward($reward, $rewardConfig, $program);
                break;
        }

        // Set expiration
        if ($program->reward_validity_days) {
            $reward->expires_at = now()->addDays($program->reward_validity_days);
            $reward->save();
        }

        // Mark as issued
        $reward->markAsIssued();
        $event->markAsProcessed();

        // Update program stats
        $program->increment('total_rewards_issued');
        $program->increment('total_reward_value', $rewardValue);

        return $reward;
    }

    /**
     * Issue a discount code reward.
     */
    protected function issueDiscountReward(
        ReferralReward $reward,
        array $rewardConfig,
        ReferralProgram $program
    ): void {
        $discountValue = $rewardConfig['value'] ?? 0;
        $discountType = $rewardConfig['discount_type'] ?? 'percentage'; // percentage or fixed
        $currencyId = $rewardConfig['currency_id'] ?? null;
        $couponCode = $rewardConfig['coupon_code'] ?? $this->generateCouponCode($program);
        $maxUses = $rewardConfig['max_uses'] ?? 1;
        $validDays = $rewardConfig['valid_days'] ?? $program->reward_validity_days ?? 30;

        // Create discount using DiscountService
        $discount = $this->discountService->percentageDiscount(
            "Referral Reward - {$program->name}",
            "referral_reward_{$reward->id}"
        )
            ->percentage($discountType === 'percentage' ? $discountValue : null)
            ->fixedAmount($discountType === 'fixed' ? ($discountValue * 100) : null) // Convert to cents
            ->couponCode($couponCode)
            ->startsAt(now())
            ->endsAt(now()->addDays($validDays))
            ->maxUses($maxUses)
            ->create();

        // Update reward with discount info
        $reward->update([
            'discount_id' => $discount->id,
            'discount_code' => $couponCode,
            'max_uses' => $maxUses,
        ]);
    }

    /**
     * Issue a credit reward.
     */
    protected function issueCreditReward(ReferralReward $reward, array $rewardConfig): void
    {
        // Credit rewards can be stored in customer meta or a separate credits table
        // For now, we'll store it in the reward record and it can be applied during checkout
        
        $reward->update([
            'reward_type' => ReferralReward::TYPE_CREDIT,
            'reward_value' => $rewardConfig['value'] ?? 0,
        ]);
    }

    /**
     * Generate a unique coupon code.
     */
    protected function generateCouponCode(ReferralProgram $program): string
    {
        $prefix = strtoupper(substr($program->handle, 0, 3));
        $code = $prefix . 'REF' . strtoupper(Str::random(6));

        while (Discount::where('coupon', $code)->exists()) {
            $code = $prefix . 'REF' . strtoupper(Str::random(6));
        }

        return $code;
    }

    /**
     * Map reward type to internal type constant.
     */
    protected function mapRewardType(string $type): string
    {
        return match($type) {
            'discount', 'discount_code' => ReferralReward::TYPE_DISCOUNT_CODE,
            'credit' => ReferralReward::TYPE_CREDIT,
            'percentage' => ReferralReward::TYPE_PERCENTAGE,
            'fixed_amount', 'fixed' => ReferralReward::TYPE_FIXED_AMOUNT,
            default => ReferralReward::TYPE_DISCOUNT_CODE,
        };
    }

    /**
     * Issue welcome discount for referee.
     */
    public function issueRefereeWelcomeDiscount(
        ReferralProgram $program,
        int $refereeUserId,
        ?int $refereeCustomerId = null
    ): ?ReferralReward {
        $refereeRewards = $program->referee_rewards ?? [];

        if (empty($refereeRewards)) {
            return null;
        }

        $rewardConfig = $refereeRewards[0]; // Use first referee reward config

        // Create a temporary event for processing
        $event = new ReferralEvent([
            'referral_program_id' => $program->id,
            'event_type' => 'referee_welcome',
            'status' => ReferralEvent::STATUS_PENDING,
            'referee_id' => $refereeUserId,
            'referee_customer_id' => $refereeCustomerId,
        ]);
        $event->save();

        // Create reward
        $reward = ReferralReward::create([
            'referral_program_id' => $program->id,
            'referral_event_id' => $event->id,
            'user_id' => $refereeUserId,
            'customer_id' => $refereeCustomerId,
            'reward_type' => ReferralReward::TYPE_DISCOUNT_CODE,
            'reward_value' => $rewardConfig['value'] ?? 0,
            'status' => ReferralReward::STATUS_PENDING,
        ]);

        // Issue discount
        $this->issueDiscountReward($reward, $rewardConfig, $program);
        $reward->markAsIssued();
        $event->markAsProcessed();

        return $reward;
    }
}

