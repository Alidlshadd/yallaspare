<?php

namespace App\Security;

use Illuminate\Contracts\Cache\Repository;

/**
 * Trips after a run of consecutive HIBP failures so a slow or dead breach API
 * costs one timeout every few minutes instead of one per sign-up. Without it a
 * 2s timeout still pins a php-fpm worker on every registration for the whole
 * outage.
 *
 * State is global to the HIBP service, never keyed by user, email or password:
 * the thing being tracked is the third party's health, and a per-identity key
 * would both leak identity into the cache and never reach the threshold.
 */
class HibpCircuitBreaker
{
    private const FAILURE_KEY = 'security:hibp:circuit:failures';

    private const OPEN_KEY = 'security:hibp:circuit:open';

    public function __construct(private readonly Repository $cache) {}

    public function isOpen(): bool
    {
        return $this->cache->has(self::OPEN_KEY);
    }

    /**
     * A usable answer from HIBP clears the run of failures. The circuit is never
     * open at this point, since an open circuit skips the request entirely.
     */
    public function recordSuccess(): void
    {
        $this->cache->forget(self::FAILURE_KEY);
    }

    public function recordFailure(): void
    {
        $ttl = now()->addMinutes($this->minutes());

        // add() only writes when the key is absent, so concurrent workers cannot
        // reset each other's counter; whoever loses the race still increments.
        $this->cache->add(self::FAILURE_KEY, 0, $ttl);

        $count = $this->cache->increment(self::FAILURE_KEY);

        // Some stores return false when the key expired between add() and
        // increment(). Treat that as the first failure of a new window rather
        // than letting a falsy value stall the counter.
        if (! is_int($count)) {
            $this->cache->put(self::FAILURE_KEY, 1, $ttl);
            $count = 1;
        }

        if ($count >= $this->threshold()) {
            $this->cache->put(self::OPEN_KEY, true, $ttl);

            // Start the next window clean. Once the open period lapses HIBP gets
            // another full run of attempts before the circuit trips again.
            $this->cache->forget(self::FAILURE_KEY);
        }
    }

    /**
     * Consecutive failures recorded in the current window.
     */
    public function failures(): int
    {
        return (int) $this->cache->get(self::FAILURE_KEY, 0);
    }

    private function threshold(): int
    {
        return max(1, (int) config('security.hibp.circuit_threshold', 5));
    }

    private function minutes(): int
    {
        return max(1, (int) config('security.hibp.circuit_minutes', 5));
    }
}
