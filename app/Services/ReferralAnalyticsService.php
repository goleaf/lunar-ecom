<?php

namespace App\Services;

use App\Models\ReferralProgram;
use App\Models\ReferralCode;
use App\Models\ReferralAnalytics;
use App\Models\ReferralEvent;
use App\Models\ReferralTracking;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Referral Analytics Service
 * 
 * Handles aggregation and calculation of referral analytics.
 */
class ReferralAnalyticsService
{
    /**
     * Aggregate analytics for a program or code.
     */
    public function aggregateAnalytics(
        ?ReferralProgram $program = null,
        ?ReferralCode $code = null,
        Carbon $startDate,
        Carbon $endDate,
        string $level = ReferralAnalytics::LEVEL_DAILY
    ): void {
        $query = ReferralTracking::query();

        if ($program) {
            $query->whereHas('referralCode', function ($q) use ($program) {
                $q->where('referral_program_id', $program->id);
            });
        }

        if ($code) {
            $query->where('referral_code_id', $code->id);
        }

        $query->whereBetween('created_at', [$startDate, $endDate]);

        $trackings = $query->get();

        // Group by date based on level
        $grouped = $trackings->groupBy(function ($tracking) use ($level) {
            return match($level) {
                ReferralAnalytics::LEVEL_DAILY => $tracking->created_at->format('Y-m-d'),
                ReferralAnalytics::LEVEL_WEEKLY => $tracking->created_at->format('Y-W'),
                ReferralAnalytics::LEVEL_MONTHLY => $tracking->created_at->format('Y-m'),
                default => $tracking->created_at->format('Y-m-d'),
            };
        });

        foreach ($grouped as $dateKey => $items) {
            $date = match($level) {
                ReferralAnalytics::LEVEL_DAILY => Carbon::parse($dateKey),
                ReferralAnalytics::LEVEL_WEEKLY => Carbon::parse($dateKey . '-1'), // Start of week
                ReferralAnalytics::LEVEL_MONTHLY => Carbon::parse($dateKey . '-01'),
                default => Carbon::parse($dateKey),
            };

            $clicks = $items->where('event_type', ReferralTracking::EVENT_CLICK)->count();
            $signups = $items->where('event_type', ReferralTracking::EVENT_SIGNUP)->count();
            $purchases = $items->where('event_type', ReferralTracking::EVENT_PURCHASE)->count();
            $converted = $items->where('converted', true)->count();

            // Get revenue from events
            $events = ReferralEvent::whereHas('referralCode', function ($q) use ($program, $code) {
                if ($program) {
                    $q->where('referral_program_id', $program->id);
                }
                if ($code) {
                    $q->where('id', $code->id);
                }
            })
            ->whereBetween('created_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()])
            ->whereHas('order')
            ->with('order')
            ->get();

            $revenue = $events->sum(function ($event) {
                return $event->order?->total->value ?? 0;
            });

            $firstPurchases = $events->where('event_type', ReferralEvent::EVENT_FIRST_PURCHASE)->count();
            $repeatPurchases = $events->where('event_type', ReferralEvent::EVENT_REPEAT_PURCHASE)->count();

            // Get rewards issued
            $rewards = ReferralReward::whereHas('program', function ($q) use ($program) {
                if ($program) {
                    $q->where('id', $program->id);
                }
            })
            ->whereBetween('created_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()])
            ->get();

            $rewardsIssued = $rewards->count();
            $rewardsValue = $rewards->sum('reward_value');

            // Calculate conversion rates
            $clickToSignupRate = $clicks > 0 ? round(($signups / $clicks) * 100, 2) : 0;
            $signupToPurchaseRate = $signups > 0 ? round(($purchases / $signups) * 100, 2) : 0;
            $overallConversionRate = $clicks > 0 ? round(($purchases / $clicks) * 100, 2) : 0;

            // Upsert analytics record
            ReferralAnalytics::updateOrCreate(
                [
                    'referral_program_id' => $program?->id,
                    'referral_code_id' => $code?->id,
                    'date' => $date->format('Y-m-d'),
                    'aggregation_level' => $level,
                ],
                [
                    'clicks' => $clicks,
                    'signups' => $signups,
                    'first_purchases' => $firstPurchases,
                    'repeat_purchases' => $repeatPurchases,
                    'total_orders' => $purchases,
                    'total_revenue' => $revenue,
                    'rewards_issued' => $rewardsIssued,
                    'rewards_value' => $rewardsValue,
                    'click_to_signup_rate' => $clickToSignupRate,
                    'signup_to_purchase_rate' => $signupToPurchaseRate,
                    'overall_conversion_rate' => $overallConversionRate,
                ]
            );
        }
    }

    /**
     * Get analytics summary for a program.
     */
    public function getProgramSummary(ReferralProgram $program, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?: now()->subDays(30);
        $endDate = $endDate ?: now();

        $analytics = ReferralAnalytics::where('referral_program_id', $program->id)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get();

        return [
            'total_clicks' => $analytics->sum('clicks'),
            'total_signups' => $analytics->sum('signups'),
            'total_first_purchases' => $analytics->sum('first_purchases'),
            'total_repeat_purchases' => $analytics->sum('repeat_purchases'),
            'total_orders' => $analytics->sum('total_orders'),
            'total_revenue' => $analytics->sum('total_revenue'),
            'total_rewards_issued' => $analytics->sum('rewards_issued'),
            'total_rewards_value' => $analytics->sum('rewards_value'),
            'avg_click_to_signup_rate' => $analytics->avg('click_to_signup_rate'),
            'avg_signup_to_purchase_rate' => $analytics->avg('signup_to_purchase_rate'),
            'avg_overall_conversion_rate' => $analytics->avg('overall_conversion_rate'),
        ];
    }

    /**
     * Get analytics summary for a code.
     */
    public function getCodeSummary(ReferralCode $code, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?: now()->subDays(30);
        $endDate = $endDate ?: now();

        $analytics = ReferralAnalytics::where('referral_code_id', $code->id)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get();

        return [
            'total_clicks' => $analytics->sum('clicks'),
            'total_signups' => $analytics->sum('signups'),
            'total_first_purchases' => $analytics->sum('first_purchases'),
            'total_repeat_purchases' => $analytics->sum('repeat_purchases'),
            'total_orders' => $analytics->sum('total_orders'),
            'total_revenue' => $analytics->sum('total_revenue'),
            'avg_click_to_signup_rate' => $analytics->avg('click_to_signup_rate'),
            'avg_signup_to_purchase_rate' => $analytics->avg('signup_to_purchase_rate'),
            'avg_overall_conversion_rate' => $analytics->avg('overall_conversion_rate'),
        ];
    }
}

