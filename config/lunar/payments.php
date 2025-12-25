<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Payment Type
    |--------------------------------------------------------------------------
    |
    | Specify the default payment type to use when none is specified.
    |
    */
    'default' => env('PAYMENTS_TYPE', 'cash-in-hand'),

    /*
    |--------------------------------------------------------------------------
    | Payment Types
    |--------------------------------------------------------------------------
    |
    | Define different payment types and their configurations.
    | Each type specifies a driver and the order status to set when payment is released.
    |
    | To use a custom payment driver, first register it using Payments::extend()
    | in a service provider (e.g., AppServiceProvider::boot()).
    |
    | See: https://docs.lunarphp.com/1.x/reference/payments
    | See: https://docs.lunarphp.com/1.x/extending/payments
    |
    */
    'types' => [
        'cash-in-hand' => [
            'driver' => 'offline',
            'released' => 'payment-offline',
        ],
        // Stripe payment type (requires lunarphp/stripe package)
        // 'card' => [
        //     'driver' => 'stripe',
        //     'released' => 'payment-received',
        // ],
        // PayPal payment type (requires lunarphp/paypal package)
        // 'paypal' => [
        //     'driver' => 'paypal',
        //     'released' => 'payment-received',
        // ],
        // Example: Custom payment type (register driver first in service provider)
        // 'custom' => [
        //     'driver' => 'custom', // Must match the driver name used in Payments::extend()
        //     'released' => 'payment-received',
        // ],
    ],

];
