<?php

namespace App\Services\Payments\Providers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Payments\Concerns\BuildsJwt;
use App\Services\Payments\PaymentProviderInterface;
use App\Services\Payments\PaymentRedirectData;
use App\Services\Payments\PaymentVerificationResult;
use Illuminate\Http\Client\PendingRequest;
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

        $response = $this->request()
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
            redirectUrl: $this->url('/transaction/pay?id='.rawurlencode($transactionId)),
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

        $response = $this->request()
            ->post($this->url('/transaction/get'), [
                'token' => $token,
                'merchantId' => (string) config('services.zaincash.merchant_id'),
            ])
            ->throw()
            ->json();

        $response = is_array($response) ? $response : [];
        $rawStatus = strtolower(trim((string) ($response['status'] ?? $response['transactionStatus'] ?? '')));

        // Only a status word can say the money arrived. A "success" flag is
        // not read at all: it may mean no more than "the lookup worked", and
        // a reply this code cannot read for certain must never ship an order.
        $mappedStatus = match (true) {
            in_array($rawStatus, ['success', 'paid', 'completed', 'complete'], true) => Payment::STATUS_PAID,
            in_array($rawStatus, ['failed', 'failure', 'cancelled', 'canceled', 'expired', 'declined'], true) => Payment::STATUS_FAILED,
            default => Payment::STATUS_PENDING,
        };

        $failureReason = (string) ($response['msg'] ?? $response['message'] ?? '');

        // "Paid" is believed only for the transaction that was asked about:
        // the same id, for this order, for the amount charged. A paid reply
        // about something else is refused outright; one that leaves any of
        // the three out proves nothing and stays pending.
        if ($mappedStatus === Payment::STATUS_PAID) {
            $unproven = $this->unprovenIdentity($response, $payment);

            if ($unproven !== null) {
                $mappedStatus = Payment::STATUS_PENDING;
                $failureReason = $unproven;
            }
        }

        return new PaymentVerificationResult(
            status: $mappedStatus,
            providerPaymentId: (string) ($response['id'] ?? $payment->provider_payment_id),
            providerTransactionId: (string) ($response['transactionId'] ?? $response['id'] ?? $payment->provider_transaction_id ?? ''),
            providerReference: (string) ($response['orderId'] ?? $response['orderid'] ?? $payment->provider_reference ?? ''),
            failureReason: $failureReason,
            rawResponse: $response,
        );
    }

    /**
     * Why a reply that says "paid" cannot be taken as payment for this
     * payment, or null when it can.
     *
     * A value that is present and wrong is a different transaction, and
     * throws: that is not something to wait out. A value that is missing
     * returns a reason, and the payment waits.
     *
     * The currency is checked when it is sent. ZainCash settles in dinars
     * and the amount was sent in dinars, so its absence is not held against
     * the reply; a reply naming another currency is.
     *
     * @param  array<string, mixed>  $response
     */
    private function unprovenIdentity(array $response, Payment $payment): ?string
    {
        $id = $this->scalar($response['id'] ?? null);
        if ($id !== null && ! hash_equals((string) $payment->provider_payment_id, $id)) {
            throw new \RuntimeException('ZainCash returned a different transaction.');
        }

        $orderId = $this->scalar($response['orderId'] ?? $response['orderid'] ?? null);
        if ($orderId !== null && ! hash_equals((string) $payment->order_id, $orderId)) {
            throw new \RuntimeException('ZainCash returned a transaction for a different order.');
        }

        $amount = $response['amount'] ?? null;
        if (is_numeric($amount) && abs((float) $amount - round((float) $payment->amount)) > 0.0001) {
            throw new \RuntimeException('ZainCash returned a mismatched payment amount.');
        }

        $currency = $this->scalar($response['currency'] ?? null);
        if ($currency !== null && strtoupper($currency) !== 'IQD') {
            throw new \RuntimeException('ZainCash returned a mismatched payment currency.');
        }

        return match (true) {
            $id === null => 'zaincash_reply_without_transaction_id',
            $orderId === null => 'zaincash_reply_without_order_id',
            ! is_numeric($amount) => 'zaincash_reply_without_amount',
            default => null,
        };
    }

    private function scalar(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
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

        // Header-only: a token in the query string would leak into access
        // logs and intermediate proxies.
        $provided = (string) (
            $request->header('X-ZainCash-Webhook-Token')
            ?: $request->header('X-Payment-Webhook-Token')
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

    /**
     * Both ZainCash calls sit inside a checkout request, so they get an
     * explicit ceiling instead of the client's 30-second default.
     */
    private function request(): PendingRequest
    {
        return Http::asForm()
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(15);
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.zaincash.base_url'), '/').$path;
    }
}
