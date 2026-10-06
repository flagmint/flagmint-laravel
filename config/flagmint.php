<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | SDK key
    |--------------------------------------------------------------------------
    |
    | Prefer FLAGMINT_SDK_KEY; FLAGMINT_API_KEY is accepted for marketing parity.
    |
    */
    'api_key' => env('FLAGMINT_SDK_KEY', env('FLAGMINT_API_KEY')),

    'enable' => (bool) env('FLAGMINT_ENABLE', true),

    'env' => env('FLAGMINT_ENV', 'production'),

    'rest_endpoint' => env('FLAGMINT_REST_ENDPOINT'),

    'handshake_endpoint' => env('FLAGMINT_HANDSHAKE_ENDPOINT'),

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | driver: memory | laravel | redis | custom
    | When driver=laravel (default), FLAGMINT_CACHE_STORE selects the cache store.
    |
    */
    'cache' => [
        'driver' => env('FLAGMINT_CACHE_DRIVER', 'laravel'),
        'store' => env('FLAGMINT_CACHE_STORE'),
        'prefix' => env('FLAGMINT_CACHE_PREFIX', 'flagmint:rules:'),
        'redis_url' => env('FLAGMINT_REDIS_URL'),
        // Fully-qualified CacheAdapter class for driver=custom
        'adapter' => env('FLAGMINT_CACHE_ADAPTER'),
    ],

    'queue_events' => (bool) env('FLAGMINT_QUEUE_EVENTS', true),

    /*
    | Octane / long-lived workers: bind FlagmintClient as singleton; never mutate shared
    | evaluation context — pass per-request context via middleware / method args.
    */
];
