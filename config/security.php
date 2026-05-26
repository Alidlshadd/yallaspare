<?php

return [
    'intrusion_prevention' => [
        'enabled' => env('INTRUSION_PREVENTION_ENABLED', env('APP_ENV') === 'production'),
        'window_minutes' => (int) env('INTRUSION_PREVENTION_WINDOW_MINUTES', 10),
        'block_minutes' => (int) env('INTRUSION_PREVENTION_BLOCK_MINUTES', 30),
        'max_score' => (int) env('INTRUSION_PREVENTION_MAX_SCORE', 8),
        'excluded_paths' => [
            'admin/two-factor*',
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
        'enabled' => env('ADMIN_TWO_FACTOR_ENABLED', env('APP_ENV') === 'production'),
        'code_ttl_minutes' => (int) env('ADMIN_TWO_FACTOR_CODE_TTL', 10),
    ],

    'email_verification' => [
        'max_attempts' => (int) env('EMAIL_VERIFICATION_MAX_ATTEMPTS', 5),
    ],
];
