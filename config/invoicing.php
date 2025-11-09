<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Mexican Invoicing Provider
    |--------------------------------------------------------------------------
    |
    | This option controls which Mexican invoicing service provider will be
    | used by your application. Supported providers: "facturacom", "facturapi"
    |
    */

    'provider' => env('MEXICAN_INVOICING_PROVIDER', 'facturacom'),

];
