<?php

namespace App\Http\Middleware;

use App\Services\CheckoutService;
use Closure;
use Illuminate\Http\Request;
use Lunar\Facades\CartSession;

/**
 * Middleware to protect cart from modifications during checkout.
 */
class ProtectCheckoutCart
{
    public function __construct(
        protected CheckoutService $checkoutService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $cart = CartSession::current();

        if ($cart && $this->checkoutService->isCartLocked($cart)) {
            // Check if this is the same session that locked it
            $lock = $this->checkoutService->getActiveLock($cart);
            
            if (!$lock || $lock->session_id !== session()->getId()) {
                return response()->json([
                    'message' => 'Cart is currently being checked out and cannot be modified',
                    'error' => 'checkout_in_progress',
                ], 423); // 423 Locked
            }
        }

        return $next($request);
    }
}


