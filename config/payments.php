<?php

return [
    'currency' => env('PAYMENT_CURRENCY', 'IQD'),

    'methods' => [
        'cash_on_delivery' => [
            'label' => 'Cash on Delivery',
            'online' => false,
            'enabled' => true,
            'coming_soon' => false,
        ],
        'fib' => [
            'label' => 'FIB',
            'online' => true,
            'enabled' => env('FIB_PAYMENTS_ENABLED', false),
            'coming_soon' => ! env('FIB_PAYMENTS_ENABLED', false),
        ],
        'zaincash' => [
            'label' => 'ZainCash',
            'online' => true,
            'enabled' => env('ZAINCASH_PAYMENTS_ENABLED', false),
            'coming_soon' => ! env('ZAINCASH_PAYMENTS_ENABLED', false),
        ],
        'fastpay' => [
            'label' => 'FastPay',
            'online' => true,
            'enabled' => env('FASTPAY_PAYMENTS_ENABLED', false),
            'coming_soon' => true,
        ],
    ],
];
