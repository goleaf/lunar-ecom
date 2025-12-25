<?php

namespace App\Listeners;

use App\Events\ReferralCodeClicked;
use App\Services\ReferralService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ProcessReferralClick implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected ReferralService $referralService
    ) {}

    public function handle(ReferralCodeClicked $event): void
    {
        $this->referralService->trackClick(
            $event->referralCode,
            $event->sessionId,
            $event->ipAddress,
            $event->userAgent,
            request()->header('referer'),
            request()->fullUrl()
        );
    }
}

