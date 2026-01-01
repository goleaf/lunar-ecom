<?php

namespace App\Livewire\Frontend\Pages;

use Livewire\Component;
use Lunar\Facades\CartSession;

class CartIndex extends Component
{
    public function render()
    {
        $cart = CartSession::current();

        if ($cart) {
            $cart->calculate();
        }

        $transparencyService = app(\App\Services\CartTransparencyService::class);
        $cartBreakdown = $transparencyService->getCartBreakdown($cart);

        return view('frontend.cart.index', compact('cart', 'cartBreakdown'));
    }
}


