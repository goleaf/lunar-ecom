<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\ReferralCheckoutService;
use Lunar\Models\Cart;
use Illuminate\Support\Facades\Auth;

/**
 * Middleware to apply referral discounts at checkout.
 */
class ApplyReferralDiscountsMiddleware
{
    public function __construct(
        protected ReferralCheckoutService $checkoutService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only apply to authenticated users
        if (!Auth::check()) {
            return $response;
        }

        $user = Auth::user();

        // Get user's cart
        $cart = Cart::where('user_id', $user->id)
            ->whereNull('completed_at')
            ->first();

        if (!$cart) {
            return $response;
        }

        // Process referral discounts
        $result = $this->checkoutService->processReferralDiscounts($cart, $user, 'checkout');

        if ($result['applied']) {
            // Store in session for display
            session()->put('referral_discounts_applied', $result);
        }

        return $response;
    }
}


