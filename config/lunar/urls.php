<?php

use Lunar\Generators\UrlGenerator;

return [

    /*
    |--------------------------------------------------------------------------
    | Required URLs
    |--------------------------------------------------------------------------
    |
    | Set whether URLs should be required across the system. Setting this as true
    | will affect how validation works when creating/editing products in the hub.
    |
    | If you have a generator specified below, this setting will have no effect
    | on validation rules across the system.
    |
    | See: https://docs.lunarphp.com/1.x/reference/urls
    |
    */
    'required' => true,

    /*
    |--------------------------------------------------------------------------
    | URL Generator
    |--------------------------------------------------------------------------
    |
    | Here you can specify a class to automatically generate URLs for models which
    | implement the `HasUrls` trait. If left null no generation will happen.
    | You are free to use your own generator, or you can use the one that
    | ships with Lunar, which by default will use the name attribute.
    |
    | Set to null to disable automatic URL generation:
    | 'generator' => null,
    |
    | See: https://docs.lunarphp.com/1.x/reference/urls#automatically-generating-urls
    |
    */
    'generator' => UrlGenerator::class,

];
