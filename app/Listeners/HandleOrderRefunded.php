<?php

namespace App\Listeners;

use App\Events\OrderRefunded;
use App\Services\ReferralRewardReversalService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Handle order refunded event - reverse referral rewards.
 */
class HandleOrderRefunded implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected ReferralRewardReversalService $reversalService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(OrderRefunded $event): void
    {
        $order = $event->order;
        $reason = $event->reason ?? 'Order refunded';

        Log::info('Processing referral reward reversal for refunded order', [
            'order_id' => $order->id,
            'refund_amount' => $event->refundAmount,
            'reason' => $reason,
        ]);

        $result = $this->reversalService->reverseOrderRewards($order, $reason, 'refund');

        if ($result['success']) {
            Log::info('Referral rewards reversed successfully', [
                'order_id' => $order->id,
                'reversed_count' => $result['reversed_count'] ?? 0,
                'errors_count' => $result['errors_count'] ?? 0,
            ]);
        } else {
            Log::error('Failed to reverse referral rewards', [
                'order_id' => $order->id,
                'reason' => $result['reason'] ?? 'Unknown error',
            ]);
        }
    }
}


