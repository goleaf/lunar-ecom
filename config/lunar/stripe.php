<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Payment Policy
    |--------------------------------------------------------------------------
    |
    | Determines the policy for taking payments and whether you wish to capture
    | the payment manually later or take payment straight away.
    |
    | Available options: 'automatic' or 'manual'
    |
    */
    'policy' => env('STRIPE_PAYMENT_POLICY', 'automatic'),

    /*
    |--------------------------------------------------------------------------
    | Sync Addresses
    |--------------------------------------------------------------------------
    |
    | When enabled, the Stripe addon will attempt to sync the billing and
    | shipping addresses which have been stored against the payment intent
    | on Stripe.
    |
    */
    'sync_addresses' => env('STRIPE_SYNC_ADDRESSES', true),

    /*
    |--------------------------------------------------------------------------
    | Webhook Path
    |--------------------------------------------------------------------------
    |
    | The path where Stripe webhooks will be received.
    | Default: '/stripe/webhook'
    |
    */
    'webhook_path' => env('STRIPE_WEBHOOK_PATH', '/stripe/webhook'),

];


