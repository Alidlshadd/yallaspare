<?php

namespace App\Services\Payments;

use App\Models\Payment;

class PaymentVerificationResult
{
    public function __construct(
        public readonly string $status,
        public readonly ?string $providerPaymentId = null,
        public readonly ?string $providerTransactionId = null,
        public readonly ?string $providerReference = null,
        public readonly ?string $failureReason = null,
        public readonly array $rawResponse = [],
    ) {
    }

    public function isPaid(): bool
    {
        return $this->status === Payment::STATUS_PAID;
    }

    public function isFailed(): bool
    {
        return in_array($this->status, [Payment::STATUS_FAILED, Payment::STATUS_CANCELLED], true);
    }
}
