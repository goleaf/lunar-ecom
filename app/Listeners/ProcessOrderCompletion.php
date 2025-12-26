<?php

namespace App\Listeners;

use App\Events\CheckoutCompleted;
use App\Services\ReferralService;
use App\Models\ReferralCode;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ProcessOrderCompletion implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected ReferralService $referralService
    ) {}

    public function handle(CheckoutCompleted $event): void
    {
        $order = $event->order;
        $user = $order->user;
        $customer = $order->customer;

        if (!$user) {
            return;
        }

        // Check if user was referred
        $referralCodeId = session()->get('referral_code_id');

        // Also check if there's a referral event for this user
        if (!$referralCodeId) {
            $signupEvent = \App\Models\ReferralEvent::where('referee_id', $user->id)
                ->where('event_type', \App\Models\ReferralEvent::EVENT_SIGNUP)
                ->where('status', \App\Models\ReferralEvent::STATUS_PROCESSED)
                ->first();

            if ($signupEvent) {
                $referralCodeId = $signupEvent->referral_code_id;
            }
        }

        if ($referralCodeId) {
            $code = ReferralCode::find($referralCodeId);

            if ($code && $code->isValid()) {
                // Check if this is the first purchase
                $isFirstPurchase = !\App\Models\ReferralEvent::where('referee_id', $user->id)
                    ->where('event_type', \App\Models\ReferralEvent::EVENT_FIRST_PURCHASE)
                    ->exists();

                // Process referral purchase
                event(new \App\Events\ReferralPurchase(
                    $code,
                    $order,
                    $user,
                    $customer,
                    $isFirstPurchase
                ));
            }
        }
    }
}


