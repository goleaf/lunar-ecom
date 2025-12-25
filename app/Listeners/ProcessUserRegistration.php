<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;
use App\Services\ReferralService;
use App\Models\ReferralCode;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ProcessUserRegistration implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected ReferralService $referralService
    ) {}

    public function handle(Registered $event): void
    {
        // Check if user came from a referral link
        $referralCodeId = session()->get('referral_code_id');

        if ($referralCodeId) {
            $code = ReferralCode::find($referralCodeId);

            if ($code && $code->isValid()) {
                $customer = $event->user->customers()->first();

                // Process referral signup
                event(new \App\Events\ReferralSignup(
                    $code,
                    $event->user,
                    $customer
                ));
            }

            // Clear session
            session()->forget('referral_code_id');
            session()->forget('referral_code_slug');
        }
    }
}

