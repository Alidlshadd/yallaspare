<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * An uploaded image that passed validation but could not be kept.
 *
 * It is still an HTTP error, so a caller that does nothing about it answers
 * exactly as before. A caller that wants to send the admin back to their form
 * can catch it and read the reason instead of parsing a message.
 */
class ImageUploadException extends HttpException
{
    public const UNSUPPORTED = 'unsupported';

    public const TOO_MANY_PIXELS = 'too_many_pixels';

    public const NOT_SAVED = 'not_saved';

    private function __construct(public readonly string $reason, int $status, string $message)
    {
        parent::__construct($status, $message);
    }

    public static function unsupported(): self
    {
        return new self(self::UNSUPPORTED, 422, 'Unsupported or unverifiable image format.');
    }

    public static function tooManyPixels(): self
    {
        return new self(self::TOO_MANY_PIXELS, 422, 'Image dimensions are too large to process.');
    }

    public static function notSaved(): self
    {
        return new self(self::NOT_SAVED, 500, 'The image could not be written to storage.');
    }
}
