<?php

namespace App\Listeners;

use Lunar\Events\OrderPaymentStatusChanged;
use App\Services\ReferralRewardService;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ProcessReferralOrderPayment implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected ReferralRewardService $rewardService
    ) {}

    public function handle(OrderPaymentStatusChanged $event): void
    {
        $order = $event->order;

        // Only process if payment is captured/paid
        if ($order->status !== 'payment-received' && $order->status !== 'completed') {
            return;
        }

        // Get customer/user
        $customer = $order->customer;
        if (!$customer || !$customer->user_id) {
            return;
        }

        $user = User::find($customer->user_id);
        if (!$user) {
            return;
        }

        // Check if this is first paid order
        $paidOrdersCount = $order->customer->orders()
            ->whereIn('status', ['payment-received', 'completed'])
            ->where('id', '!=', $order->id)
            ->count();

        if ($paidOrdersCount === 0) {
            // First paid order
            $this->rewardService->processReward(
                $user,
                \App\Models\ReferralRule::TRIGGER_FIRST_ORDER_PAID,
                $order
            );
        } else {
            // Check for nth order
            $this->checkNthOrder($user, $order, $paidOrdersCount + 1);
        }
    }

    protected function checkNthOrder(User $user, $order, int $orderNumber): void
    {
        // Get rules for nth order
        $rules = \App\Models\ReferralRule::where('trigger_event', \App\Models\ReferralRule::TRIGGER_NTH_ORDER_PAID)
            ->where('is_active', true)
            ->get();

        foreach ($rules as $rule) {
            if ($rule->nth_order === $orderNumber) {
                $this->rewardService->processReward(
                    $user,
                    \App\Models\ReferralRule::TRIGGER_NTH_ORDER_PAID,
                    $order
                );
                break; // Only process first matching rule
            }
        }
    }
}

