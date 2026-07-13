<?php

namespace App\Services;

/**
 * Safe representation of a successful OTPiQ delivery response. Holds only
 * non-sensitive bookkeeping fields; never the OTP or the recipient number.
 */
class OtpiqSendResult
{
    public function __construct(
        public readonly string $smsId,
        public readonly ?int $remainingCredit = null,
        public readonly ?int $cost = null,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromResponse(array $payload): self
    {
        return new self(
            smsId: (string) $payload['smsId'],
            remainingCredit: isset($payload['remainingCredit']) ? (int) $payload['remainingCredit'] : null,
            cost: isset($payload['cost']) ? (int) $payload['cost'] : null,
        );
    }
}
