<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\ReferralAttributionService;
use App\Models\User;
use App\Models\ReferralProgram;

class TrackReferralLink
{
    public function __construct(
        protected ReferralAttributionService $attributionService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check for referral code in URL or query parameter
        $referralCode = $request->route('ref') ?? $request->query('ref');

        if ($referralCode) {
            // Find referrer by code (case-insensitive)
            $referrer = User::whereRaw('UPPER(referral_code) = ?', [strtoupper($referralCode)])
                ->orWhereRaw('UPPER(referral_link_slug) = ?', [strtoupper($referralCode)])
                ->first();

            if ($referrer && $referrer->status === 'active' && !$referrer->referral_blocked) {
                // Get active programs
                $programs = ReferralProgram::active()->get();

                foreach ($programs as $program) {
                    if ($program->isEligibleForUser(null)) {
                        // Track click with last-click-wins setting from program
                        $this->attributionService->trackClick(
                            $referrer->referral_code,
                            $referrer,
                            $program->last_click_wins ?? true
                        );
                        break; // Only track for first eligible program
                    }
                }

                // Store in session for later use
                session()->put('referral_code', $referrer->referral_code);
                session()->put('referral_code_id', $referrer->id);
            }
        }

        return $next($request);
    }
}

