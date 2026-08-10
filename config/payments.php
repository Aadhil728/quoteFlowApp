<?php

return [
    'stripe' => [
        'enabled' => env('STRIPE_ENABLED', false),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'api_url' => env('STRIPE_API_URL', 'https://api.stripe.com'),
        'webhook_tolerance' => 300,
    ],
    'paypal' => [
        'enabled' => env('PAYPAL_ENABLED', false),
        'mode' => env('PAYPAL_MODE', 'sandbox'),
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
        'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
        'api_url' => env('PAYPAL_API_URL', env('PAYPAL_MODE', 'sandbox') === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com'),
    ],
];
