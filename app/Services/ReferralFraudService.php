<?php

namespace App\Services;

use App\Models\ReferralRule;
use App\Models\ReferralAttribution;
use App\Models\ReferralRewardIssuance;
use App\Models\FraudPolicy;
use App\Models\User;
use Lunar\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Referral Fraud Prevention Service
 * 
 * Implements fraud detection and prevention rules.
 */
class ReferralFraudService
{
    /**
     * Check if reward can be issued based on fraud policy.
     */
    public function canIssueReward(ReferralRule $rule, User $referee, User $referrer, ?Order $order): bool
    {
        $policy = $rule->fraudPolicy ?? $rule->program->fraudPolicy;

        if (!$policy) {
            return true; // No policy, allow
        }

        // Check self-referral
        if ($referee->id === $referrer->id) {
            return false;
        }

        // Check same IP
        if (!$policy->allow_same_ip && $order) {
            if ($this->hasSameIp($referee, $referrer, $order)) {
                return false;
            }
        }

        // Check IP signup limits
        if ($policy->max_signups_per_ip_per_day) {
            if ($this->exceedsIpSignupLimit($order, $policy->max_signups_per_ip_per_day)) {
                return false;
            }
        }

        // Check IP order limits
        if ($policy->max_orders_per_ip_per_day) {
            if ($this->exceedsIpOrderLimit($order, $policy->max_orders_per_ip_per_day)) {
                return false;
            }
        }

        // Check disposable email
        if ($policy->block_disposable_emails) {
            if ($this->isDisposableEmail($referee->email)) {
                return false;
            }
        }

        // Check same card fingerprint
        if ($policy->block_same_card_fingerprint && $order) {
            if ($this->hasSameCardFingerprint($referee, $referrer, $order)) {
                return false;
            }
        }

        // Check email verification
        if ($policy->require_email_verified) {
            if (!$referee->email_verified_at) {
                return false;
            }
        }

        // Check phone verification
        if ($policy->require_phone_verified) {
            if (!$referee->phone_verified_at) {
                return false;
            }
        }

        // Check minimum account age
        if ($policy->min_account_age_days_before_reward) {
            if ($referee->created_at->addDays($policy->min_account_age_days_before_reward)->isFuture()) {
                return false;
            }
        }

        // Check payment captured
        if ($order && !$this->isPaymentCaptured($order)) {
            return false;
        }

        // Check order not refunded
        if ($order && $this->isOrderRefunded($order)) {
            return false;
        }

        // Check one referee can only reward one referrer
        if (!$this->isOneRefereeOneReferrer($referee, $referrer)) {
            return false;
        }

        return true;
    }

    /**
     * Check if referee and referrer have same IP.
     */
    protected function hasSameIp(User $referee, User $referrer, Order $order): bool
    {
        $orderIpHash = hash('sha256', $order->meta['ip_address'] ?? request()->ip());

        // Check referrer's recent orders
        $referrerOrders = Order::whereHas('customer', function ($query) use ($referrer) {
            $query->where('user_id', $referrer->id);
        })
        ->where('created_at', '>=', now()->subDays(30))
        ->get();

        foreach ($referrerOrders as $referrerOrder) {
            $referrerIpHash = hash('sha256', $referrerOrder->meta['ip_address'] ?? '');
            if ($orderIpHash === $referrerIpHash) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if IP exceeds signup limit.
     */
    protected function exceedsIpSignupLimit(?Order $order, int $limit): bool
    {
        if (!$order) {
            return false;
        }

        $ipHash = hash('sha256', $order->meta['ip_address'] ?? request()->ip());
        $date = now()->startOfDay();

        $signups = DB::table('users')
            ->whereRaw('SHA2(CONCAT(ip_address, ?), 256) = ?', ['', $ipHash])
            ->whereDate('created_at', $date)
            ->count();

        return $signups >= $limit;
    }

    /**
     * Check if IP exceeds order limit.
     */
    protected function exceedsIpOrderLimit(?Order $order, int $limit): bool
    {
        if (!$order) {
            return false;
        }

        $ipHash = hash('sha256', $order->meta['ip_address'] ?? request()->ip());
        $date = now()->startOfDay();

        $orders = Order::where('meta->ip_address_hash', $ipHash)
            ->whereDate('created_at', $date)
            ->count();

        return $orders >= $limit;
    }

    /**
     * Check if email is disposable.
     */
    protected function isDisposableEmail(string $email): bool
    {
        $domain = substr(strrchr($email, "@"), 1);
        
        // Common disposable email domains
        $disposableDomains = [
            '10minutemail.com',
            'tempmail.com',
            'guerrillamail.com',
            'mailinator.com',
            'throwaway.email',
            // Add more as needed
        ];

        return in_array(strtolower($domain), $disposableDomains);
    }

    /**
     * Check if same card fingerprint.
     */
    protected function hasSameCardFingerprint(User $referee, User $referrer, Order $order): bool
    {
        $cardFingerprint = $order->meta['card_fingerprint'] ?? null;

        if (!$cardFingerprint) {
            return false;
        }

        // Check referrer's orders for same card
        $referrerOrders = Order::whereHas('customer', function ($query) use ($referrer) {
            $query->where('user_id', $referrer->id);
        })
        ->where('meta->card_fingerprint', $cardFingerprint)
        ->exists();

        return $referrerOrders;
    }

    /**
     * Check if payment is captured.
     */
    protected function isPaymentCaptured(Order $order): bool
    {
        // Lunar uses placed_at to indicate order is paid
        return $order->placed_at && $order->placed_at->isPast();
    }

    /**
     * Check if order is refunded.
     */
    protected function isOrderRefunded(Order $order): bool
    {
        return $order->status === 'refunded' || 
               $order->refund_total > 0;
    }

    /**
     * Check one referee can only reward one referrer.
     */
    protected function isOneRefereeOneReferrer(User $referee, User $referrer): bool
    {
        // Check if referee has already rewarded a different referrer
        $otherAttributions = ReferralAttribution::where('referee_user_id', $referee->id)
            ->where('referrer_user_id', '!=', $referrer->id)
            ->where('status', ReferralAttribution::STATUS_CONFIRMED)
            ->exists();

        if ($otherAttributions) {
            return false;
        }

        // Check if referee has already received reward from different referrer
        $otherRewards = ReferralRewardIssuance::where('referee_user_id', $referee->id)
            ->where('referrer_user_id', '!=', $referrer->id)
            ->where('status', ReferralRewardIssuance::STATUS_ISSUED)
            ->exists();

        return !$otherRewards;
    }
}

