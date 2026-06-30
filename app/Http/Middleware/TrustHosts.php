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
        $patterns = [];

        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        if (is_string($appHost) && $appHost !== '') {
            $patterns = array_merge($patterns, $this->patternsForHost($appHost));
        }

        foreach ($this->configuredHosts() as $host) {
            $patterns = array_merge($patterns, $this->patternsForHost($host));
        }

        if (! app()->environment('production')) {
            $patterns[] = '^localhost$';
            $patterns[] = '^127\.0\.0\.1$';
            $patterns[] = '^\[::1\]$';
            $patterns[] = '^192\.168\.\d{1,3}\.\d{1,3}$';
            $patterns[] = '^10\.\d{1,3}\.\d{1,3}\.\d{1,3}$';
            $patterns[] = '^172\.(1[6-9]|2\d|3[0-1])\.\d{1,3}\.\d{1,3}$';
        }

        return array_values(array_unique(array_filter($patterns)));
    }

    /**
     * @return array<int, string>
     */
    private function configuredHosts(): array
    {
        return array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('TRUSTED_HOSTS', ''))
        )));
    }

    /**
     * @return array<int, string>
     */
    private function patternsForHost(string $host): array
    {
        $host = trim($host);
        if ($host === '') {
            return [];
        }

        if (Str::startsWith($host, '*.')) {
            $quotedHost = preg_quote(Str::after($host, '*.'), '/');

            return ['^(.*\.)?' . $quotedHost . '$'];
        }

        return ['^' . preg_quote($host, '/') . '$'];
    }
}
