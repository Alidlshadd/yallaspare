<x-app-layout>
    <x-slot name="header">
        <h2>{{ __('Inbound WhatsApp') }}</h2>
    </x-slot>

    <div class="mx-auto max-w-[1600px] space-y-5">
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-300">
                <i class="fas fa-circle-check mr-1.5" aria-hidden="true"></i>{{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/30 dark:text-rose-300">
                <i class="fas fa-triangle-exclamation mr-1.5" aria-hidden="true"></i>{{ $errors->first() }}
            </div>
        @endif

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5" aria-label="{{ __('WhatsApp webhook summary') }}">
            @foreach ([
                ['label' => __('Received today'), 'value' => $stats['today'], 'icon' => 'fas fa-calendar-day', 'tone' => 'sky'],
                ['label' => __('Unread messages'), 'value' => $stats['unread'], 'icon' => 'fas fa-envelope', 'tone' => 'amber'],
                ['label' => __('Successfully processed'), 'value' => $stats['processed'], 'icon' => 'fas fa-circle-check', 'tone' => 'emerald'],
                ['label' => __('Failed events'), 'value' => $stats['failed'], 'icon' => 'fas fa-triangle-exclamation', 'tone' => 'rose'],
                ['label' => __('Total webhook events'), 'value' => $stats['total'], 'icon' => 'fab fa-whatsapp', 'tone' => 'green'],
            ] as $card)
                @php
                    $tone = match ($card['tone']) {
                        'sky' => 'bg-info text-info dark:bg-info/10 dark:text-info',
                        'amber' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300',
                        'emerald' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300',
                        'rose' => 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300',
                        default => 'bg-green-50 text-green-600 dark:bg-green-500/10 dark:text-green-300',
                    };
                @endphp
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center justify-between gap-3">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl {{ $tone }}"><i class="{{ $card['icon'] }}" aria-hidden="true"></i></span>
                        <span class="font-mono text-2xl font-bold tabular-nums text-slate-900 dark:text-white">{{ number_format($card['value']) }}</span>
                    </div>
                    <p class="mt-3 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ $card['label'] }}</p>
                </div>
            @endforeach
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-col gap-4 border-b border-slate-200 px-5 py-4 dark:border-slate-800 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-green-500 text-white shadow-lg shadow-green-500/20"><i class="fab fa-whatsapp text-xl" aria-hidden="true"></i></span>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-muted">{{ __('System status') }}</p>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ __('OTPIQ inbound webhook') }}</h3>
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.whatsapp.processing.update') }}" class="flex items-center gap-3">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="enabled" value="{{ $processingEnabled ? 0 : 1 }}">
                    <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-bold {{ $processingEnabled ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-300' : 'border-slate-200 bg-slate-100 text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300' }}">
                        <span class="h-2 w-2 rounded-full {{ $processingEnabled ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                        {{ $processingEnabled ? __('Processing enabled') : __('Processing disabled') }}
                    </span>
                    <button type="submit" class="inline-flex h-10 items-center justify-center rounded-xl px-4 text-sm font-bold text-white transition {{ $processingEnabled ? 'bg-rose-600 hover:bg-rose-700' : 'bg-emerald-600 hover:bg-emerald-700' }}">
                        {{ $processingEnabled ? __('Disable') : __('Enable') }}
                    </button>
                </form>
            </div>

            <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-4">
                <div class="md:col-span-2 xl:col-span-4">
                    <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-widest text-muted">{{ __('Webhook URL') }}</label>
                    <div class="flex min-w-0 gap-2">
                        <div class="min-w-0 flex-1 overflow-x-auto rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 font-mono text-sm font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200" dir="ltr">{{ $webhookUrl }}</div>
                        <button type="button" data-copy-webhook-url="{{ $webhookUrl }}" class="inline-flex h-10 shrink-0 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 transition hover:border-green-300 hover:text-green-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-green-500/50 dark:hover:text-green-300">
                            <i class="fas fa-copy" aria-hidden="true"></i><span data-copy-label>{{ __('Copy') }}</span>
                        </button>
                    </div>
                </div>

                @foreach ([
                    [__('Webhook secret configured'), $secretConfigured ? __('Yes') : __('No'), $secretConfigured],
                    [__('WhatsApp enabled in config'), $whatsappConfigured ? __('Yes') : __('No'), $whatsappConfigured],
                    [__('Last webhook received'), $lastEvent?->received_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s') ?? __('Never'), (bool) $lastEvent],
                    [__('Last successful webhook'), $lastSuccessfulEvent?->processed_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s') ?? __('Never'), (bool) $lastSuccessfulEvent],
                ] as [$label, $value, $ok])
                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 px-4 py-3 dark:border-slate-800 dark:bg-slate-950/40">
                        <div class="text-[10px] font-bold uppercase tracking-widest text-muted">{{ $label }}</div>
                        <div class="mt-1 flex items-center gap-2 text-sm font-bold text-slate-800 dark:text-slate-100"><span class="h-2 w-2 rounded-full {{ $ok ? 'bg-emerald-500' : 'bg-accent' }}"></span>{{ $value }}</div>
                    </div>
                @endforeach

                <div class="rounded-xl border border-slate-200 bg-slate-50/70 px-4 py-3 dark:border-slate-800 dark:bg-slate-950/40 xl:col-span-2">
                    <div class="text-[10px] font-bold uppercase tracking-widest text-muted">{{ __('Queue status') }}</div>
                    <div class="mt-1 text-sm font-bold text-slate-800 dark:text-slate-100">{{ strtoupper($queueStatus['connection']) }} · {{ $queueStatus['mode'] }}</div>
                    <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        {{ __('Pending') }}: {{ $queueStatus['pending'] ?? __('Unavailable') }} · {{ __('Failed jobs') }}: {{ $queueStatus['failed'] ?? __('Unavailable') }}
                    </div>
                    <p class="mt-1 text-[11px] text-muted">{{ __('This confirms queue configuration and database counts, not worker process liveness.') }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50/70 px-4 py-3 dark:border-slate-800 dark:bg-slate-950/40 xl:col-span-2">
                    <div class="text-[10px] font-bold uppercase tracking-widest text-muted">{{ __('Last error') }}</div>
                    <div class="mt-1 text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $lastFailedEvent?->error_message ?? __('No processing error recorded.') }}</div>
                    @if($lastFailedEvent)<div class="mt-1 font-mono text-[11px] text-muted">{{ $lastFailedEvent->event_id }}</div>@endif
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <form method="GET" action="{{ route('admin.whatsapp.events.index') }}" class="grid gap-3 border-b border-slate-200 p-4 dark:border-slate-800 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
                <input name="phone" value="{{ $filters['phone'] ?? '' }}" placeholder="{{ __('Phone number') }}" class="rounded-xl border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                <select name="event_type" class="rounded-xl border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    <option value="">{{ __('All event types') }}</option>
                    @foreach($eventTypes as $type)<option value="{{ $type }}" @selected(($filters['event_type'] ?? '') === $type)>{{ $type }}</option>@endforeach
                </select>
                <select name="message_type" class="rounded-xl border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    <option value="">{{ __('All message types') }}</option>
                    @foreach($messageTypes as $type)<option value="{{ $type }}" @selected(($filters['message_type'] ?? '') === $type)>{{ $type }}</option>@endforeach
                </select>
                <select name="processing_status" class="rounded-xl border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    <option value="">{{ __('All statuses') }}</option>
                    @foreach(['received', 'processing', 'processed', 'ignored', 'failed'] as $status)<option value="{{ $status }}" @selected(($filters['processing_status'] ?? '') === $status)>{{ __(ucfirst($status)) }}</option>@endforeach
                </select>
                <select name="read_status" class="rounded-xl border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    <option value="">{{ __('Read and unread') }}</option>
                    <option value="unread" @selected(($filters['read_status'] ?? '') === 'unread')>{{ __('Unread') }}</option>
                    <option value="read" @selected(($filters['read_status'] ?? '') === 'read')>{{ __('Read') }}</option>
                </select>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" aria-label="{{ __('Start date') }}" class="rounded-xl border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" aria-label="{{ __('End date') }}" class="rounded-xl border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                <div class="flex gap-2 sm:col-span-2 lg:col-span-4 xl:col-span-7">
                    <button class="inline-flex h-10 items-center gap-2 rounded-xl bg-primary px-4 text-sm font-bold text-white hover:bg-primary-hover"><i class="fas fa-filter" aria-hidden="true"></i>{{ __('Apply filters') }}</button>
                    <a href="{{ route('admin.whatsapp.index') }}" class="inline-flex h-10 items-center rounded-xl border border-slate-200 px-4 text-sm font-bold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">{{ __('Clear') }}</a>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-[1180px] w-full text-left text-sm">
                    <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-widest text-slate-500 dark:bg-slate-950/60 dark:text-slate-400">
                        <tr>
                            <th class="px-4 py-3">{{ __('Sender') }}</th><th class="px-4 py-3">{{ __('Message') }}</th><th class="px-4 py-3">{{ __('Type') }}</th><th class="px-4 py-3">{{ __('Event') }}</th><th class="px-4 py-3">{{ __('Attempt') }}</th><th class="px-4 py-3">{{ __('Status') }}</th><th class="px-4 py-3">{{ __('Read') }}</th><th class="px-4 py-3">{{ __('Received') }}</th><th class="px-4 py-3 text-right">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($events as $event)
                            @php
                                $statusClass = match($event->processing_status) {
                                    'processed' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
                                    'failed' => 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300',
                                    'ignored' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
                                    'processing' => 'bg-info text-info dark:bg-info/10 dark:text-info',
                                    default => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
                                };
                            @endphp
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/30">
                                <td class="px-4 py-3"><div class="font-mono font-bold text-slate-800 dark:text-slate-100" dir="ltr">{{ $event->sender_phone ?? '—' }}</div><div class="max-w-40 truncate text-xs text-muted">{{ $event->sender_name ?? '—' }}</div></td>
                                <td class="max-w-xs px-4 py-3"><div class="truncate text-slate-700 dark:text-slate-200" title="{{ $event->message_text }}">{{ \Illuminate\Support\Str::limit($event->message_text ?? __('No text payload'), 80) }}</div><div class="font-mono text-[10px] text-muted">{{ $event->external_message_id }}</div></td>
                                <td class="px-4 py-3 font-mono text-xs text-slate-600 dark:text-slate-300">{{ $event->message_type ?? '—' }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-slate-600 dark:text-slate-300">{{ $event->event_type ?? '—' }}</td>
                                <td class="px-4 py-3 text-center font-mono text-slate-600 dark:text-slate-300">{{ $event->attempt_number ?? '—' }}</td>
                                <td class="px-4 py-3"><span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold {{ $statusClass }}">{{ __(ucfirst($event->processing_status)) }}</span></td>
                                <td class="px-4 py-3"><span class="text-xs font-semibold {{ $event->read_at ? 'text-muted' : 'text-accent dark:text-accent' }}">{{ $event->read_at ? __('Read') : __('Unread') }}</span></td>
                                <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-500 dark:text-slate-400">{{ $event->received_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') ?? '—' }}</td>
                                <td class="px-4 py-3 text-right"><a href="{{ route('admin.whatsapp.events.show', $event) }}" class="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 px-3 text-xs font-bold text-slate-700 hover:border-green-300 hover:text-green-700 dark:border-slate-700 dark:text-slate-200 dark:hover:border-green-500/50 dark:hover:text-green-300"><i class="fas fa-eye" aria-hidden="true"></i>{{ __('Details') }}</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="px-6 py-16 text-center text-sm text-slate-500 dark:text-slate-400"><i class="fab fa-whatsapp mb-3 block text-3xl text-slate-300 dark:text-slate-600" aria-hidden="true"></i>{{ __('No webhook events match these filters.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($events->hasPages())<div class="border-t border-slate-200 px-4 py-3 dark:border-slate-800">{{ $events->links() }}</div>@endif
        </section>
    </div>

    @push('scripts')
        <script nonce="{{ $cspNonce }}">
            document.addEventListener('DOMContentLoaded', () => {
                const button = document.querySelector('[data-copy-webhook-url]');
                if (!button) return;
                button.addEventListener('click', async () => {
                    try {
                        await navigator.clipboard.writeText(button.dataset.copyWebhookUrl || '');
                        const label = button.querySelector('[data-copy-label]');
                        if (label) label.textContent = @json(__('Copied'));
                        window.setTimeout(() => { if (label) label.textContent = @json(__('Copy')); }, 1600);
                    } catch (error) {
                        button.focus();
                    }
                });
            });
        </script>
    @endpush
</x-app-layout>
