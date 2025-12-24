<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Models for indexing
    |--------------------------------------------------------------------------
    |
    | The model listed here will be used to create/populate the indexes.
    | You can provide your own model here to run them all on the same
    | search engine.
    |
    | See: https://docs.lunarphp.com/1.x/reference/search#configuration
    |
    */
    'models' => [
        /*
         * These models are required by the system, do not change them.
         */
        \Lunar\Models\Brand::class,
        \Lunar\Models\Collection::class,
        \Lunar\Models\Customer::class,
        \Lunar\Models\Order::class,
        \Lunar\Models\Product::class,
        \Lunar\Models\ProductOption::class,

        /*
         * Below you can add your own models for indexing...
         */
        // App\Models\Example::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Search engine mapping
    |--------------------------------------------------------------------------
    |
    | You can define what search driver each searchable model should use.
    | If the model isn't defined here, it will use the SCOUT_DRIVER env variable.
    |
    | This allows you to use different search engines for different models.
    | For example, you might use Algolia for Products and Meilisearch for Orders.
    |
    | See: https://docs.lunarphp.com/1.x/reference/search#engine-mapping
    |
    */
    'engine_map' => [
        // Example: Use Algolia for Products
        // \Lunar\Models\Product::class => 'algolia',
        
        // Example: Use Meilisearch for Orders and Collections
        // \Lunar\Models\Order::class => 'meilisearch',
        // \Lunar\Models\Collection::class => 'meilisearch',
    ],

    'indexers' => [
        Lunar\Models\Brand::class => Lunar\Search\BrandIndexer::class,
        Lunar\Models\Collection::class => Lunar\Search\CollectionIndexer::class,
        Lunar\Models\Customer::class => Lunar\Search\CustomerIndexer::class,
        Lunar\Models\Order::class => Lunar\Search\OrderIndexer::class,
        Lunar\Models\Product::class => Lunar\Search\ProductIndexer::class,
        Lunar\Models\ProductOption::class => Lunar\Search\ProductOptionIndexer::class,
    ],

];
