<?php

return [
    'association_types_enum' => \Lunar\Base\Enums\ProductAssociation::class,
    
    // Product Identifier Validation
    // See: https://docs.lunarphp.com/1.x/reference/products#product-identifiers
    'sku' => [
        'required' => false, // Set to true if SKU is required
        'unique' => false, // Set to true if SKU must be unique
    ],
    'gtin' => [
        'required' => false,
        'unique' => false,
    ],
    'mpn' => [
        'required' => false,
        'unique' => false,
    ],
    'ean' => [
        'required' => false,
        'unique' => false,
    ],
];
