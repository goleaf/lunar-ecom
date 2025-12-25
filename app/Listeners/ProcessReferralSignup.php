<?php

namespace App\Listeners;

use App\Events\ReferralSignup;
use Illuminate\Auth\Events\Registered;
use App\Services\ReferralRewardService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ProcessReferralSignup implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected ReferralRewardService $rewardService
    ) {}

    public function handle(ReferralSignup $event): void
    {
        // Process signup reward
        $this->rewardService->processReward(
            $event->user,
            \App\Models\ReferralRule::TRIGGER_SIGNUP
        );
    }

    /**
     * Handle user registration for signup rewards.
     */
    public function handleSignupReward(Registered $event): void
    {
        // Process signup reward after user registration
        // This is called after attribution is created
        $this->rewardService->processReward(
            $event->user,
            \App\Models\ReferralRule::TRIGGER_SIGNUP
        );
    }
}
