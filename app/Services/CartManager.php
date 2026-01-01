<?php

namespace App\Services;

use App\Contracts\CartManagerInterface;
use App\Services\CartPricingEngine;
use Lunar\Models\Cart;
use Lunar\Models\CartLine;
use Lunar\Base\Purchasable;
use Lunar\Facades\CartSession;
use Lunar\Models\Discount;

class CartManager implements CartManagerInterface
{
    public function __construct(
        protected CartSessionService $cartSession,
        protected CartPricingEngine $pricingEngine
    ) {}

    /**
     * Add an item to the cart
     */
    public function addItem(Purchasable $item, int $quantity): CartLine
    {
        // Validate quantity
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than 0');
        }

        // Validate item is purchasable / available.
        // Lunar purchasables expose availability via methods, and some variants use a string
        // `purchasable` attribute (always|in_stock|never).
        if (method_exists($item, 'isAvailable') && !$item->isAvailable()) {
            throw new \InvalidArgumentException('Item is not purchasable');
        }

        if (isset($item->purchasable)) {
            $purchasable = (string) $item->purchasable;
            if ($purchasable === 'never' || $purchasable === '0' || $purchasable === 'false') {
                throw new \InvalidArgumentException('Item is not purchasable');
            }
        }

        $cart = $this->cartSession->getOrCreate();

        // Check if item already exists in cart
        $existingLine = $cart->lines()
            ->where('purchasable_type', get_class($item))
            ->where('purchasable_id', $item->id)
            ->first();

        if ($existingLine) {
            $newQuantity = $existingLine->quantity + $quantity;
            
            // Validate stock availability for updated quantity
            $this->validateStockAvailability($item, $newQuantity);
            
            // Update quantity of existing line
            $existingLine->update([
                'quantity' => $newQuantity
            ]);
            
            // Recalculate cart totals and repricing
            $this->calculateTotals();
            $this->pricingEngine->repriceCart($cart, 'quantity_changed');
            
            return $existingLine;
        }

        // Validate stock availability for new item
        $this->validateStockAvailability($item, $quantity);

        // Create new cart line
        $cartLine = $cart->lines()->create([
            'purchasable_type' => get_class($item),
            'purchasable_id' => $item->id,
            'quantity' => $quantity,
        ]);

        // Recalculate cart totals and repricing
        $this->calculateTotals();
        $this->pricingEngine->repriceCart($cart, 'quantity_changed');

        return $cartLine;
    }

    /**
     * Remove an item from the cart
     */
    public function removeItem(int $lineId): void
    {
        $cart = $this->cartSession->current();
        
        if (!$cart) {
            throw new \RuntimeException('No active cart found');
        }

        $cartLine = $cart->lines()->find($lineId);
        
        if (!$cartLine) {
            throw new \InvalidArgumentException('Cart line not found');
        }

        $cartLine->delete();
        
        // Recalculate cart totals and repricing
        $this->calculateTotals();
        $this->pricingEngine->repriceCart($cart, 'quantity_changed');
    }

    /**
     * Calculate cart totals
     */
    public function calculateTotals(): Cart
    {
        $cart = $this->cartSession->current();
        
        if (!$cart) {
            throw new \RuntimeException('No active cart found');
        }

        // Use Lunar's cart calculation pipeline
        $cart->calculate();
        
        return $cart;
    }

    /**
     * Apply discount to cart
     */
    public function applyDiscount(string $couponCode): void
    {
        $cart = $this->cartSession->current();
        
        if (!$cart) {
            throw new \RuntimeException('No active cart found');
        }

        // Validate coupon code format
        if (empty(trim($couponCode))) {
            throw new \InvalidArgumentException('Coupon code cannot be empty');
        }

        // Find the discount by coupon code
        $discount = Discount::where('coupon', $couponCode)
            ->active()
            ->first();

        if (!$discount) {
            throw new \InvalidArgumentException('Invalid or expired coupon code');
        }

        // Check if discount is already applied
        if ($cart->coupon_code === $couponCode) {
            throw new \InvalidArgumentException('Coupon code is already applied');
        }

        // Apply discount to cart (fires repricing / cache invalidation events).
        $cart->update(['coupon_code' => $couponCode]);
        
        // Recalculate totals with discount applied and repricing
        $this->calculateTotals();
        // NOTE: Custom repricing can be expensive and is triggered elsewhere via cart repricing events.
        // Avoid double repricing here.
        // $this->pricingEngine->repriceCart($cart, 'promotion_changed');
    }

    /**
     * Update cart line quantity
     */
    public function updateQuantity(int $lineId, int $quantity): void
    {
        $cart = $this->cartSession->current();
        
        if (!$cart) {
            throw new \RuntimeException('No active cart found');
        }

        $cartLine = $cart->lines()->find($lineId);
        
        if (!$cartLine) {
            throw new \InvalidArgumentException('Cart line not found');
        }

        if ($quantity <= 0) {
            $this->removeItem($lineId);
        } else {
            // Validate stock availability for updated quantity
            $this->validateStockAvailability($cartLine->purchasable, $quantity);
            
            $cartLine->update(['quantity' => $quantity]);
            $this->calculateTotals();
            $this->pricingEngine->repriceCart($cart, 'quantity_changed');
        }
    }

    /**
     * Clear all items from cart
     */
    public function clear(): void
    {
        $cart = $this->cartSession->current();
        
        if ($cart) {
            // Delete all cart lines
            foreach ($cart->lines as $line) {
                $line->delete();
            }
            
            $cart->update(['coupon_code' => null]);
            $this->calculateTotals();
            $this->pricingEngine->repriceCart($cart, 'promotion_changed');
        }
    }

    /**
     * Get cart item count
     */
    public function getItemCount(): int
    {
        $cart = $this->cartSession->current();
        
        if (!$cart) {
            return 0;
        }

        // Refresh the cart to get latest data
        $cart->refresh();
        $cart->load('lines');

        return $cart->lines->sum('quantity');
    }

    /**
     * Validate stock availability for a purchasable item
     */
    protected function validateStockAvailability(Purchasable $item, int $quantity): void
    {
        // Lunar product variants track stock via attributes, not declared properties.
        // Prefer explicit handling for variants.
        if ($item instanceof \Lunar\Models\ProductVariant || $item instanceof \App\Models\ProductVariant) {
            $stock = (int) ($item->stock ?? 0);
            $backorder = (bool) ($item->backorder ?? false);

            if (!$backorder && $stock < $quantity) {
                throw new \InvalidArgumentException(
                    "Insufficient stock. Only {$stock} items available."
                );
            }
        } elseif (isset($item->stock)) {
            $stock = (int) $item->stock;
            if ($stock < $quantity) {
                throw new \InvalidArgumentException(
                    "Insufficient stock. Only {$stock} items available."
                );
            }
        }

        // Check minimum quantity requirements
        if (isset($item->min_quantity)) {
            if ($quantity < $item->min_quantity) {
                throw new \InvalidArgumentException(
                    "Minimum quantity is {$item->min_quantity} for this item."
                );
            }
        }

        // Check if item is shippable when required
        if (property_exists($item, 'shippable') && $item->shippable === false) {
            // This could be extended to check shipping requirements
        }
    }

    /**
     * Remove discount from cart
     */
    public function removeDiscount(): void
    {
        $cart = $this->cartSession->current();
        
        if (!$cart) {
            throw new \RuntimeException('No active cart found');
        }

        $cart->update(['coupon_code' => null]);
        $this->calculateTotals();
        // NOTE: Custom repricing can be expensive and is triggered elsewhere via cart repricing events.
        // Avoid double repricing here.
        // $this->pricingEngine->repriceCart($cart, 'promotion_changed');
    }

    /**
     * Force repricing of cart (e.g., before checkout).
     */
    public function forceReprice(): Cart
    {
        $cart = $this->cartSession->current();
        
        if (!$cart) {
            throw new \RuntimeException('No active cart found');
        }

        return $this->pricingEngine->repriceCart($cart, 'forced');
    }

    /**
     * Check if cart has items
     */
    public function hasItems(): bool
    {
        return $this->getItemCount() > 0;
    }

    /**
     * Get cart total value
     */
    public function getTotal(): ?int
    {
        $cart = $this->cartSession->current();
        
        if (!$cart) {
            return null;
        }

        return $cart->total?->value;
    }
}