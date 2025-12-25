<?php

namespace App\Lunar\Shipping\Modifiers;

use Closure;
use Lunar\Base\ShippingModifier;
use Lunar\DataTypes\Price;
use Lunar\DataTypes\ShippingOption;
use Lunar\Facades\ShippingManifest;
use Lunar\Models\Cart;
use Lunar\Models\TaxClass;

/**
 * Example custom shipping modifier.
 * 
 * This is a scaffolding example. For production use, refer to:
 * https://docs.lunarphp.com/1.x/extending/shipping
 * 
 * Shipping modifiers determine what shipping options are available
 * for a cart and add them to the ShippingManifest.
 */
class CustomShippingModifier extends ShippingModifier
{
    /**
     * Handle the shipping modifier.
     * 
     * This method is called during cart calculation to determine available
     * shipping options for the cart.
     *
     * @param Cart $cart
     * @param Closure $next
     * @return Cart
     */
    public function handle(Cart $cart, Closure $next): Cart
    {
        // Only add shipping options if cart has shippable items
        if ($cart->lines->isEmpty() || !$cart->hasShippableItems()) {
            return $next($cart);
        }

        // Get the tax class for shipping
        $taxClass = TaxClass::first();

        // Add a basic delivery option
        ShippingManifest::addOption(
            new ShippingOption(
                name: 'Basic Delivery',
                description: 'A basic delivery option (5-7 business days)',
                identifier: 'BASDEL',
                price: new Price(500, $cart->currency, 1), // 500 = $5.00 (in cents)
                taxClass: $taxClass
            )
        );

        // Add an express delivery option
        ShippingManifest::addOption(
            new ShippingOption(
                name: 'Express Delivery',
                description: 'Express delivery option (1-2 business days)',
                identifier: 'EXDEL',
                price: new Price(1000, $cart->currency, 1), // 1000 = $10.00 (in cents)
                taxClass: $taxClass
            )
        );

        // Add a free shipping option (e.g., for orders over a certain amount)
        if ($cart->subTotal->value >= 50000) { // $500.00
            ShippingManifest::addOption(
                new ShippingOption(
                    name: 'Free Shipping',
                    description: 'Free shipping on orders over $500',
                    identifier: 'FREESHIP',
                    price: new Price(0, $cart->currency, 1),
                    taxClass: $taxClass
                )
            );
        }

        // Add a pickup option (collect = true means customer picks up)
        ShippingManifest::addOption(
            new ShippingOption(
                name: 'Pick up in store',
                description: 'Pick your order up in store',
                identifier: 'PICKUP',
                price: new Price(0, $cart->currency, 1),
                taxClass: $taxClass,
                // collect: true indicates this is a collection/pickup option
                collect: true
            )
        );

        // Alternative: Add multiple options at once
        // ShippingManifest::addOptions(collect([
        //     new ShippingOption(
        //         name: 'Basic Delivery',
        //         description: 'A basic delivery option',
        //         identifier: 'BASDEL',
        //         price: new Price(500, $cart->currency, 1),
        //         taxClass: $taxClass
        //     ),
        //     new ShippingOption(
        //         name: 'Express Delivery',
        //         description: 'Express delivery option',
        //         identifier: 'EXDEL',
        //         price: new Price(1000, $cart->currency, 1),
        //         taxClass: $taxClass
        //     )
        // ]));

        return $next($cart);
    }
}


