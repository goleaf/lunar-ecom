<?php

namespace App\Listeners;

use App\Events\ReferralClicked;
use App\Services\ReferralAttributionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Handle referral link click event.
 */
class HandleReferralClicked implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected ReferralAttributionService $attributionService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(ReferralClicked $event): void
    {
        $click = $event->click;

        Log::info('Referral link clicked', [
            'click_id' => $click->id,
            'referrer_user_id' => $click->referrer_user_id,
            'referral_code' => $click->referral_code,
            'landing_url' => $click->landing_url,
        ]);

        // Attribution is handled by ReferralAttributionService
        // This listener can be used for additional tracking/analytics
    }
}


