<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tax Driver
    |--------------------------------------------------------------------------
    |
    | Specify the tax driver to use. By default, Lunar uses the 'system' driver
    | which uses Lunar's internal tax models and database.
    |
    | You can create custom tax drivers to integrate with external tax services
    | (e.g., TaxJar, Avalara) or implement custom tax calculation logic.
    |
    | See: https://docs.lunarphp.com/1.x/extending/taxation#writing-your-own-driver
    |
    */
    'driver' => env('TAX_DRIVER', 'system'),

    /*
    |--------------------------------------------------------------------------
    | Tax Calculators
    |--------------------------------------------------------------------------
    |
    | Define tax calculators for specific tax calculation scenarios.
    | These are used by the system driver for calculating tax amounts.
    |
    */
    'calculators' => [
        'standard' => \App\Lunar\Taxation\TaxCalculators\StandardTaxCalculator::class,
    ],

];
