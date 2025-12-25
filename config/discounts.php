<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Discount Stacking Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for discount stacking rules and behavior.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default Stacking Behavior
    |--------------------------------------------------------------------------
    |
    | Default stacking mode and strategy when not explicitly set on a discount.
    |
    */
    'default_stacking_mode' => env('DISCOUNT_DEFAULT_STACKING_MODE', 'non_stackable'),
    'default_stacking_strategy' => env('DISCOUNT_DEFAULT_STACKING_STRATEGY', 'priority_first'),

    /*
    |--------------------------------------------------------------------------
    | Conflict Resolution Rules
    |--------------------------------------------------------------------------
    |
    | Rules for resolving conflicts between discounts.
    |
    */
    'manual_coupons_override_auto' => env('DISCOUNT_MANUAL_OVERRIDE_AUTO', true),
    'b2b_contracts_override_promotions' => env('DISCOUNT_B2B_OVERRIDE_PROMO', true),
    'map_protected_block_discounts' => env('DISCOUNT_MAP_BLOCK', true),

    /*
    |--------------------------------------------------------------------------
    | Compliance & Audit
    |--------------------------------------------------------------------------
    |
    | Compliance and audit trail settings.
    |
    */
    'require_audit_trail' => env('DISCOUNT_REQUIRE_AUDIT_TRAIL', false),
    'track_price_before_discount' => env('DISCOUNT_TRACK_PRICE_BEFORE', true),
    'log_discount_reason' => env('DISCOUNT_LOG_REASON', true),
    'jurisdiction_enforcement' => env('DISCOUNT_JURISDICTION_ENFORCE', false),

    /*
    |--------------------------------------------------------------------------
    | Shipping Discounts
    |--------------------------------------------------------------------------
    |
    | Settings for shipping discounts.
    |
    */
    'shipping_discounts_affect_tax_base' => env('DISCOUNT_SHIPPING_AFFECT_TAX', false),

    /*
    |--------------------------------------------------------------------------
    | Anti-Double-Discount Safeguards
    |--------------------------------------------------------------------------
    |
    | Enable safeguards to prevent double discounting.
    |
    */
    'prevent_double_discount' => env('DISCOUNT_PREVENT_DOUBLE', true),
    'double_discount_check_strict' => env('DISCOUNT_DOUBLE_CHECK_STRICT', false),
];

