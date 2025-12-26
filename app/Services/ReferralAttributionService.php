<?php

namespace App\Services;

use App\Models\ReferralAttribution;
use App\Models\ReferralProgram;
use App\Models\User;
use App\Models\ReferralClick;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cookie;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Referral Attribution Service
 * 
 * Handles attribution logic with priority system:
 * 1. Explicit referral code entered at signup
 * 2. Referral link cookie stored (first-click or last-click)
 * 3. Manual admin assignment
 */
class ReferralAttributionService
{
    protected const COOKIE_NAME = 'referral_code';
    protected const COOKIE_TTL = 30; // days (fallback if program does not specify)

    /**
     * Attribution priority constants
     */
    const PRIORITY_EXPLICIT_CODE = 1;
    const PRIORITY_COOKIE = 2;
    const PRIORITY_MANUAL_ADMIN = 3;

    /**
     * Track referral click and store in cookie.
     * 
     * @param string $referralCode
     * @param User|null $referrer
     * @param bool $lastClickWins If true, overwrite existing cookie; if false, only set if not exists
     * @param ReferralProgram|null $program Program context (optional, used for TTL + metadata cookies)
     * @param int|null $cookieTtlDays Override cookie TTL in days (optional)
     * @param string|null $localeSeen Locale the user saw when attribution occurred (optional)
     */
    public function trackClick(
        string $referralCode,
        ?User $referrer = null,
        bool $lastClickWins = true,
        ?ReferralProgram $program = null,
        ?int $cookieTtlDays = null,
        ?string $localeSeen = null
    ): void
    {
        // Validate code exists
        $referrer = $referrer ?? User::whereRaw('UPPER(referral_code) = ?', [strtoupper($referralCode)])->first();

        if (!$referrer) {
            return;
        }

        $cookieTtlDays = $cookieTtlDays
            ?? $program?->attribution_ttl_days
            ?? self::COOKIE_TTL;

        // Track click
        $click = ReferralClick::create([
            'referrer_user_id' => $referrer->id,
            'referral_code' => strtoupper($referralCode),
            'ip_hash' => hash('sha256', request()->ip()),
            'user_agent_hash' => hash('sha256', request()->userAgent()),
            'landing_url' => request()->fullUrl(),
            'session_id' => session()->getId(),
        ]);

        // Fire referral clicked event
        event(new \App\Events\ReferralClicked($click));

        // Store in cookie based on last-click-wins setting
        // Note: Cookie::has() doesn't work for queued cookies, so we check request cookies
        $hasCookie = request()->cookie(self::COOKIE_NAME);
        
        if ($lastClickWins || !$hasCookie) {
            $minutes = now()->addDays($cookieTtlDays)->diffInMinutes();

            // Primary attribution cookie (legacy + required)
            Cookie::queue(Cookie::make(self::COOKIE_NAME, $referralCode, $minutes));

            // Extra attribution metadata cookies (required by referral landing pages)
            Cookie::queue(Cookie::make('referral_referrer_id', (string) $referrer->id, $minutes));
            if ($program) {
                Cookie::queue(Cookie::make('referral_program_id', (string) $program->id, $minutes));
            }
            Cookie::queue(Cookie::make('referral_attributed_at', now()->toIso8601String(), $minutes));
            Cookie::queue(Cookie::make('referral_locale_seen', $localeSeen ?: app()->getLocale(), $minutes));
        }
    }

    /**
     * Get referral code from cookie.
     */
    public function getCodeFromCookie(): ?string
    {
        return request()->cookie(self::COOKIE_NAME);
    }

    /**
     * Clear referral cookie.
     */
    public function clearCookie(): void
    {
        Cookie::queue(
            Cookie::make(self::COOKIE_NAME, '', -1)
        );
    }

    /**
     * Create attribution for a user.
     * 
     * @param User $referee The user being referred
     * @param ReferralProgram $program The referral program
     * @param string|null $explicitCode Explicit code entered at signup
     * @param bool $lastClickWins Whether last click wins
     * @param int|null $attributionTtlDays TTL for attribution (from program config)
     * @return ReferralAttribution|null
     */
    public function createAttribution(
        User $referee,
        ReferralProgram $program,
        ?string $explicitCode = null,
        bool $lastClickWins = true,
        ?int $attributionTtlDays = null
    ): ?ReferralAttribution {
        $attributionTtlDays = $attributionTtlDays ?? $program->attribution_ttl_days ?? 7;

        // Priority 1: Explicit code entered at signup
        if ($explicitCode) {
            $referrer = User::whereRaw('UPPER(referral_code) = ?', [strtoupper($explicitCode)])->first();
            
            if ($referrer && $this->isValidAttribution($referee, $referrer, $program, $attributionTtlDays)) {
                return $this->createAttributionRecord(
                    $referee,
                    $referrer,
                    $program,
                    $explicitCode,
                    ReferralAttribution::METHOD_CODE,
                    self::PRIORITY_EXPLICIT_CODE
                );
            }
        }

        // Priority 2: Cookie-based attribution
        $cookieCode = $this->getCodeFromCookie();
        if ($cookieCode) {
            $referrer = User::whereRaw('UPPER(referral_code) = ?', [strtoupper($cookieCode)])->first();
            
            if ($referrer && $this->isValidAttribution($referee, $referrer, $program, $attributionTtlDays)) {
                // Check if click is within TTL
                // Get the most recent click for this code (either from current session or any recent click)
                $click = ReferralClick::where('referral_code', strtoupper($cookieCode))
                    ->orderBy('created_at', 'desc')
                    ->first();

                // If no click found or click is within TTL, create attribution
                if (!$click || $click->created_at->addDays($attributionTtlDays)->isFuture()) {
                    return $this->createAttributionRecord(
                        $referee,
                        $referrer,
                        $program,
                        $cookieCode,
                        ReferralAttribution::METHOD_LINK,
                        self::PRIORITY_COOKIE
                    );
                }
            }
        }

        // No valid attribution found
        return null;
    }

    /**
     * Create attribution record manually (admin function).
     */
    public function createManualAttribution(
        User $referee,
        User $referrer,
        ReferralProgram $program,
        string $code,
        ?string $notes = null
    ): ReferralAttribution {
        return $this->createAttributionRecord(
            $referee,
            $referrer,
            $program,
            $code,
            ReferralAttribution::METHOD_MANUAL_ADMIN,
            self::PRIORITY_MANUAL_ADMIN,
            $notes
        );
    }

    /**
     * Create attribution record.
     */
    protected function createAttributionRecord(
        User $referee,
        User $referrer,
        ReferralProgram $program,
        string $code,
        string $method,
        int $priority,
        ?string $notes = null
    ): ReferralAttribution {
        // Check if attribution already exists
        $existing = ReferralAttribution::where('referee_user_id', $referee->id)
            ->where('program_id', $program->id)
            ->first();

        if ($existing) {
            // If existing has higher priority (lower number = higher priority), don't overwrite
            if ($existing->priority < $priority) {
                return $existing;
            }
            
            // If same priority but confirmed, don't overwrite
            if ($existing->priority === $priority && $existing->status === ReferralAttribution::STATUS_CONFIRMED) {
                return $existing;
            }
        }

        // Create or update attribution
        $attribution = ReferralAttribution::updateOrCreate(
            [
                'referee_user_id' => $referee->id,
                'program_id' => $program->id,
            ],
            [
                'referrer_user_id' => $referrer->id,
                'code_used' => strtoupper($code),
                'attributed_at' => now(),
                'attribution_method' => $method,
                'status' => ReferralAttribution::STATUS_PENDING,
                'priority' => $priority,
                'metadata' => [
                    'notes' => $notes,
                ],
            ]
        );

        // Update user's referred_by_user_id if not set
        if (!$referee->referred_by_user_id) {
            $referee->update([
                'referred_by_user_id' => $referrer->id,
                'referred_at' => now(),
            ]);
        }

        return $attribution;
    }

    /**
     * Check if attribution is valid.
     */
    protected function isValidAttribution(
        User $referee,
        User $referrer,
        ReferralProgram $program,
        int $ttlDays
    ): bool {
        // Can't refer yourself
        if ($referee->id === $referrer->id) {
            return false;
        }

        // Check if user is blocked
        if ($referee->referral_blocked || $referrer->referral_blocked) {
            return false;
        }

        // Check program eligibility
        if (!$program->isEligibleForUser($referee)) {
            return false;
        }

        // Check if attribution already exists and is within TTL
        $existing = ReferralAttribution::where('referee_user_id', $referee->id)
            ->where('referrer_user_id', $referrer->id)
            ->where('program_id', $program->id)
            ->where('attributed_at', '>=', now()->subDays($ttlDays))
            ->first();

        if ($existing) {
            return false;
        }

        return true;
    }

    /**
     * Confirm attribution (after fraud check).
     */
    public function confirmAttribution(ReferralAttribution $attribution, ?string $notes = null): bool
    {
        return $attribution->confirm();
    }

    /**
     * Reject attribution.
     */
    public function rejectAttribution(ReferralAttribution $attribution, string $reason): bool
    {
        return $attribution->reject($reason);
    }

    /**
     * Get attribution for a user and program.
     */
    public function getAttribution(User $referee, ReferralProgram $program): ?ReferralAttribution
    {
        return ReferralAttribution::where('referee_user_id', $referee->id)
            ->where('program_id', $program->id)
            ->where('status', ReferralAttribution::STATUS_CONFIRMED)
            ->orderBy('priority', 'asc')
            ->first();
    }
}

