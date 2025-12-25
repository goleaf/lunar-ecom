<?php

namespace App\Listeners;

use App\Events\ReferralSignup;
use App\Services\ReferralService;
use App\Services\ReferralRewardService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ProcessReferralSignup implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected ReferralService $referralService,
        protected ReferralRewardService $rewardService
    ) {}

    public function handle(ReferralSignup $event): void
    {
        // Process signup event
        $referralEvent = $this->referralService->processSignup(
            $event->referralCode,
            $event->referee,
            $event->refereeCustomer
        );

        // Issue welcome discount for referee if configured
        if ($referralEvent && $event->referralCode->program->referee_rewards) {
            $this->rewardService->issueRefereeWelcomeDiscount(
                $event->referralCode->program,
                $event->referee->id,
                $event->refereeCustomer?->id
            );
        }
    }
}

