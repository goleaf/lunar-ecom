<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Models\ReferralAttribution;
use App\Models\ReferralRewardIssuance;
use App\Models\ReferralRule;
use App\Services\ReferralRewardService;
use App\Services\ReferralFraudService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Handle order paid event for referral rewards.
 */
class HandleOrderPaid implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected ReferralRewardService $rewardService,
        protected ReferralFraudService $fraudService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(OrderPaid $event): void
    {
        $order = $event->order;
        $user = $order->user;

        if (!$user) {
            Log::warning('Order paid but no user associated', [
                'order_id' => $order->id,
            ]);
            return;
        }

        Log::info('Processing referral rewards for paid order', [
            'order_id' => $order->id,
            'user_id' => $user->id,
            'order_total' => $order->total->value ?? 0,
        ]);

        // Find confirmed attribution
        $attribution = ReferralAttribution::where('referee_user_id', $user->id)
            ->where('status', ReferralAttribution::STATUS_CONFIRMED)
            ->orderBy('priority', 'asc')
            ->first();

        if (!$attribution) {
            Log::info('No referral attribution found for order', [
                'order_id' => $order->id,
                'user_id' => $user->id,
            ]);
            return;
        }

        $program = $attribution->program;
        $referrer = $attribution->referrer;

        if (!$program || !$referrer) {
            return;
        }

        // Check if this is the referee's first paid order for this program
        $isFirstOrder = !ReferralRewardIssuance::where('referee_user_id', $user->id)
            ->where('referral_program_id', $program->id)
            ->whereHas('rule', function ($query) {
                $query->where('trigger_event', ReferralRule::TRIGGER_FIRST_ORDER_PAID);
            })
            ->where('status', ReferralRewardIssuance::STATUS_ISSUED)
            ->exists();

        // Get count of paid orders for the referee
        $paidOrdersCount = $user->orders()
            ->whereHas('transactions', fn ($q) => $q->where('success', true)->where('type', 'capture'))
            ->where('status', '!=', 'refunded')
            ->count();

        DB::beginTransaction();

        try {
            // Process rules for first_order_paid and nth_order_paid
            foreach ($program->activeRules as $rule) {
                $shouldProcess = false;

                if ($rule->trigger_event === ReferralRule::TRIGGER_FIRST_ORDER_PAID && $isFirstOrder) {
                    $shouldProcess = true;
                } elseif ($rule->trigger_event === ReferralRule::TRIGGER_NTH_ORDER_PAID && $rule->nth_order === $paidOrdersCount) {
                    $shouldProcess = true;
                }

                if ($shouldProcess) {
                    // Issue rewards
                    $this->rewardService->issueRewards($rule, $user, $referrer, $order);

                    Log::info('Referral reward issued for paid order', [
                        'rule_id' => $rule->id,
                        'trigger_event' => $rule->trigger_event,
                        'order_id' => $order->id,
                        'referee_id' => $user->id,
                        'referrer_id' => $referrer->id,
                        'is_first_order' => $isFirstOrder,
                        'order_number' => $paidOrdersCount,
                    ]);
                }
            }

            // Log everything
            $this->logOrderPaid($order, $user, $attribution, $program, $referrer);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to process referral rewards for paid order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Log order paid event.
     */
    protected function logOrderPaid($order, $user, $attribution, $program, $referrer): void
    {
        \App\Models\ReferralAuditLog::create([
            'actor' => 'system',
            'action' => 'order_paid',
            'before' => [
                'order_id' => $order->id,
                'order_status' => $order->status,
                'attribution_status' => $attribution->status,
            ],
            'after' => [
                'order_id' => $order->id,
                'order_status' => 'paid',
                'attribution_id' => $attribution->id,
                'program_id' => $program->id,
                'referrer_id' => $referrer->id,
                'referee_id' => $user->id,
                'order_total' => $order->total->value ?? 0,
            ],
            'timestamp' => now(),
        ]);
    }
}


