<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Pricing Priority Order
    |--------------------------------------------------------------------------
    |
    | Defines the priority order for pricing resolution. Higher numbers
    | are checked first. The first valid price found wins.
    |
    | Priority layers:
    | - manual_override: Manual price override (highest priority)
    | - contract: B2B contract / customer-specific pricing
    | - customer_group: Customer group pricing
    | - channel: Channel-specific pricing
    | - promotional: Time-based promotional pricing
    | - tiered: Quantity-based tiered pricing
    | - base: Base variant price (lowest priority)
    |
    */
    'priority_order' => [
        'manual_override' => 1000,
        'contract' => 900,
        'customer_group' => 800,
        'channel' => 700,
        'promotional' => 600,
        'tiered' => 500,
        'base' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Enable Priority-Based Resolution
    |--------------------------------------------------------------------------
    |
    | If true, uses priority-based pricing resolution. If false, uses
    | the legacy pricing system.
    |
    */
    'enable_priority_resolution' => env('LUNAR_ENABLE_PRIORITY_PRICING', true),

    /*
    |--------------------------------------------------------------------------
    | Stop on First Match
    |--------------------------------------------------------------------------
    |
    | If true, stops at the first valid price found. If false, continues
    | checking all layers and returns the highest priority match.
    |
    */
    'stop_on_first_match' => env('LUNAR_PRICING_STOP_ON_FIRST_MATCH', true),
];


