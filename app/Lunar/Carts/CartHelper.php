<?php

namespace App\Lunar\Carts;

use Illuminate\Support\Collection;
use Lunar\Facades\CartSession;
use Lunar\Models\Cart;
use Lunar\Models\CartLine;
use Lunar\Models\ProductVariant;

/**
 * Helper class for working with Lunar Carts.
 * 
 * Provides convenience methods for managing carts, cart lines, and cart calculations.
 * See: https://docs.lunarphp.com/1.x/reference/carts
 */
class CartHelper
{
    /**
     * Get the current cart from session.
     * 
     * @param bool $createIfNotExists Whether to create a cart if none exists
     * @return Cart|null
     */
    public static function current(bool $createIfNotExists = false): ?Cart
    {
        return CartSession::current();
    }

    /**
     * Create a new cart.
     * 
     * @param int $currencyId
     * @param int $channelId
     * @param int|null $userId
     * @param int|null $customerId
     * @return Cart
     */
    public static function create(int $currencyId, int $channelId, ?int $userId = null, ?int $customerId = null): Cart
    {
        return Cart::create([
            'currency_id' => $currencyId,
            'channel_id' => $channelId,
            'user_id' => $userId,
            'customer_id' => $customerId,
        ]);
    }

    /**
     * Add a purchasable item to the cart.
     * 
     * @param ProductVariant $purchasable
     * @param int $quantity
     * @param array $meta Optional metadata
     * @return Cart
     * @throws \Lunar\Exceptions\Carts\CartException
     */
    public static function add(ProductVariant $purchasable, int $quantity, array $meta = []): Cart
    {
        CartSession::add($purchasable, $quantity, $meta);
        return CartSession::current();
    }

    /**
     * Add multiple lines to the cart.
     * 
     * @param array|Collection $lines Array of ['purchasable' => ProductVariant, 'quantity' => int, 'meta' => array]
     * @return Cart
     */
    public static function addLines(array|Collection $lines): Cart
    {
        CartSession::addLines($lines);
        return CartSession::current();
    }

    /**
     * Update a single cart line.
     * 
     * @param int $cartLineId
     * @param int $quantity
     * @param array|null $meta Optional metadata
     * @return Cart
     */
    public static function updateLine(int $cartLineId, int $quantity, ?array $meta = null): Cart
    {
        CartSession::updateLine($cartLineId, $quantity, $meta);
        return CartSession::current();
    }

    /**
     * Update multiple cart lines.
     * 
     * @param array|Collection $lines Array of ['id' => int, 'quantity' => int, 'meta' => array]
     * @return Cart
     */
    public static function updateLines(array|Collection $lines): Cart
    {
        CartSession::updateLines($lines);
        return CartSession::current();
    }

    /**
     * Remove a cart line.
     * 
     * @param int $cartLineId
     * @return Cart
     */
    public static function remove(int $cartLineId): Cart
    {
        CartSession::remove($cartLineId);
        return CartSession::current();
    }

    /**
     * Clear all lines from the cart.
     * 
     * @return Cart
     */
    public static function clear(): Cart
    {
        CartSession::clear();
        return CartSession::current();
    }

    /**
     * Calculate cart totals (hydrate the cart).
     * 
     * This will populate all calculated values like total, subTotal, taxTotal, etc.
     * 
     * @param Cart|null $cart If null, uses current cart
     * @return Cart
     */
    public static function calculate(?Cart $cart = null): Cart
    {
        $cart = $cart ?? CartSession::current();
        if ($cart) {
            $cart->calculate();
        }
        return $cart;
    }

    /**
     * Get cart totals as an array.
     * 
     * @param Cart|null $cart If null, uses current cart
     * @return array
     */
    public static function getTotals(?Cart $cart = null): array
    {
        $cart = $cart ?? CartSession::current();
        if (!$cart) {
            return [];
        }

        $cart->calculate();

        return [
            'total' => $cart->total?->formatted,
            'subTotal' => $cart->subTotal?->formatted,
            'subTotalDiscounted' => $cart->subTotalDiscounted?->formatted,
            'shippingTotal' => $cart->shippingTotal?->formatted,
            'taxTotal' => $cart->taxTotal?->formatted,
            'discountTotal' => $cart->discountTotal?->formatted,
            'shippingSubTotal' => $cart->shippingSubTotal?->formatted,
        ];
    }

    /**
     * Set shipping address on cart.
     * 
     * @param array $addressData
     * @param Cart|null $cart If null, uses current cart
     * @return Cart
     */
    public static function setShippingAddress(array $addressData, ?Cart $cart = null): Cart
    {
        $cart = $cart ?? CartSession::current();
        if ($cart) {
            $cart->setShippingAddress($addressData);
            $cart->calculate();
        }
        return $cart;
    }

    /**
     * Set billing address on cart.
     * 
     * @param array $addressData
     * @param Cart|null $cart If null, uses current cart
     * @return Cart
     */
    public static function setBillingAddress(array $addressData, ?Cart $cart = null): Cart
    {
        $cart = $cart ?? CartSession::current();
        if ($cart) {
            $cart->setBillingAddress($addressData);
            $cart->calculate();
        }
        return $cart;
    }

    /**
     * Associate cart to a user.
     * 
     * @param \App\Models\User $user
     * @param string $policy 'merge' or 'override'
     * @return Cart
     */
    public static function associateUser(\App\Models\User $user, string $policy = 'merge'): Cart
    {
        CartSession::associate($user, $policy);
        return CartSession::current();
    }

    /**
     * Associate cart to a customer.
     * 
     * @param \Lunar\Models\Customer $customer
     * @return Cart
     */
    public static function associateCustomer(\Lunar\Models\Customer $customer): Cart
    {
        CartSession::setCustomer($customer);
        return CartSession::current();
    }

    /**
     * Forget the cart (remove from session and optionally delete).
     * 
     * @param bool $delete Whether to delete the cart from database
     * @return void
     */
    public static function forget(bool $delete = true): void
    {
        CartSession::forget(delete: $delete);
    }

    /**
     * Use a specific cart for the session.
     * 
     * @param Cart $cart
     * @return void
     */
    public static function use(Cart $cart): void
    {
        CartSession::use($cart);
    }

    /**
     * Get cart fingerprint for change detection.
     * 
     * @param Cart|null $cart If null, uses current cart
     * @return string|null
     */
    public static function getFingerprint(?Cart $cart = null): ?string
    {
        $cart = $cart ?? CartSession::current();
        return $cart?->fingerprint();
    }

    /**
     * Check if cart fingerprint matches (detect changes).
     * 
     * @param string $fingerprint
     * @param Cart|null $cart If null, uses current cart
     * @return bool
     */
    public static function checkFingerprint(string $fingerprint, ?Cart $cart = null): bool
    {
        $cart = $cart ?? CartSession::current();
        if (!$cart) {
            return false;
        }

        try {
            $cart->checkFingerprint($fingerprint);
            return true;
        } catch (\Lunar\Exceptions\FingerprintMismatchException $e) {
            return false;
        }
    }

    /**
     * Get estimated shipping for a cart.
     * 
     * @param array $addressData Address data for estimation
     * @param bool $setOverride Whether to set as shipping override
     * @param Cart|null $cart If null, uses current cart
     * @return \Lunar\DataTypes\ShippingOption|null
     */
    public static function getEstimatedShipping(array $addressData, bool $setOverride = false, ?Cart $cart = null): ?\Lunar\DataTypes\ShippingOption
    {
        $cart = $cart ?? CartSession::current();
        return $cart?->getEstimatedShipping($addressData, setOverride: $setOverride);
    }

    /**
     * Set shipping estimation parameters.
     * 
     * @param array $params
     * @return void
     */
    public static function estimateShippingUsing(array $params): void
    {
        CartSession::estimateShippingUsing($params);
    }

    /**
     * Get current cart with shipping estimation.
     * 
     * @param bool $estimateShipping Whether to use shipping override
     * @return Cart|null
     */
    public static function currentWithShipping(bool $estimateShipping = true): ?Cart
    {
        return CartSession::current(estimateShipping: $estimateShipping);
    }
}


