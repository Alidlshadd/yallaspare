<?php

namespace App\Services\Payments\Providers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentProviderLog;
use App\Services\Payments\PaymentPayloadSanitizer;
use App\Services\Payments\PaymentProviderInterface;
use App\Services\Payments\PaymentProviderLogger;
use App\Services\Payments\PaymentRedirectData;
use App\Services\Payments\PaymentVerificationResult;
use App\Services\Payments\WaylProviderException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class WaylPaymentService implements PaymentProviderInterface
{
    public function __construct(
        private readonly PaymentProviderLogger $traffic,
        private readonly PaymentPayloadSanitizer $sanitizer,
    ) {}

    public function provider(): string
    {
        return 'wayl';
    }

    public function createPayment(Order $order, Payment $payment): PaymentRedirectData
    {
        $amount = $this->amountInIqd($payment);
        $referenceId = $this->referenceId($payment);

        $payload = [
            'env' => $this->environment(),
            'referenceId' => $referenceId,
            'total' => $amount,
            'currency' => 'IQD',
            'customParameter' => (string) $order->order_number,
            'lineItem' => [
                [
                    'label' => 'YallaSpare order '.$order->order_number,
                    'amount' => $amount,
                    'type' => 'increase',
                ],
            ],
            // WAYL currently rejects omitted webhook fields even though
            // its public schema describes them as optional. Empty strings
            // preserve the no-webhook flow while satisfying its validator.
            'webhookUrl' => (string) config('services.wayl.webhook_url', ''),
            'webhookSecret' => (string) config('services.wayl.webhook_secret', ''),
            'redirectionUrl' => (string) $payment->return_url,
        ];

        $response = $this->send(
            method: 'POST',
            path: '/api/v1/links',
            eventType: PaymentProviderLog::EVENT_CREATE_LINK,
            payment: $payment,
            payload: $payload,
            referenceId: $referenceId,
        );

        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        $this->assertPaymentIdentity($data, $referenceId, $amount);

        $redirectUrl = trim((string) ($data['url'] ?? ''));
        if (! str_starts_with($redirectUrl, 'https://')) {
            throw new \RuntimeException('WAYL did not return a secure payment link.');
        }

        $providerPaymentId = $this->stringValue($data['id'] ?? null);
        if ($providerPaymentId === null) {
            throw new \RuntimeException('WAYL did not return a payment link id.');
        }

        return new PaymentRedirectData(
            redirectUrl: $redirectUrl,
            providerPaymentId: $providerPaymentId,
            providerTransactionId: $this->stringValue($data['code'] ?? null),
            providerReference: $referenceId,
            rawResponse: is_array($response) ? $response : [],
        );
    }

    public function verifyPayment(Payment $payment): PaymentVerificationResult
    {
        $referenceId = trim((string) ($payment->provider_reference ?: $this->referenceId($payment)));

        $response = $this->send(
            method: 'GET',
            path: '/api/v1/links/'.rawurlencode($referenceId),
            eventType: PaymentProviderLog::EVENT_STATUS_CHECK,
            payment: $payment,
            referenceId: $referenceId,
        );

        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        $this->assertPaymentIdentity($data, $referenceId, $this->amountInIqd($payment));

        $rawStatus = strtolower(trim((string) ($data['status'] ?? '')));
        $status = match ($rawStatus) {
            'complete', 'delivered' => Payment::STATUS_PAID,
            'cancelled', 'canceled' => Payment::STATUS_CANCELLED,
            'rejected', 'returned' => Payment::STATUS_FAILED,
            default => Payment::STATUS_PENDING,
        };

        return new PaymentVerificationResult(
            status: $status,
            providerPaymentId: $this->stringValue($data['id'] ?? $payment->provider_payment_id),
            providerTransactionId: $this->stringValue($data['code'] ?? $payment->provider_transaction_id),
            providerReference: $referenceId,
            failureReason: in_array($status, [Payment::STATUS_FAILED, Payment::STATUS_CANCELLED], true)
                ? 'wayl_status_'.($rawStatus !== '' ? $rawStatus : 'unknown')
                : null,
            rawResponse: is_array($response) ? $response : [],
        );
    }

    public function paymentIdFromWebhook(Request $request): ?string
    {
        return null;
    }

    public function validateWebhook(Request $request): bool
    {
        return false;
    }

    /** @return array{connected: bool, http_status: ?int, result: string, message: string} */
    public function healthCheck(): array
    {
        $path = '/'.ltrim((string) config('services.wayl.health_path', '/api/v1/links'), '/');

        try {
            $this->send(
                method: 'GET',
                path: $path,
                eventType: PaymentProviderLog::EVENT_HEALTH_CHECK,
                payload: ['limit' => 1],
            );

            return [
                'connected' => true,
                'http_status' => 200,
                'result' => 'Success',
                'message' => 'Authentication valid',
            ];
        } catch (WaylProviderException $exception) {
            return [
                'connected' => false,
                'http_status' => $exception->httpStatus,
                'result' => $exception->httpStatus === 401 ? 'Authentication Failed' : 'Connection Failed',
                'message' => $exception->getMessage(),
            ];
        }
    }

    /**
     * Probe WAYL's create-link validator without creating any local commerce records.
     *
     * @return array{
     *     http_status: ?int,
     *     success: bool,
     *     message: string,
     *     errors: array<string|int, mixed>,
     *     webhook_required: ?bool,
     *     reference_id: string
     * }
     */
    public function createLinkDiagnostic(): array
    {
        if ($this->environment() !== 'test') {
            throw new \RuntimeException('WAYL create-link diagnostic is available in the test environment only.');
        }

        $referenceId = 'wayl-diagnostic-'.Str::ulid();
        $amount = max(3000, (int) config('payments.methods.wayl.minimum_amount', 3000));
        $redirectionUrl = rtrim((string) config('app.url'), '/').'/admin/integrations/wayl';

        if (! str_starts_with($redirectionUrl, 'https://')) {
            throw new \RuntimeException('WAYL diagnostic requires APP_URL to be a real HTTPS URL.');
        }

        $payload = [
            'env' => 'test',
            'referenceId' => $referenceId,
            'total' => $amount,
            'currency' => 'IQD',
            'customParameter' => $referenceId,
            'lineItem' => [
                [
                    'label' => 'YallaSpare WAYL diagnostic',
                    'amount' => $amount,
                    'type' => 'increase',
                ],
            ],
            'redirectionUrl' => $redirectionUrl,
        ];

        $startedAt = hrtime(true);
        $path = '/api/v1/links';

        try {
            $response = $this->request()->send('POST', $this->url($path), ['json' => $payload]);
        } catch (ConnectionException $exception) {
            $message = 'WAYL API is unavailable.';
            $this->traffic->record(
                provider: $this->provider(),
                eventType: PaymentProviderLog::EVENT_CREATE_LINK_DIAGNOSTIC,
                method: 'POST',
                endpoint: $path,
                result: 'Unavailable',
                durationMs: $this->durationMs($startedAt),
                safeMessage: $message,
                requestMetadata: $payload,
                referenceId: $referenceId,
            );

            return $this->diagnosticResult(null, false, $message, [], null, $referenceId);
        } catch (Throwable $exception) {
            $message = 'WAYL diagnostic request could not be completed.';
            $this->traffic->record(
                provider: $this->provider(),
                eventType: PaymentProviderLog::EVENT_CREATE_LINK_DIAGNOSTIC,
                method: 'POST',
                endpoint: $path,
                result: 'Request Failed',
                durationMs: $this->durationMs($startedAt),
                safeMessage: $message,
                requestMetadata: $payload,
                referenceId: $referenceId,
            );

            return $this->diagnosticResult(null, false, $message, [], null, $referenceId);
        }

        $body = $this->responseBody($response);
        $errors = $this->validationErrors($body);
        $message = $this->safeProviderMessage($body, $response);
        $webhookRequired = $response->successful()
            ? false
            : ($response->status() === 422 && $this->mentionsBothWebhookFields($errors) ? true : null);

        $responseMetadata = $body;
        if ($errors !== []) {
            $responseMetadata['_validation_errors'] = $errors;
        }

        $this->traffic->record(
            provider: $this->provider(),
            eventType: PaymentProviderLog::EVENT_CREATE_LINK_DIAGNOSTIC,
            method: 'POST',
            endpoint: $path,
            result: $this->result($response),
            httpStatus: $response->status(),
            durationMs: $this->durationMs($startedAt),
            safeMessage: $message,
            requestMetadata: $payload,
            responseMetadata: $responseMetadata,
            referenceId: $referenceId,
        );

        return $this->diagnosticResult(
            $response->status(),
            $response->successful(),
            $message,
            $errors,
            $webhookRequired,
            $referenceId,
        );
    }

    private function request(): PendingRequest
    {
        return Http::withHeaders([
            'X-WAYL-AUTHENTICATION' => $this->token(),
        ])
            ->acceptJson()
            ->asJson()
            ->connectTimeout(5)
            ->timeout(15);
    }

    /**
     * Execute and audit one WAYL call without ever persisting credentials or headers.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function send(
        string $method,
        string $path,
        string $eventType,
        ?Payment $payment = null,
        array $payload = [],
        ?string $referenceId = null,
    ): array {
        $startedAt = hrtime(true);
        $url = $this->url($path);

        try {
            $response = strtoupper($method) === 'GET'
                ? $this->request()->get($url, $payload)
                : $this->request()->send(strtoupper($method), $url, ['json' => $payload]);
        } catch (ConnectionException $exception) {
            $duration = $this->durationMs($startedAt);
            $message = 'WAYL API is unavailable.';
            $this->traffic->record(
                provider: $this->provider(),
                eventType: $eventType,
                method: $method,
                endpoint: $path,
                result: 'Unavailable',
                payment: $payment,
                durationMs: $duration,
                safeMessage: $message,
                requestMetadata: $payload,
                referenceId: $referenceId,
            );

            throw new WaylProviderException(null, $message, previous: $exception);
        } catch (Throwable $exception) {
            $duration = $this->durationMs($startedAt);
            $message = 'WAYL request could not be completed.';
            $this->traffic->record(
                provider: $this->provider(),
                eventType: $eventType,
                method: $method,
                endpoint: $path,
                result: 'Request Failed',
                payment: $payment,
                durationMs: $duration,
                safeMessage: $message,
                requestMetadata: $payload,
                referenceId: $referenceId,
            );

            throw new WaylProviderException(null, $message, previous: $exception);
        }

        $body = $this->responseBody($response);
        $duration = $this->durationMs($startedAt);
        $message = $this->safeProviderMessage($body, $response);
        $errors = is_array($body['errors'] ?? null)
            ? $this->sanitizer->sanitize($body['errors'])
            : [];
        $result = $this->result($response);

        $this->traffic->record(
            provider: $this->provider(),
            eventType: $eventType,
            method: $method,
            endpoint: $path,
            result: $result,
            payment: $payment,
            httpStatus: $response->status(),
            durationMs: $duration,
            safeMessage: $message,
            requestMetadata: $payload,
            responseMetadata: $body,
            referenceId: $referenceId,
        );

        if (! $response->successful()) {
            throw new WaylProviderException($response->status(), $message, $errors);
        }

        return $body;
    }

    /** @return array<string, mixed> */
    private function responseBody(Response $response): array
    {
        $body = $response->json();

        return is_array($body) ? $body : [];
    }

    /** @param array<string, mixed> $body */
    private function safeProviderMessage(array $body, Response $response): string
    {
        $message = is_scalar($body['message'] ?? null) ? trim((string) $body['message']) : '';
        if ($message !== '') {
            return $this->sanitizer->text($message) ?: 'WAYL provider request failed.';
        }

        return match ($response->status()) {
            401, 403 => 'Authentication failed.',
            422 => 'WAYL rejected the request validation.',
            429 => 'WAYL rate limit reached.',
            default => $response->successful() ? 'Request completed successfully.' : 'WAYL provider request failed.',
        };
    }

    private function result(Response $response): string
    {
        return match (true) {
            $response->successful() => 'Success',
            in_array($response->status(), [401, 403], true) => 'Authentication Error',
            $response->status() === 422 => 'Validation Error',
            $response->status() === 429 => 'Rate Limited',
            $response->serverError() => 'Provider Error',
            default => 'Request Failed',
        };
    }

    /** @param array<string, mixed> $body */
    private function validationErrors(array $body): array
    {
        $errors = null;
        foreach (['errors', 'validationErrors', 'issues'] as $key) {
            if (is_array($body[$key] ?? null)) {
                $errors = $body[$key];
                break;
            }
        }

        if (! is_array($errors)) {
            $errors = data_get($body, 'error.errors');
        }

        if (! is_array($errors)) {
            return [];
        }

        $normalized = [];
        foreach ($errors as $key => $error) {
            if (is_array($error) && is_int($key)) {
                $item = [];
                foreach (['path', 'code', 'expected', 'message'] as $field) {
                    if (array_key_exists($field, $error)) {
                        $item[$field] = $error[$field];
                    }
                }
                if ($item !== []) {
                    $normalized[] = $this->sanitizer->sanitize($item);
                }

                continue;
            }

            if (is_string($key)) {
                $messages = is_array($error) ? $error : [$error];
                foreach ($messages as $message) {
                    if (is_scalar($message)) {
                        $normalized[] = [
                            'path' => $this->sanitizer->text($key, 500),
                            'message' => $this->sanitizer->text($message, 1000),
                        ];
                    }
                }
            }
        }

        return $normalized;
    }

    /** @param array<string|int, mixed> $errors */
    private function mentionsBothWebhookFields(array $errors): bool
    {
        $encoded = strtolower((string) json_encode($errors));

        return str_contains($encoded, 'webhookurl') && str_contains($encoded, 'webhooksecret');
    }

    /**
     * @param  array<string|int, mixed>  $errors
     * @return array{http_status: ?int, success: bool, message: string, errors: array<string|int, mixed>, webhook_required: ?bool, reference_id: string}
     */
    private function diagnosticResult(
        ?int $httpStatus,
        bool $success,
        string $message,
        array $errors,
        ?bool $webhookRequired,
        string $referenceId,
    ): array {
        return [
            'http_status' => $httpStatus,
            'success' => $success,
            'message' => $message,
            'errors' => $errors,
            'webhook_required' => $webhookRequired,
            'reference_id' => $referenceId,
        ];
    }

    private function durationMs(int $startedAt): int
    {
        return max(0, (int) round((hrtime(true) - $startedAt) / 1_000_000));
    }

    private function token(): string
    {
        $token = trim((string) config('services.wayl.token'));
        if ($token === '') {
            throw new \RuntimeException('WAYL API token is not configured.');
        }

        return $token;
    }

    private function environment(): string
    {
        $environment = strtolower(trim((string) config('services.wayl.env', 'test')));
        if (! in_array($environment, ['test', 'live'], true)) {
            throw new \RuntimeException('WAYL environment must be test or live.');
        }

        return $environment;
    }

    private function amountInIqd(Payment $payment): int
    {
        if (strtoupper((string) $payment->currency) !== 'IQD') {
            throw new \RuntimeException('WAYL supports IQD payments only.');
        }

        $rawAmount = (float) $payment->amount;
        $amount = (int) round($rawAmount);
        if (abs($rawAmount - $amount) > 0.0001) {
            throw new \RuntimeException('WAYL requires a positive whole-IQD amount.');
        }

        $minimumAmount = max(1, (int) config('payments.methods.wayl.minimum_amount', 3000));
        if ($amount < $minimumAmount) {
            throw new \RuntimeException("WAYL requires a minimum payment amount of {$minimumAmount} IQD.");
        }

        return $amount;
    }

    private function referenceId(Payment $payment): string
    {
        return 'yallaspare-payment-'.$payment->getKey();
    }

    private function assertPaymentIdentity(array $data, string $referenceId, int $amount): void
    {
        $returnedReference = trim((string) ($data['referenceId'] ?? ''));
        if ($returnedReference === '' || ! hash_equals($referenceId, $returnedReference)) {
            throw new \RuntimeException('WAYL returned a mismatched payment reference.');
        }

        $returnedCurrency = strtoupper(trim((string) ($data['currency'] ?? '')));
        if ($returnedCurrency !== 'IQD') {
            throw new \RuntimeException('WAYL returned a mismatched payment currency.');
        }

        $returnedTotal = $data['total'] ?? null;
        if (! is_numeric($returnedTotal) || abs((float) $returnedTotal - $amount) > 0.0001) {
            throw new \RuntimeException('WAYL returned a mismatched payment amount.');
        }
    }

    private function stringValue(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.wayl.base_url', 'https://api.thewayl.com'), '/').$path;
    }
}
