<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;
use App\Services\ReferralAttributionService;
use App\Models\ReferralProgram;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ProcessUserRegistration implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected ReferralAttributionService $attributionService
    ) {}

    public function handle(Registered $event): void
    {
        $user = $event->user;

        // Generate referral code for new user if not exists
        if (!$user->referral_code) {
            $user->generateReferralCode();
        }

        // Get active referral programs
        $programs = ReferralProgram::active()->get();

        foreach ($programs as $program) {
            if (!$program->isEligibleForUser($user)) {
                continue;
            }

            // Get explicit code from request (if user entered one)
            $explicitCode = request()->input('referral_code');

            // Create attribution with priority system
            $attribution = $this->attributionService->createAttribution(
                $user,
                $program,
                $explicitCode,
                $program->last_click_wins ?? true,
                $program->attribution_ttl_days ?? 7
            );

            if ($attribution) {
                // Process signup event for rules
                // This will be handled by another listener/service
                break; // Only attribute to first eligible program
            }
        }

        // Clear session
        session()->forget('referral_code');
        session()->forget('referral_code_id');
    }
}

