<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrustProxies extends Middleware
{
    /**
     * Published Cloudflare edge IP ranges (https://www.cloudflare.com/ips/).
     * Hardcoded so the app does not depend on an outbound fetch at runtime;
     * refresh manually if Cloudflare publishes new ranges.
     *
     * @var array<int, string>
     */
    private const CLOUDFLARE_PROXIES = [
        // IPv4
        '173.245.48.0/20',
        '103.21.244.0/22',
        '103.22.200.0/22',
        '103.31.4.0/22',
        '141.101.64.0/18',
        '108.162.192.0/18',
        '190.93.240.0/20',
        '188.114.96.0/20',
        '197.234.240.0/22',
        '198.41.128.0/17',
        '162.158.0.0/15',
        '104.16.0.0/13',
        '104.24.0.0/14',
        '172.64.0.0/13',
        '131.0.72.0/22',
        // IPv6
        '2400:cb00::/32',
        '2606:4700::/32',
        '2803:f800::/32',
        '2405:b500::/32',
        '2405:8100::/32',
        '2a06:98c0::/29',
        '2c0f:f248::/32',
    ];

    /**
     * Headers used to detect proxies. CF-Connecting-IP is not in this set
     * because Cloudflare also populates X-Forwarded-For with the real client
     * IP, which Laravel already consumes — so $request->ip() returns the
     * end-user's address as long as we trust Cloudflare's edge as a proxy.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;

    /**
     * Trusted proxies. Resolved lazily per request because env vars are read
     * after the container builds middleware instances; setting it in the
     * constructor or as a property default would miss runtime config.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies;

    public function handle(Request $request, Closure $next): Response
    {
        $this->proxies = $this->resolveTrustedProxies();

        return parent::handle($request, $next);
    }

    /**
     * Resolve the trusted proxy list based on environment configuration.
     *
     * Modes:
     *  - TRUSTED_PROXIES=cloudflare  -> use the published Cloudflare ranges
     *  - TRUSTED_PROXIES="<csv>"      -> trust the explicit list
     *  - TRUSTED_PROXIES="*"          -> trust any proxy (only safe behind
     *                                    a known reverse proxy)
     *  - (unset / empty)              -> trust nothing (default Laravel)
     *
     * @return array<int, string>|string|null
     */
    private function resolveTrustedProxies(): array|string|null
    {
        $value = trim((string) env('TRUSTED_PROXIES', ''));

        if ($value === '') {
            return null;
        }

        if (strtolower($value) === 'cloudflare') {
            return self::CLOUDFLARE_PROXIES;
        }

        if ($value === '*') {
            return '*';
        }

        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }
}
