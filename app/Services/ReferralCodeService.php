<?php

namespace App\Services;

use App\Models\ReferralCode;
use App\Models\ReferralProgram;
use App\Models\User;
use Lunar\Models\Customer;
use Illuminate\Support\Str;

/**
 * Referral Code Service
 * 
 * Handles generation and management of referral codes.
 */
class ReferralCodeService
{
    /**
     * Generate a unique referral code.
     */
    public function generateCode(ReferralProgram $program, ?string $prefix = null): string
    {
        $prefix = $prefix ?: strtoupper(substr($program->handle, 0, 3));
        $code = $prefix . strtoupper(Str::random(8));

        while (ReferralCode::where('code', $code)->exists()) {
            $code = $prefix . strtoupper(Str::random(8));
        }

        return $code;
    }

    /**
     * Generate a URL-friendly slug.
     */
    public function generateSlug(string $code): string
    {
        $slug = Str::slug($code);

        while (ReferralCode::where('slug', $slug)->exists()) {
            $slug = Str::slug($code . '-' . Str::random(4));
        }

        return $slug;
    }

    /**
     * Create a referral code.
     */
    public function createCode(
        ReferralProgram $program,
        ?User $user = null,
        ?Customer $customer = null,
        ?string $customCode = null,
        ?string $customSlug = null
    ): ReferralCode {
        $code = $customCode ?: $this->generateCode($program);
        $slug = $customSlug ?: $this->generateSlug($code);

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
     * Get or create a referral code for a user/customer.
     */
    public function getOrCreateCode(
        ReferralProgram $program,
        ?User $user = null,
        ?Customer $customer = null
    ): ReferralCode {
        $query = ReferralCode::where('referral_program_id', $program->id);
        
        if ($user) {
            $query->where('referrer_id', $user->id);
        } elseif ($customer) {
            $query->where('referrer_customer_id', $customer->id);
        }

        $existing = $query->first();

        if ($existing && $existing->isValid()) {
            return $existing;
        }

        return $this->createCode($program, $user, $customer);
    }
}

