<?php

namespace App\Listeners;

use App\Events\ReferralPurchase;
use App\Services\ReferralService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ProcessReferralPurchase implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected ReferralService $referralService
    ) {}

    public function handle(ReferralPurchase $event): void
    {
        $this->referralService->processPurchase(
            $event->referralCode,
            $event->order,
            $event->referee,
            $event->refereeCustomer,
            $event->isFirstPurchase
        );
    }
}


