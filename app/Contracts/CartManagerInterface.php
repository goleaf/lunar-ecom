<?php

namespace App\Contracts;

use Lunar\Models\Cart;
use Lunar\Models\CartLine;
use Lunar\Base\Purchasable;

interface CartManagerInterface
{
    /**
     * Add an item to the cart
     */
    public function addItem(Purchasable $item, int $quantity): CartLine;

    /**
     * Remove an item from the cart
     */
    public function removeItem(int $lineId): void;

    /**
     * Update cart line quantity
     */
    public function updateQuantity(int $lineId, int $quantity): void;

    /**
     * Calculate cart totals
     */
    public function calculateTotals(): Cart;

    /**
     * Apply discount to cart
     */
    public function applyDiscount(string $couponCode): void;

    /**
     * Remove discount from cart
     */
    public function removeDiscount(): void;

    /**
     * Clear all items from cart
     */
    public function clear(): void;

    /**
     * Get cart item count
     */
    public function getItemCount(): int;

    /**
     * Check if cart has items
     */
    public function hasItems(): bool;

    /**
     * Get cart total value
     */
    public function getTotal(): ?int;
}