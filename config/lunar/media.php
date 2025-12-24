<?php

use App\Lunar\Media\CustomMediaDefinitions;
use Lunar\Base\StandardMediaDefinitions;

return [

    'definitions' => [
        // Use custom media definitions for products and collections
        \Lunar\Models\Product::class => CustomMediaDefinitions::class,
        \Lunar\Models\Collection::class => CustomMediaDefinitions::class,
        
        // Use standard definitions for other models
        'asset' => StandardMediaDefinitions::class,
        'brand' => StandardMediaDefinitions::class,
        'product-option' => StandardMediaDefinitions::class,
        'product-option-value' => StandardMediaDefinitions::class,
    ],

    'collection' => 'images',

    'fallback' => [
        'url' => env('FALLBACK_IMAGE_URL', null),
        'path' => env('FALLBACK_IMAGE_PATH', null),
    ],

];
