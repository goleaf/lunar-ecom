<?php

namespace App\Listeners;

use App\Events\UserSignedUp;
use App\Models\ReferralAttribution;
use App\Services\ReferralRewardService;
use App\Services\ReferralFraudService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Handle user signup event with referral attribution.
 */
class HandleUserSignedUp implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected ReferralRewardService $rewardService,
        protected ReferralFraudService $fraudService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(UserSignedUp $event): void
    {
        $user = $event->user;

        Log::info('User signed up with referral', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'referral_code' => $event->referralCode,
            'attribution_id' => $event->attributionId,
        ]);

        // Find attribution if provided
        $attribution = null;
        if ($event->attributionId) {
            $attribution = ReferralAttribution::find($event->attributionId);
        } else {
            // Find pending attribution
            $attribution = ReferralAttribution::where('referee_user_id', $user->id)
                ->where('status', ReferralAttribution::STATUS_PENDING)
                ->orderBy('priority', 'asc')
                ->first();
        }

        if (!$attribution) {
            Log::info('No referral attribution found for user', [
                'user_id' => $user->id,
            ]);
            return;
        }

        $program = $attribution->program;
        $referrer = $attribution->referrer;

        if (!$program || !$referrer) {
            return;
        }

        // Perform fraud checks
        $fraudCheckResult = $this->fraudService->runAllChecks($user, $referrer, $program);

        if ($fraudCheckResult['is_fraudulent']) {
            $attribution->reject($fraudCheckResult['reason']);
            
            Log::warning('Referral attribution rejected due to fraud', [
                'attribution_id' => $attribution->id,
                'reason' => $fraudCheckResult['reason'],
            ]);
            return;
        }

        // Confirm attribution
        $attribution->confirm();

        Log::info('Referral attribution confirmed', [
            'attribution_id' => $attribution->id,
            'referrer_id' => $referrer->id,
            'program_id' => $program->id,
        ]);

        // Process signup rewards
        foreach ($program->activeRules as $rule) {
            if ($rule->trigger_event === \App\Models\ReferralRule::TRIGGER_SIGNUP) {
                $this->rewardService->issueRewards($rule, $user, $referrer);
                
                Log::info('Signup reward issued', [
                    'rule_id' => $rule->id,
                    'referee_id' => $user->id,
                    'referrer_id' => $referrer->id,
                ]);
            }
        }
    }
}


