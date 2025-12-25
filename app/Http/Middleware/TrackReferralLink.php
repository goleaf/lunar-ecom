<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\ReferralService;

class TrackReferralLink
{
    public function __construct(
        protected ReferralService $referralService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check for referral code in URL or query parameter
        $referralSlug = $request->route('ref') ?? $request->query('ref');

        if ($referralSlug) {
            $code = $this->referralService->getReferralCodeBySlugOrCode($referralSlug);

            if ($code && $code->isValid()) {
                // Store in session for later use
                session()->put('referral_code_id', $code->id);
                session()->put('referral_code_slug', $referralSlug);

                // Track click (async via event)
                event(new \App\Events\ReferralCodeClicked(
                    $code,
                    session()->getId(),
                    $request->ip(),
                    $request->userAgent()
                ));
            }
        }

        return $next($request);
    }
}

