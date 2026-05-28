<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendBroadcastEmailJob;
use App\Mail\BroadcastMail;
use App\Models\EmailBroadcast;
use App\Models\EmailBroadcastRecipient;
use App\Support\HtmlSanitizer;
use App\Support\RecipientFilter;
use App\Support\SecureImageStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;

class EmailBroadcastController extends Controller
{
    public function __construct(private readonly HtmlSanitizer $sanitizer)
    {
    }

    public function previewRecipients(Request $request): JsonResponse
    {
        $filter = new RecipientFilter((array) $request->input('filters', []));
        $query = $filter->query();

        return response()->json([
            'count' => $query->count(),
            'first10' => $query->limit(10)->get(['id', 'name', 'email', 'role'])->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->role,
            ])->all(),
            'filters_normalized' => $filter->normalize(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body_html' => ['required', 'string'],
            'filters' => ['nullable'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240'],
        ]);

        $total = collect($request->file('attachments', []))->sum(fn ($f) => $f->getSize());
        abort_if($total > 25 * 1024 * 1024, 422, 'Total attachment size exceeds 25MB.');

        // The form posts filters as a JSON-encoded string from the broadcast UI;
        // accept both JSON-encoded and already-decoded arrays for robustness.
        $rawFilters = $data['filters'] ?? [];
        if (is_string($rawFilters)) {
            $decoded = json_decode($rawFilters, true);
            $rawFilters = is_array($decoded) ? $decoded : [];
        }

        $sanitized = $this->sanitizer->clean($data['body_html']);

        $attachments = collect($request->file('attachments', []))
            ->map(fn ($file) => [
                'path' => SecureImageStorage::storeAttachment($file, 'email-attachments'),
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ])
            ->all();

        $filter = new RecipientFilter($rawFilters);
        $users = $filter->query()->get(['id', 'email']);

        $broadcast = new EmailBroadcast();
        $broadcast->forceFill([
            'admin_user_id' => $request->user()->id,
            'subject' => $data['subject'],
            'body_html' => $sanitized,
            'attachments' => $attachments,
            'filters_snapshot' => $filter->normalize(),
            'recipient_count' => $users->count(),
            'status' => EmailBroadcast::STATUS_QUEUED,
        ])->save();

        $rows = $users->map(fn ($u) => [
            'broadcast_id' => $broadcast->id,
            'user_id' => $u->id,
            'email' => $u->email,
            'status' => EmailBroadcastRecipient::STATUS_QUEUED,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all();

        if ($rows !== []) {
            EmailBroadcastRecipient::insert($rows);

            $jobIds = EmailBroadcastRecipient::where('broadcast_id', $broadcast->id)->pluck('id');
            $jobs = $jobIds->map(fn ($id) => new SendBroadcastEmailJob($broadcast->id, $id))->all();

            $broadcastId = $broadcast->id;
            $batch = Bus::batch($jobs)
                ->onQueue('mail-broadcast')
                ->name('email-broadcast-' . $broadcastId)
                ->then(fn () => EmailBroadcast::where('id', $broadcastId)->update([
                    'status' => EmailBroadcast::STATUS_COMPLETED, 'sent_at' => now(),
                ]))
                ->catch(fn () => EmailBroadcast::where('id', $broadcastId)->update([
                    'status' => EmailBroadcast::STATUS_FAILED,
                ]))
                ->dispatch();

            $broadcast->forceFill([
                'batch_id' => $batch->id,
                'status' => EmailBroadcast::STATUS_SENDING,
            ])->save();
        }

        return redirect()
            ->route('admin.email.index')
            ->with('success', __('Broadcast queued: :count recipients.', ['count' => $users->count()]));
    }

    public function sendTestToSelf(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body_html' => ['required', 'string'],
        ]);

        Mail::to($request->user())->send(new BroadcastMail(
            subjectLine: $data['subject'],
            bodyHtml: $this->sanitizer->clean($data['body_html']),
        ));

        return redirect()
            ->route('admin.email.index')
            ->with('success', __('Test broadcast sent to :email.', ['email' => $request->user()->email]));
    }
}
