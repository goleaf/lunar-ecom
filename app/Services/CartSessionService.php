<?php

namespace App\Services;

use Lunar\Models\Cart;
use App\Models\User;
use Illuminate\Support\Facades\Session;
use Lunar\Facades\CartSession;
use Lunar\Models\Currency;
use Lunar\Models\Channel;

class CartSessionService
{
    /**
     * Get the current cart from session or create a new one
     */
    public function current(): ?Cart
    {
        return CartSession::current();
    }

    /**
     * Create a new cart instance
     */
    public function create(): Cart
    {
        // Get default currency and channel
        $defaultCurrency = Currency::getDefault();
        $defaultChannel = Channel::getDefault();
        
        if (!$defaultCurrency) {
            throw new \RuntimeException('No default currency found. Please ensure Lunar is properly configured.');
        }
        
        if (!$defaultChannel) {
            throw new \RuntimeException('No default channel found. Please ensure Lunar is properly configured.');
        }

        $cart = Cart::create([
            'currency_id' => $defaultCurrency->id,
            'channel_id' => $defaultChannel->id,
        ]);

        // Store cart ID in session
        Session::put(config('lunar.cart_session.session_key'), $cart->id);

        return $cart;
    }

    /**
     * Associate cart with authenticated user
     */
    public function associate(User $user): void
    {
        $cart = $this->current();
        
        if ($cart) {
            $customer = $user->latestCustomer();
            
            $cart->update([
                'user_id' => $user->id,
                'customer_id' => $customer?->id,
            ]);
        }
    }

    /**
     * Get or create cart for current session
     */
    public function getOrCreate(): Cart
    {
        $cart = $this->current();
        
        if (!$cart) {
            $cart = $this->create();
        }

        return $cart;
    }

    /**
     * Clear cart from session
     */
    public function forget(): void
    {
        $cart = $this->current();
        
        if ($cart && config('lunar.cart_session.delete_on_forget', true)) {
            $cart->delete();
        }

        Session::forget(config('lunar.cart_session.session_key'));
    }

    /**
     * Merge guest cart with user cart on authentication
     */
    public function mergeOnAuth(User $user): void
    {
        $guestCart = $this->current();
        $userCart = $user->carts()->active()->first();

        if ($guestCart && $userCart && $guestCart->id !== $userCart->id) {
            // Merge guest cart lines into user cart
            foreach ($guestCart->lines as $line) {
                $userCart->lines()->create([
                    'purchasable_type' => $line->purchasable_type,
                    'purchasable_id' => $line->purchasable_id,
                    'quantity' => $line->quantity,
                    'meta' => $line->meta,
                ]);
            }

            // Delete guest cart and update session
            $guestCart->delete();
            Session::put(config('lunar.cart_session.session_key'), $userCart->id);
        } elseif ($guestCart && !$userCart) {
            // Associate guest cart with user
            $this->associate($user);
        } elseif (!$guestCart && $userCart) {
            // Use existing user cart
            Session::put(config('lunar.cart_session.session_key'), $userCart->id);
        }
    }
}