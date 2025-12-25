<?php

use App\Lunar\Media\BrandMediaDefinitions;
use App\Lunar\Media\CategoryMediaDefinitions;
use App\Lunar\Media\CustomMediaDefinitions;
use App\Lunar\Media\ReviewMediaDefinitions;
use App\Models\Category;
use App\Models\Review;
use Lunar\Base\StandardMediaDefinitions;

return [

    'definitions' => [
        // Use custom media definitions for products and collections
        \Lunar\Models\Product::class => CustomMediaDefinitions::class,
        \Lunar\Models\Collection::class => CustomMediaDefinitions::class,
        \Lunar\Models\Brand::class => BrandMediaDefinitions::class,
        Category::class => CategoryMediaDefinitions::class,
        Review::class => ReviewMediaDefinitions::class,
        
        // Use standard definitions for other models
        'asset' => StandardMediaDefinitions::class,
        'product-option' => StandardMediaDefinitions::class,
        'product-option-value' => StandardMediaDefinitions::class,
    ],

    'collection' => 'images',

    'fallback' => [
        'url' => env('FALLBACK_IMAGE_URL', null),
        'path' => env('FALLBACK_IMAGE_PATH', null),
    ],

    // Image optimization settings
    'optimization' => [
        'enabled' => env('MEDIA_OPTIMIZATION_ENABLED', true),
        'quality' => [
            'thumb' => 85,
            'medium' => 90,
            'large' => 92,
            'xlarge' => 95,
        ],
        'max_width' => env('MEDIA_MAX_WIDTH', 1920),
        'max_height' => env('MEDIA_MAX_HEIGHT', 1920),
    ],

    // Responsive image breakpoints
    'responsive' => [
        'breakpoints' => [320, 640, 768, 1024, 1280, 1920],
        'sizes' => [
            'default' => '(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw',
            'product_card' => '(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 25vw',
            'product_detail' => '(max-width: 768px) 100vw, 50vw',
        ],
    ],

];
