<?php

use Lunar\Actions\Carts\GenerateFingerprint;

return [
    /*
    |--------------------------------------------------------------------------
    | Fingerprint Generator
    |--------------------------------------------------------------------------
    |
    | Specify which class should be used when generating a cart fingerprint.
    |
    */
    'fingerprint_generator' => GenerateFingerprint::class,

    /*
    |--------------------------------------------------------------------------
    | Authentication policy
    |--------------------------------------------------------------------------
    |
    | When a user logs in, by default, Lunar will merge the current (guest) cart
    | with the users current cart, if they have one.
    | Available options: 'merge', 'override'
    |
    */
    'auth_policy' => 'merge',

    /*
    |--------------------------------------------------------------------------
    | Cart Pipelines
    |--------------------------------------------------------------------------
    |
    | Define which pipelines should be run when performing cart calculations.
    | The default ones provided should suit most needs, however you are
    | free to add your own as you see fit.
    |
    | Each pipeline class will be run from top to bottom.
    |
    | See: https://docs.lunarphp.com/1.x/extending/carts#pipelines
    |
    */
    'pipelines' => [
        /*
         * Run these pipelines when the cart is calculating.
         * Pipelines run from top to bottom.
        */
        'cart' => [
            Lunar\Pipelines\Cart\CalculateLines::class,
            Lunar\Pipelines\Cart\ApplyShipping::class,
            Lunar\Pipelines\Cart\ApplyDiscounts::class,
            Lunar\Pipelines\Cart\CalculateTax::class,
            // Add your custom cart pipelines here:
            // App\Lunar\Cart\Pipelines\Cart\CustomCartPipeline::class,
            Lunar\Pipelines\Cart\Calculate::class,
        ],

        /*
         * Run these pipelines when the cart lines are being calculated.
        */
        'cart_lines' => [
            Lunar\Pipelines\CartLine\GetUnitPrice::class,
            // Add your custom cart line pipelines here:
            // App\Lunar\Cart\Pipelines\CartLine\CustomCartLinePipeline::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cart Validators
    |--------------------------------------------------------------------------
    |
    | Define validators for cart actions. Validators allow you to add custom
    | validation logic before cart actions are executed (e.g., adding items,
    | updating quantities, setting shipping options).
    |
    | If validation fails, a Lunar\Exceptions\CartException will be thrown.
    |
    | See: https://docs.lunarphp.com/1.x/extending/carts#action-validation
    |
    */
    'validators' => [
        'add_to_cart' => [
            // Add your custom validators here:
            // App\Lunar\Cart\Validation\CartLine\CartLineQuantityValidator::class,
        ],
        // Other action validators can be added here:
        // 'update_line' => [...],
        // 'set_shipping_option' => [...],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cart Actions
    |--------------------------------------------------------------------------
    |
    | Here you can decide what action should be run during a Carts lifecycle.
    | The default actions should be fine for most cases.
    |
    */
    'actions' => [
        'add_to_cart' => Lunar\Actions\Carts\AddOrUpdatePurchasable::class,
        'get_existing_cart_line' => Lunar\Actions\Carts\GetExistingCartLine::class,
        'update_cart_line' => Lunar\Actions\Carts\UpdateCartLine::class,
        'remove_from_cart' => Lunar\Actions\Carts\RemovePurchasable::class,
        'add_address' => Lunar\Actions\Carts\AddAddress::class,
        'set_shipping_option' => Lunar\Actions\Carts\SetShippingOption::class,
        'order_create' => Lunar\Actions\Carts\CreateOrder::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cart Action Validators
    |--------------------------------------------------------------------------
    |
    | You may wish to provide additional validation when actions executed on
    | the cart model. The defaults provided should be enough for most cases.
    |
    */
    'validators' => [

        'add_to_cart' => [
            Lunar\Validation\CartLine\CartLineQuantity::class,
            Lunar\Validation\CartLine\CartLineStock::class,
        ],

        'update_cart_line' => [
            Lunar\Validation\CartLine\CartLineQuantity::class,
            Lunar\Validation\CartLine\CartLineStock::class,
        ],

        'remove_from_cart' => [],

        'set_shipping_option' => [
            Lunar\Validation\Cart\ShippingOptionValidator::class,
        ],

        'order_create' => [
            Lunar\Validation\Cart\ValidateCartForOrderCreation::class,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Default eager loading
    |--------------------------------------------------------------------------
    |
    | When loading up a cart and doing calculations, there's a few relationships
    | that are used when it's running. Here you can define which relationships
    | should be eager loaded when these calculations take place.
    |
    */
    'eager_load' => [
        'currency',
        'lines.purchasable.taxClass',
        'lines.purchasable.values',
        'lines.purchasable.product.thumbnail',
        'lines.purchasable.prices.currency',
        'lines.purchasable.prices.priceable',
        'lines.purchasable.product',
        'lines.cart.currency',
    ],

    /*
    |--------------------------------------------------------------------------
    | Prune carts
    |--------------------------------------------------------------------------
    |
    | Should the cart models be pruned to prevent data build up and
    | some settings controlling how pruning should be determined
    |
    */
    'prune_tables' => [

        'enabled' => false,

        'pipelines' => [
            Lunar\Pipelines\CartPrune\PruneAfter::class,
            Lunar\Pipelines\CartPrune\WithoutOrders::class,
            Lunar\Pipelines\CartPrune\WhereNotMerged::class,
        ],

        'prune_interval' => 90, // days

    ],

    /*
    |--------------------------------------------------------------------------
    | Cart Pricing Engine Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the deterministic, auditable, real-time cart pricing engine.
    |
    */
    'pricing' => [
        // Automatically reprice cart on changes
        'auto_reprice' => true,

        // Enforce MAP (Minimum Advertised Price)
        'enforce_map' => true,

        // Enforce minimum price (prevent negative/zero prices)
        'enforce_minimum_price' => true,

        // Price expiration in hours (quote validity period)
        'price_expiration_hours' => 24,

        // Enable price hash for tamper detection
        'enable_price_hash' => true,

        // Store pricing snapshots in database (false = calculate on-the-fly)
        'store_snapshots' => false,
    ],
];
