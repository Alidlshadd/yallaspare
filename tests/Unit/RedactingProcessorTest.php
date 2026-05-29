<?php

namespace Tests\Unit;

use App\Logging\RedactingProcessor;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;

class RedactingProcessorTest extends TestCase
{
    private function record(string $message, array $context = [], array $extra = []): LogRecord
    {
        return new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: $message,
            context: $context,
            extra: $extra,
        );
    }

    public function test_redacts_sensitive_keys_in_context(): void
    {
        $processor = new RedactingProcessor();
        $record = $this->record('login attempt', [
            'user' => 'jane',
            'password' => 'super-secret',
            'remember_token' => 'abc123',
            'token' => 'xyz',
        ]);

        $result = $processor($record);

        $this->assertSame('[redacted]', $result->context['password']);
        $this->assertSame('[redacted]', $result->context['remember_token']);
        $this->assertSame('[redacted]', $result->context['token']);
        $this->assertSame('jane', $result->context['user']);
    }

    public function test_redacts_sensitive_keys_recursively(): void
    {
        $processor = new RedactingProcessor();
        $record = $this->record('payload', [
            'request' => [
                'body' => [
                    'current_password' => 'shh',
                    'name' => 'Alice',
                ],
            ],
        ]);

        $result = $processor($record);

        $this->assertSame('[redacted]', $result->context['request']['body']['current_password']);
        $this->assertSame('Alice', $result->context['request']['body']['name']);
    }

    public function test_redacts_emails_in_message(): void
    {
        $processor = new RedactingProcessor();
        $result = $processor($this->record('user signed up: john.doe+x@example.com here'));

        $this->assertStringNotContainsString('john.doe', $result->message);
        $this->assertStringContainsString('[email-redacted]', $result->message);
    }

    public function test_redacts_bearer_tokens(): void
    {
        $processor = new RedactingProcessor();
        $result = $processor($this->record('Authorization: Bearer eyJhbGciOiJIUzI1NiJ9.foo'));

        $this->assertStringNotContainsString('eyJhbGciOiJIUzI1NiJ9.foo', $result->message);
        $this->assertStringContainsString('Bearer [redacted]', $result->message);
    }

    public function test_redacts_long_hex_strings(): void
    {
        $processor = new RedactingProcessor();
        $hex = str_repeat('a1b2c3d4', 6);
        $result = $processor($this->record("session={$hex}"));

        $this->assertStringNotContainsString($hex, $result->message);
        $this->assertStringContainsString('[hex-redacted]', $result->message);
    }

    public function test_redacts_phone_numbers_in_context_strings(): void
    {
        $processor = new RedactingProcessor();
        $result = $processor($this->record('user info', [
            'note' => 'reach me at +905551234567 anytime',
        ]));

        $this->assertStringNotContainsString('905551234567', $result->context['note']);
        $this->assertStringContainsString('[phone-redacted]', $result->context['note']);
    }

    public function test_preserves_non_sensitive_data(): void
    {
        $processor = new RedactingProcessor();
        $record = $this->record('benign event', [
            'id' => 42,
            'status' => 'ok',
            'count' => 7,
        ]);

        $result = $processor($record);

        $this->assertSame(42, $result->context['id']);
        $this->assertSame('ok', $result->context['status']);
        $this->assertSame(7, $result->context['count']);
    }
}
