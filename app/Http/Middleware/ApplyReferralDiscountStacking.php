<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\ReferralDiscountStackingService;
use App\Models\ReferralAttribution;
use App\Models\ReferralRule;
use Lunar\Models\Cart;
use Illuminate\Support\Facades\Auth;

/**
 * Middleware to apply referral discount stacking logic to cart.
 */
class ApplyReferralDiscountStacking
{
    public function __construct(
        protected ReferralDiscountStackingService $stackingService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only apply to authenticated users with carts
        if (!Auth::check()) {
            return $response;
        }

        $user = Auth::user();
        $cart = Cart::where('user_id', $user->id)->first();

        if (!$cart) {
            return $response;
        }

        // Get active referral attribution
        $attribution = ReferralAttribution::where('referee_user_id', $user->id)
            ->where('status', ReferralAttribution::STATUS_CONFIRMED)
            ->first();

        if (!$attribution) {
            return $response;
        }

        // Get applicable rules for signup (for immediate discount)
        $rules = ReferralRule::where('referral_program_id', $attribution->program_id)
            ->where('trigger_event', ReferralRule::TRIGGER_SIGNUP)
            ->where('is_active', true)
            ->whereNotNull('referee_reward_type')
            ->orderBy('priority', 'desc')
            ->get();

        foreach ($rules as $rule) {
            // Apply stacking logic
            $result = $this->stackingService->applyReferralDiscount($cart, $rule, $user);
            
            if ($result['applied']) {
                break; // Only apply first applicable rule
            }
        }

        return $response;
    }
}


