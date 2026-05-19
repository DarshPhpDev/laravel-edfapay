<?php

return [
    /**
     * Choose the operating environment mode.
     * Supported options: 'demo' or 'live'
     */
    'mode' => env('EDFAPAY_MODE', 'demo'),

    /**
     * EdfaPay API Credentials
     */
    'api_key' => env('EDFAPAY_API_KEY'),

    /**
     * API Environment Base Url Endpoints
     */
    'urls' => [
        'demo' => 'https://demo-api.edfapay.com',
        'live' => 'https://app-api.edfapay.com',
    ],

    /**
     * Default Fallback Currency
     */
    'currency' => env('EDFAPAY_CURRENCY', 'SAR'),

    /**
     * Webhook Routing Configurations
     */
    'webhook' => [
        /**
         * Set to false if you want to write your own custom routing definitions manually.
         */
        'enable_default_route' => true,

        /**
         * The URL path where EdfaPay should post transaction IPN callbacks.
         */
        'path' => 'api/v1/payments/edfapay/webhook',

        /**
         * Optional middleware layers to run against the webhook endpoint route.
         */
        'middleware' => ['api'],
    ],
];