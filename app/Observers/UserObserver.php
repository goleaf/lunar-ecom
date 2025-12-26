<?php

namespace App\Observers;

use App\Models\User;
use App\Services\ReferralCodeGeneratorService;

class UserObserver
{
    public function __construct(
        protected ReferralCodeGeneratorService $codeGenerator
    ) {}

    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        // Auto-generate referral code if not provided
        if (!$user->referral_code) {
            $user->generateReferralCode();
        }
    }
}


