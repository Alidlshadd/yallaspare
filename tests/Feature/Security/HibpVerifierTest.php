<?php

namespace Tests\Feature\Security;

use App\Security\HibpCircuitBreaker;
use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use RuntimeException;
use Tests\TestCase;

class HibpVerifierTest extends TestCase
{
    /**
     * Invented for this test file. Never a real credential, and deliberately
     * long enough that the surrounding password rules are not what fails.
     */
    private const PROBE = 'ZZ-invented-probe-9182-not-a-password';

    /** @var list<MessageLogged> */
    private array $logged = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Any request that does not match a fake must blow up rather than
        // quietly reaching the real breach API.
        Http::preventStrayRequests();

        // Keep the suite from writing storage/logs/security-*.log while still
        // letting MessageLogged fire, which is what the assertions read.
        config(['logging.channels.security' => ['driver' => 'null']]);

        $this->logged = [];
        Event::listen(MessageLogged::class, function (MessageLogged $event): void {
            $this->logged[] = $event;
        });
    }

    private function verify(string $value = self::PROBE): bool
    {
        return app(UncompromisedVerifier::class)->verify(['value' => $value, 'threshold' => 0]);
    }

    private function suffix(string $value = self::PROBE): string
    {
        return substr(strtoupper(sha1($value)), 5);
    }

    /** @return list<array<string, mixed>> */
    private function securityEvents(): array
    {
        $events = [];

        foreach ($this->logged as $entry) {
            if (($entry->context['event'] ?? null) === 'auth.hibp_unavailable') {
                $events[] = $entry->context + ['__level' => $entry->level];
            }
        }

        return $events;
    }

    private function fakeBody(string $body, int $status = 200): void
    {
        Http::fake(['api.pwnedpasswords.com/*' => Http::response($body, $status)]);
    }

    public function test_clean_response_allows_the_password(): void
    {
        $this->fakeBody("FFFF1111222233334444555566667777888:3\n");

        $this->assertTrue($this->verify());
        $this->assertSame([], $this->securityEvents(), 'A clean lookup must not raise an availability event.');
    }

    public function test_compromised_response_rejects_the_password(): void
    {
        $this->fakeBody($this->suffix().":42\n");

        $this->assertFalse($this->verify());
        $this->assertSame([], $this->securityEvents());
    }

    public function test_padding_entries_are_not_treated_as_a_match(): void
    {
        // Add-Padding makes HIBP return decoy suffixes with a count of zero.
        $this->fakeBody($this->suffix().":0\n");

        $this->assertTrue($this->verify());
    }

    public function test_timeout_allows_and_logs_one_event(): void
    {
        Http::fake(['api.pwnedpasswords.com/*' => function (): never {
            throw new ConnectionException('cURL error 28: Operation timed out after 2000 ms');
        }]);

        $this->assertTrue($this->verify());

        $events = $this->securityEvents();
        $this->assertCount(1, $events);
        $this->assertSame('timeout', $events[0]['failure_category']);
        $this->assertNull($events[0]['http_status']);
        $this->assertSame('warning', $events[0]['__level']);
    }

    public function test_connection_failure_allows_and_logs_one_event(): void
    {
        Http::fake(['api.pwnedpasswords.com/*' => function (): never {
            throw new ConnectionException('cURL error 7: Failed to connect to api.pwnedpasswords.com port 443');
        }]);

        $this->assertTrue($this->verify());

        $events = $this->securityEvents();
        $this->assertCount(1, $events);
        $this->assertSame('connection', $events[0]['failure_category']);
    }

    public function test_dns_failure_is_categorised_separately(): void
    {
        Http::fake(['api.pwnedpasswords.com/*' => function (): never {
            throw new ConnectionException('cURL error 6: Could not resolve host: api.pwnedpasswords.com');
        }]);

        $this->assertTrue($this->verify());
        $this->assertSame('dns', $this->securityEvents()[0]['failure_category']);
    }

    public function test_http_429_allows_with_the_correct_category(): void
    {
        $this->fakeBody('rate limited', 429);

        $this->assertTrue($this->verify());

        $events = $this->securityEvents();
        $this->assertCount(1, $events);
        $this->assertSame('http_429', $events[0]['failure_category']);
        $this->assertSame(429, $events[0]['http_status']);
    }

    public function test_http_500_allows_with_the_correct_category(): void
    {
        $this->fakeBody('server error', 500);

        $this->assertTrue($this->verify());

        $events = $this->securityEvents();
        $this->assertSame('http_5xx', $events[0]['failure_category']);
        $this->assertSame(500, $events[0]['http_status']);
    }

    public function test_unexpected_non_2xx_allows_with_the_correct_category(): void
    {
        $this->fakeBody('gone', 410);

        $this->assertTrue($this->verify());

        $events = $this->securityEvents();
        $this->assertSame('http_other', $events[0]['failure_category']);
        $this->assertSame(410, $events[0]['http_status']);
    }

    public function test_empty_200_body_allows_with_the_correct_category(): void
    {
        $this->fakeBody('');

        $this->assertTrue($this->verify());
        $this->assertSame('empty_body', $this->securityEvents()[0]['failure_category']);
    }

    public function test_malformed_200_body_allows_with_the_correct_category(): void
    {
        $this->fakeBody("<html>maintenance</html>\nno colons here\n");

        $this->assertTrue($this->verify());
        $this->assertSame('malformed_body', $this->securityEvents()[0]['failure_category']);
    }

    public function test_unexpected_throwable_allows_and_logs_critical(): void
    {
        Http::fake(['api.pwnedpasswords.com/*' => function (): never {
            throw new RuntimeException('something nobody planned for');
        }]);

        $this->assertTrue($this->verify());

        $events = $this->securityEvents();
        $this->assertCount(1, $events);
        $this->assertSame('unexpected', $events[0]['failure_category']);
        $this->assertSame('critical', $events[0]['__level']);
    }

    public function test_no_password_derived_data_reaches_the_logs(): void
    {
        $hash = strtoupper(sha1(self::PROBE));
        $forbidden = [
            self::PROBE,
            $hash,
            substr($hash, 0, 5),
            substr($hash, 5),
            strtolower($hash),
        ];

        $bodies = [
            fn () => $this->fakeBody('rate limited', 429),
            fn () => $this->fakeBody('', 200),
            fn () => $this->fakeBody("secret-looking body {$hash}", 200),
            fn () => Http::fake(['api.pwnedpasswords.com/*' => function (): never {
                throw new ConnectionException('cURL error 28: Operation timed out');
            }]),
        ];

        foreach ($bodies as $arrange) {
            $this->logged = [];
            $arrange();
            $this->verify();

            $serialised = json_encode($this->logged === [] ? [] : array_map(
                fn (MessageLogged $e): array => ['message' => $e->message, 'context' => $e->context],
                $this->logged
            ), JSON_THROW_ON_ERROR);

            foreach ($forbidden as $needle) {
                $this->assertStringNotContainsString(
                    $needle,
                    $serialised,
                    'Password-derived material must never be logged.'
                );
            }
        }
    }

    public function test_request_uses_the_configured_timeouts(): void
    {
        $captured = [];

        Http::fake(['api.pwnedpasswords.com/*' => function (Request $request, array $options) use (&$captured) {
            $captured = $options;

            return Http::response("FFFF1111222233334444555566667777888:3\n", 200);
        }]);

        $this->verify();

        $this->assertSame(2, $captured['timeout'] ?? null, 'Total timeout must be 2 seconds, not the framework default of 30.');
        $this->assertSame(1, $captured['connect_timeout'] ?? null);
        $this->assertSame(2, config('security.hibp.timeout'));
        $this->assertSame(1, config('security.hibp.connect_timeout'));
    }

    public function test_successful_response_resets_the_failure_counter(): void
    {
        $circuit = app(HibpCircuitBreaker::class);
        $circuit->recordFailure();
        $circuit->recordFailure();
        $this->assertSame(2, $circuit->failures());

        $this->fakeBody("FFFF1111222233334444555566667777888:3\n");
        $this->verify();

        $this->assertSame(0, $circuit->failures());
        $this->assertFalse($circuit->isOpen());
    }

    public function test_threshold_of_consecutive_failures_opens_the_circuit(): void
    {
        $this->fakeBody('server error', 500);
        $circuit = app(HibpCircuitBreaker::class);

        for ($i = 0; $i < 5; $i++) {
            $this->assertTrue($this->verify());
        }

        $this->assertTrue($circuit->isOpen());
    }

    public function test_open_circuit_sends_no_http_request(): void
    {
        $this->fakeBody('server error', 500);

        for ($i = 0; $i < 5; $i++) {
            $this->verify();
        }

        $this->assertTrue(app(HibpCircuitBreaker::class)->isOpen());

        Http::fake(['api.pwnedpasswords.com/*' => Http::response('should never be requested', 200)]);
        $this->logged = [];

        $this->assertTrue($this->verify());

        Http::assertNothingSent();
        $this->assertSame('circuit_open', $this->securityEvents()[0]['failure_category']);
    }

    public function test_circuit_closes_again_once_the_open_window_expires(): void
    {
        $this->fakeBody('server error', 500);

        for ($i = 0; $i < 5; $i++) {
            $this->verify();
        }
        $this->assertTrue(app(HibpCircuitBreaker::class)->isOpen());

        $this->travel(6)->minutes();

        $this->assertFalse(app(HibpCircuitBreaker::class)->isOpen());

        $captured = false;
        Http::fake(['api.pwnedpasswords.com/*' => function () use (&$captured) {
            $captured = true;

            return Http::response("FFFF1111222233334444555566667777888:3\n", 200);
        }]);

        $this->assertTrue($this->verify());
        $this->assertTrue($captured, 'HIBP must be tried again after the open window lapses.');
    }

    public function test_disabling_hibp_skips_the_lookup_entirely(): void
    {
        config(['security.hibp.enabled' => false]);
        Http::fake(['api.pwnedpasswords.com/*' => Http::response('never', 200)]);

        $this->assertTrue($this->verify());
        Http::assertNothingSent();
    }

    public function test_password_defaults_outside_production_never_calls_hibp(): void
    {
        $this->assertFalse(app()->isProduction(), 'Guard: the suite must not run as production.');

        $validator = Validator::make(
            ['password' => self::PROBE],
            ['password' => [Password::defaults()]]
        );

        $this->assertTrue($validator->passes());
        Http::assertNothingSent();
    }

    public function test_empty_value_is_reported_as_compromised(): void
    {
        Http::fake(['api.pwnedpasswords.com/*' => Http::response('never', 200)]);

        $this->assertFalse($this->verify(''));
        Http::assertNothingSent();
    }
}
