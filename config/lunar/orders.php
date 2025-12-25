<?php

use Lunar\Base\OrderReferenceGenerator;

return [
    /*
    |--------------------------------------------------------------------------
    | Order Reference Format
    |--------------------------------------------------------------------------
    |
    | Specify the format for the order reference generator to use.
    |
    */
    'reference_format' => [
        /**
         * Optional prefix for the order reference
         */
        'prefix' => null,

        /**
         * STR_PAD_LEFT: 00001965
         * STR_PAD_RIGHT: 19650000
         * STR_PAD_BOTH: 00196500
         */
        'padding_direction' => STR_PAD_LEFT,

        /**
         * 00001965
         * AAAA1965
         */
        'padding_character' => '0',

        /**
         * If the length specified below is smaller than the length
         * of the Order ID, then no padding will take place.
         */
        'length' => 8,
    ],

    /*
    |--------------------------------------------------------------------------
    | Order Reference Generator
    |--------------------------------------------------------------------------
    |
    | Here you can specify how you want your order references to be generated
    | when you create an order from a cart.
    |
    */
    'reference_generator' => OrderReferenceGenerator::class,

    /*
    |--------------------------------------------------------------------------
    | Draft Status
    |--------------------------------------------------------------------------
    |
    | When a draft order is created from a cart, we need an initial status for
    | the order that's created. Define that here, it can be anything that would
    | make sense for the store you're building.
    |
    */
    'draft_status' => 'pending',

    'statuses' => [
        'pending' => [
            'label' => 'Pending',
            'color' => '#f59e0b',
            'mailers' => [],
            'notifications' => [
                \App\Notifications\OrderStatusUpdatedNotification::class,
            ],
            'favourite' => true,
        ],

        'processing' => [
            'label' => 'Processing',
            'color' => '#3b82f6',
            'mailers' => [],
            'notifications' => [
                \App\Notifications\OrderStatusUpdatedNotification::class,
            ],
            'favourite' => true,
        ],

        'shipped' => [
            'label' => 'Shipped',
            'color' => '#10b981',
            'mailers' => [],
            'notifications' => [
                \App\Notifications\OrderShippedNotification::class,
            ],
            'favourite' => true,
        ],

        'completed' => [
            'label' => 'Completed',
            'color' => '#059669',
            'mailers' => [],
            'notifications' => [
                \App\Notifications\OrderStatusUpdatedNotification::class,
            ],
            'favourite' => true,
        ],

        'cancelled' => [
            'label' => 'Cancelled',
            'color' => '#ef4444',
            'mailers' => [],
            'notifications' => [
                \App\Notifications\OrderStatusUpdatedNotification::class,
            ],
            'favourite' => true,
        ],

        // Legacy statuses (kept for backward compatibility)
        'awaiting-payment' => [
            'label' => 'Awaiting Payment',
            'color' => '#848a8c',
            'mailers' => [],
            'notifications' => [],
            'favourite' => false,
        ],

        'payment-offline' => [
            'label' => 'Payment Offline',
            'color' => '#0A81D7',
            'mailers' => [],
            'notifications' => [],
            'favourite' => false,
        ],

        'payment-received' => [
            'label' => 'Payment Received',
            'color' => '#6a67ce',
            'mailers' => [],
            'notifications' => [],
            'favourite' => false,
        ],

        'dispatched' => [
            'label' => 'Dispatched',
            'mailers' => [],
            'notifications' => [],
            'favourite' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Order Pipelines
    |--------------------------------------------------------------------------
    |
    | Define which pipelines should be run throughout an order's lifecycle.
    | The default ones provided should suit most needs, however you are
    | free to add your own as you see fit.
    |
    | Each pipeline class will be run from top to bottom.
    |
    | See: https://docs.lunarphp.com/1.x/extending/orders#pipelines
    |
    */
    'pipelines' => [
        'creation' => [
            Lunar\Pipelines\Order\Creation\FillOrderFromCart::class,
            Lunar\Pipelines\Order\Creation\CreateOrderLines::class,
            Lunar\Pipelines\Order\Creation\CreateOrderAddresses::class,
            Lunar\Pipelines\Order\Creation\CreateShippingLine::class,
            Lunar\Pipelines\Order\Creation\CleanUpOrderLines::class,
            Lunar\Pipelines\Order\Creation\MapDiscountBreakdown::class,
            // Add your custom order pipelines here:
            // App\Lunar\Orders\Pipelines\OrderCreation\CustomOrderPipeline::class,
        ],
    ],

];
