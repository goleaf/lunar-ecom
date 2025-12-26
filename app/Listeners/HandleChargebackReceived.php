<?php

namespace App\Listeners;

use App\Events\ChargebackReceived;
use App\Services\ReferralRewardReversalService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Handle chargeback received event - reverse referral rewards.
 */
class HandleChargebackReceived implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected ReferralRewardReversalService $reversalService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(ChargebackReceived $event): void
    {
        $order = $event->order;
        $reason = $event->reason ?? 'Chargeback received';
        $chargebackId = $event->chargebackId;

        Log::warning('Processing referral reward reversal for chargeback', [
            'order_id' => $order->id,
            'chargeback_id' => $chargebackId,
            'chargeback_amount' => $event->amount,
            'reason' => $reason,
        ]);

        $result = $this->reversalService->reverseOrderRewards($order, $reason, 'chargeback');

        if ($result['success']) {
            Log::info('Referral rewards reversed due to chargeback', [
                'order_id' => $order->id,
                'chargeback_id' => $chargebackId,
                'reversed_count' => $result['reversed_count'] ?? 0,
                'errors_count' => $result['errors_count'] ?? 0,
            ]);
        } else {
            Log::error('Failed to reverse referral rewards for chargeback', [
                'order_id' => $order->id,
                'chargeback_id' => $chargebackId,
                'reason' => $result['reason'] ?? 'Unknown error',
            ]);
        }
    }
}


