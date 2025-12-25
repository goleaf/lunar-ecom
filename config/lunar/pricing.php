<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Pricing Hooks Configuration
    |--------------------------------------------------------------------------
    |
    | Configure dynamic pricing hooks for ERP, AI, rules engine, etc.
    | Format: 'hook_type' => ['identifier' => 'HandlerClass']
    |
    */
    'hooks' => [
        'erp' => [
            // 'sap' => \App\Services\PricingHooks\SapPricingHook::class,
            // 'oracle' => \App\Services\PricingHooks\OraclePricingHook::class,
        ],
        'ai' => [
            // 'dynamic_pricing' => \App\Services\PricingHooks\AIDynamicPricingHook::class,
        ],
        'rules_engine' => [
            // 'drools' => \App\Services\PricingHooks\DroolsPricingHook::class,
        ],
        'external_api' => [
            // 'pricing_api' => \App\Services\PricingHooks\ExternalPricingApiHook::class,
        ],
        'custom' => [
            // 'custom_hook' => \App\Services\PricingHooks\CustomPricingHook::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Price Rounding Rules
    |--------------------------------------------------------------------------
    |
    | Default rounding rules applied when variant doesn't have specific rules.
    |
    */
    'default_rounding' => [
        'method' => 'none', // none, round, round_up, round_down, nearest
        'precision' => 0,
        'nearest' => 100, // For 'nearest' method
    ],

    /*
    |--------------------------------------------------------------------------
    | MAP Pricing Enforcement
    |--------------------------------------------------------------------------
    |
    | Enable/disable MAP pricing enforcement globally.
    |
    */
    'enforce_map' => env('ENFORCE_MAP_PRICING', true),

    /*
    |--------------------------------------------------------------------------
    | Price Lock Behavior
    |--------------------------------------------------------------------------
    |
    | When price is locked, discounts are prevented.
    |
    */
    'price_lock_prevents_discounts' => env('PRICE_LOCK_PREVENTS_DISCOUNTS', true),

    /*
    |--------------------------------------------------------------------------
    | Tax Calculation
    |--------------------------------------------------------------------------
    |
    | Default tax-inclusive behavior.
    |
    */
    'default_tax_inclusive' => env('DEFAULT_TAX_INCLUSIVE', false),

    /*
    |--------------------------------------------------------------------------
    | Cache Duration
    |--------------------------------------------------------------------------
    |
    | Default cache duration for hook prices (seconds).
    |
    */
    'hook_cache_duration' => env('PRICING_HOOK_CACHE_DURATION', 3600),
];
