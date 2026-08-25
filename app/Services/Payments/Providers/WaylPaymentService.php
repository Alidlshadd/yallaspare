<?php

namespace App\Services\Payments\Providers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Payments\PaymentProviderInterface;
use App\Services\Payments\PaymentRedirectData;
use App\Services\Payments\PaymentVerificationResult;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WaylPaymentService implements PaymentProviderInterface
{
    public function provider(): string
    {
        return 'wayl';
    }

    public function createPayment(Order $order, Payment $payment): PaymentRedirectData
    {
        $amount = $this->amountInIqd($payment);
        $referenceId = $this->referenceId($payment);

        $response = $this->request()
            ->post($this->url('/api/v1/links'), [
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
                'redirectionUrl' => (string) $payment->return_url,
            ])
            ->throw()
            ->json();

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

        $response = $this->request()
            ->get($this->url('/api/v1/links/'.rawurlencode($referenceId)))
            ->throw()
            ->json();

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
        if ($amount < 1 || abs($rawAmount - $amount) > 0.0001) {
            throw new \RuntimeException('WAYL requires a positive whole-IQD amount.');
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
