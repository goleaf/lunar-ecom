<?php

namespace App\Livewire\Frontend\Pages;

use App\Services\CheckoutService;
use Livewire\Component;
use Lunar\Facades\CartSession;

class CheckoutIndex extends Component
{
    public function mount(): void
    {
        $cart = CartSession::current();

        if (!$cart || $cart->lines->isEmpty()) {
            $this->redirectRoute('frontend.cart.index', navigate: true);
            return;
        }

        $checkoutService = app(CheckoutService::class);

        // If cart is already locked, send them back to cart with a message.
        if ($checkoutService->isCartLocked($cart)) {
            session()->flash('error', 'Your cart is currently being checked out. Please wait or try again.');
            $this->redirectRoute('frontend.cart.index', navigate: true);
            return;
        }

        // Ensure a lock exists (idempotent per cart in service).
        $checkoutService->startCheckout($cart);
    }

    public function render()
    {
        $cart = CartSession::current();

        if (!$cart || $cart->lines->isEmpty()) {
            // In case cart became empty after mount (rare), bounce to cart.
            $this->redirectRoute('frontend.cart.index', navigate: true);
        }

        $checkoutService = app(CheckoutService::class);
        $lock = $checkoutService->getActiveLock($cart);

        $cart->calculate();

        return view('frontend.checkout.index', compact('cart', 'lock'));
    }
}



