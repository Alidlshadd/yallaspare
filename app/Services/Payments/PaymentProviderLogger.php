<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\PaymentProviderLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class PaymentProviderLogger
{
    public function __construct(private readonly PaymentPayloadSanitizer $sanitizer) {}

    /**
     * @param  array<string, mixed>  $requestMetadata
     * @param  array<string, mixed>  $responseMetadata
     */
    public function record(
        string $provider,
        string $eventType,
        string $method,
        string $endpoint,
        string $result,
        ?Payment $payment = null,
        ?int $httpStatus = null,
        ?int $durationMs = null,
        ?string $safeMessage = null,
        array $requestMetadata = [],
        array $responseMetadata = [],
        ?string $referenceId = null,
    ): ?PaymentProviderLog {
        try {
            if (! Schema::hasTable('payment_provider_logs')) {
                return null;
            }

            return PaymentProviderLog::query()->create([
                'provider' => strtolower(trim($provider)),
                'payment_id' => $payment?->id,
                'order_id' => $payment?->order_id,
                'reference_id' => $this->sanitizer->text($referenceId ?: $payment?->provider_reference, 255),
                'event_type' => strtoupper(trim($eventType)),
                'http_method' => strtoupper(trim($method)),
                'endpoint' => $this->safeEndpoint($endpoint),
                'http_status' => $httpStatus,
                'result' => $this->sanitizer->text($result, 80) ?: 'Unknown',
                'duration_ms' => $durationMs,
                'safe_message' => $this->sanitizer->text($safeMessage),
                'request_metadata' => $requestMetadata === [] ? null : $this->sanitizer->sanitize($requestMetadata),
                'response_metadata' => $responseMetadata === [] ? null : $this->sanitizer->sanitize($responseMetadata),
            ]);
        } catch (Throwable $exception) {
            Log::warning('Payment provider traffic could not be recorded', [
                'provider' => strtolower(trim($provider)),
                'event_type' => strtoupper(trim($eventType)),
                'exception' => $exception::class,
            ]);

            return null;
        }
    }

    private function safeEndpoint(string $endpoint): string
    {
        $path = parse_url($endpoint, PHP_URL_PATH);

        return $this->sanitizer->text(is_string($path) && $path !== '' ? $path : '/', 500) ?: '/';
    }
}
