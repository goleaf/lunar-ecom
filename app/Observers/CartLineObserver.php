<?php

namespace App\Observers;

use App\Events\CartQuantityChanged;
use App\Events\CartVariantChanged;
use Lunar\Models\CartLine;

/**
 * Cart Line Observer - Triggers repricing events on cart line changes.
 */
class CartLineObserver
{
    /**
     * Handle cart line created event.
     */
    public function created(CartLine $cartLine): void
    {
        $this->triggerRepricing($cartLine, 'created');
    }

    /**
     * Handle cart line updated event.
     */
    public function updated(CartLine $cartLine): void
    {
        $changes = $cartLine->getChanges();
        
        // Quantity changed
        if (isset($changes['quantity'])) {
            event(new CartQuantityChanged($cartLine->cart));
        }
        
        // Purchasable changed (variant change)
        if (isset($changes['purchasable_type']) || isset($changes['purchasable_id'])) {
            event(new CartVariantChanged($cartLine->cart));
        }
    }

    /**
     * Handle cart line deleted event.
     */
    public function deleted(CartLine $cartLine): void
    {
        if ($cartLine->cart) {
            event(new CartQuantityChanged($cartLine->cart));
        }
    }

    /**
     * Trigger repricing for cart line changes.
     */
    protected function triggerRepricing(CartLine $cartLine, string $action): void
    {
        if ($cartLine->cart) {
            event(new CartQuantityChanged($cartLine->cart, ['action' => $action]));
        }
    }
}

