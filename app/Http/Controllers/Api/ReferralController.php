<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReferralProgram;
use App\Models\ReferralCode;
use App\Services\ReferralService;
use App\Services\ReferralCodeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Lunar\Models\Customer;

class ReferralController extends Controller
{
    public function __construct(
        protected ReferralService $referralService,
        protected ReferralCodeService $codeService
    ) {}

    /**
     * Get user's referral codes.
     */
    public function myCodes(Request $request): JsonResponse
    {
        $user = Auth::user();
        $customer = $user->customers()->first();

        $programs = $this->referralService->getActivePrograms($user, $customer);
        
        $codes = [];
        foreach ($programs as $program) {
            $code = $this->referralService->getOrCreateReferralCode($program, $user, $customer);
            $codes[] = [
                'program_id' => $program->id,
                'program_name' => $program->name,
                'code' => $code->code,
                'slug' => $code->slug,
                'url' => $code->getReferralUrl(),
                'stats' => [
                    'clicks' => $code->total_clicks,
                    'signups' => $code->total_signups,
                    'purchases' => $code->total_purchases,
                    'revenue' => $code->total_revenue,
                ],
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $codes,
        ]);
    }

    /**
     * Get referral code by slug.
     */
    public function getCode(string $slug): JsonResponse
    {
        $code = $this->referralService->getReferralCodeBySlugOrCode($slug);

        if (!$code || !$code->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Referral code not found or invalid',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'code' => $code->code,
                'program' => $code->program->name,
                'url' => $code->getReferralUrl(),
            ],
        ]);
    }

    /**
     * Track referral link click.
     */
    public function trackClick(Request $request, string $slug): JsonResponse
    {
        $code = $this->referralService->getReferralCodeBySlugOrCode($slug);

        if (!$code || !$code->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Referral code not found or invalid',
            ], 404);
        }

        // Store in session for later tracking
        session()->put('referral_code', $code->id);

        // Track click
        event(new \App\Events\ReferralCodeClicked(
            $code,
            session()->getId(),
            $request->ip(),
            $request->userAgent()
        ));

        return response()->json([
            'success' => true,
            'message' => 'Click tracked',
        ]);
    }

    /**
     * Get referral statistics.
     */
    public function stats(Request $request): JsonResponse
    {
        $user = Auth::user();
        $customer = $user->customers()->first();

        $programs = $this->referralService->getActivePrograms($user, $customer);
        
        $stats = [];
        foreach ($programs as $program) {
            $code = $this->referralService->getOrCreateReferralCode($program, $user, $customer);
            
            $stats[] = [
                'program_id' => $program->id,
                'program_name' => $program->name,
                'code' => $code->code,
                'url' => $code->getReferralUrl(),
                'clicks' => $code->total_clicks,
                'signups' => $code->total_signups,
                'purchases' => $code->total_purchases,
                'revenue' => $code->total_revenue,
                'current_uses' => $code->current_uses,
                'max_uses' => $code->max_uses,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get user's rewards.
     */
    public function rewards(Request $request): JsonResponse
    {
        $user = Auth::user();
        $customer = $user->customers()->first();

        $rewards = \App\Models\ReferralReward::query()
            ->where(function ($q) use ($user, $customer) {
                if ($user) {
                    $q->where('user_id', $user->id);
                }
                if ($customer) {
                    $q->orWhere('customer_id', $customer->id);
                }
            })
            ->where('status', \App\Models\ReferralReward::STATUS_ISSUED)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->get()
            ->map(function ($reward) {
                return [
                    'id' => $reward->id,
                    'type' => $reward->reward_type,
                    'value' => $reward->reward_value,
                    'currency' => $reward->currency?->code,
                    'discount_code' => $reward->discount_code,
                    'expires_at' => $reward->expires_at?->toIso8601String(),
                    'times_used' => $reward->times_used,
                    'max_uses' => $reward->max_uses,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $rewards,
        ]);
    }
}

