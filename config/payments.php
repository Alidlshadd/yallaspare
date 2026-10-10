<?php

return [
    'currency' => env('PAYMENT_CURRENCY', 'IQD'),

    // Customer checkout kill switch. Provider integrations and admin tooling
    // remain available while online methods are hidden and rejected at checkout.
    'customer_online_payments_enabled' => (bool) env('CUSTOMER_ONLINE_PAYMENTS_ENABLED', false),

    // How long an order placed with an online method keeps its stock while
    // unpaid, in minutes. After this, orders:release-unpaid cancels it and
    // returns the stock. Never treated as less than 60, the life of a
    // provider's payment link.
    'unpaid_order_release_minutes' => (int) env('UNPAID_ORDER_RELEASE_MINUTES', 90),

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
        'wayl' => [
            'label' => 'WAYL',
            'online' => true,
            'enabled' => env('WAYL_ENABLED', false),
            'coming_soon' => ! env('WAYL_ENABLED', false),
            'minimum_amount' => 3000,
        ],
        'fastpay' => [
            'label' => 'FastPay',
            'online' => true,
            'enabled' => env('FASTPAY_PAYMENTS_ENABLED', false),
            'coming_soon' => true,
        ],
    ],
];
