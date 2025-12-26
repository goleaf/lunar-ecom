<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Checkout Lock Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for checkout locking and order processing.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default TTL (Time To Live)
    |--------------------------------------------------------------------------
    |
    | Default expiration time for checkout locks in minutes.
    | After this time, the lock expires and resources are released.
    |
    */

    'default_ttl_minutes' => env('CHECKOUT_TTL_MINUTES', 15),

    /*
    |--------------------------------------------------------------------------
    | Maximum TTL
    |--------------------------------------------------------------------------
    |
    | Maximum allowed TTL for checkout locks in minutes.
    | Prevents setting excessively long lock times.
    |
    */

    'max_ttl_minutes' => env('CHECKOUT_MAX_TTL_MINUTES', 60),

    /*
    |--------------------------------------------------------------------------
    | Cleanup Interval
    |--------------------------------------------------------------------------
    |
    | How often to run cleanup of expired locks (in minutes).
    | This should match your scheduled task frequency.
    |
    */

    'cleanup_interval_minutes' => env('CHECKOUT_CLEANUP_INTERVAL', 5),

    /*
    |--------------------------------------------------------------------------
    | Price Drift Tolerance
    |--------------------------------------------------------------------------
    |
    | Maximum allowed price difference (in cents) between snapshot and
    | current cart price before warning is logged.
    |
    */

    'price_drift_tolerance_cents' => env('CHECKOUT_PRICE_DRIFT_TOLERANCE', 1),

    /*
    |--------------------------------------------------------------------------
    | Enable Concurrent Checkout Prevention
    |--------------------------------------------------------------------------
    |
    | If true, prevents multiple sessions from checking out the same cart
    | simultaneously.
    |
    */

    'prevent_concurrent_checkout' => env('CHECKOUT_PREVENT_CONCURRENT', true),

    /*
    |--------------------------------------------------------------------------
    | Enable Cart Protection Middleware
    |--------------------------------------------------------------------------
    |
    | If true, middleware will prevent cart modifications during checkout.
    |
    */

    'enable_cart_protection' => env('CHECKOUT_ENABLE_CART_PROTECTION', true),

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Logging settings for checkout operations.
    |
    */

    'logging' => [
        'enabled' => env('CHECKOUT_LOGGING_ENABLED', true),
        'channel' => env('CHECKOUT_LOG_CHANNEL', 'daily'),
        'log_all_phases' => env('CHECKOUT_LOG_ALL_PHASES', true),
        'log_failures' => env('CHECKOUT_LOG_FAILURES', true),
        'log_rollbacks' => env('CHECKOUT_LOG_ROLLBACKS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Payment gateway settings. These should be configured based on your
    | payment provider.
    |
    */

    'payment' => [
        'gateway' => env('CHECKOUT_PAYMENT_GATEWAY', 'stripe'),
        'authorization_timeout' => env('CHECKOUT_PAYMENT_AUTH_TIMEOUT', 300), // 5 minutes
        'capture_timeout' => env('CHECKOUT_PAYMENT_CAPTURE_TIMEOUT', 3600), // 1 hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Configure notifications for checkout events.
    |
    */

    'notifications' => [
        'on_failure' => env('CHECKOUT_NOTIFY_ON_FAILURE', false),
        'on_expiration' => env('CHECKOUT_NOTIFY_ON_EXPIRATION', false),
        'email' => env('CHECKOUT_NOTIFICATION_EMAIL'),
    ],
];


