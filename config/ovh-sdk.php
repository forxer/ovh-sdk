<?php

declare(strict_types=1);

return [

    /*
    | OVHcloud API region: "eu", "ca" or "us".
    */
    'region' => env('OVH_REGION', 'eu'),

    /*
    | Authentication mode: "application_keys" (signed requests) or
    | "service_account" (OAuth2 client credentials).
    */
    'auth' => env('OVH_AUTH', 'application_keys'),

    /*
    | Application keys. The consumer key may stay empty until it has been
    | requested with "php artisan ovh:consumer-key" and validated.
    */
    'application_keys' => [
        'application_key' => env('OVH_APPLICATION_KEY'),
        'application_secret' => env('OVH_APPLICATION_SECRET'),
        'consumer_key' => env('OVH_CONSUMER_KEY'),
    ],

    /*
    | Service account (OAuth2 client credentials).
    */
    'service_account' => [
        'client_id' => env('OVH_CLIENT_ID'),
        'client_secret' => env('OVH_CLIENT_SECRET'),
    ],

    /*
    | Cache store keeping the server time delta and the OAuth2 token.
    | Null uses the default cache store of the application.
    */
    'cache_store' => env('OVH_CACHE_STORE'),

];
