<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustHosts as Middleware;
use Illuminate\Support\Str;

class TrustHosts extends Middleware
{
    /**
     * Get the host patterns that should be trusted.
     *
     * @return array<int, string|null>
     */
    public function hosts(): array
    {
        $patterns = [
            '^localhost$',
            '^127\.0\.0\.1$',
            '^\[::1\]$',
        ];

        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        if (is_string($appHost) && $appHost !== '') {
            $quotedHost = preg_quote($appHost, '/');
            $patterns[] = '^' . $quotedHost . '$';

            if (! filter_var($appHost, FILTER_VALIDATE_IP) && ! Str::contains($appHost, 'localhost')) {
                $patterns[] = '^(.*\.)?' . $quotedHost . '$';
            }

            if (Str::contains($appHost, 'localhost')) {
                $patterns[] = '^192\.168\.\d{1,3}\.\d{1,3}$';
                $patterns[] = '^10\.\d{1,3}\.\d{1,3}\.\d{1,3}$';
                $patterns[] = '^172\.(1[6-9]|2\d|3[0-1])\.\d{1,3}\.\d{1,3}$';
            }
        }

        return array_values(array_unique(array_filter($patterns)));
    }
}
