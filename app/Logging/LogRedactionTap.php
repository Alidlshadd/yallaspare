<?php

namespace App\Logging;

use Illuminate\Log\Logger;

class LogRedactionTap
{
    public function __invoke(Logger $logger): void
    {
        $processor = new RedactingProcessor();

        foreach ($logger->getHandlers() as $handler) {
            $handler->pushProcessor($processor);
        }
    }
}
