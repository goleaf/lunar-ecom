<?php

namespace App\Filament\Resources\ReferralProgramResource\Pages;

use App\Filament\Resources\ReferralProgramResource;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use App\Models\ReferralClick;
use App\Models\ReferralAttribution;
use App\Models\ReferralRewardIssuance;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\ChartWidget;

class ReferralProgramAnalytics extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ReferralProgramResource::class;

    protected static string $view = 'filament.resources.referral-program-resource.pages.referral-program-analytics';

    public function mount($record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string
    {
        return 'Analytics: ' . $this->record->name;
    }

    public function getHeading(): string
    {
        return 'Analytics: ' . $this->record->name;
    }

    public function getStats(): array
    {
        $programId = $this->record->id;

        // Clicks - count clicks for users who have referral codes for this program
        $clicks = ReferralClick::whereHas('referrer', function ($query) use ($programId) {
            $query->whereHas('referralCodes', function ($q) use ($programId) {
                $q->where('referral_program_id', $programId);
            });
        })->count();

        // Signups
        $signups = ReferralAttribution::where('program_id', $programId)
            ->where('status', ReferralAttribution::STATUS_CONFIRMED)
            ->count();

        // First Purchases
        $firstPurchases = ReferralRewardIssuance::whereHas('rule', function ($query) use ($programId) {
            $query->where('referral_program_id', $programId)
                ->where('trigger_event', \App\Models\ReferralRule::TRIGGER_FIRST_ORDER_PAID);
        })
        ->where('status', ReferralRewardIssuance::STATUS_ISSUED)
        ->count();

        // Conversion Rates
        $clickToSignupRate = $clicks > 0 ? ($signups / $clicks) * 100 : 0;
        $signupToPurchaseRate = $signups > 0 ? ($firstPurchases / $signups) * 100 : 0;
        $clickToPurchaseRate = $clicks > 0 ? ($firstPurchases / $clicks) * 100 : 0;

        // Revenue
        $revenue = ReferralRewardIssuance::whereHas('rule', function ($query) use ($programId) {
            $query->where('referral_program_id', $programId);
        })
        ->whereHas('order')
        ->where('status', ReferralRewardIssuance::STATUS_ISSUED)
        ->with('order')
        ->get()
        ->sum(function ($issuance) {
            return $issuance->order ? $issuance->order->total->value : 0;
        });

        // Cost
        $cost = ReferralRewardIssuance::whereHas('rule', function ($query) use ($programId) {
            $query->where('referral_program_id', $programId);
        })
        ->where('status', ReferralRewardIssuance::STATUS_ISSUED)
        ->sum(DB::raw('COALESCE(referee_reward_value, 0) + COALESCE(referrer_reward_value, 0)'));

        return [
            'clicks' => $clicks,
            'signups' => $signups,
            'first_purchases' => $firstPurchases,
            'click_to_signup_rate' => round($clickToSignupRate, 2),
            'signup_to_purchase_rate' => round($signupToPurchaseRate, 2),
            'click_to_purchase_rate' => round($clickToPurchaseRate, 2),
            'revenue' => $revenue,
            'cost' => $cost,
            'roi' => $cost > 0 ? round((($revenue - $cost) / $cost) * 100, 2) : 0,
        ];
    }

    public function getFunnelData(): array
    {
        $programId = $this->record->id;

        // Get data for last 30 days
        $startDate = now()->subDays(30);

        $clicks = ReferralClick::whereHas('referrer', function ($query) use ($programId) {
            $query->whereHas('referralCodes', function ($q) use ($programId) {
                $q->where('referral_program_id', $programId);
            });
        })
        ->where('created_at', '>=', $startDate)
        ->count();

        $signups = ReferralAttribution::where('program_id', $programId)
            ->where('status', ReferralAttribution::STATUS_CONFIRMED)
            ->where('attributed_at', '>=', $startDate)
            ->count();

        $firstPurchases = ReferralRewardIssuance::whereHas('rule', function ($query) use ($programId) {
            $query->where('referral_program_id', $programId)
                ->where('trigger_event', \App\Models\ReferralRule::TRIGGER_FIRST_ORDER_PAID);
        })
        ->where('status', ReferralRewardIssuance::STATUS_ISSUED)
        ->where('issued_at', '>=', $startDate)
        ->count();

        return [
            ['stage' => 'Clicks', 'count' => $clicks, 'percentage' => 100],
            ['stage' => 'Signups', 'count' => $signups, 'percentage' => $clicks > 0 ? ($signups / $clicks) * 100 : 0],
            ['stage' => 'First Purchases', 'count' => $firstPurchases, 'percentage' => $signups > 0 ? ($firstPurchases / $signups) * 100 : 0],
        ];
    }

    public function getTopReferrers(): array
    {
        $programId = $this->record->id;

        return ReferralRewardIssuance::whereHas('rule', function ($query) use ($programId) {
            $query->where('referral_program_id', $programId);
        })
        ->where('status', ReferralRewardIssuance::STATUS_ISSUED)
        ->select('referrer_user_id', DB::raw('COUNT(*) as referral_count'))
        ->groupBy('referrer_user_id')
        ->orderBy('referral_count', 'desc')
        ->limit(10)
        ->get()
        ->map(function ($item) {
            $revenue = ReferralRewardIssuance::where('referrer_user_id', $item->referrer_user_id)
                ->whereHas('order')
                ->with('order')
                ->get()
                ->sum(function ($issuance) {
                    return $issuance->order ? $issuance->order->total->value : 0;
                });

            return [
                'user' => $item->referrer,
                'count' => $item->referral_count,
                'revenue' => $revenue,
            ];
        })
        ->toArray();
    }
}
