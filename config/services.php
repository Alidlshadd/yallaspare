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

    // Master switch for the social login section on the login/register pages.
    // Buttons additionally require the per-provider 'enabled' flag and
    // complete credentials before they are rendered.
    'social_login' => [
        'visible' => (bool) env('SOCIAL_LOGIN_VISIBLE', false),
    ],

    'google' => [
        'enabled' => (bool) env('GOOGLE_LOGIN_ENABLED', false),
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'apple' => [
        'enabled' => (bool) env('APPLE_LOGIN_ENABLED', false),
        'client_id' => env('APPLE_CLIENT_ID'), // Services ID from Apple Developer.
        'client_secret' => env('APPLE_CLIENT_SECRET'), // Optional pre-generated JWT; leave empty when using the key material below.
        'team_id' => env('APPLE_TEAM_ID'),
        'key_id' => env('APPLE_KEY_ID'),
        'private_key' => env('APPLE_PRIVATE_KEY'), // Absolute path to the .p8 key file.
        'redirect' => env('APPLE_REDIRECT_URI'),
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

    'otpiq' => [
        'api_key' => env('OTPIQ_API_KEY'),
        'base_url' => env('OTPIQ_BASE_URL', 'https://api.otpiq.com/api'),
        'provider' => env('OTPIQ_PROVIDER', 'sms'),
        'default_country_code' => env('OTPIQ_DEFAULT_COUNTRY_CODE', '964'),
        'verification_ttl' => (int) env('OTPIQ_VERIFICATION_TTL', 10),
        'whatsapp_enabled' => (bool) env('OTPIQ_WHATSAPP_ENABLED', false),
        'whatsapp_account_id' => env('OTPIQ_WHATSAPP_ACCOUNT_ID'),
        'whatsapp_phone_id' => env('OTPIQ_WHATSAPP_PHONE_ID'),
        'whatsapp_template_name' => env('OTPIQ_WHATSAPP_TEMPLATE_NAME'),
    ],

];
