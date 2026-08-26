<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.2em] text-muted">
                    <span>{{ __('Integrations') }}</span>
                    <span class="h-1 w-1 rounded-full bg-slate-300 dark:bg-slate-600"></span>
                    <span>{{ __('Payments') }}</span>
                </div>
                <h2 class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">{{ __('WAYL Payment Gateway') }}</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Monitor payments, API health and provider traffic.') }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ request()->fullUrl() }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 transition hover:border-primary/30 hover:text-primary dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                    <i class="fas fa-rotate text-xs" aria-hidden="true"></i>{{ __('Refresh') }}
                </a>
                @can(\App\Models\User::PERMISSION_FINANCE_MANAGE)
                    <form method="POST" action="{{ route('admin.wayl.health') }}" data-loading-form data-loading-button-text="{{ __('Checking') }}">
                        @csrf
                        <button type="submit" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-primary px-4 text-sm font-bold text-white shadow-lg shadow-primary/20 transition hover:bg-primary-hover" data-loading-button>
                            <i class="fas fa-signal text-xs" aria-hidden="true"></i>{{ __('Check Connection') }}
                        </button>
                    </form>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="waylTraffic">
        <div class="mx-auto max-w-[1600px] space-y-5 px-4 sm:px-6 lg:px-8">
            <section class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-start gap-4">
                    <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-primary text-white shadow-lg shadow-primary/20"><i class="fas fa-credit-card text-lg" aria-hidden="true"></i></span>
                    <div>
                        <div class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-[0.2em] text-muted"><span>{{ __('Integrations') }}</span><span class="h-1 w-1 rounded-full bg-slate-300 dark:bg-slate-600"></span><span>{{ __('Payments') }}</span></div>
                        <h1 class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">{{ __('WAYL Payment Gateway') }}</h1>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Monitor payments, API health and provider traffic.') }}</p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ request()->fullUrl() }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 transition hover:border-primary/30 hover:text-primary dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200"><i class="fas fa-rotate text-xs" aria-hidden="true"></i>{{ __('Refresh') }}</a>
                    @can(\App\Models\User::PERMISSION_FINANCE_MANAGE)
                        <form method="POST" action="{{ route('admin.wayl.health') }}" data-loading-form data-loading-button-text="{{ __('Checking') }}">@csrf<button type="submit" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-primary px-4 text-sm font-bold text-white shadow-lg shadow-primary/20 transition hover:bg-primary-hover" data-loading-button><i class="fas fa-signal text-xs" aria-hidden="true"></i>{{ __('Check Connection') }}</button></form>
                    @endcan
                </div>
            </section>

            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-300">
                    <i class="fas fa-circle-check me-1.5" aria-hidden="true"></i>{{ session('success') }}
                </div>
            @endif
            @if (session('error') || $errors->any())
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/30 dark:text-rose-300">
                    <i class="fas fa-triangle-exclamation me-1.5" aria-hidden="true"></i>{{ session('error') ?: $errors->first() }}
                </div>
            @endif

            @php
                $connected = $latestHealth?->result === 'Success';
                $connectionLabel = ! $latestHealth ? __('Not Checked') : ($connected ? __('Connected') : __('Unavailable'));
                $connectionTone = ! $latestHealth ? 'slate' : ($connected ? 'emerald' : 'rose');
                $integrationCards = [
                    [
                        'label' => __('Integration'),
                        'value' => $configuration['enabled'] ? __('Enabled') : __('Disabled'),
                        'detail' => __('WAYL payment method'),
                        'icon' => 'fas fa-plug-circle-check',
                        'tone' => $configuration['enabled'] ? 'emerald' : 'slate',
                    ],
                    [
                        'label' => __('Environment'),
                        'value' => $configuration['environment'],
                        'detail' => $configuration['environment'] === 'LIVE' ? __('Production traffic') : __('Test traffic only'),
                        'icon' => 'fas fa-flask',
                        'tone' => $configuration['environment'] === 'LIVE' ? 'emerald' : 'amber',
                    ],
                    [
                        'label' => __('API Connection'),
                        'value' => $connectionLabel,
                        'detail' => $latestHealth?->created_at?->diffForHumans() ?? __('Run a connection check'),
                        'icon' => 'fas fa-tower-broadcast',
                        'tone' => $connectionTone,
                    ],
                ];
            @endphp

            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="{{ __('WAYL integration summary') }}">
                @foreach ($integrationCards as $card)
                    @php
                        $tone = match ($card['tone']) {
                            'emerald' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300',
                            'amber' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300',
                            'rose' => 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300',
                            default => 'bg-slate-100 text-slate-500 dark:bg-white/5 dark:text-slate-400',
                        };
                    @endphp
                    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-start justify-between gap-3">
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl {{ $tone }}"><i class="{{ $card['icon'] }}" aria-hidden="true"></i></span>
                            <span class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ $card['value'] }}</span>
                        </div>
                        <p class="mt-4 text-xs font-bold uppercase tracking-wider text-slate-500">{{ $card['label'] }}</p>
                        <p class="mt-1 text-sm font-semibold text-slate-700 dark:text-slate-300">{{ $card['detail'] }}</p>
                    </article>
                @endforeach

                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-start justify-between gap-3">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-info/10 text-info"><i class="fas fa-credit-card" aria-hidden="true"></i></span>
                        <span class="font-mono text-2xl font-bold tabular-nums text-slate-900 dark:text-white">{{ number_format($statusCounts['total']) }}</span>
                    </div>
                    <p class="mt-4 text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('WAYL Payments') }}</p>
                    <p class="mt-1 text-xs font-semibold text-slate-500">
                        <span class="text-emerald-600">{{ $statusCounts['paid'] }} {{ __('Paid') }}</span>
                        <span class="mx-1 text-slate-300">·</span>{{ $statusCounts['pending'] }} {{ __('Pending') }}
                        <span class="mx-1 text-slate-300">·</span><span class="text-rose-600">{{ $statusCounts['failed'] }} {{ __('Failed') }}</span>
                    </p>
                </article>
            </section>

            <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5" aria-label="{{ __('Last 24 hours') }}">
                @foreach ([
                    [__('Payment Requests'), $metrics['requests'], 'fas fa-receipt'],
                    [__('Successful'), $metrics['successful'], 'fas fa-circle-check'],
                    [__('Failed'), $metrics['failed'], 'fas fa-circle-xmark'],
                    [__('Pending'), $metrics['pending'], 'fas fa-clock'],
                    [__('Success Rate'), number_format($metrics['success_rate'], 1).'%', 'fas fa-chart-line'],
                ] as [$label, $value, $icon])
                    <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary dark:bg-info/10 dark:text-info"><i class="{{ $icon }} text-sm" aria-hidden="true"></i></span>
                        <div class="min-w-0">
                            <p class="truncate text-[10px] font-bold uppercase tracking-wider text-slate-500">{{ $label }}</p>
                            <p class="font-mono text-xl font-bold tabular-nums text-slate-900 dark:text-white">{{ is_numeric($value) ? number_format($value) : $value }}</p>
                        </div>
                    </div>
                @endforeach
            </section>

            <div class="grid gap-5 xl:grid-cols-[minmax(0,1.15fr)_minmax(340px,.85fr)]">
                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary dark:bg-info/10 dark:text-info"><i class="fas fa-sliders" aria-hidden="true"></i></span>
                        <div><p class="text-xs font-bold uppercase tracking-[0.2em] text-muted">{{ __('Configuration') }}</p><h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ __('Gateway readiness') }}</h3></div>
                    </div>
                    <dl class="grid gap-x-6 p-5 sm:grid-cols-2">
                        @foreach ([
                            [__('Gateway'), $configuration['enabled'] ? __('Enabled') : __('Disabled'), $configuration['enabled']],
                            [__('Environment'), $configuration['environment'], $configuration['environment'] === 'LIVE'],
                            [__('Currency'), $configuration['currency'], true],
                            [__('API Token'), $configuration['token_configured'] ? __('Configured') : __('Missing'), $configuration['token_configured']],
                            [__('Base URL'), $configuration['base_url'], $configuration['base_url'] !== '—'],
                            [__('Webhook'), $configuration['webhook_configured'] ? __('Configured') : __('Not configured'), $configuration['webhook_configured']],
                            [__('Minimum Amount'), number_format($configuration['minimum_amount']).' '.$configuration['currency'], true],
                        ] as [$label, $value, $ok])
                            <div class="flex items-center gap-3 border-b border-slate-100 py-3 dark:border-slate-800">
                                <span class="h-2 w-2 shrink-0 rounded-full {{ $ok ? 'bg-emerald-500' : 'bg-amber-400' }}"></span>
                                <dt class="text-sm font-semibold text-slate-500">{{ $label }}</dt>
                                <dd class="ms-auto max-w-[55%] truncate text-end text-sm font-bold text-slate-800 dark:text-slate-200">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </section>

                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-300"><i class="fas fa-shield-halved" aria-hidden="true"></i></span>
                        <div><p class="text-xs font-bold uppercase tracking-[0.2em] text-muted">{{ __('Webhook') }}</p><h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ __('Signature monitor') }}</h3></div>
                    </div>
                    <div class="grid gap-3 p-5 sm:grid-cols-2">
                        @foreach ([
                            [__('Status'), $configuration['webhook_configured'] ? __('Configured') : __('Not Configured')],
                            [__('Endpoint'), $configuration['webhook_configured'] ? __('Configured') : '—'],
                            [__('Last Event'), $webhook['last_event']?->diffForHumans() ?? '—'],
                            [__('Valid Signatures'), number_format($webhook['valid'])],
                            [__('Rejected Signatures'), number_format($webhook['rejected'])],
                        ] as [$label, $value])
                            <div class="rounded-xl border border-slate-200 bg-slate-50/70 px-4 py-3 dark:border-slate-800 dark:bg-slate-950/40">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-muted">{{ $label }}</p>
                                <p class="mt-1 text-sm font-bold text-slate-800 dark:text-slate-200">{{ $value }}</p>
                            </div>
                        @endforeach
                        <p class="text-xs leading-5 text-slate-500 sm:col-span-2"><i class="fas fa-lock me-1" aria-hidden="true"></i>{{ __('Unsigned webhook requests are never allowed to change payment state.') }}</p>
                    </div>
                </section>
            </div>

            @if($diagnosticVisible)
                @php
                    $diagnosticTone = match ($diagnostic['status']) {
                        'Passed' => 'emerald',
                        'Failed' => 'rose',
                        default => 'slate',
                    };
                    $webhookRequiredLabel = match ($diagnostic['webhook_required']) {
                        true => __('Yes'),
                        false => __('No'),
                        default => __('Unknown'),
                    };
                @endphp
                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900" aria-labelledby="wayl-diagnostics-title">
                    <div class="flex flex-col gap-4 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300"><i class="fas fa-vial-circle-check" aria-hidden="true"></i></span>
                            <div><p class="text-xs font-bold uppercase tracking-[0.2em] text-muted">{{ __('Diagnostics') }}</p><h3 id="wayl-diagnostics-title" class="text-lg font-bold text-slate-900 dark:text-white">Create-Link API</h3></div>
                        </div>
                        @if($diagnosticCanRun)
                            <form method="POST" action="{{ route('admin.wayl.diagnostics.create-link') }}" data-loading-form data-loading-button-text="{{ __('Running') }}">
                                @csrf
                                <button type="submit" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-amber-500 px-4 text-sm font-bold text-white shadow-lg shadow-amber-500/20 transition hover:bg-amber-600" data-loading-button>
                                    <i class="fas fa-play text-xs" aria-hidden="true"></i>Run Create-Link Diagnostic
                                </button>
                            </form>
                        @endif
                    </div>
                    <div class="grid gap-3 p-5 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ([
                            ['Create-Link API', __($diagnostic['status'])],
                            [__('Last Check'), $diagnostic['last_check']?->diffForHumans() ?? __('Not Tested')],
                            [__('HTTP Status'), $diagnostic['http_status'] !== null ? 'HTTP '.$diagnostic['http_status'] : '—'],
                            [__('Webhook Required'), $webhookRequiredLabel],
                        ] as [$label, $value])
                            <div class="rounded-xl border border-slate-200 bg-slate-50/70 px-4 py-3 dark:border-slate-800 dark:bg-slate-950/40">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-muted">{{ $label }}</p>
                                <p class="mt-1 text-sm font-bold {{ $diagnosticTone === 'emerald' ? 'text-emerald-600 dark:text-emerald-300' : ($diagnosticTone === 'rose' ? 'text-rose-600 dark:text-rose-300' : 'text-slate-800 dark:text-slate-200') }}">{{ $value }}</p>
                            </div>
                        @endforeach
                        <p class="text-xs leading-5 text-slate-500 sm:col-span-2 lg:col-span-4"><i class="fas fa-shield-halved me-1" aria-hidden="true"></i>{{ __('Test only. Creates no order or payment record, changes no stock or cart, and omits webhookUrl and webhookSecret entirely.') }}</p>
                    </div>
                </section>
            @endif

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-col gap-4 border-b border-slate-200 px-5 py-4 lg:flex-row lg:items-center lg:justify-between dark:border-slate-800">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-muted">{{ __('Transactions') }}</p>
                        <h3 class="mt-1 text-lg font-bold text-slate-900 dark:text-white">{{ __('Payment Traffic') }}</h3>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach ([null => __('All'), 'paid' => __('Paid'), 'pending' => __('Pending'), 'failed' => __('Failed'), 'cancelled' => __('Cancelled')] as $key => $label)
                            <a href="{{ route('admin.wayl.index', array_merge(request()->except(['status', 'payments_page']), $key ? ['status' => $key] : [])) }}" class="rounded-full border px-3 py-1.5 text-xs font-bold transition {{ ($filters['status'] ?? null) === $key ? 'border-primary bg-primary text-white' : 'border-slate-200 text-slate-600 hover:border-primary/40 hover:text-primary dark:border-slate-700 dark:text-slate-300' }}">{{ $label }}</a>
                        @endforeach
                    </div>
                </div>

                <form method="GET" action="{{ route('admin.wayl.index') }}" class="grid gap-3 border-b border-slate-200 p-4 sm:grid-cols-[minmax(0,1fr)_180px_auto] dark:border-slate-800">
                    @if(! empty($filters['status']))<input type="hidden" name="status" value="{{ $filters['status'] }}">@endif
                    <label class="relative"><span class="sr-only">{{ __('Search') }}</span><i class="fas fa-magnifying-glass pointer-events-none absolute start-3.5 top-3 text-xs text-slate-400" aria-hidden="true"></i><input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Order number, reference or payment ID') }}" class="h-10 w-full rounded-xl border-slate-300 bg-white ps-9 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white"></label>
                    <select name="period" aria-label="{{ __('Period') }}" class="h-10 rounded-xl border-slate-300 bg-white text-sm font-semibold dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                        <option value="">{{ __('All time') }}</option>
                        <option value="today" @selected(($filters['period'] ?? '') === 'today')>{{ __('Today') }}</option>
                        <option value="7_days" @selected(($filters['period'] ?? '') === '7_days')>{{ __('Last 7 days') }}</option>
                        <option value="30_days" @selected(($filters['period'] ?? '') === '30_days')>{{ __('Last 30 days') }}</option>
                    </select>
                    <button class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 text-sm font-bold text-white dark:bg-slate-700"><i class="fas fa-filter text-xs" aria-hidden="true"></i>{{ __('Filter') }}</button>
                </form>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                        <thead class="bg-slate-50/80 text-start text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:bg-slate-950/40"><tr>
                            @foreach ([__('Time'), __('Order'), __('Payment'), __('Reference'), __('Amount'), __('Status'), __('Provider Status'), __('Last Verification'), __('Action')] as $heading)<th class="whitespace-nowrap px-4 py-3 text-start">{{ $heading }}</th>@endforeach
                        </tr></thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse ($payments as $payment)
                                @php
                                    $statusClass = match ($payment->status) {
                                        'paid' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-300',
                                        'failed', 'cancelled' => 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/30 dark:text-rose-300',
                                        default => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-300',
                                    };
                                    $providerStatus = data_get($payment->provider_response, 'data.status', '—');
                                    $reference = $payment->provider_reference ?: 'yallaspare-payment-'.$payment->id;
                                @endphp
                                <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40">
                                    <td class="whitespace-nowrap px-4 py-3"><p class="font-mono text-xs font-bold text-slate-700 dark:text-slate-300">{{ $payment->created_at?->format('H:i') }}</p><p class="mt-0.5 text-[10px] text-muted">{{ $payment->created_at?->format('d M Y') }}</p></td>
                                    <td class="whitespace-nowrap px-4 py-3">@can(\App\Models\User::PERMISSION_ORDERS_MANAGE)<a href="{{ route('admin.orders.show', $payment->order_id) }}" class="font-bold text-primary hover:underline">#{{ $payment->order?->order_number ?? $payment->order_id }}</a>@else<span class="font-bold text-slate-700 dark:text-slate-300">#{{ $payment->order?->order_number ?? $payment->order_id }}</span>@endcan</td>
                                    <td class="px-4 py-3 font-mono text-xs font-bold text-slate-700 dark:text-slate-300">#{{ $payment->id }}</td>
                                    <td class="max-w-[220px] truncate px-4 py-3 font-mono text-xs text-slate-600 dark:text-slate-400" title="{{ $reference }}">{{ $reference }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 font-mono font-bold tabular-nums text-slate-800 dark:text-slate-200">{{ number_format((float) $payment->amount, 0) }} {{ $payment->currency }}</td>
                                    <td class="px-4 py-3"><span class="inline-flex rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide {{ $statusClass }}">{{ __(ucfirst($payment->status)) }}</span></td>
                                    <td class="px-4 py-3 text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __(ucfirst((string) $providerStatus)) }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-500">{{ $payment->verified_at?->diffForHumans() ?? __('Never') }}</td>
                                    <td class="px-4 py-3"><div class="flex items-center gap-2">
                                        @can(\App\Models\User::PERMISSION_ORDERS_MANAGE)
                                            <a href="{{ route('admin.orders.show', $payment->order_id) }}" class="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 px-3 text-xs font-bold text-slate-700 hover:border-primary/30 hover:text-primary dark:border-slate-700 dark:text-slate-300"><i class="fas fa-eye" aria-hidden="true"></i>{{ __('View') }}</a>
                                            @if(auth()->user()?->hasPermission(\App\Models\User::PERMISSION_FINANCE_MANAGE) && $payment->status !== \App\Models\Payment::STATUS_PAID)
                                                <form method="POST" action="{{ route('admin.orders.payments.verify-wayl', [$payment->order_id, $payment]) }}" data-loading-form>@csrf<button class="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 px-3 text-xs font-bold text-slate-700 hover:border-emerald-300 hover:text-emerald-700 dark:border-slate-700 dark:text-slate-300"><i class="fas fa-shield-check" aria-hidden="true"></i>{{ __('Verify') }}</button></form>
                                            @endif
                                        @endcan
                                    </div></td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="px-4 py-12 text-center"><i class="fas fa-credit-card text-2xl text-slate-300" aria-hidden="true"></i><p class="mt-2 text-sm font-semibold text-slate-500">{{ __('No WAYL payments match these filters.') }}</p></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($payments->hasPages())<div class="border-t border-slate-200 px-4 py-3 dark:border-slate-800">{{ $payments->links() }}</div>@endif
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-col gap-4 border-b border-slate-200 px-5 py-4 lg:flex-row lg:items-center lg:justify-between dark:border-slate-800">
                    <div><p class="text-xs font-bold uppercase tracking-[0.2em] text-muted">{{ __('Provider communications') }}</p><h3 class="mt-1 text-lg font-bold text-slate-900 dark:text-white">{{ __('API Traffic') }}</h3></div>
                    @if($latestHealth)
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs dark:border-slate-700 dark:bg-slate-800">
                            <span class="font-bold text-slate-500">{{ __('Last health check') }}:</span>
                            <span class="ms-1 font-mono font-bold {{ $connected ? 'text-emerald-600' : 'text-rose-600' }}">HTTP {{ $latestHealth->http_status ?? '—' }} · {{ $latestHealth->result }}</span>
                            <span class="ms-1 text-slate-500">{{ $latestHealth->created_at?->diffForHumans() }}</span>
                        </div>
                    @endif
                </div>
                <form method="GET" action="{{ route('admin.wayl.index') }}" class="grid gap-3 border-b border-slate-200 p-4 sm:grid-cols-[220px_minmax(0,1fr)_auto] dark:border-slate-800">
                    @foreach(['status', 'search', 'period'] as $key)@if(! empty($filters[$key]))<input type="hidden" name="{{ $key }}" value="{{ $filters[$key] }}">@endif @endforeach
                    <select name="event_type" aria-label="{{ __('Event type') }}" class="h-10 rounded-xl border-slate-300 bg-white text-sm font-semibold dark:border-slate-700 dark:bg-slate-950 dark:text-white"><option value="">{{ __('All event types') }}</option>@foreach(['CREATE_LINK', 'CREATE_LINK_DIAGNOSTIC', 'STATUS_CHECK', 'HEALTH_CHECK', 'WEBHOOK_RECEIVED', 'WEBHOOK_REJECTED'] as $type)<option value="{{ $type }}" @selected(($filters['event_type'] ?? '') === $type)>{{ $type }}</option>@endforeach</select>
                    <input type="search" name="api_result" value="{{ $filters['api_result'] ?? '' }}" placeholder="{{ __('Result, e.g. Validation Error') }}" class="h-10 rounded-xl border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    <button class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 text-sm font-bold text-white dark:bg-slate-700"><i class="fas fa-filter text-xs" aria-hidden="true"></i>{{ __('Filter') }}</button>
                </form>
                <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                    <thead class="bg-slate-50/80 text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:bg-slate-950/40"><tr>@foreach([__('Time'), __('Type'), __('Order'), __('Reference'), __('HTTP'), __('Result'), __('Duration'), __('Action')] as $heading)<th class="whitespace-nowrap px-4 py-3 text-start">{{ $heading }}</th>@endforeach</tr></thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($logs as $log)
                            @php
                                $success = $log->result === 'Success';
                                $detail = [
                                    'event' => $log->event_type,
                                    'order' => $log->order?->order_number ? '#'.$log->order->order_number : '—',
                                    'payment' => $log->payment_id ? '#'.$log->payment_id : '—',
                                    'reference' => $log->reference_id ?: '—',
                                    'endpoint' => $log->http_method.' '.$log->endpoint,
                                    'http' => $log->http_status ?: '—',
                                    'duration' => $log->duration_ms !== null ? $log->duration_ms.' ms' : '—',
                                    'result' => $log->result,
                                    'message' => $log->safe_message ?: '—',
                                    'validation_errors' => data_get($log->response_metadata, '_validation_errors')
                                        ?? data_get($log->response_metadata, 'errors')
                                        ?? data_get($log->response_metadata, 'validationErrors')
                                        ?? data_get($log->response_metadata, 'issues')
                                        ?? data_get($log->response_metadata, 'error.errors', []),
                                    'request' => $debugVisible ? ($log->request_metadata ?? []) : null,
                                    'response' => $debugVisible ? ($log->response_metadata ?? []) : null,
                                    'debug_visible' => $debugVisible,
                                ];
                            @endphp
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40">
                                <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-slate-600 dark:text-slate-400">{{ $log->created_at?->format('d M H:i:s') }}</td>
                                <td class="px-4 py-3"><span class="inline-flex rounded-lg bg-slate-100 px-2.5 py-1 font-mono text-[10px] font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ $log->event_type }}</span></td>
                                <td class="whitespace-nowrap px-4 py-3">@if($log->order_id)@can(\App\Models\User::PERMISSION_ORDERS_MANAGE)<a href="{{ route('admin.orders.show', $log->order_id) }}" class="font-bold text-primary hover:underline">#{{ $log->order?->order_number ?? $log->order_id }}</a>@else<span class="font-bold text-slate-700 dark:text-slate-300">#{{ $log->order?->order_number ?? $log->order_id }}</span>@endcan @else<span class="text-slate-400">—</span>@endif</td>
                                <td class="max-w-[220px] truncate px-4 py-3 font-mono text-xs text-slate-600 dark:text-slate-400" title="{{ $log->reference_id }}">{{ $log->reference_id ?: '—' }}</td>
                                <td class="px-4 py-3"><span class="font-mono text-xs font-bold {{ $log->http_status && $log->http_status < 400 ? 'text-emerald-600' : 'text-rose-600' }}">{{ $log->http_status ?? '—' }}</span></td>
                                <td class="px-4 py-3"><span class="inline-flex rounded-full border px-2.5 py-1 text-[10px] font-bold {{ $success ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-300' : 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/30 dark:text-rose-300' }}">{{ __($log->result) }}</span></td>
                                <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-slate-500">{{ $log->duration_ms !== null ? $log->duration_ms.' ms' : '—' }}</td>
                                <td class="px-4 py-3"><button type="button" data-wayl-log="{{ json_encode($detail, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) }}" @click="openLog" class="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 px-3 text-xs font-bold text-slate-700 hover:border-primary/30 hover:text-primary dark:border-slate-700 dark:text-slate-300"><i class="fas fa-eye" aria-hidden="true"></i>{{ __('View') }}</button></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-12 text-center"><i class="fas fa-wave-square text-2xl text-slate-300" aria-hidden="true"></i><p class="mt-2 text-sm font-semibold text-slate-500">{{ __('No WAYL API traffic has been recorded yet.') }}</p></td></tr>
                        @endforelse
                    </tbody>
                </table></div>
                @if($logs->hasPages())<div class="border-t border-slate-200 px-4 py-3 dark:border-slate-800">{{ $logs->links() }}</div>@endif
            </section>
        </div>

        <div class="fixed inset-0 z-50 bg-slate-950/60" x-show="drawerOpen" x-cloak @click.self="closeDrawer" @keydown.escape.window="closeDrawer" role="dialog" aria-modal="true" aria-labelledby="wayl-log-title">
            <aside class="absolute end-0 top-0 flex h-full w-full max-w-xl flex-col bg-white shadow-2xl dark:bg-slate-900">
                <div class="flex items-start justify-between gap-3 border-b border-slate-200 bg-slate-950 px-5 py-4 text-white dark:border-slate-800">
                    <div><p class="text-[10px] font-bold uppercase tracking-[0.2em] text-white/50">{{ __('WAYL request details') }}</p><h3 id="wayl-log-title" class="mt-1 font-mono text-lg font-bold" x-text="log.event"></h3></div>
                    <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-white/10 text-white/70 hover:bg-white/10 hover:text-white" @click="closeDrawer" aria-label="{{ __('Close') }}"><i class="fas fa-xmark" aria-hidden="true"></i></button>
                </div>
                <div class="min-h-0 flex-1 space-y-5 overflow-y-auto p-5">
                    <dl class="grid gap-3 sm:grid-cols-2">
                        @foreach ([['Order', 'order'], ['Payment', 'payment'], ['Reference', 'reference'], ['Endpoint', 'endpoint'], ['HTTP Status', 'http'], ['Duration', 'duration'], ['Result', 'result']] as [$label, $field])
                            <div class="rounded-xl border border-slate-200 bg-slate-50/70 px-4 py-3 dark:border-slate-800 dark:bg-slate-950/40 {{ $field === 'reference' || $field === 'endpoint' ? 'sm:col-span-2' : '' }}"><dt class="text-[10px] font-bold uppercase tracking-widest text-muted">{{ __($label) }}</dt><dd class="mt-1 break-all font-mono text-sm font-bold text-slate-800 dark:text-slate-200" x-text="log.{{ $field }}"></dd></div>
                        @endforeach
                    </dl>
                    <section><p class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('Safe response') }}</p><div class="mt-2 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm font-semibold leading-6 text-slate-700 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-300" x-text="log.message"></div></section>
                    <section x-show="hasValidationErrors"><p class="text-xs font-bold uppercase tracking-wider text-rose-600">{{ __('Validation errors') }}</p><pre class="mt-2 max-h-72 overflow-auto whitespace-pre-wrap rounded-xl border border-rose-200 bg-rose-50 p-4 font-mono text-xs leading-6 text-rose-800 dark:border-rose-900/60 dark:bg-rose-950/30 dark:text-rose-200" x-text="validationJson"></pre></section>
                    <section x-show="log.debug_visible"><p class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('Request Details') }}</p><pre class="mt-2 max-h-72 overflow-auto whitespace-pre-wrap rounded-xl bg-slate-950 p-4 font-mono text-xs leading-6 text-slate-200" x-text="requestJson"></pre></section>
                    <section x-show="log.debug_visible"><p class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('Response Details') }}</p><pre class="mt-2 max-h-72 overflow-auto whitespace-pre-wrap rounded-xl bg-slate-950 p-4 font-mono text-xs leading-6 text-slate-200" x-text="responseJson"></pre></section>
                    <p class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs leading-5 text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-300"><i class="fas fa-shield-halved me-1" aria-hidden="true"></i>{{ __('Credentials, authorization headers, card data and customer PII are redacted before storage.') }}</p>
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>
