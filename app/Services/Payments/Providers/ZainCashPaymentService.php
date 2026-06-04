<?php

namespace App\Services\Payments\Providers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Payments\Concerns\BuildsJwt;
use App\Services\Payments\PaymentProviderInterface;
use App\Services\Payments\PaymentRedirectData;
use App\Services\Payments\PaymentVerificationResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ZainCashPaymentService implements PaymentProviderInterface
{
    use BuildsJwt;

    public function provider(): string
    {
        return 'zaincash';
    }

    public function createPayment(Order $order, Payment $payment): PaymentRedirectData
    {
        $payload = [
            'amount' => (int) round((float) $payment->amount),
            'serviceType' => (string) config('services.zaincash.service_type', 'Yalla Spare order'),
            'msisdn' => (string) config('services.zaincash.msisdn'),
            'orderId' => (string) $order->id,
            'redirectUrl' => $payment->return_url,
            'iat' => now()->timestamp,
            'exp' => now()->addHour()->timestamp,
        ];

        $response = Http::asForm()
            ->acceptJson()
            ->post($this->url('/transaction/init'), [
                'token' => $this->encodeJwt($payload, $this->secret()),
                'merchantId' => (string) config('services.zaincash.merchant_id'),
                'lang' => in_array(app()->getLocale(), ['en', 'ar', 'ku'], true) ? app()->getLocale() : 'en',
            ])
            ->throw()
            ->json();

        $transactionId = (string) ($response['id'] ?? '');
        if ($transactionId === '') {
            throw new \RuntimeException('ZainCash did not return a transaction id.');
        }

        return new PaymentRedirectData(
            redirectUrl: $this->url('/transaction/pay?id=' . rawurlencode($transactionId)),
            providerPaymentId: $transactionId,
            providerTransactionId: $transactionId,
            rawResponse: is_array($response) ? $response : [],
        );
    }

    public function verifyPayment(Payment $payment): PaymentVerificationResult
    {
        if (! $payment->provider_payment_id) {
            return new PaymentVerificationResult(Payment::STATUS_FAILED, failureReason: 'missing_provider_payment_id');
        }

        $token = $this->encodeJwt([
            'id' => (string) $payment->provider_payment_id,
            'msisdn' => (string) config('services.zaincash.msisdn'),
            'iat' => now()->timestamp,
            'exp' => now()->addHour()->timestamp,
        ], $this->secret());

        $response = Http::asForm()
            ->acceptJson()
            ->post($this->url('/transaction/get'), [
                'token' => $token,
                'merchantId' => (string) config('services.zaincash.merchant_id'),
            ])
            ->throw()
            ->json();

        $rawStatus = strtolower((string) ($response['status'] ?? $response['transactionStatus'] ?? ''));
        $success = (bool) ($response['success'] ?? false);
        $mappedStatus = match (true) {
            $success || in_array($rawStatus, ['success', 'paid', 'completed', 'complete'], true) => Payment::STATUS_PAID,
            in_array($rawStatus, ['failed', 'failure', 'cancelled', 'canceled', 'expired', 'declined'], true) => Payment::STATUS_FAILED,
            default => Payment::STATUS_PENDING,
        };

        return new PaymentVerificationResult(
            status: $mappedStatus,
            providerPaymentId: (string) ($response['id'] ?? $payment->provider_payment_id),
            providerTransactionId: (string) ($response['transactionId'] ?? $response['id'] ?? $payment->provider_transaction_id ?? ''),
            providerReference: (string) ($response['orderId'] ?? $payment->provider_reference ?? ''),
            failureReason: (string) ($response['msg'] ?? $response['message'] ?? ''),
            rawResponse: is_array($response) ? $response : [],
        );
    }

    public function paymentIdFromWebhook(Request $request): ?string
    {
        $token = (string) $request->input('token', '');
        if ($token !== '') {
            $payload = $this->decodeJwt($token, $this->secret());
            $id = $payload['id'] ?? $payload['transactionId'] ?? null;
            if (is_scalar($id) && trim((string) $id) !== '') {
                return trim((string) $id);
            }
        }

        $id = $request->input('id', $request->input('transactionId'));

        return is_scalar($id) && trim((string) $id) !== '' ? trim((string) $id) : null;
    }

    public function validateWebhook(Request $request): bool
    {
        $expected = (string) config('services.zaincash.webhook_token', '');
        if ($expected === '') {
            return ! app()->environment('production');
        }

        $provided = (string) (
            $request->header('X-ZainCash-Webhook-Token')
            ?: $request->header('X-Payment-Webhook-Token')
            ?: $request->query('token', '')
        );

        return hash_equals($expected, $provided);
    }

    private function secret(): string
    {
        $secret = (string) config('services.zaincash.secret');
        if ($secret === '') {
            throw new \RuntimeException('ZainCash secret is not configured.');
        }

        return $secret;
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.zaincash.base_url'), '/') . $path;
    }
}
