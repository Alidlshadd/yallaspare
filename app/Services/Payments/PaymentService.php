<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Payments\Providers\FibPaymentService;
use App\Services\Payments\Providers\ZainCashPaymentService;
use App\Support\UserCommunication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public const METHOD_COD = 'cash_on_delivery';

    public function __construct(
        private readonly FibPaymentService $fib,
        private readonly ZainCashPaymentService $zainCash,
    ) {
    }

    public function checkoutMethods(): array
    {
        return collect((array) config('payments.methods', []))
            ->only([self::METHOD_COD, 'fib', 'zaincash'])
            ->filter(fn (array $method): bool => (bool) ($method['enabled'] ?? false))
            ->map(fn (array $method, string $key): array => [
                'key' => $key,
                'label' => (string) ($method['label'] ?? $key),
                'online' => (bool) ($method['online'] ?? false),
            ])
            ->values()
            ->all();
    }

    public function allowedCheckoutMethods(): array
    {
        return array_column($this->checkoutMethods(), 'key');
    }

    public function isOnlineMethod(string $method): bool
    {
        return (bool) config("payments.methods.{$method}.online", false);
    }

    public function start(Order $order, string $method): Payment
    {
        $provider = $this->provider($method);

        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'provider' => $provider->provider(),
            'method' => $method,
            'status' => Payment::STATUS_PENDING,
            'amount' => (float) ($order->grand_total ?: $order->total_amount),
            'currency' => (string) config('payments.currency', 'IQD'),
            'return_url' => route('payments.return', ['payment' => '__payment__']),
            'metadata' => [
                'order_number' => $order->order_number,
            ],
        ]);

        $payment->forceFill([
            'return_url' => route('payments.return', $payment),
        ])->save();

        try {
            $redirect = $provider->createPayment($order, $payment);
        } catch (\Throwable $exception) {
            $payment->forceFill([
                'status' => Payment::STATUS_FAILED,
                'failed_at' => now(),
                'failure_reason' => 'provider_create_failed',
            ])->save();

            $order->forceFill(['payment_status' => Order::PAYMENT_FAILED])->save();

            Log::error('Payment creation failed', [
                'provider' => $provider->provider(),
                'payment_id' => $payment->id,
                'order_id' => $order->id,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        $payment->forceFill([
            'provider_payment_id' => $redirect->providerPaymentId ?: null,
            'provider_transaction_id' => $redirect->providerTransactionId ?: null,
            'provider_reference' => $redirect->providerReference ?: null,
            'redirect_url' => $redirect->redirectUrl,
            'provider_response' => $this->sanitizePayload($redirect->rawResponse),
        ])->save();

        Log::info('Payment created', [
            'provider' => $provider->provider(),
            'payment_id' => $payment->id,
            'order_id' => $order->id,
            'provider_payment_id' => $payment->provider_payment_id,
        ]);

        return $payment;
    }

    public function verifyAndApply(Payment $payment, string $source = 'server'): Payment
    {
        $provider = $this->provider($payment->provider);
        $result = $provider->verifyPayment($payment);

        $becamePaid = $this->applyVerification($payment, $result, $source);

        if ($becamePaid) {
            $fresh = $payment->fresh(['order.user']);
            if ($fresh?->order?->user) {
                UserCommunication::sendOrderPlaced($fresh->order->user, $fresh->order);
            }
        }

        return $payment->fresh(['order']);
    }

    public function handleWebhook(string $providerName, Request $request): ?Payment
    {
        $provider = $this->provider($providerName);
        if (! $provider->validateWebhook($request)) {
            Log::warning('Payment webhook rejected', [
                'provider' => $providerName,
                'reason' => 'invalid_token',
            ]);

            return null;
        }

        $providerPaymentId = $provider->paymentIdFromWebhook($request);
        if (! $providerPaymentId) {
            Log::warning('Payment webhook rejected', [
                'provider' => $providerName,
                'reason' => 'missing_payment_id',
            ]);

            return null;
        }

        $payment = Payment::query()
            ->where('provider', $provider->provider())
            ->where(function ($query) use ($providerPaymentId): void {
                $query->where('provider_payment_id', $providerPaymentId)
                    ->orWhere('provider_transaction_id', $providerPaymentId);
            })
            ->latest('id')
            ->first();

        if (! $payment) {
            Log::warning('Payment webhook rejected', [
                'provider' => $providerName,
                'reason' => 'payment_not_found',
                'provider_payment_id' => $providerPaymentId,
            ]);

            return null;
        }

        $payment->forceFill([
            'webhook_received_at' => now(),
            'metadata' => array_merge($payment->metadata ?? [], [
                'last_webhook' => $this->sanitizePayload($request->all()),
            ]),
        ])->save();

        return $this->verifyAndApply($payment, 'webhook');
    }

    public function sanitizePayload(array $payload): array
    {
        $blocked = ['authorization', 'access_token', 'refresh_token', 'token', 'secret', 'password', 'pin', 'cvv', 'cvc', 'card', 'pan', 'qrcode', 'qr_code'];

        $sanitize = function ($value, $key = null) use (&$sanitize, $blocked) {
            $keyText = strtolower((string) $key);
            foreach ($blocked as $blockedKey) {
                if ($keyText !== '' && str_contains($keyText, $blockedKey)) {
                    return '[redacted]';
                }
            }

            if (is_array($value)) {
                $clean = [];
                foreach ($value as $childKey => $childValue) {
                    $clean[$childKey] = $sanitize($childValue, $childKey);
                }

                return $clean;
            }

            return $value;
        };

        return $sanitize($payload);
    }

    private function applyVerification(Payment $payment, PaymentVerificationResult $result, string $source): bool
    {
        return DB::transaction(function () use ($payment, $result, $source): bool {
            $lockedPayment = Payment::query()
                ->whereKey($payment->id)
                ->with('order')
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPayment->status === Payment::STATUS_PAID) {
                return false;
            }

            $lockedPayment->forceFill([
                'status' => $result->status,
                'provider_payment_id' => $result->providerPaymentId ?: $lockedPayment->provider_payment_id,
                'provider_transaction_id' => $result->providerTransactionId ?: $lockedPayment->provider_transaction_id,
                'provider_reference' => $result->providerReference ?: $lockedPayment->provider_reference,
                'provider_response' => $this->sanitizePayload($result->rawResponse),
                'verified_at' => now(),
                'failure_reason' => $result->failureReason ?: $lockedPayment->failure_reason,
            ]);

            if ($result->isPaid()) {
                $lockedPayment->paid_at = now();
            } elseif ($result->isFailed()) {
                $lockedPayment->failed_at = now();
            }

            $lockedPayment->save();

            $order = $lockedPayment->order;
            if (! $order) {
                return $result->isPaid();
            }

            if ($result->isPaid()) {
                $previousStatus = (string) $order->status;
                $updates = [
                    'payment_status' => Order::PAYMENT_PAID,
                    'payment_reference' => $lockedPayment->provider_transaction_id
                        ?: $lockedPayment->provider_payment_id
                        ?: $lockedPayment->provider_reference,
                ];

                if ($order->status === Order::STATUS_PENDING) {
                    $updates['status'] = Order::STATUS_PROCESSING;
                }

                $order->forceFill($updates)->save();

                if (($updates['status'] ?? null) === Order::STATUS_PROCESSING) {
                    $order->statusHistory()->create([
                        'from_status' => $previousStatus,
                        'to_status' => Order::STATUS_PROCESSING,
                        'changed_by' => $order->user_id,
                        'note' => 'Payment verified via ' . $source,
                        'created_at' => now(),
                    ]);
                }

                return true;
            }

            if ($result->isFailed()) {
                $order->forceFill([
                    'payment_status' => Order::PAYMENT_FAILED,
                    'payment_reference' => $lockedPayment->provider_transaction_id
                        ?: $lockedPayment->provider_payment_id
                        ?: $lockedPayment->provider_reference,
                ])->save();
            }

            return false;
        });
    }

    private function provider(string $method): PaymentProviderInterface
    {
        return match ($method) {
            'fib' => $this->fib,
            'zaincash' => $this->zainCash,
            default => throw new \InvalidArgumentException("Unsupported payment provider [{$method}]."),
        };
    }
}
