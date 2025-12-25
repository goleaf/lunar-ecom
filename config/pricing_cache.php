<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Pricing Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for pricing cache strategy.
    |
    */

    'enabled' => env('PRICING_CACHE_ENABLED', true),

    'ttl' => [
        'default' => env('PRICING_CACHE_TTL', 3600), // 1 hour
        'base_price' => env('PRICING_CACHE_TTL_BASE_PRICE', 3600),
        'variant_availability' => env('PRICING_CACHE_TTL_VARIANT_AVAIL', 1800), // 30 minutes
        'contract_prices' => env('PRICING_CACHE_TTL_CONTRACT', 7200), // 2 hours
        'promotions' => env('PRICING_CACHE_TTL_PROMOTIONS', 1800), // 30 minutes
        'currency_rates' => env('PRICING_CACHE_TTL_CURRENCY', 300), // 5 minutes
        'attribute_metadata' => env('PRICING_CACHE_TTL_ATTRIBUTES', 7200), // 2 hours
    ],

    'store' => env('PRICING_CACHE_STORE', 'redis'),

    'versioning' => [
        'enabled' => env('PRICING_CACHE_VERSIONING', true),
    ],

    'observability' => [
        'enabled' => env('PRICING_METRICS_ENABLED', true),
        'slow_threshold_ms' => env('PRICING_SLOW_THRESHOLD_MS', 500),
    ],

    'circuit_breaker' => [
        'enabled' => env('CIRCUIT_BREAKER_ENABLED', true),
        'failure_threshold' => env('CIRCUIT_BREAKER_THRESHOLD', 5),
        'timeout' => env('CIRCUIT_BREAKER_TIMEOUT', 60),
    ],

    'rate_limiting' => [
        'checkout' => [
            'enabled' => env('RATE_LIMIT_CHECKOUT_ENABLED', true),
            'max_attempts' => env('RATE_LIMIT_CHECKOUT_MAX', 10),
            'decay_minutes' => env('RATE_LIMIT_CHECKOUT_DECAY', 1),
        ],
    ],
];

