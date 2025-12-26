<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default SKU Format
    |--------------------------------------------------------------------------
    |
    | Default format for auto-generating SKUs. Available placeholders:
    | - {PRODUCT-SKU}: Product SKU
    | - {PRODUCT-ID}: Product ID
    | - {OPTIONS}: Option values (first 3 chars, uppercase)
    | - {VARIANT-ID}: Variant ID
    | - {UUID}: First 8 chars of UUID
    | - {TIMESTAMP}: Unix timestamp
    |
    */
    'default_sku_format' => env('VARIANT_SKU_FORMAT', '{PRODUCT-SKU}-{OPTIONS}'),

    /*
    |--------------------------------------------------------------------------
    | SKU Generation Rules
    |--------------------------------------------------------------------------
    |
    | Rules for SKU generation:
    | - max_length: Maximum SKU length
    | - prefix: Optional prefix for all SKUs
    | - suffix: Optional suffix for all SKUs
    | - separator: Separator between parts
    |
    */
    'sku_generation' => [
        'max_length' => 100,
        'prefix' => null,
        'suffix' => null,
        'separator' => '-',
        'uppercase' => true,
        'alphanumeric_only' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Variant Status Defaults
    |--------------------------------------------------------------------------
    |
    | Default status for new variants.
    |
    */
    'default_status' => env('VARIANT_DEFAULT_STATUS', 'active'),

    /*
    |--------------------------------------------------------------------------
    | Variant Visibility Defaults
    |--------------------------------------------------------------------------
    |
    | Default visibility for new variants.
    |
    */
    'default_visibility' => env('VARIANT_DEFAULT_VISIBILITY', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Variant Position Increment
    |--------------------------------------------------------------------------
    |
    | Increment value for variant positions.
    |
    */
    'position_increment' => 10,

    /*
    |--------------------------------------------------------------------------
    | Barcode Validation
    |--------------------------------------------------------------------------
    |
    | Enable/disable barcode validation.
    |
    */
    'validate_barcodes' => env('VALIDATE_BARCODES', true),

    /*
    |--------------------------------------------------------------------------
    | Auto-generate UUID
    |--------------------------------------------------------------------------
    |
    | Automatically generate UUID for variants if not provided.
    |
    */
    'auto_generate_uuid' => env('AUTO_GENERATE_VARIANT_UUID', true),

    /*
    |--------------------------------------------------------------------------
    | Auto-generate SKU
    |--------------------------------------------------------------------------
    |
    | Automatically generate SKU for variants if not provided.
    |
    */
    'auto_generate_sku' => env('AUTO_GENERATE_VARIANT_SKU', true),

    /*
    |--------------------------------------------------------------------------
    | Auto-generate Title
    |--------------------------------------------------------------------------
    |
    | Automatically generate title for variants if not provided.
    |
    */
    'auto_generate_title' => env('AUTO_GENERATE_VARIANT_TITLE', true),
];


