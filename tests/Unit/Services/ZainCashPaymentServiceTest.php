<?php

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Payments\PaymentVerificationResult;
use App\Services\Payments\Providers\ZainCashPaymentService;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * ZainCash, exercised at the service rather than through checkout.
 *
 * Two calls leave this class and both carry a signed JWT that the provider
 * rejects outright if a single claim is wrong, so the assertions open the
 * token back up and read it rather than trusting that something was sent.
 *
 * Nothing here touches the database — the service reads an id off the order
 * and three fields off the payment — and preventStrayRequests turns any call
 * this file has not faked into a failure rather than a request to the real
 * provider.
 */
class ZainCashPaymentServiceTest extends TestCase
{
    private const SECRET = 'zaincash-unit-test-secret';

    private const MERCHANT = 'merchant-abc';

    private const MSISDN = '9647700000000';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        config([
            'services.zaincash.base_url' => 'https://zaincash.test',
            'services.zaincash.merchant_id' => self::MERCHANT,
            'services.zaincash.msisdn' => self::MSISDN,
            'services.zaincash.secret' => self::SECRET,
            'services.zaincash.service_type' => 'Yalla Spare order',
        ]);
    }

    public function test_create_payment_signs_the_order_and_returns_the_pay_link(): void
    {
        Http::fake([
            'https://zaincash.test/transaction/init' => Http::response(['id' => 'zc-txn-123']),
        ]);

        $result = $this->service()->createPayment(
            $this->order(4242),
            $this->payment(['amount' => 25000.0, 'return_url' => 'https://shop.test/payments/7/return'])
        );

        $this->assertSame('https://zaincash.test/transaction/pay?id=zc-txn-123', $result->redirectUrl);
        $this->assertSame('zc-txn-123', $result->providerPaymentId);
        $this->assertSame('zc-txn-123', $result->providerTransactionId);
        $this->assertSame(['id' => 'zc-txn-123'], $result->rawResponse);

        Http::assertSent(function (HttpRequest $request): bool {
            $claims = $this->readJwt((string) $request['token']);

            return $request->method() === 'POST'
                && $request->url() === 'https://zaincash.test/transaction/init'
                && $request->isForm()
                && $request['merchantId'] === self::MERCHANT
                && $request['lang'] === 'en'
                && $claims['amount'] === 25000
                && $claims['serviceType'] === 'Yalla Spare order'
                && $claims['msisdn'] === self::MSISDN
                && $claims['orderId'] === '4242'
                && $claims['redirectUrl'] === 'https://shop.test/payments/7/return'
                && $claims['exp'] > $claims['iat'];
        });
    }

    public function test_create_payment_rounds_the_amount_to_whole_dinars(): void
    {
        Http::fake([
            'https://zaincash.test/transaction/init' => Http::response(['id' => 'zc-txn-124']),
        ]);

        // IQD has no minor unit and ZainCash takes the amount as an integer.
        $this->service()->createPayment($this->order(1), $this->payment(['amount' => 25000.6]));

        Http::assertSent(function (HttpRequest $request): bool {
            $claims = $this->readJwt((string) $request['token']);

            return $claims['amount'] === 25001;
        });
    }

    public function test_create_payment_sends_the_active_locale(): void
    {
        Http::fake([
            'https://zaincash.test/transaction/init' => Http::response(['id' => 'zc-txn-125']),
        ]);

        $this->app->setLocale('ar');

        $this->service()->createPayment($this->order(1), $this->payment());

        Http::assertSent(fn (HttpRequest $request): bool => $request['lang'] === 'ar');
    }

    public function test_create_payment_falls_back_to_english_for_a_locale_zaincash_does_not_take(): void
    {
        Http::fake([
            'https://zaincash.test/transaction/init' => Http::response(['id' => 'zc-txn-126']),
        ]);

        $this->app->setLocale('fr');

        $this->service()->createPayment($this->order(1), $this->payment());

        Http::assertSent(fn (HttpRequest $request): bool => $request['lang'] === 'en');
    }

    public function test_create_payment_refuses_a_response_without_a_transaction_id(): void
    {
        Http::fake([
            'https://zaincash.test/transaction/init' => Http::response(['err' => 'no id here']),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ZainCash did not return a transaction id.');

        $this->service()->createPayment($this->order(1), $this->payment());
    }

    public function test_create_payment_refuses_to_sign_without_a_configured_secret(): void
    {
        config(['services.zaincash.secret' => '']);
        Http::fake();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ZainCash secret is not configured.');

        $this->service()->createPayment($this->order(1), $this->payment());
    }

    public function test_verify_payment_signs_the_transaction_id_and_reads_a_settled_transaction(): void
    {
        Http::fake([
            'https://zaincash.test/transaction/get' => Http::response([
                'id' => 'zc-txn-123',
                'status' => 'success',
                'transactionId' => 'zc-inner-9',
                'orderId' => '4242',
            ]),
        ]);

        $result = $this->service()->verifyPayment($this->payment(['provider_payment_id' => 'zc-txn-123']));

        $this->assertSame(Payment::STATUS_PAID, $result->status);
        $this->assertTrue($result->isPaid());
        $this->assertSame('zc-txn-123', $result->providerPaymentId);
        $this->assertSame('zc-inner-9', $result->providerTransactionId);
        $this->assertSame('4242', $result->providerReference);

        Http::assertSent(function (HttpRequest $request): bool {
            $claims = $this->readJwt((string) $request['token']);

            return $request->method() === 'POST'
                && $request->url() === 'https://zaincash.test/transaction/get'
                && $request->isForm()
                && $request['merchantId'] === self::MERCHANT
                && $claims['id'] === 'zc-txn-123'
                && $claims['msisdn'] === self::MSISDN
                && $claims['exp'] > $claims['iat'];
        });
    }

    /**
     * The success flag is read before the status word, so a response that sets
     * one and not the other still resolves to paid.
     *
     * This records what the service does today; it is not an endorsement. If
     * ZainCash sets `success` to mean "the API call worked" rather than "the
     * money arrived", then a pending transaction reaches applyVerification as
     * paid and the order ships. Confirm the meaning against the provider's
     * documentation before relying on this line.
     */
    public function test_verify_payment_treats_the_success_flag_as_paid_on_its_own(): void
    {
        $result = $this->verifyWith(['success' => true, 'status' => 'something-else']);

        $this->assertSame(Payment::STATUS_PAID, $result->status);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function paidStatuses(): array
    {
        return [
            'success' => ['success'],
            'paid' => ['paid'],
            'completed' => ['completed'],
            'complete' => ['complete'],
            'mixed case' => ['CoMpLeTeD'],
        ];
    }

    #[DataProvider('paidStatuses')]
    public function test_verify_payment_maps_a_settled_status_to_paid(string $status): void
    {
        $this->assertSame(Payment::STATUS_PAID, $this->verifyWith(['status' => $status])->status);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function failedStatuses(): array
    {
        return [
            'failed' => ['failed'],
            'failure' => ['failure'],
            'cancelled' => ['cancelled'],
            'canceled' => ['canceled'],
            'expired' => ['expired'],
            'declined' => ['declined'],
            'upper case' => ['DECLINED'],
        ];
    }

    #[DataProvider('failedStatuses')]
    public function test_verify_payment_maps_a_rejected_status_to_failed(string $status): void
    {
        $result = $this->verifyWith(['status' => $status]);

        $this->assertSame(Payment::STATUS_FAILED, $result->status);
        $this->assertTrue($result->isFailed());
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function pendingStatuses(): array
    {
        return [
            'pending' => ['pending'],
            'processing' => ['processing'],
            'empty' => [''],
            'unrecognised' => ['something-new'],
        ];
    }

    #[DataProvider('pendingStatuses')]
    public function test_verify_payment_leaves_anything_it_does_not_recognise_pending(string $status): void
    {
        $result = $this->verifyWith(['status' => $status]);

        $this->assertSame(Payment::STATUS_PENDING, $result->status);
        $this->assertFalse($result->isPaid());
        $this->assertFalse($result->isFailed());
    }

    /**
     * ZainCash names this field two ways depending on the endpoint.
     */
    public function test_verify_payment_reads_transaction_status_when_status_is_absent(): void
    {
        $this->assertSame(
            Payment::STATUS_FAILED,
            $this->verifyWith(['transactionStatus' => 'declined'])->status
        );
    }

    public function test_verify_payment_carries_the_provider_message_as_the_failure_reason(): void
    {
        $result = $this->verifyWith(['status' => 'failed', 'msg' => 'Insufficient balance']);

        $this->assertSame('Insufficient balance', $result->failureReason);
    }

    public function test_verify_payment_fails_without_calling_the_provider_when_the_id_is_missing(): void
    {
        Http::fake();

        $result = $this->service()->verifyPayment($this->payment(['provider_payment_id' => null]));

        $this->assertSame(Payment::STATUS_FAILED, $result->status);
        $this->assertSame('missing_provider_payment_id', $result->failureReason);
        $this->assertNull($result->providerPaymentId);
        Http::assertNothingSent();
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function verifyWith(array $body): PaymentVerificationResult
    {
        Http::fake([
            'https://zaincash.test/transaction/get' => Http::response($body + ['id' => 'zc-txn-123']),
        ]);

        return $this->service()->verifyPayment($this->payment(['provider_payment_id' => 'zc-txn-123']));
    }

    private function service(): ZainCashPaymentService
    {
        return new ZainCashPaymentService;
    }

    private function order(int $id): Order
    {
        $order = new Order;
        $order->id = $id;

        return $order;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function payment(array $attributes = []): Payment
    {
        $payment = new Payment;
        $payment->amount = $attributes['amount'] ?? 25000.0;
        $payment->return_url = $attributes['return_url'] ?? 'https://shop.test/payments/1/return';
        $payment->provider_payment_id = $attributes['provider_payment_id'] ?? null;

        return $payment;
    }

    /**
     * Open the token the service signed, checking the signature on the way in,
     * so no claim is ever read off a token ZainCash would have rejected.
     *
     * @return array<string, mixed>
     */
    private function readJwt(string $jwt): array
    {
        [$header, $payload, $signature] = explode('.', $jwt);

        $expected = rtrim(strtr(base64_encode(
            hash_hmac('sha256', $header.'.'.$payload, self::SECRET, true)
        ), '+/', '-_'), '=');

        $this->assertSame($expected, $signature, 'The JWT was not signed with the configured secret.');
        $this->assertSame(
            ['alg' => 'HS256', 'typ' => 'JWT'],
            json_decode((string) base64_decode(strtr($header, '-_', '+/')), true)
        );

        return json_decode((string) base64_decode(strtr($payload, '-_', '+/')), true);
    }
}
