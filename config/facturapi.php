<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Facturapi Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Facturapi CFDI invoicing service.
    | Get your credentials at: https://www.facturapi.io
    |
    */

    'api_url' => env('FACTURAPI_API_URL', 'https://www.facturapi.io'),

    'api_key' => env('FACTURAPI_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    |
    | Determines if the service is running in test or production mode.
    | Test mode uses test API keys (sk_test_...), production uses live keys (sk_live_...)
    |
    */

    'environment' => env('FACTURAPI_ENVIRONMENT', 'test'), // 'test' or 'production'

];
