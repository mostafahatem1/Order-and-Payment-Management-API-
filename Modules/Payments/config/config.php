<?php

use Modules\Payments\Gateways\CreditCardGateway;
use Modules\Payments\Gateways\PaymobGateway;
use Modules\Payments\Gateways\PaypalGateway;

return [
    'name' => 'Payments',

    /*
    |--------------------------------------------------------------------------
    | Paymob Environment Variables
    |--------------------------------------------------------------------------
    |
    | PAYMOB_API_KEY=
    | PAYMOB_INTEGRATION_ID=
    | PAYMOB_IFRAME_ID=
    |
    */

    'gateways' => [
        'credit_card' => CreditCardGateway::class,
        'paypal' => PaypalGateway::class,
        'paymob' => [
            'driver' => PaymobGateway::class,
            'config' => [
                'api_key' => env('PAYMOB_API_KEY'),
                'integration_id' => env('PAYMOB_INTEGRATION_ID'),
                'iframe_id' => env('PAYMOB_IFRAME_ID'),
            ],
        ],
    ],
];
