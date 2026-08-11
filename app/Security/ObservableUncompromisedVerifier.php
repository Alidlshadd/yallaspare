<?php

namespace App\Security;

use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Replaces Laravel's NotPwnedVerifier behind Password::defaults()->uncompromised().
 *
 * The policy is unchanged and deliberate: a breach lookup that cannot complete
 * never blocks a registration or a password reset. What changes is that the
 * framework version reaches that outcome silently for four of its failure modes
 * — 429, 5xx, an empty body and a malformed body all collapse into "no match
 * found" with no log at all — so an HIBP outage was indistinguishable from every
 * password being clean. Here each failure is classified and recorded on the
 * security channel, which turns the gap into something measurable.
 *
 * Nothing derived from the password is ever logged: not the plaintext, not the
 * SHA-1, not the five-character k-anonymity prefix, not the suffix, not the
 * response body. The hash exists only as a local variable for the duration of
 * the lookup.
 */
class ObservableUncompromisedVerifier implements UncompromisedVerifier
{
    private const ENDPOINT = 'https://api.pwnedpasswords.com/range/';

    public function __construct(
        private readonly Factory $http,
        private readonly HibpCircuitBreaker $circuit,
    ) {}

    /**
     * @param  array{value: mixed, threshold: mixed}  $data
     */
    public function verify($data): bool
    {
        $value = (string) ($data['value'] ?? '');
        $threshold = (int) ($data['threshold'] ?? 0);

        // Matches the framework: an empty value is reported as compromised so the
        // rule fails rather than silently passing. 'required' normally gets here
        // first, so this is a guard, not a user-facing path.
        if ($value === '') {
            return false;
        }

        if (! (bool) config('security.hibp.enabled', true)) {
            return true;
        }

        if ($this->circuit->isOpen()) {
            $this->logUnavailable('circuit_open');

            return true;
        }

        $hash = strtoupper(sha1($value));
        $prefix = substr($hash, 0, 5);

        try {
            $response = $this->http
                // A string, not the bool the framework sends, which trips a guzzle
                // 8.0 deprecation on every lookup.
                ->withHeaders(['Add-Padding' => 'true'])
                ->connectTimeout($this->connectTimeout())
                ->timeout($this->timeout())
                ->get(self::ENDPOINT.$prefix);
        } catch (ConnectionException $e) {
            return $this->fail($this->classifyConnectionException($e));
        } catch (Throwable $e) {
            // Deliberately separate from ConnectionException. The framework wraps
            // everything in catch (Exception), so a guard or a plain bug also
            // becomes "password is clean". Still fail-open, but loudly.
            report($e);

            return $this->fail('unexpected', null, 'critical');
        }

        $status = $response->status();

        if ($status === 429) {
            return $this->fail('http_429', $status);
        }

        if ($response->serverError()) {
            return $this->fail('http_5xx', $status);
        }

        if (! $response->successful()) {
            return $this->fail('http_other', $status);
        }

        $body = trim($response->body());

        if ($body === '') {
            return $this->fail('empty_body', $status);
        }

        $lines = $this->parse($body);

        if ($lines === []) {
            return $this->fail('malformed_body', $status);
        }

        $this->circuit->recordSuccess();

        return ! $this->matches($lines, $hash, $threshold);
    }

    /**
     * Record a failure and let the password through.
     */
    private function fail(string $category, ?int $status = null, string $level = 'warning'): bool
    {
        $this->circuit->recordFailure();
        $this->logUnavailable($category, $status, $level);

        return true;
    }

    /**
     * cURL does not expose a machine-readable reason through the client, so the
     * message is the only signal available for splitting a timeout from a name
     * resolution failure.
     */
    private function classifyConnectionException(ConnectionException $e): string
    {
        $message = strtolower($e->getMessage());

        return match (true) {
            str_contains($message, 'timed out'), str_contains($message, 'timeout'), str_contains($message, 'error 28') => 'timeout',
            str_contains($message, 'resolve host'), str_contains($message, 'resolve proxy'), str_contains($message, 'error 6') => 'dns',
            default => 'connection',
        };
    }

    /**
     * @return list<array{suffix: string, count: int}>
     */
    private function parse(string $body): array
    {
        $lines = [];

        foreach (preg_split('/\R/', $body) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || ! str_contains($line, ':')) {
                continue;
            }

            [$suffix, $count] = explode(':', $line, 2);

            if (! ctype_xdigit($suffix)) {
                continue;
            }

            $lines[] = ['suffix' => strtoupper($suffix), 'count' => (int) $count];
        }

        return $lines;
    }

    /**
     * @param  list<array{suffix: string, count: int}>  $lines
     */
    private function matches(array $lines, string $hash, int $threshold): bool
    {
        $prefix = substr($hash, 0, 5);

        foreach ($lines as $line) {
            // Padding entries come back with a count of zero, so a strict
            // comparison against the threshold keeps them from matching.
            if ($line['count'] > $threshold && hash_equals($hash, $prefix.$line['suffix'])) {
                return true;
            }
        }

        return false;
    }

    private function logUnavailable(string $category, ?int $status = null, string $level = 'warning'): void
    {
        $request = request();

        Log::channel('security')->{$level}('security event', [
            'event' => 'auth.hibp_unavailable',
            'failure_category' => $category,
            'http_status' => $status,
            'timeout_seconds' => $this->timeout(),
            'flow' => $this->flow(),
            'route' => $request->route()?->getName() ?? $request->path(),
            'user_id' => $request->user()?->getAuthIdentifier(),
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);
    }

    /**
     * The seven live call sites all go through Password::defaults(), which gives
     * the verifier no argument to identify the caller. Deriving the flow from the
     * matched route keeps every one of them untouched. Three of the routes have
     * no name, so the URI pattern is the primary key and the name is a fallback.
     */
    private function flow(): string
    {
        $route = request()->route();

        if ($route === null) {
            return 'unknown';
        }

        $method = strtoupper(request()->method());
        $uri = trim($route->uri(), '/');

        $flow = match ("{$method} {$uri}") {
            'POST register' => 'register',
            'POST api/mobile/register' => 'mobile_register',
            'POST reset-password' => 'reset',
            'PUT password' => 'change',
            'PATCH user/account/password' => 'account_change',
            'PATCH api/mobile/profile/password' => 'mobile_change',
            'PATCH admin/users/{user}/password' => 'admin_set',
            default => null,
        };

        if ($flow !== null) {
            return $flow;
        }

        return match ($route->getName()) {
            'password.store' => 'reset',
            'password.update' => 'change',
            'user.account.password' => 'account_change',
            'admin.users.update-password' => 'admin_set',
            default => 'unknown',
        };
    }

    private function timeout(): int
    {
        return max(1, (int) config('security.hibp.timeout', 2));
    }

    private function connectTimeout(): int
    {
        return max(1, (int) config('security.hibp.connect_timeout', 1));
    }
}
