<x-app-layout>
    <x-slot name="header"><h2>{{ __('WhatsApp webhook details') }}</h2></x-slot>

    @php
        $prettyPayload = json_encode($event->raw_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
        $statusClass = match($event->processing_status) {
            'processed' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
            'failed' => 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300',
            'ignored' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
            'processing' => 'bg-info text-info dark:bg-info/10 dark:text-info',
            default => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
        };
    @endphp

    <div class="mx-auto max-w-6xl space-y-5">
        @if (session('success'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $errors->first() }}</div>@endif

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('admin.whatsapp.index') }}" class="text-xs font-bold text-slate-500 hover:text-primary"><i class="fas fa-arrow-left mr-1" aria-hidden="true"></i>{{ __('Back to events') }}</a>
                <h1 class="mt-2 text-2xl font-bold text-slate-900">{{ __('Webhook event') }} #{{ $event->id }}</h1>
                <p class="mt-1 break-all font-mono text-xs text-muted">{{ $event->event_id }}</p>
            </div>
            <span class="inline-flex w-fit rounded-full px-3 py-1.5 text-xs font-bold {{ $statusClass }}">{{ __(ucfirst($event->processing_status)) }}</span>
        </div>

        <section class="grid gap-4 lg:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center gap-3"><span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-green-500 text-white"><i class="fab fa-whatsapp" aria-hidden="true"></i></span><h2 class="text-lg font-bold text-slate-900">{{ __('Message information') }}</h2></div>
                <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                    @foreach([
                        [__('Sender phone'), $event->sender_phone], [__('Sender name'), $event->sender_name],
                        [__('Message type'), $event->message_type], [__('External message ID'), $event->external_message_id],
                    ] as [$label, $value])
                        <div><dt class="text-[10px] font-bold uppercase tracking-widest text-muted">{{ $label }}</dt><dd class="mt-1 break-all text-sm font-semibold text-slate-800">{{ $value ?: '—' }}</dd></div>
                    @endforeach
                    <div class="sm:col-span-2"><dt class="text-[10px] font-bold uppercase tracking-widest text-muted">{{ __('Message text') }}</dt><dd class="mt-1 whitespace-pre-wrap break-words rounded-xl bg-slate-50 p-3 text-sm text-slate-700">{{ $event->message_text ?: __('No text field was detected. See raw payload.') }}</dd></div>
                </dl>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center gap-3"><span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-info text-info dark:bg-info/10 dark:text-info"><i class="fas fa-shield-halved" aria-hidden="true"></i></span><h2 class="text-lg font-bold text-slate-900">{{ __('Header and processing information') }}</h2></div>
                <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                    @foreach([
                        [__('Event type'), $event->event_type], [__('Attempt'), $event->attempt_number],
                        [__('Webhook timestamp'), $event->webhook_timestamp], [__('Signature verified'), $event->signature_verified ? __('Yes') : __('No')],
                        [__('Received at'), $event->received_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s')], [__('Processed at'), $event->processed_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s')],
                        [__('Read at'), $event->read_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s')], [__('Archived at'), $event->archived_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s')],
                    ] as [$label, $value])
                        <div><dt class="text-[10px] font-bold uppercase tracking-widest text-muted">{{ $label }}</dt><dd class="mt-1 break-all font-mono text-sm font-semibold text-slate-800">{{ $value ?? '—' }}</dd></div>
                    @endforeach
                    @if($event->error_message)<div class="sm:col-span-2 rounded-xl border border-rose-200 bg-rose-50 p-3"><dt class="text-[10px] font-bold uppercase tracking-widest text-rose-500">{{ __('Error message') }}</dt><dd class="mt-1 text-sm font-semibold text-rose-700">{{ $event->error_message }}</dd></div>@endif
                </dl>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap gap-2">
                @if($event->read_at)
                    <form method="POST" action="{{ route('admin.whatsapp.events.unread', $event) }}">@csrf @method('PATCH')<button class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 px-4 text-sm font-bold text-slate-700"><i class="fas fa-envelope" aria-hidden="true"></i>{{ __('Mark unread') }}</button></form>
                @else
                    <form method="POST" action="{{ route('admin.whatsapp.events.read', $event) }}">@csrf @method('PATCH')<button class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 px-4 text-sm font-bold text-slate-700"><i class="fas fa-envelope-open" aria-hidden="true"></i>{{ __('Mark read') }}</button></form>
                @endif
                @if($event->processing_status === \App\Models\OtpiqWebhookEvent::STATUS_FAILED)
                    <form method="POST" action="{{ route('admin.whatsapp.events.retry', $event) }}">@csrf<button class="inline-flex h-10 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-bold text-white hover:bg-accent"><i class="fas fa-rotate" aria-hidden="true"></i>{{ __('Retry Processing') }}</button></form>
                @endif
                @unless($event->archived_at)
                    <form method="POST" action="{{ route('admin.whatsapp.events.archive', $event) }}">@csrf @method('PATCH')<button class="inline-flex h-10 items-center gap-2 rounded-xl bg-slate-800 px-4 text-sm font-bold text-white hover:bg-slate-900 dark:hover:bg-slate-600"><i class="fas fa-box-archive" aria-hidden="true"></i>{{ __('Archive') }}</button></form>
                @endunless
            </div>
        </section>

        <details class="group rounded-2xl border border-slate-200 bg-white shadow-sm">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-5 py-4 text-sm font-bold text-slate-800">
                <span><i class="fas fa-code mr-2 text-muted" aria-hidden="true"></i>{{ __('Raw payload') }}</span><i class="fas fa-chevron-down text-xs text-muted transition group-open:rotate-180" aria-hidden="true"></i>
            </summary>
            <div class="border-t border-slate-200 p-4"><pre class="max-h-[36rem] overflow-auto whitespace-pre-wrap break-words rounded-xl bg-slate-950 p-4 text-xs leading-6 text-slate-200">{{ $prettyPayload }}</pre></div>
        </details>
    </div>
</x-app-layout>
