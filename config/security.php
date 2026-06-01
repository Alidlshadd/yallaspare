<?php

return [
    'intrusion_prevention' => [
        'enabled' => env('INTRUSION_PREVENTION_ENABLED', env('APP_ENV') === 'production'),
        'window_minutes' => (int) env('INTRUSION_PREVENTION_WINDOW_MINUTES', 10),
        'block_minutes' => (int) env('INTRUSION_PREVENTION_BLOCK_MINUTES', 30),
        'max_score' => (int) env('INTRUSION_PREVENTION_MAX_SCORE', 8),
        'excluded_paths' => [
            'admin/two-factor*',
            'user/two-factor*',
        ],
        'probe_paths' => [
            '/.env',
            '/wp-admin',
            '/wp-login',
            '/phpmyadmin',
            '/vendor/phpunit',
            '/storage/logs',
            '/server-status',
        ],
        'patterns' => [
            '/(\bunion\b.{0,40}\bselect\b|\bselect\b.{0,40}\bfrom\b)/i',
            '/(\bor\b|\band\b)\s+[\w\'"]+\s*=\s*[\w\'"]+/i',
            '/(--|#|\/\*|\*\/|;\s*(drop|alter|truncate|insert|update|delete)\b)/i',
            '/(<script\b|javascript:|onerror\s*=|onload\s*=)/i',
            '/(\.\.\/|\.\.\\\\|%2e%2e%2f|%252e%252e%252f)/i',
            '/(\bbenchmark\s*\(|\bsleep\s*\(|\bload_file\s*\()/i',
        ],
    ],

    'admin_two_factor' => [
        // Production: always on. Disabling requires the explicit emergency flag
        // ADMIN_TWO_FACTOR_FORCE_DISABLE=true, which should never be set without
        // a documented incident — a stray ADMIN_TWO_FACTOR_ENABLED=false in the
        // production env should NOT silently kill admin 2FA.
        'enabled' => env('APP_ENV') === 'production'
            ? ! filter_var(env('ADMIN_TWO_FACTOR_FORCE_DISABLE', false), FILTER_VALIDATE_BOOLEAN)
            : filter_var(env('ADMIN_TWO_FACTOR_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'code_ttl_minutes' => (int) env('ADMIN_TWO_FACTOR_CODE_TTL', 10),
    ],

    'user_two_factor' => [
        'code_ttl_minutes' => (int) env('USER_TWO_FACTOR_CODE_TTL', 10),
    ],

    'email_verification' => [
        'max_attempts' => (int) env('EMAIL_VERIFICATION_MAX_ATTEMPTS', 5),
    ],
];
