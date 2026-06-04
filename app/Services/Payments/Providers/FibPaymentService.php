<?php

namespace App\Services\Payments\Providers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Payments\PaymentProviderInterface;
use App\Services\Payments\PaymentRedirectData;
use App\Services\Payments\PaymentVerificationResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FibPaymentService implements PaymentProviderInterface
{
    public function provider(): string
    {
        return 'fib';
    }

    public function createPayment(Order $order, Payment $payment): PaymentRedirectData
    {
        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->asJson()
            ->post($this->url('/protected/v1/payments'), [
                'monetaryValue' => [
                    'amount' => number_format((float) $payment->amount, 2, '.', ''),
                    'currency' => $payment->currency,
                ],
                'statusCallbackUrl' => route('payments.webhook', ['provider' => $this->provider()]),
                'description' => substr((string) $order->order_number, 0, 50),
            ])
            ->throw()
            ->json();

        $redirectUrl = (string) ($response['personalAppLink'] ?? $response['businessAppLink'] ?? $response['corporateAppLink'] ?? '');
        if ($redirectUrl === '') {
            throw new \RuntimeException('FIB did not return a redirect link.');
        }

        return new PaymentRedirectData(
            redirectUrl: $redirectUrl,
            providerPaymentId: (string) ($response['paymentId'] ?? ''),
            providerReference: (string) ($response['readableCode'] ?? ''),
            rawResponse: is_array($response) ? $response : [],
        );
    }

    public function verifyPayment(Payment $payment): PaymentVerificationResult
    {
        if (! $payment->provider_payment_id) {
            return new PaymentVerificationResult(Payment::STATUS_FAILED, failureReason: 'missing_provider_payment_id');
        }

        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->get($this->url('/protected/v1/payments/' . rawurlencode((string) $payment->provider_payment_id) . '/status'))
            ->throw()
            ->json();

        $status = strtoupper((string) ($response['status'] ?? ''));
        $mappedStatus = match ($status) {
            'PAID' => Payment::STATUS_PAID,
            'DECLINED', 'EXPIRED', 'CANCELLED' => Payment::STATUS_FAILED,
            default => Payment::STATUS_PENDING,
        };

        return new PaymentVerificationResult(
            status: $mappedStatus,
            providerPaymentId: (string) ($response['paymentId'] ?? $payment->provider_payment_id),
            providerTransactionId: (string) ($response['transactionId'] ?? $response['transaction_id'] ?? ''),
            providerReference: (string) ($response['readableCode'] ?? $payment->provider_reference ?? ''),
            failureReason: (string) ($response['decliningReason'] ?? ''),
            rawResponse: is_array($response) ? $response : [],
        );
    }

    public function paymentIdFromWebhook(Request $request): ?string
    {
        $id = $request->input('id', $request->input('paymentId'));

        return is_scalar($id) && trim((string) $id) !== '' ? trim((string) $id) : null;
    }

    public function validateWebhook(Request $request): bool
    {
        $expected = (string) config('services.fib.webhook_token', '');
        if ($expected === '') {
            return ! app()->environment('production');
        }

        $provided = (string) (
            $request->header('X-FIB-Webhook-Token')
            ?: $request->header('X-Payment-Webhook-Token')
            ?: $request->query('token', '')
        );

        return hash_equals($expected, $provided);
    }

    private function accessToken(): string
    {
        $response = Http::asForm()
            ->acceptJson()
            ->post($this->url('/auth/realms/fib-online-shop/protocol/openid-connect/token'), [
                'grant_type' => 'client_credentials',
                'client_id' => (string) config('services.fib.client_id'),
                'client_secret' => (string) config('services.fib.client_secret'),
            ])
            ->throw()
            ->json();

        $token = (string) ($response['access_token'] ?? '');
        if ($token === '') {
            throw new \RuntimeException('FIB authorization did not return an access token.');
        }

        return $token;
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.fib.base_url'), '/') . $path;
    }
}
