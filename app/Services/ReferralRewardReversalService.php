<?php

namespace App\Services;

use App\Models\ReferralRewardIssuance;
use App\Models\ReferralAttribution;
use App\Models\Coupon;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\ReferralAuditLog;
use Lunar\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Referral Reward Reversal Service
 * 
 * Handles reversal of referral rewards when orders are refunded or chargebacks occur.
 */
class ReferralRewardReversalService
{
    /**
     * Reverse all rewards for an order.
     * 
     * @param Order $order
     * @param string $reason
     * @param string $type 'refund' | 'chargeback'
     * @return array Reversal results
     */
    public function reverseOrderRewards(Order $order, string $reason, string $type = 'refund'): array
    {
        $user = $order->user;
        if (!$user) {
            return [
                'success' => false,
                'reason' => 'Order has no associated user',
            ];
        }

        // Find all reward issuances for this order
        $issuances = ReferralRewardIssuance::where('order_id', $order->id)
            ->where('status', ReferralRewardIssuance::STATUS_ISSUED)
            ->get();

        if ($issuances->isEmpty()) {
            return [
                'success' => true,
                'message' => 'No rewards to reverse',
                'reversed_count' => 0,
            ];
        }

        $reversed = [];
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($issuances as $issuance) {
                $result = $this->reverseRewardIssuance($issuance, $reason, $type);
                
                if ($result['success']) {
                    $reversed[] = $result;
                } else {
                    $errors[] = $result;
                }
            }

            // Log reversal
            $this->logReversal($order, $user, $reversed, $errors, $reason, $type);

            DB::commit();

            return [
                'success' => true,
                'reversed_count' => count($reversed),
                'errors_count' => count($errors),
                'reversed' => $reversed,
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to reverse referral rewards', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'reason' => $e->getMessage(),
            ];
        }
    }

    /**
     * Reverse a single reward issuance.
     */
    protected function reverseRewardIssuance(ReferralRewardIssuance $issuance, string $reason, string $type): array
    {
        try {
            // Reverse referee reward
            if ($issuance->referee_reward_type && $issuance->referee_reward_value) {
                $this->reverseRefereeReward($issuance, $reason, $type);
            }

            // Reverse referrer reward
            if ($issuance->referrer_reward_type && $issuance->referrer_reward_value) {
                $this->reverseReferrerReward($issuance, $reason, $type);
            }

            // Mark issuance as reversed
            $issuance->reverse($reason);

            return [
                'success' => true,
                'issuance_id' => $issuance->id,
                'referee_reward_reversed' => !empty($issuance->referee_reward_type),
                'referrer_reward_reversed' => !empty($issuance->referrer_reward_type),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'issuance_id' => $issuance->id,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Reverse referee reward.
     */
    protected function reverseRefereeReward(ReferralRewardIssuance $issuance, string $reason, string $type): void
    {
        $referee = $issuance->referee;

        switch ($issuance->referee_reward_type) {
            case \App\Models\ReferralRule::REWARD_COUPON:
                $this->invalidateCoupon($issuance, $referee, $reason);
                break;

            case \App\Models\ReferralRule::REWARD_STORE_CREDIT:
                $this->reverseStoreCredit($issuance, $referee, $reason, $type);
                break;

            case \App\Models\ReferralRule::REWARD_PERCENTAGE_DISCOUNT:
            case \App\Models\ReferralRule::REWARD_FIXED_DISCOUNT:
                // Discounts are already applied, can't reverse
                // But we can log it
                Log::info('Discount reward cannot be reversed', [
                    'issuance_id' => $issuance->id,
                    'type' => $issuance->referee_reward_type,
                ]);
                break;

            case \App\Models\ReferralRule::REWARD_FREE_SHIPPING:
                // Free shipping already used, can't reverse
                Log::info('Free shipping reward cannot be reversed', [
                    'issuance_id' => $issuance->id,
                ]);
                break;
        }
    }

    /**
     * Reverse referrer reward.
     */
    protected function reverseReferrerReward(ReferralRewardIssuance $issuance, string $reason, string $type): void
    {
        $referrer = $issuance->referrer;

        switch ($issuance->referrer_reward_type) {
            case \App\Models\ReferralRule::REWARD_COUPON:
                $this->invalidateCoupon($issuance, $referrer, $reason);
                break;

            case \App\Models\ReferralRule::REWARD_STORE_CREDIT:
                $this->reverseStoreCredit($issuance, $referrer, $reason, $type);
                break;

            case \App\Models\ReferralRule::REWARD_PERCENTAGE_DISCOUNT_NEXT_ORDER:
            case \App\Models\ReferralRule::REWARD_FIXED_AMOUNT:
                // These are typically coupons, invalidate them
                $this->invalidateCoupon($issuance, $referrer, $reason);
                break;
        }
    }

    /**
     * Invalidate coupon.
     */
    protected function invalidateCoupon(ReferralRewardIssuance $issuance, $user, string $reason): void
    {
        // Find coupon created for this issuance
        // Check metadata for issuance reference or match by rule and user
        $coupon = Coupon::where('created_by_rule_id', $issuance->referral_rule_id)
            ->where('assigned_to_user_id', $user->id)
            ->where(function ($query) {
                $query->whereNull('end_at')
                    ->orWhere('end_at', '>=', now());
            })
            ->first();

        if ($coupon) {
            $meta = $coupon->meta ?? [];
            $meta['reversed_at'] = now()->toIso8601String();
            $meta['reversal_reason'] = $reason;
            $meta['reversed_by_issuance_id'] = $issuance->id;
            $meta['is_active'] = false;

            // Invalidate coupon by setting end_at to past
            $coupon->update([
                'end_at' => now()->subSecond(),
                'meta' => $meta,
            ]);

            Log::info('Coupon invalidated due to reward reversal', [
                'coupon_id' => $coupon->id,
                'issuance_id' => $issuance->id,
                'user_id' => $user->id,
                'reason' => $reason,
            ]);
        }
    }

    /**
     * Reverse store credit.
     */
    protected function reverseStoreCredit(ReferralRewardIssuance $issuance, $user, string $reason, string $type): void
    {
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0]
        );

        $amount = $issuance->referee_reward_type === \App\Models\ReferralRule::REWARD_STORE_CREDIT
            ? $issuance->referee_reward_value
            : $issuance->referrer_reward_value;

        // Deduct from wallet
        $wallet->decrement('balance', $amount);

        // Create reversal transaction
        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'type' => WalletTransaction::TYPE_DEBIT,
            'amount' => $amount,
            'reason' => "referral_reward_reversal_{$type}",
            'related_order_id' => $issuance->order_id,
            'related_referral_id' => $issuance->id,
            'metadata' => [
                'reversal_reason' => $reason,
                'original_issuance_id' => $issuance->id,
                'reversed_at' => now()->toIso8601String(),
            ],
        ]);

        Log::info('Store credit reversed', [
            'wallet_id' => $wallet->id,
            'user_id' => $user->id,
            'amount' => $amount,
            'issuance_id' => $issuance->id,
            'reason' => $reason,
        ]);
    }

    /**
     * Log reversal action.
     */
    protected function logReversal(Order $order, $user, array $reversed, array $errors, string $reason, string $type): void
    {
        ReferralAuditLog::create([
            'actor' => 'system',
            'action' => 'reward_reversed',
            'before' => [
                'order_id' => $order->id,
                'user_id' => $user->id,
                'reversed_count' => count($reversed),
            ],
            'after' => [
                'reversed' => $reversed,
                'errors' => $errors,
                'reason' => $reason,
                'type' => $type,
            ],
            'timestamp' => now(),
        ]);
    }
}

