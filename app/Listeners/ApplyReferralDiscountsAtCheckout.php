<?php

namespace App\Listeners;

use App\Services\ReferralCheckoutService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\CartRepricingEvent;
use Lunar\Events\OrderStatusChanged;
use Illuminate\Support\Facades\Auth;

/**
 * Apply referral discounts when cart is updated or order is created.
 */
class ApplyReferralDiscountsAtCheckout implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected ReferralCheckoutService $checkoutService
    ) {}

    /**
     * Handle cart repricing event (when cart is updated).
     */
    public function handleCartRepricing(CartRepricingEvent $event): void
    {
        $cart = $event->cart;
        $user = $cart->user ?? Auth::user();

        if (!$user) {
            return;
        }

        // Process referral discounts at checkout stage
        $result = $this->checkoutService->processReferralDiscounts($cart, $user, 'checkout');

        if ($result['applied']) {
            // Save discount application record
            $this->checkoutService->saveDiscountApplicationRecord($cart, $user, $result, 'checkout');
        }
    }

    /**
     * Handle order status changed event (when order is paid).
     */
    public function handleOrderPaid(OrderStatusChanged $event): void
    {
        $order = $event->order;
        
        // Only process when order is paid
        if (!$order->isPaid()) {
            return;
        }

        $user = $order->user ?? Auth::user();

        if (!$user) {
            return;
        }

        // Process referral discounts at payment stage
        $result = $this->checkoutService->processReferralDiscounts($order, $user, 'payment');

        if ($result['applied']) {
            // Save discount application to order metadata
            $this->checkoutService->saveDiscountApplication($order, $result);

            // Save discount application record
            $this->checkoutService->saveDiscountApplicationRecord($order, $user, $result, 'payment');
        }
    }
}

