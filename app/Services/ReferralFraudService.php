<?php

namespace App\Services;

use App\Models\ReferralRule;
use App\Models\ReferralAttribution;
use App\Models\ReferralRewardIssuance;
use App\Models\FraudPolicy;
use App\Models\FraudReview;
use App\Models\FraudRateLimit;
use App\Models\User;
use Lunar\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

/**
 * Enhanced Referral Fraud Prevention Service
 * 
 * Implements comprehensive fraud detection including:
 * - Device fingerprinting
 * - IP rate limits
 * - Disposable email detection
 * - Payment fingerprint checks
 * - Velocity checks
 * - Manual review queue
 * - Reward hold system
 */
class ReferralFraudService
{
    public function __construct(
        protected DeviceFingerprintService $deviceFingerprintService,
        protected PaymentFingerprintService $paymentFingerprintService,
        protected DisposableEmailService $disposableEmailService
    ) {}

    /**
     * Run all fraud checks and return comprehensive result.
     * 
     * @return array ['is_fraudulent' => bool, 'reason' => string, 'risk_score' => int, 'risk_factors' => array]
     */
    public function runAllChecks(User $referee, User $referrer, ?Order $order = null, ?Request $request = null): array
    {
        $request = $request ?? request();
        $policy = $this->getFraudPolicy($order);
        
        $riskScore = 0;
        $riskFactors = [];
        $isFraudulent = false;
        $reason = null;

        // 1. Self-referral check
        if ($referee->id === $referrer->id) {
            return [
                'is_fraudulent' => true,
                'reason' => 'Self-referral detected',
                'risk_score' => 100,
                'risk_factors' => ['self_referral'],
            ];
        }

        // 2. Device fingerprint check
        $deviceFingerprint = $this->checkDeviceFingerprint($referee, $referrer, $request, $policy);
        if ($deviceFingerprint['is_fraudulent']) {
            return $deviceFingerprint;
        }
        $riskScore += $deviceFingerprint['risk_score'];
        $riskFactors = array_merge($riskFactors, $deviceFingerprint['risk_factors']);

        // 3. IP rate limit checks
        $ipChecks = $this->checkIpRateLimits($referee, $referrer, $order, $request, $policy);
        if ($ipChecks['is_fraudulent']) {
            return $ipChecks;
        }
        $riskScore += $ipChecks['risk_score'];
        $riskFactors = array_merge($riskFactors, $ipChecks['risk_factors']);

        // 4. Disposable email check
        if ($policy && $policy->block_disposable_emails) {
            $emailCheck = $this->checkDisposableEmail($referee->email);
            if ($emailCheck['is_fraudulent']) {
                return $emailCheck;
            }
            $riskScore += $emailCheck['risk_score'];
            $riskFactors = array_merge($riskFactors, $emailCheck['risk_factors']);
        }

        // 5. Payment fingerprint check
        if ($order && $policy && $policy->block_same_card_fingerprint) {
            $paymentCheck = $this->checkPaymentFingerprint($referee, $referrer, $order);
            if ($paymentCheck['is_fraudulent']) {
                return $paymentCheck;
            }
            $riskScore += $paymentCheck['risk_score'];
            $riskFactors = array_merge($riskFactors, $paymentCheck['risk_factors']);
        }

        // 6. Velocity checks
        $velocityCheck = $this->checkVelocity($referee, $referrer, $order);
        if ($velocityCheck['is_fraudulent']) {
            return $velocityCheck;
        }
        $riskScore += $velocityCheck['risk_score'];
        $riskFactors = array_merge($riskFactors, $velocityCheck['risk_factors']);

        // 7. Verification checks
        $verificationCheck = $this->checkVerification($referee, $policy);
        if ($verificationCheck['is_fraudulent']) {
            return $verificationCheck;
        }
        $riskScore += $verificationCheck['risk_score'];
        $riskFactors = array_merge($riskFactors, $verificationCheck['risk_factors']);

        // 8. Account age check
        $ageCheck = $this->checkAccountAge($referee, $policy);
        if ($ageCheck['is_fraudulent']) {
            return $ageCheck;
        }
        $riskScore += $ageCheck['risk_score'];
        $riskFactors = array_merge($riskFactors, $ageCheck['risk_factors']);

        // 9. Payment status check
        if ($order) {
            $paymentStatusCheck = $this->checkPaymentStatus($order);
            if ($paymentStatusCheck['is_fraudulent']) {
                return $paymentStatusCheck;
            }
            $riskScore += $paymentStatusCheck['risk_score'];
            $riskFactors = array_merge($riskFactors, $paymentStatusCheck['risk_factors']);
        }

        // 10. One referee one referrer check
        $oneRefereeCheck = $this->checkOneRefereeOneReferrer($referee, $referrer);
        if ($oneRefereeCheck['is_fraudulent']) {
            return $oneRefereeCheck;
        }
        $riskScore += $oneRefereeCheck['risk_score'];
        $riskFactors = array_merge($riskFactors, $oneRefereeCheck['risk_factors']);

        // Determine if fraudulent based on risk score
        $manualReviewThreshold = $policy->manual_review_threshold ?? 50;
        $isFraudulent = $riskScore >= 100; // Hard block at 100
        $requiresReview = $riskScore >= $manualReviewThreshold && $riskScore < 100;

        return [
            'is_fraudulent' => $isFraudulent,
            'requires_review' => $requiresReview,
            'reason' => $isFraudulent ? 'Risk score exceeds threshold' : null,
            'risk_score' => $riskScore,
            'risk_factors' => array_unique($riskFactors),
        ];
    }

    /**
     * Check device fingerprint.
     */
    protected function checkDeviceFingerprint(User $referee, User $referrer, Request $request, ?FraudPolicy $policy): array
    {
        $riskScore = 0;
        $riskFactors = [];

        // Generate device fingerprint
        $deviceFingerprint = $this->deviceFingerprintService->getFingerprint($request);
        
        if (!$deviceFingerprint) {
            return [
                'is_fraudulent' => false,
                'risk_score' => 0,
                'risk_factors' => [],
            ];
        }

        // Store fingerprint
        $this->deviceFingerprintService->storeFingerprint($deviceFingerprint, $request);

        // Check if referrer has used same device
        $referrerAttributions = ReferralAttribution::where('referrer_user_id', $referrer->id)
            ->where('device_fingerprint_hash', $deviceFingerprint)
            ->exists();

        if ($referrerAttributions) {
            $riskScore += 30;
            $riskFactors[] = 'same_device_as_referrer';
        }

        // Check device velocity (many signups from same device)
        $deviceSignups = FraudRateLimit::getCountToday(
            FraudRateLimit::IDENTIFIER_DEVICE,
            $deviceFingerprint,
            FraudRateLimit::ACTION_SIGNUP
        );

        if ($deviceSignups >= 5) {
            $riskScore += 40;
            $riskFactors[] = 'high_device_signup_velocity';
        }

        return [
            'is_fraudulent' => false,
            'risk_score' => $riskScore,
            'risk_factors' => $riskFactors,
        ];
    }

    /**
     * Check IP rate limits.
     */
    protected function checkIpRateLimits(User $referee, User $referrer, ?Order $order, Request $request, ?FraudPolicy $policy): array
    {
        $riskScore = 0;
        $riskFactors = [];
        $ipHash = hash('sha256', $order->meta['ip_address'] ?? $request->ip());

        if (!$policy) {
            return [
                'is_fraudulent' => false,
                'risk_score' => 0,
                'risk_factors' => [],
            ];
        }

        // Check same IP
        if (!$policy->allow_same_ip && $order) {
            $sameIp = $this->hasSameIp($referee, $referrer, $order, $ipHash);
            if ($sameIp) {
                return [
                    'is_fraudulent' => true,
                    'reason' => 'Same IP address as referrer',
                    'risk_score' => 100,
                    'risk_factors' => ['same_ip_as_referrer'],
                ];
            }
        }

        // Check IP signup limits
        if ($policy->max_signups_per_ip_per_day) {
            FraudRateLimit::incrementCount(
                FraudRateLimit::IDENTIFIER_IP,
                $ipHash,
                FraudRateLimit::ACTION_SIGNUP
            );

            if (FraudRateLimit::isLimitExceeded(
                FraudRateLimit::IDENTIFIER_IP,
                $ipHash,
                FraudRateLimit::ACTION_SIGNUP,
                $policy->max_signups_per_ip_per_day
            )) {
                return [
                    'is_fraudulent' => true,
                    'reason' => "IP exceeded signup limit ({$policy->max_signups_per_ip_per_day} per day)",
                    'risk_score' => 100,
                    'risk_factors' => ['ip_signup_limit_exceeded'],
                ];
            }
        }

        // Check IP order limits
        if ($order && $policy->max_orders_per_ip_per_day) {
            FraudRateLimit::incrementCount(
                FraudRateLimit::IDENTIFIER_IP,
                $ipHash,
                FraudRateLimit::ACTION_ORDER
            );

            if (FraudRateLimit::isLimitExceeded(
                FraudRateLimit::IDENTIFIER_IP,
                $ipHash,
                FraudRateLimit::ACTION_ORDER,
                $policy->max_orders_per_ip_per_day
            )) {
                return [
                    'is_fraudulent' => true,
                    'reason' => "IP exceeded order limit ({$policy->max_orders_per_ip_per_day} per day)",
                    'risk_score' => 100,
                    'risk_factors' => ['ip_order_limit_exceeded'],
                ];
            }
        }

        return [
            'is_fraudulent' => false,
            'risk_score' => $riskScore,
            'risk_factors' => $riskFactors,
        ];
    }

    /**
     * Check disposable email.
     */
    protected function checkDisposableEmail(string $email): array
    {
        $isDisposable = $this->disposableEmailService->isDisposable($email);

        return [
            'is_fraudulent' => $isDisposable,
            'reason' => $isDisposable ? 'Disposable email address detected' : null,
            'risk_score' => $isDisposable ? 100 : 0,
            'risk_factors' => $isDisposable ? ['disposable_email'] : [],
        ];
    }

    /**
     * Check payment fingerprint.
     */
    protected function checkPaymentFingerprint(User $referee, User $referrer, Order $order): array
    {
        $riskScore = 0;
        $riskFactors = [];

        // Generate payment fingerprint
        $paymentFingerprint = $this->paymentFingerprintService->generateFingerprint($order);
        
        if (!$paymentFingerprint) {
            return [
                'is_fraudulent' => false,
                'risk_score' => 0,
                'risk_factors' => [],
            ];
        }

        // Store fingerprint
        $this->paymentFingerprintService->storeFingerprint($paymentFingerprint, $order);

        // Check if referrer has used same card
        $referrerOrders = Order::whereHas('customer', function ($query) use ($referrer) {
            $query->where('user_id', $referrer->id);
        })
        ->whereHas('transactions', function ($query) {
            $query->where('type', 'capture')->where('success', true);
        })
        ->get();

        foreach ($referrerOrders as $referrerOrder) {
            $referrerPaymentFingerprint = $this->paymentFingerprintService->generateFingerprint($referrerOrder);
            if ($referrerPaymentFingerprint === $paymentFingerprint) {
                return [
                    'is_fraudulent' => true,
                    'reason' => 'Same payment card as referrer',
                    'risk_score' => 100,
                    'risk_factors' => ['same_payment_card'],
                ];
            }
        }

        return [
            'is_fraudulent' => false,
            'risk_score' => $riskScore,
            'risk_factors' => $riskFactors,
        ];
    }

    /**
     * Check velocity (many signups/orders in short time).
     */
    protected function checkVelocity(User $referee, User $referrer, ?Order $order): array
    {
        $riskScore = 0;
        $riskFactors = [];

        // Check referee signup velocity (signups in last hour)
        $recentSignups = User::where('created_at', '>=', now()->subHour())
            ->count();

        if ($recentSignups >= 10) {
            $riskScore += 20;
            $riskFactors[] = 'high_signup_velocity';
        }

        // Check referrer referral velocity (referrals in last hour)
        $recentReferrals = ReferralAttribution::where('referrer_user_id', $referrer->id)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($recentReferrals >= 5) {
            $riskScore += 30;
            $riskFactors[] = 'high_referral_velocity';
        }

        // Check order velocity
        if ($order) {
            $recentOrders = Order::where('created_at', '>=', now()->subHour())
                ->where('user_id', $referee->id)
                ->count();

            if ($recentOrders >= 3) {
                $riskScore += 25;
                $riskFactors[] = 'high_order_velocity';
            }
        }

        return [
            'is_fraudulent' => false,
            'risk_score' => $riskScore,
            'risk_factors' => $riskFactors,
        ];
    }

    /**
     * Check verification requirements.
     */
    protected function checkVerification(User $referee, ?FraudPolicy $policy): array
    {
        if (!$policy) {
            return [
                'is_fraudulent' => false,
                'risk_score' => 0,
                'risk_factors' => [],
            ];
        }

        $riskScore = 0;
        $riskFactors = [];

        if ($policy->require_email_verified && !$referee->email_verified_at) {
            return [
                'is_fraudulent' => true,
                'reason' => 'Email verification required',
                'risk_score' => 100,
                'risk_factors' => ['email_not_verified'],
            ];
        }

        if ($policy->require_phone_verified && !$referee->phone_verified_at) {
            return [
                'is_fraudulent' => true,
                'reason' => 'Phone verification required',
                'risk_score' => 100,
                'risk_factors' => ['phone_not_verified'],
            ];
        }

        return [
            'is_fraudulent' => false,
            'risk_score' => $riskScore,
            'risk_factors' => $riskFactors,
        ];
    }

    /**
     * Check account age.
     */
    protected function checkAccountAge(User $referee, ?FraudPolicy $policy): array
    {
        if (!$policy || !$policy->min_account_age_days_before_reward) {
            return [
                'is_fraudulent' => false,
                'risk_score' => 0,
                'risk_factors' => [],
            ];
        }

        $accountAge = $referee->created_at->diffInDays(now());

        if ($accountAge < $policy->min_account_age_days_before_reward) {
            return [
                'is_fraudulent' => true,
                'reason' => "Account age requirement not met ({$policy->min_account_age_days_before_reward} days)",
                'risk_score' => 100,
                'risk_factors' => ['insufficient_account_age'],
            ];
        }

        return [
            'is_fraudulent' => false,
            'risk_score' => 0,
            'risk_factors' => [],
        ];
    }

    /**
     * Check payment status.
     */
    protected function checkPaymentStatus(Order $order): array
    {
        // Check payment captured
        if (!$this->isPaymentCaptured($order)) {
            return [
                'is_fraudulent' => true,
                'reason' => 'Payment not captured',
                'risk_score' => 100,
                'risk_factors' => ['payment_not_captured'],
            ];
        }

        // Check order not refunded
        if ($this->isOrderRefunded($order)) {
            return [
                'is_fraudulent' => true,
                'reason' => 'Order has been refunded',
                'risk_score' => 100,
                'risk_factors' => ['order_refunded'],
            ];
        }

        return [
            'is_fraudulent' => false,
            'risk_score' => 0,
            'risk_factors' => [],
        ];
    }

    /**
     * Check one referee one referrer rule.
     */
    protected function checkOneRefereeOneReferrer(User $referee, User $referrer): array
    {
        // Check if referee has already rewarded a different referrer
        $otherAttributions = ReferralAttribution::where('referee_user_id', $referee->id)
            ->where('referrer_user_id', '!=', $referrer->id)
            ->where('status', ReferralAttribution::STATUS_CONFIRMED)
            ->exists();

        if ($otherAttributions) {
            return [
                'is_fraudulent' => true,
                'reason' => 'Referee already attributed to different referrer',
                'risk_score' => 100,
                'risk_factors' => ['multiple_referrers'],
            ];
        }

        return [
            'is_fraudulent' => false,
            'risk_score' => 0,
            'risk_factors' => [],
        ];
    }

    /**
     * Check if referee and referrer have same IP.
     */
    protected function hasSameIp(User $referee, User $referrer, Order $order, string $ipHash): bool
    {
        // Check referrer's recent orders
        $referrerOrders = Order::whereHas('customer', function ($query) use ($referrer) {
            $query->where('user_id', $referrer->id);
        })
        ->where('created_at', '>=', now()->subDays(30))
        ->get();

        foreach ($referrerOrders as $referrerOrder) {
            $referrerIpHash = hash('sha256', $referrerOrder->meta['ip_address'] ?? '');
            if ($ipHash === $referrerIpHash) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if payment is captured.
     */
    protected function isPaymentCaptured(Order $order): bool
    {
        return $order->placed_at && $order->placed_at->isPast() &&
               $order->transactions()->where('type', 'capture')->where('success', true)->exists();
    }

    /**
     * Check if order is refunded.
     */
    protected function isOrderRefunded(Order $order): bool
    {
        return $order->status === 'refunded' || 
               ($order->refund_total && $order->refund_total->value > 0);
    }

    /**
     * Get fraud policy.
     */
    protected function getFraudPolicy(?Order $order): ?FraudPolicy
    {
        // Try to get from order's referral attribution
        if ($order && $order->user) {
            $attribution = ReferralAttribution::where('referee_user_id', $order->user->id)
                ->where('status', ReferralAttribution::STATUS_CONFIRMED)
                ->first();

            if ($attribution && $attribution->program) {
                return $attribution->program->fraudPolicy;
            }
        }

        return null;
    }

    /**
     * Create fraud review entry.
     */
    public function createFraudReview(ReferralAttribution $attribution, ?Order $order, array $fraudCheckResult): FraudReview
    {
        return FraudReview::create([
            'referral_attribution_id' => $attribution->id,
            'order_id' => $order?->id,
            'status' => FraudReview::STATUS_PENDING,
            'risk_score' => $fraudCheckResult['risk_score'],
            'risk_factors' => $fraudCheckResult['risk_factors'],
        ]);
    }

    /**
     * Hold rewards for attribution.
     */
    public function holdRewards(ReferralAttribution $attribution, int $holdDays): void
    {
        $attribution->update([
            'rewards_held' => true,
            'rewards_held_until' => now()->addDays($holdDays),
        ]);
    }

    /**
     * Release held rewards.
     */
    public function releaseRewards(ReferralAttribution $attribution): void
    {
        $attribution->update([
            'rewards_held' => false,
            'rewards_released_at' => now(),
        ]);
    }

    /**
     * Check if rewards should be held.
     */
    public function shouldHoldRewards(ReferralRule $rule, ReferralAttribution $attribution): bool
    {
        if (!$rule->hold_rewards_days) {
            return false;
        }

        // Check if hold period has passed
        if ($attribution->rewards_held_until && $attribution->rewards_held_until->isPast()) {
            return false;
        }

        return $attribution->rewards_held ?? false;
    }

    /**
     * Legacy method for backward compatibility.
     */
    public function canIssueReward(ReferralRule $rule, User $referee, User $referrer, ?Order $order): bool
    {
        $result = $this->runAllChecks($referee, $referrer, $order);
        return !$result['is_fraudulent'];
    }
}
