<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessOtpiqWhatsAppWebhook;
use App\Models\OtpiqWebhookEvent;
use App\Services\Otpiq\OtpiqInboundSettings;
use App\Services\Otpiq\OtpiqWebhookPayloadMapper;
use App\Services\Otpiq\OtpiqWebhookSignatureVerifier;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use JsonException;
use Throwable;

class OtpiqWhatsAppWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        OtpiqWebhookSignatureVerifier $signatureVerifier,
        OtpiqWebhookPayloadMapper $payloadMapper,
        OtpiqInboundSettings $inboundSettings,
    ): JsonResponse {
        abort_unless((bool) config('services.otpiq.whatsapp.admin_visible', true), 404);

        if (! $signatureVerifier->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Webhook service is not configured.',
            ], 503);
        }

        $rawBody = $request->getContent();
        $maxBodyBytes = max(1024, (int) config('services.otpiq.webhook_max_body_bytes', 1048576));

        if (strlen($rawBody) > $maxBodyBytes) {
            return response()->json([
                'success' => false,
                'message' => 'Webhook payload is too large.',
            ], 413);
        }

        $timestamp = trim((string) $request->header('X-OTPIQ-Webhook-Timestamp', ''));
        $providedSignature = trim((string) $request->header('X-OTPIQ-Webhook-Signature', ''));
        $eventId = trim((string) $request->header('X-OTPIQ-Webhook-Event-Id', ''));

        $missingHeaders = [];
        if ($timestamp === '') {
            $missingHeaders[] = 'X-OTPIQ-Webhook-Timestamp';
        }
        if ($providedSignature === '') {
            $missingHeaders[] = 'X-OTPIQ-Webhook-Signature';
        }
        if ($eventId === '') {
            $missingHeaders[] = 'X-OTPIQ-Webhook-Event-Id';
        }

        if ($missingHeaders !== []) {
            return response()->json([
                'success' => false,
                'message' => 'Missing required webhook headers.',
                'missing' => $missingHeaders,
            ], 400);
        }

        if (strlen($timestamp) > 255 || strlen($eventId) > 255) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid webhook header value.',
            ], 400);
        }

        if (! $signatureVerifier->verify($rawBody, $timestamp, $providedSignature)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid webhook signature.',
            ], 401);
        }

        try {
            $payload = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid JSON payload.',
            ], 400);
        }

        if (! is_array($payload)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid JSON payload.',
            ], 400);
        }

        $attemptHeader = trim((string) $request->header('X-OTPIQ-Webhook-Attempt', ''));
        $attemptNumber = ctype_digit($attemptHeader) && (int) $attemptHeader <= 4294967295
            ? (int) $attemptHeader
            : null;
        $processingEnabled = $inboundSettings->enabled();

        // The event id is a header the signature does not cover: OTPiQ signs
        // the timestamp and the body, nothing else. Left as the only identity,
        // a captured request could be replayed under a fresh id and stored as
        // a new event. The body is what was verified above, so its hash is
        // the identity that cannot be forged, and it is checked alongside the
        // id. OTPiQ's own retries (three at once, then hourly for two days)
        // carry the same body and are answered as the duplicates they are.
        //
        // No timestamp window is applied. The provider documents none, and
        // does not say whether a retry is re-stamped; a window tight enough
        // to matter could turn away a legitimate delivery two days late.
        // Events are kept, so this check has no expiry to outlast.
        $bodyHash = hash('sha256', $rawBody);

        if ($this->alreadyReceived($eventId, $bodyHash)) {
            return response()->json([
                'success' => true,
                'duplicate' => true,
            ]);
        }

        try {
            $event = OtpiqWebhookEvent::query()->create(array_merge(
                [
                    'event_id' => $eventId,
                    'body_hash' => $bodyHash,
                    'event_type' => $this->limitedHeader($request, 'X-OTPIQ-Webhook-Event'),
                    'attempt_number' => $attemptNumber,
                    'webhook_timestamp' => $timestamp,
                    'signature_verified' => true,
                    'processing_status' => $processingEnabled
                        ? OtpiqWebhookEvent::STATUS_RECEIVED
                        : OtpiqWebhookEvent::STATUS_IGNORED,
                    'raw_payload' => $payload,
                    'received_at' => now(),
                ],
                $payloadMapper->map($payload),
            ));
        } catch (QueryException) {
            // Two copies arriving together both pass the check above; the
            // unique indexes let one insert through and send the other here.
            if ($this->alreadyReceived($eventId, $bodyHash)) {
                return response()->json([
                    'success' => true,
                    'duplicate' => true,
                ]);
            }

            Log::error('OTPiQ webhook persistence failed', [
                'event_id' => $eventId,
                'event_type' => $this->limitedHeader($request, 'X-OTPIQ-Webhook-Event'),
                'processing_status' => 'persistence_failed',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to persist webhook event.',
            ], 500);
        } catch (Throwable) {
            Log::error('OTPiQ webhook persistence failed', [
                'event_id' => $eventId,
                'event_type' => $this->limitedHeader($request, 'X-OTPIQ-Webhook-Event'),
                'processing_status' => 'persistence_failed',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to persist webhook event.',
            ], 500);
        }

        if ($processingEnabled) {
            try {
                ProcessOtpiqWhatsAppWebhook::dispatch($event->id);
            } catch (Throwable) {
                $event->forceFill([
                    'processing_status' => OtpiqWebhookEvent::STATUS_FAILED,
                    'error_message' => 'Unable to dispatch processing job.',
                ])->save();

                Log::error('OTPiQ webhook job dispatch failed', [
                    'event_id' => $event->event_id,
                    'event_type' => $event->event_type,
                    'processing_status' => OtpiqWebhookEvent::STATUS_FAILED,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'duplicate' => false,
        ]);
    }

    private function alreadyReceived(string $eventId, string $bodyHash): bool
    {
        return OtpiqWebhookEvent::query()
            ->where('event_id', $eventId)
            ->orWhere('body_hash', $bodyHash)
            ->exists();
    }

    private function limitedHeader(Request $request, string $name): ?string
    {
        $value = trim((string) $request->header($name, ''));

        return $value === '' ? null : Str::limit($value, 255, '');
    }
}
