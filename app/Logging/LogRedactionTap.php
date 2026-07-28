<?php

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\Handler\ProcessableHandlerInterface;

class LogRedactionTap
{
    public function __invoke(Logger $logger): void
    {
        $processor = new RedactingProcessor;

        foreach ($logger->getHandlers() as $handler) {
            // Not every Monolog handler accepts processors — NullHandler and
            // NoopHandler extend Handler directly and have no pushProcessor.
            // The papertrail channel takes its handler from an env var and is
            // tapped, so an unchecked call here is one setting away from a
            // fatal error. Handlers without processor support discard records
            // anyway, so skipping them redacts nothing less.
            if ($handler instanceof ProcessableHandlerInterface) {
                $handler->pushProcessor($processor);
            }
        }
    }
}
