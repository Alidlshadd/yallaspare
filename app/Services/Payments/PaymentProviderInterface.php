<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;

interface PaymentProviderInterface
{
    public function provider(): string;

    public function createPayment(Order $order, Payment $payment): PaymentRedirectData;

    public function verifyPayment(Payment $payment): PaymentVerificationResult;

    public function paymentIdFromWebhook(Request $request): ?string;

    public function validateWebhook(Request $request): bool;
}
