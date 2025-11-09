<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Factura.com API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Factura.com CFDI invoicing service.
    | Get your credentials at: https://www.factura.com
    |
    */

    'api_url' => env('FACTURACOM_API_URL', 'https://api.factura.com'),

    'api_key' => env('FACTURACOM_API_KEY'),

    'secret_key' => env('FACTURACOM_SECRET_KEY'),

    'plugin_key' => env('FACTURACOM_PLUGIN_KEY', '9d4095c8f7ed5785cb14c0e3b033eeb8252416ed'),

];
