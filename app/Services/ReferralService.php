<?php

namespace App\Services;

use App\Models\ReferralProgram;
use App\Models\ReferralCode;
use App\Models\ReferralEvent;
use App\Models\ReferralReward;
use App\Models\ReferralTracking;
use App\Models\User;
use Lunar\Models\Customer;
use Lunar\Models\Order;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Referral Service
 * 
 * Main service for managing referral programs, codes, and events.
 */
class ReferralService
{
    protected ReferralCodeService $codeService;
    protected ReferralRewardService $rewardService;

    public function __construct(
        ReferralCodeService $codeService,
        ReferralRewardService $rewardService
    ) {
        $this->codeService = $codeService;
        $this->rewardService = $rewardService;
    }

    /**
     * Generate or retrieve a referral code for a user/customer.
     */
    public function getOrCreateReferralCode(
        ReferralProgram $program,
        ?User $user = null,
        ?Customer $customer = null,
        ?string $customCode = null
    ): ReferralCode {
        // Check if code already exists
        $query = ReferralCode::where('referral_program_id', $program->id);
        
        if ($user) {
            $query->where('referrer_id', $user->id);
        } elseif ($customer) {
            $query->where('referrer_customer_id', $customer->id);
        }

        $existingCode = $query->first();

        if ($existingCode && $existingCode->isValid()) {
            return $existingCode;
        }

        // Generate new code
        $code = $customCode ?: $this->generateUniqueCode($program);
        $slug = Str::slug($code);

        // Ensure slug is unique
        while (ReferralCode::where('slug', $slug)->exists()) {
            $slug = Str::slug($code . '-' . Str::random(4));
        }

        $expiresAt = $program->referral_code_validity_days 
            ? now()->addDays($program->referral_code_validity_days)
            : null;

        return ReferralCode::create([
            'referral_program_id' => $program->id,
            'code' => $code,
            'slug' => $slug,
            'referrer_id' => $user?->id,
            'referrer_customer_id' => $customer?->id,
            'is_active' => true,
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * Track a referral link click.
     */
    public function trackClick(
        ReferralCode $code,
        ?string $sessionId = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?string $referrerUrl = null,
        ?string $landingPage = null
    ): ReferralTracking {
        $code->incrementClicks();

        return ReferralTracking::create([
            'referral_code_id' => $code->id,
            'session_id' => $sessionId ?: session()->getId(),
            'ip_address' => $ipAddress ?: request()->ip(),
            'user_agent' => $userAgent ?: request()->userAgent(),
            'referrer_url' => $referrerUrl ?: request()->header('referer'),
            'landing_page' => $landingPage ?: request()->fullUrl(),
            'event_type' => ReferralTracking::EVENT_CLICK,
        ]);
    }

    /**
     * Process a signup event.
     */
    public function processSignup(
        ReferralCode $code,
        User $referee,
        ?Customer $refereeCustomer = null
    ): ?ReferralEvent {
        if (!$code->isValid()) {
            return null;
        }

        $program = $code->program;
        
        // Check eligibility
        if (!$program->isEligible($code->referrer, $code->referrerCustomer)) {
            return null;
        }

        // Check self-referral
        if (!$program->allow_self_referral) {
            if ($code->referrer_id && $code->referrer_id === $referee->id) {
                return null;
            }
            if ($code->referrer_customer_id && $refereeCustomer && 
                $code->referrer_customer_id === $refereeCustomer->id) {
                return null;
            }
        }

        // Check max referrals
        if ($program->hasReachedMaxReferrals()) {
            return null;
        }

        // Check per-referrer limit
        if ($program->max_referrals_per_referrer) {
            $referrerCodes = ReferralCode::where('referral_program_id', $program->id)
                ->where(function ($q) use ($code) {
                    if ($code->referrer_id) {
                        $q->where('referrer_id', $code->referrer_id);
                    }
                    if ($code->referrer_customer_id) {
                        $q->orWhere('referrer_customer_id', $code->referrer_customer_id);
                    }
                })
                ->sum('current_uses');

            if ($referrerCodes >= $program->max_referrals_per_referrer) {
                return null;
            }
        }

        // Create event
        $event = ReferralEvent::create([
            'referral_program_id' => $program->id,
            'referral_code_id' => $code->id,
            'event_type' => ReferralEvent::EVENT_SIGNUP,
            'status' => ReferralEvent::STATUS_PENDING,
            'referrer_id' => $code->referrer_id,
            'referrer_customer_id' => $code->referrer_customer_id,
            'referee_id' => $referee->id,
            'referee_customer_id' => $refereeCustomer?->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Update counters
        $code->incrementSignups();
        $code->incrementUses();
        $program->increment('total_referrals');

        // Track signup
        ReferralTracking::create([
            'referral_code_id' => $code->id,
            'user_id' => $referee->id,
            'customer_id' => $refereeCustomer?->id,
            'session_id' => session()->getId(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'event_type' => ReferralTracking::EVENT_SIGNUP,
        ]);

        // Process rewards
        $this->processRewards($event, ReferralEvent::EVENT_SIGNUP);

        return $event;
    }

    /**
     * Process a purchase event.
     */
    public function processPurchase(
        ReferralCode $code,
        Order $order,
        User $referee,
        ?Customer $refereeCustomer = null,
        bool $isFirstPurchase = false
    ): ?ReferralEvent {
        if (!$code->isValid()) {
            return null;
        }

        $program = $code->program;

        // Check if this referee was referred by this code
        $hasSignup = ReferralEvent::where('referral_code_id', $code->id)
            ->where('referee_id', $referee->id)
            ->where('event_type', ReferralEvent::EVENT_SIGNUP)
            ->exists();

        if (!$hasSignup) {
            return null;
        }

        $eventType = $isFirstPurchase 
            ? ReferralEvent::EVENT_FIRST_PURCHASE 
            : ReferralEvent::EVENT_REPEAT_PURCHASE;

        // Create event
        $event = ReferralEvent::create([
            'referral_program_id' => $program->id,
            'referral_code_id' => $code->id,
            'event_type' => $eventType,
            'status' => ReferralEvent::STATUS_PENDING,
            'referrer_id' => $code->referrer_id,
            'referrer_customer_id' => $code->referrer_customer_id,
            'referee_id' => $referee->id,
            'referee_customer_id' => $refereeCustomer?->id,
            'order_id' => $order->id,
            'order_reference' => $order->reference,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Update counters
        $code->incrementPurchases();
        $code->addRevenue($order->total->value);
        
        // Update tracking
        ReferralTracking::where('referral_code_id', $code->id)
            ->where('referee_id', $referee->id)
            ->where('converted', false)
            ->update([
                'converted' => true,
                'converted_at' => now(),
                'conversion_order_id' => $order->id,
            ]);

        // Process rewards
        $this->processRewards($event, $eventType);

        return $event;
    }

    /**
     * Process rewards for an event.
     */
    protected function processRewards(ReferralEvent $event, string $eventType): void
    {
        $program = $event->program;
        $rewards = $program->referrer_rewards ?? [];

        foreach ($rewards as $rewardConfig) {
            if (($rewardConfig['action'] ?? null) !== $eventType) {
                continue;
            }

            try {
                $this->rewardService->issueReward($event, $rewardConfig);
            } catch (\Exception $e) {
                Log::error('Failed to issue referral reward', [
                    'event_id' => $event->id,
                    'reward_config' => $rewardConfig,
                    'error' => $e->getMessage(),
                ]);

                $event->markAsFailed($e->getMessage());
            }
        }
    }

    /**
     * Generate a unique referral code.
     */
    protected function generateUniqueCode(ReferralProgram $program): string
    {
        $prefix = strtoupper(substr($program->handle, 0, 3));
        $code = $prefix . strtoupper(Str::random(8));

        while (ReferralCode::where('code', $code)->exists()) {
            $code = $prefix . strtoupper(Str::random(8));
        }

        return $code;
    }

    /**
     * Get referral code from slug or code string.
     */
    public function getReferralCodeBySlugOrCode(string $identifier): ?ReferralCode
    {
        return ReferralCode::where('slug', $identifier)
            ->orWhere('code', $identifier)
            ->first();
    }

    /**
     * Get active programs for a user/customer.
     */
    public function getActivePrograms(?User $user = null, ?Customer $customer = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = ReferralProgram::active();

        if ($user || $customer) {
            $query->where(function ($q) use ($user, $customer) {
                // Check customer groups
                if ($customer) {
                    $customerGroupIds = $customer->customerGroups()->pluck('id')->toArray();
                    $q->whereJsonContains('eligible_customer_groups', $customerGroupIds);
                }

                // Check specific users
                if ($user) {
                    $q->orWhereJsonContains('eligible_users', $user->id);
                }

                // Or no restrictions
                $q->orWhereNull('eligible_customer_groups')
                  ->whereNull('eligible_users');
            });
        }

        return $query->get();
    }
}

