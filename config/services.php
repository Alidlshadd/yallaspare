<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI') ?: rtrim((string) env('APP_URL', 'http://localhost'), '/') . '/auth/google/callback',
    ],

    'apple' => [
        'client_id' => env('APPLE_CLIENT_ID'),
        'client_secret' => env('APPLE_CLIENT_SECRET'),
        'key_id' => env('APPLE_KEY_ID'),
        'team_id' => env('APPLE_TEAM_ID'),
        'private_key' => env('APPLE_PRIVATE_KEY'),
        'passphrase' => env('APPLE_PASSPHRASE'),
        'signer' => env('APPLE_SIGNER'),
        'redirect' => env('APPLE_REDIRECT_URI') ?: rtrim((string) env('APP_URL', 'http://localhost'), '/') . '/auth/apple/callback',
        'jwt_issued_time_leeway' => env('APPLE_JWT_ISSUED_TIME_LEEWAY', 'PT3S'),
    ],

    'fib' => [
        'enabled' => env('FIB_PAYMENTS_ENABLED', false),
        'base_url' => env('FIB_BASE_URL', 'https://fib.stage.fib.iq'),
        'client_id' => env('FIB_CLIENT_ID'),
        'client_secret' => env('FIB_CLIENT_SECRET'),
        'webhook_token' => env('FIB_WEBHOOK_TOKEN'),
    ],

    'zaincash' => [
        'enabled' => env('ZAINCASH_PAYMENTS_ENABLED', false),
        'base_url' => env('ZAINCASH_BASE_URL', 'https://test.zaincash.iq'),
        'merchant_id' => env('ZAINCASH_MERCHANT_ID'),
        'msisdn' => env('ZAINCASH_MSISDN'),
        'secret' => env('ZAINCASH_SECRET'),
        'service_type' => env('ZAINCASH_SERVICE_TYPE', 'Yalla Spare order'),
        'webhook_token' => env('ZAINCASH_WEBHOOK_TOKEN'),
    ],

];
