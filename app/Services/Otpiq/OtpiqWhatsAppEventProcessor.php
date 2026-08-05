<?php

namespace App\Services\Otpiq;

use App\Models\OtpiqWebhookEvent;
use RuntimeException;

class OtpiqWhatsAppEventProcessor
{
    /**
     * Processing extension point for inbound WhatsApp automation. Persisting
     * and reviewing the event is the current business action; no outbound
     * OTPiQ request is made here.
     */
    public function process(OtpiqWebhookEvent $event): void
    {
        if (! $event->signature_verified) {
            throw new RuntimeException('Unverified webhook events cannot be processed.');
        }
    }
}
