<?php

namespace App\Services\Payments;

use RuntimeException;
use Throwable;

class WaylProviderException extends RuntimeException
{
    /** @param array<string|int, mixed> $validationErrors */
    public function __construct(
        public readonly ?int $httpStatus,
        string $safeMessage,
        public readonly array $validationErrors = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($safeMessage, $httpStatus ?? 0, $previous);
    }
}
