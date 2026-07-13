<?php

namespace App\Services;

use RuntimeException;
use Throwable;

/**
 * Raised when OTPiQ cannot deliver a verification message. The message and
 * category are always safe to log: they never contain the API key, the OTP,
 * a full phone number, or raw provider response bodies.
 */
class OtpiqDeliveryException extends RuntimeException
{
    public const CATEGORY_NOT_CONFIGURED = 'not_configured';

    public const CATEGORY_UNAUTHORIZED = 'unauthorized';

    public const CATEGORY_INSUFFICIENT_CREDIT = 'insufficient_credit';

    public const CATEGORY_SPENDING_THRESHOLD = 'spending_threshold';

    public const CATEGORY_TRIAL_MODE = 'trial_mode';

    public const CATEGORY_RATE_LIMITED = 'rate_limited';

    public const CATEGORY_VALIDATION = 'validation';

    public const CATEGORY_SERVER_ERROR = 'server_error';

    public const CATEGORY_CONNECTION = 'connection';

    public const CATEGORY_INVALID_RESPONSE = 'invalid_response';

    public function __construct(
        public readonly string $category,
        string $message,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
