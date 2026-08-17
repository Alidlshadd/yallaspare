<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessOtpiqWhatsAppWebhook;
use App\Models\OtpiqWebhookEvent;
use App\Services\Otpiq\OtpiqInboundSettings;
use App\Support\AdminLogger;
use App\Support\SqlSafe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class OtpiqWhatsAppController extends Controller
{
    public function index(Request $request, OtpiqInboundSettings $inboundSettings): View
    {
        $this->ensureVisible();

        $filters = $request->validate([
            'phone' => ['nullable', 'string', 'max:120'],
            'event_type' => ['nullable', 'string', 'max:255'],
            'message_type' => ['nullable', 'string', 'max:255'],
            'processing_status' => ['nullable', 'in:received,processing,processed,ignored,failed'],
            'read_status' => ['nullable', 'in:read,unread'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);

        $query = OtpiqWebhookEvent::query()->notArchived();

        if (! empty($filters['phone'])) {
            SqlSafe::whereLike($query, 'sender_phone', $filters['phone']);
        }
        if (! empty($filters['event_type'])) {
            $query->where('event_type', $filters['event_type']);
        }
        if (! empty($filters['message_type'])) {
            $query->where('message_type', $filters['message_type']);
        }
        if (! empty($filters['processing_status'])) {
            $query->where('processing_status', $filters['processing_status']);
        }
        if (($filters['read_status'] ?? null) === 'read') {
            $query->whereNotNull('read_at');
        } elseif (($filters['read_status'] ?? null) === 'unread') {
            $query->unread();
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('received_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('received_at', '<=', $filters['date_to']);
        }

        $events = $query->latest('received_at')->latest('id')->paginate(20)->withQueryString();
        $stats = [
            'today' => OtpiqWebhookEvent::query()->whereDate('received_at', now()->toDateString())->count(),
            'unread' => OtpiqWebhookEvent::query()->notArchived()->unread()->count(),
            'processed' => OtpiqWebhookEvent::query()->processed()->count(),
            'failed' => OtpiqWebhookEvent::query()->failed()->count(),
            'total' => OtpiqWebhookEvent::query()->count(),
        ];

        $lastEvent = OtpiqWebhookEvent::query()->latest('received_at')->first();
        $lastSuccessfulEvent = OtpiqWebhookEvent::query()->processed()->latest('processed_at')->first();
        $lastFailedEvent = OtpiqWebhookEvent::query()->failed()->latest('updated_at')->first();

        return view('admin.whatsapp.index', [
            'events' => $events,
            'stats' => $stats,
            'filters' => $filters,
            'processingEnabled' => $inboundSettings->enabled(),
            'webhookUrl' => route('webhooks.otpiq'),
            'secretConfigured' => filled(config('services.otpiq.webhook_secret')),
            'whatsappConfigured' => (bool) config('services.otpiq.whatsapp_enabled', false),
            'lastEvent' => $lastEvent,
            'lastSuccessfulEvent' => $lastSuccessfulEvent,
            'lastFailedEvent' => $lastFailedEvent,
            'queueStatus' => $this->queueStatus(),
            'eventTypes' => OtpiqWebhookEvent::query()->whereNotNull('event_type')->distinct()->orderBy('event_type')->pluck('event_type'),
            'messageTypes' => OtpiqWebhookEvent::query()->whereNotNull('message_type')->distinct()->orderBy('message_type')->pluck('message_type'),
        ]);
    }

    public function events(Request $request, OtpiqInboundSettings $inboundSettings): View
    {
        return $this->index($request, $inboundSettings);
    }

    public function show(OtpiqWebhookEvent $event): View
    {
        $this->ensureVisible();

        return view('admin.whatsapp.show', compact('event'));
    }

    public function markRead(OtpiqWebhookEvent $event): RedirectResponse
    {
        $this->ensureVisible();

        $event->forceFill(['read_at' => now()])->save();
        AdminLogger::log('otpiq_webhook.marked_read', $event, ['event_id' => $event->event_id]);

        return back()->with('success', __('Webhook event marked as read.'));
    }

    public function markUnread(OtpiqWebhookEvent $event): RedirectResponse
    {
        $this->ensureVisible();

        $event->forceFill(['read_at' => null])->save();
        AdminLogger::log('otpiq_webhook.marked_unread', $event, ['event_id' => $event->event_id]);

        return back()->with('success', __('Webhook event marked as unread.'));
    }

    public function archive(OtpiqWebhookEvent $event): RedirectResponse
    {
        $this->ensureVisible();

        $event->forceFill(['archived_at' => now()])->save();
        AdminLogger::log('otpiq_webhook.archived', $event, ['event_id' => $event->event_id]);

        return redirect()->route('admin.whatsapp.index')->with('success', __('Webhook event archived.'));
    }

    public function retry(OtpiqWebhookEvent $event): RedirectResponse
    {
        $this->ensureVisible();

        $reset = OtpiqWebhookEvent::query()
            ->whereKey($event->id)
            ->where('processing_status', OtpiqWebhookEvent::STATUS_FAILED)
            ->update([
                'processing_status' => OtpiqWebhookEvent::STATUS_RECEIVED,
                'error_message' => null,
                'processed_at' => null,
                'updated_at' => now(),
            ]);

        if ($reset !== 1) {
            return back()->withErrors(['retry' => __('Only failed webhook events can be retried.')]);
        }

        try {
            ProcessOtpiqWhatsAppWebhook::dispatch($event->id);
        } catch (Throwable) {
            $event->forceFill([
                'processing_status' => OtpiqWebhookEvent::STATUS_FAILED,
                'error_message' => 'Unable to dispatch processing job.',
            ])->save();

            return back()->withErrors(['retry' => __('The retry job could not be queued. Please try again.')]);
        }

        AdminLogger::log('otpiq_webhook.retried', $event, ['event_id' => $event->event_id]);

        return back()->with('success', __('Webhook event queued for retry.'));
    }

    public function updateProcessing(Request $request, OtpiqInboundSettings $inboundSettings): RedirectResponse
    {
        $this->ensureVisible();

        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $enabled = (bool) $validated['enabled'];
        $inboundSettings->setEnabled($enabled);
        AdminLogger::log('otpiq_webhook.processing_toggled', null, ['enabled' => $enabled]);

        return back()->with('success', $enabled
            ? __('Inbound WhatsApp processing enabled.')
            : __('Inbound WhatsApp processing disabled.'));
    }

    /** @return array{connection: string, mode: string, pending: ?int, failed: ?int} */
    private function queueStatus(): array
    {
        $connection = (string) config('queue.default', 'sync');
        $pending = null;
        $failed = null;

        try {
            if ($connection === 'database' && Schema::hasTable('jobs')) {
                $pending = DB::table('jobs')->count();
            }
            if (Schema::hasTable('failed_jobs')) {
                $failed = DB::table('failed_jobs')->count();
            }
        } catch (Throwable) {
            // Configuration remains safe to show even when queue metrics are unavailable.
        }

        return [
            'connection' => $connection,
            'mode' => $connection === 'sync' ? 'synchronous' : 'queued',
            'pending' => $pending,
            'failed' => $failed,
        ];
    }

    private function ensureVisible(): void
    {
        abort_unless((bool) config('services.otpiq.whatsapp.visible', false), 404);
    }
}
