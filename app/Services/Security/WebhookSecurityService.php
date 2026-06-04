<?php

namespace App\Services\Security;

class WebhookSecurityService
{
    public function isAllowed(string $url): bool
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower(trim((string) ($parts['host'] ?? '')));

        if ($scheme !== 'https' || $host === '' || isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }

        if (! $this->hostIsAllowlisted($host) || $this->isLocalhost($host)) {
            return false;
        }

        $ips = $this->resolveHost($host);
        if ($ips === []) {
            return false;
        }

        foreach ($ips as $ip) {
            if (! $this->isPublicIp($ip)) {
                return false;
            }
        }

        return true;
    }

    public function allowedHosts(): array
    {
        return collect((array) config('security.notification_webhooks.allowed_hosts', []))
            ->map(fn ($host): string => strtolower(trim((string) $host)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function hostIsAllowlisted(string $host): bool
    {
        return in_array($host, $this->allowedHosts(), true);
    }

    private function isLocalhost(string $host): bool
    {
        return in_array($host, ['localhost', 'localhost.localdomain'], true)
            || str_ends_with($host, '.localhost');
    }

    /**
     * @return array<int, string>
     */
    private function resolveHost(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $records = @dns_get_record($host, DNS_A + DNS_AAAA) ?: [];

        return collect($records)
            ->flatMap(fn (array $record): array => array_filter([
                $record['ip'] ?? null,
                $record['ipv6'] ?? null,
            ]))
            ->map(fn ($ip): string => (string) $ip)
            ->unique()
            ->values()
            ->all();
    }

    private function isPublicIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }
}
