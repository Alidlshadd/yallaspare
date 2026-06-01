<?php

namespace App\Services\Payments;

class PaymentRedirectData
{
    public function __construct(
        public readonly string $redirectUrl,
        public readonly ?string $providerPaymentId = null,
        public readonly ?string $providerTransactionId = null,
        public readonly ?string $providerReference = null,
        public readonly array $rawResponse = [],
    ) {
    }
}
