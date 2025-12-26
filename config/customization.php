<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Profanity Filter
    |--------------------------------------------------------------------------
    |
    | List of words to filter from text customizations. In production, consider
    | using a proper profanity filtering library or API.
    |
    */

    'profanity_words' => [
        // Add profanity words here
        // This is a basic list - use a proper library in production
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Upload Settings
    |--------------------------------------------------------------------------
    */

    'image' => [
        'disk' => 'public',
        'path' => 'customizations',
        'max_size_kb' => 10240, // 10MB default
        'allowed_formats' => ['jpg', 'jpeg', 'png', 'svg', 'webp'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Preview Settings
    |--------------------------------------------------------------------------
    */

    'preview' => [
        'quality' => 90,
        'format' => 'png',
    ],
];


