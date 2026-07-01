<x-app-layout>
    <x-slot name="header">
        <div class="relative rounded-2xl bg-white border border-slate-200/70 shadow-sm overflow-hidden dark:bg-slate-900 dark:border-slate-800">
            <div class="absolute -top-16 -right-16 h-40 w-40 rounded-full bg-amber-400/10 blur-3xl pointer-events-none"></div>
            <div class="absolute top-0 left-0 right-0 h-[2px] bg-gradient-to-r from-primary via-indigo-500 to-amber-400"></div>
            <div class="relative flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                <div class="flex items-center gap-3">
                    <div class="relative h-11 w-11 rounded-2xl bg-gradient-to-br from-primary to-indigo-700 text-white grid place-items-center shadow-lg shadow-primary/20">
                        <i class="fas fa-envelope-open-text text-sm"></i>
                        <span class="absolute -top-1 -right-1 h-3 w-3 rounded-full bg-amber-400 border-2 border-white dark:border-slate-900"></span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <p class="text-[10px] uppercase tracking-[0.22em] text-slate-400 font-bold leading-none">{{ __('Customer communication') }}</p>
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 text-emerald-700 px-1.5 py-0.5 text-[9px] font-bold border border-emerald-100 dark:bg-emerald-900/40 dark:text-emerald-200 dark:border-emerald-800">
                                <span class="h-1 w-1 rounded-full bg-emerald-500 animate-pulse"></span> {{ __('LIVE') }}
                            </span>
                        </div>
                        <p class="text-2xl font-semibold text-slate-900 dark:text-white leading-tight mt-1 tracking-tight">{{ __('Email Center') }}</p>
                        <p class="text-[11px] text-slate-400 font-mono mt-0.5">{{ __('queue: :q', ['q' => $summary['queue'] ?: 'sync']) }} · {{ $emailStats['last_sent_label'] }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-1.5">
                    <a href="{{ route('admin.email.outbox') }}"
                       class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-100 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                        <i class="fas fa-clock-rotate-left text-[10px]"></i> {{ __('Outbox') }}
                    </a>
                    <a href="{{ route('admin.email.preview', ['template' => 'order-status', 'locale' => app()->getLocale()]) }}" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                        <i class="fas fa-eye text-[10px]"></i> {{ __('Preview templates') }}
                    </a>
                    <a href="{{ route('admin.email.broadcasts.create') }}"
                       class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-br from-primary to-indigo-700 px-4 py-2 text-xs font-bold text-white shadow-md shadow-primary/20 hover:shadow-lg hover:shadow-primary/30 transition">
                        <i class="fas fa-plus text-[10px]"></i> {{ __('Create Broadcast') }}
                    </a>
                </div>
            </div>
        </div>
    </x-slot>

    @php
        $healthClasses = [
            'green' => ['badge' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-200', 'bar' => 'bg-emerald-500'],
            'amber' => ['badge' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-200', 'bar' => 'bg-amber-500'],
            'rose' => ['badge' => 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-200', 'bar' => 'bg-rose-500'],
        ][$health['tone']] ?? ['badge' => 'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200', 'bar' => 'bg-slate-500'];

        $toneClasses = [
            'blue' => 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900/50 dark:bg-blue-950/30 dark:text-blue-200',
            'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-200',
            'rose' => 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-200',
            'amber' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-200',
            'violet' => 'border-violet-200 bg-violet-50 text-violet-700 dark:border-violet-900/50 dark:bg-violet-950/30 dark:text-violet-200',
            'cyan' => 'border-cyan-200 bg-cyan-50 text-cyan-700 dark:border-cyan-900/50 dark:bg-cyan-950/30 dark:text-cyan-200',
            'indigo' => 'border-indigo-200 bg-indigo-50 text-indigo-700 dark:border-indigo-900/50 dark:bg-indigo-950/30 dark:text-indigo-200',
            'orange' => 'border-orange-200 bg-orange-50 text-orange-700 dark:border-orange-900/50 dark:bg-orange-950/30 dark:text-orange-200',
            'slate' => 'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200',
        ];

        $statusClasses = [
            'sent' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-200',
            'failed' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/60 dark:text-rose-200',
        ];

        $broadcastStatusClasses = [
            'sent' => ['cls' => 'bg-emerald-50 text-emerald-700 border-emerald-100', 'icon' => 'fa-check'],
            'sending' => ['cls' => 'bg-sky-50 text-sky-700 border-sky-100', 'icon' => 'fa-paper-plane'],
            'queued' => ['cls' => 'bg-amber-50 text-amber-700 border-amber-100', 'icon' => 'fa-clock'],
            'failed' => ['cls' => 'bg-rose-50 text-rose-700 border-rose-100', 'icon' => 'fa-xmark'],
        ];

        $audienceLabels = [
            \App\Models\EmailBroadcast::AUDIENCE_ALL => __('All'),
            \App\Models\EmailBroadcast::AUDIENCE_ROLE => __('Role'),
            \App\Models\EmailBroadcast::AUDIENCE_USER => __('User'),
        ];

        $audienceAvatarClasses = [
            \App\Models\EmailBroadcast::AUDIENCE_ALL => 'bg-indigo-100 text-indigo-700',
            \App\Models\EmailBroadcast::AUDIENCE_ROLE => 'bg-amber-100 text-amber-700',
            \App\Models\EmailBroadcast::AUDIENCE_USER => 'bg-emerald-100 text-emerald-700',
        ];

        $queuedCount = $broadcastCounts['pending'] ?? 0;
        $totalSent7d = (int) ($emailStats['total_7d'] ?? 0);
        $sent7d = (int) ($emailStats['sent_7d'] ?? 0);
        $sent24h = (int) ($emailStats['sent_24h'] ?? 0);
        $failed24h = (int) ($emailStats['failed_24h'] ?? 0);
        $successRate = $emailStats['success_rate_24h'];
        $successRate7d = $emailStats['success_rate_7d'];
        $total24h = (int) ($emailStats['total_24h'] ?? 0);
        $failureRate = $total24h > 0 ? max(0, 100 - (int) ($successRate ?? 0)) : 0;
        $broadcastFilters = $broadcastFilters ?? ['status' => '', 'q' => ''];
        $activeStatus = $broadcastFilters['status'] ?? '';
        $searchTerm = $broadcastFilters['q'] ?? '';
        $broadcastAll = (int) ($broadcastCounts['all'] ?? 0);
        $broadcastSentTotal = (int) ($broadcastCounts['sent'] ?? 0);
        $broadcastFailedTotal = (int) ($broadcastCounts['failed'] ?? 0);
        $broadcastPendingTotal = (int) ($broadcastCounts['pending'] ?? 0);
        $pendingShare = $broadcastAll > 0 ? min(100, (int) round(($broadcastPendingTotal / $broadcastAll) * 100)) : 0;
    @endphp

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-900/30 dark:text-emerald-200">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 dark:border-rose-900/60 dark:bg-rose-900/30 dark:text-rose-200">
                    {{ $errors->first() }}
                </div>
            @endif

            @if($emailStats['last_sent_label'] === __('Mail log table is not installed yet'))
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200">
                    <i class="fas fa-triangle-exclamation mr-1"></i>
                    {{ __('Mail log table is not installed yet') }}. {{ __('Run the pending migrations to start recording email activity.') }}
                </div>
            @endif

            {{-- ============== 6 PREMIUM STAT CARDS ============== --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">

                {{-- Total Sent (7d) --}}
                <div class="group relative rounded-2xl bg-white p-4 shadow-sm border border-slate-200/70 overflow-hidden hover:-translate-y-0.5 transition-all dark:bg-slate-900 dark:border-slate-800">
                    <div class="absolute top-0 left-0 bottom-0 w-1 bg-gradient-to-b from-indigo-500 to-indigo-700"></div>
                    <div class="absolute -top-4 -right-4 h-16 w-16 rounded-full bg-indigo-500/5 blur-xl"></div>
                    <div class="relative flex items-start justify-between">
                        <p class="text-[10px] uppercase tracking-[0.22em] text-slate-500 font-bold dark:text-slate-400">{{ __('Total Sent') }}</p>
                        <div class="h-7 w-7 rounded-lg bg-indigo-50 text-indigo-600 grid place-items-center dark:bg-indigo-900/40 dark:text-indigo-300"><i class="fas fa-envelopes-bulk text-[10px]"></i></div>
                    </div>
                    <p class="relative mt-3 text-2xl font-black text-slate-900 dark:text-white" style="font-feature-settings:'tnum' 1,'lnum' 1;letter-spacing:-0.025em">{{ number_format($totalSent7d) }}</p>
                    <div class="relative mt-2 flex items-center justify-between">
                        <p class="text-[10px] text-slate-400 font-mono">{{ __('last 7 days') }}</p>
                        @if($successRate7d !== null)
                            <span class="inline-flex items-center gap-0.5 text-[10px] font-bold text-indigo-600 dark:text-indigo-300">
                                <i class="fas fa-arrow-up text-[8px]"></i> {{ $successRate7d }}%
                            </span>
                        @endif
                    </div>
                    <div class="relative mt-2 h-1 rounded-full bg-slate-100 overflow-hidden dark:bg-slate-800">
                        <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-indigo-600" style="width: {{ $successRate7d ?? 0 }}%"></div>
                    </div>
                </div>

                {{-- Delivered (Sent 24h) --}}
                <div class="group relative rounded-2xl bg-white p-4 shadow-sm border border-slate-200/70 overflow-hidden hover:-translate-y-0.5 transition-all dark:bg-slate-900 dark:border-slate-800">
                    <div class="absolute top-0 left-0 bottom-0 w-1 bg-gradient-to-b from-emerald-500 to-emerald-600"></div>
                    <div class="absolute -top-4 -right-4 h-16 w-16 rounded-full bg-emerald-500/5 blur-xl"></div>
                    <div class="relative flex items-start justify-between">
                        <p class="text-[10px] uppercase tracking-[0.22em] text-slate-500 font-bold dark:text-slate-400">{{ __('Delivered') }}</p>
                        <div class="h-7 w-7 rounded-lg bg-emerald-50 text-emerald-600 grid place-items-center dark:bg-emerald-900/40 dark:text-emerald-300"><i class="fas fa-circle-check text-[10px]"></i></div>
                    </div>
                    <p class="relative mt-3 text-2xl font-black text-slate-900 dark:text-white" style="font-feature-settings:'tnum' 1,'lnum' 1;letter-spacing:-0.025em">{{ number_format($sent24h) }}</p>
                    <div class="relative mt-2 flex items-center justify-between">
                        <p class="text-[10px] text-emerald-600 font-mono font-bold">{{ $successRate === null ? '—' : $successRate . '%' }}</p>
                        <span class="text-[10px] font-mono text-slate-400">{{ __('24h') }}</span>
                    </div>
                    <div class="relative mt-2 h-1 rounded-full bg-slate-100 overflow-hidden dark:bg-slate-800">
                        <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-emerald-600" style="width: {{ $successRate ?? 0 }}%"></div>
                    </div>
                </div>

                {{-- Opened (placeholder — not tracked yet) --}}
                <div class="group relative rounded-2xl bg-white p-4 shadow-sm border border-slate-200/70 overflow-hidden hover:-translate-y-0.5 transition-all dark:bg-slate-900 dark:border-slate-800">
                    <div class="absolute top-0 left-0 bottom-0 w-1 bg-gradient-to-b from-sky-500 to-sky-600"></div>
                    <div class="absolute -top-4 -right-4 h-16 w-16 rounded-full bg-sky-500/5 blur-xl"></div>
                    <div class="relative flex items-start justify-between">
                        <p class="text-[10px] uppercase tracking-[0.22em] text-slate-500 font-bold dark:text-slate-400">{{ __('Opened') }}</p>
                        <div class="h-7 w-7 rounded-lg bg-sky-50 text-sky-600 grid place-items-center dark:bg-sky-900/40 dark:text-sky-300"><i class="fas fa-envelope-open text-[10px]"></i></div>
                    </div>
                    <p class="relative mt-3 text-2xl font-black text-slate-300 dark:text-slate-600">—</p>
                    <p class="relative mt-2 text-[10px] text-slate-400 font-mono">{{ __('not tracked yet') }}</p>
                </div>

                {{-- Failed --}}
                <div class="group relative rounded-2xl bg-white p-4 shadow-sm border border-slate-200/70 overflow-hidden hover:-translate-y-0.5 transition-all dark:bg-slate-900 dark:border-slate-800">
                    <div class="absolute top-0 left-0 bottom-0 w-1 bg-gradient-to-b from-rose-500 to-rose-600"></div>
                    <div class="absolute -top-4 -right-4 h-16 w-16 rounded-full bg-rose-500/5 blur-xl"></div>
                    <div class="relative flex items-start justify-between">
                        <p class="text-[10px] uppercase tracking-[0.22em] text-slate-500 font-bold dark:text-slate-400">{{ __('Failed') }}</p>
                        <div class="h-7 w-7 rounded-lg bg-rose-50 text-rose-600 grid place-items-center dark:bg-rose-900/40 dark:text-rose-300"><i class="fas fa-triangle-exclamation text-[10px]"></i></div>
                    </div>
                    <p class="relative mt-3 text-2xl font-black text-slate-900 dark:text-white" style="font-feature-settings:'tnum' 1,'lnum' 1;letter-spacing:-0.025em">{{ number_format($failed24h) }}</p>
                    <div class="relative mt-2 flex items-center justify-between">
                        <p class="text-[10px] text-rose-600 font-mono font-bold">{{ $failureRate }}%</p>
                        <span class="text-[10px] font-mono text-slate-400">{{ __('24h') }}</span>
                    </div>
                    <div class="relative mt-2 h-1 rounded-full bg-slate-100 overflow-hidden dark:bg-slate-800">
                        <div class="h-full rounded-full bg-gradient-to-r from-rose-500 to-rose-600" style="width: {{ $failureRate }}%"></div>
                    </div>
                </div>

                {{-- Drafts (placeholder — model doesn't track yet) --}}
                <div class="group relative rounded-2xl bg-white p-4 shadow-sm border border-slate-200/70 overflow-hidden hover:-translate-y-0.5 transition-all dark:bg-slate-900 dark:border-slate-800">
                    <div class="absolute top-0 left-0 bottom-0 w-1 bg-gradient-to-b from-slate-400 to-slate-500"></div>
                    <div class="absolute -top-4 -right-4 h-16 w-16 rounded-full bg-slate-500/5 blur-xl"></div>
                    <div class="relative flex items-start justify-between">
                        <p class="text-[10px] uppercase tracking-[0.22em] text-slate-500 font-bold dark:text-slate-400">{{ __('Drafts') }}</p>
                        <div class="h-7 w-7 rounded-lg bg-slate-100 text-slate-600 grid place-items-center dark:bg-slate-800 dark:text-slate-300"><i class="fas fa-file-pen text-[10px]"></i></div>
                    </div>
                    <p class="relative mt-3 text-2xl font-black text-slate-300 dark:text-slate-600">0</p>
                    <p class="relative mt-2 text-[10px] text-slate-400 font-mono">{{ __('drafts coming soon') }}</p>
                </div>

                {{-- Pending (queued/sending broadcasts) --}}
                <div class="group relative rounded-2xl bg-gradient-to-br from-amber-50/60 to-white p-4 shadow-sm border border-amber-200/70 overflow-hidden hover:-translate-y-0.5 transition-all dark:from-amber-950/20 dark:to-slate-900 dark:border-amber-900/50">
                    <div class="absolute top-0 left-0 bottom-0 w-1 bg-gradient-to-b from-amber-400 to-amber-500"></div>
                    <div class="absolute -top-4 -right-4 h-16 w-16 rounded-full bg-amber-500/10 blur-xl"></div>
                    <div class="relative flex items-start justify-between">
                        <p class="text-[10px] uppercase tracking-[0.22em] text-amber-700 font-bold dark:text-amber-300">{{ __('Pending') }}</p>
                        <div class="h-7 w-7 rounded-lg bg-amber-100 text-amber-700 grid place-items-center dark:bg-amber-900/60 dark:text-amber-200"><i class="fas fa-clock text-[10px]"></i></div>
                    </div>
                    <p class="relative mt-3 text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2" style="font-feature-settings:'tnum' 1,'lnum' 1;letter-spacing:-0.025em">
                        {{ number_format($queuedCount) }}
                        @if($queuedCount > 0)
                            <span class="inline-flex h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                        @endif
                    </p>
                    <div class="relative mt-2 flex items-center justify-between">
                        <p class="text-[10px] text-amber-700 font-mono font-bold">{{ __('in queue') }}</p>
                        @if($broadcastAll > 0)
                            <span class="text-[10px] font-mono text-amber-700">{{ $pendingShare }}%</span>
                        @endif
                    </div>
                    <div class="relative mt-2 h-1 rounded-full bg-amber-100 overflow-hidden dark:bg-amber-950/60">
                        <div class="h-full rounded-full bg-gradient-to-r from-amber-400 to-amber-500 @if($queuedCount > 0) animate-pulse @endif" style="width: {{ $pendingShare }}%"></div>
                    </div>
                </div>
            </div>

            {{-- ============== HISTORY (Recent Broadcasts) — Premium A table ============== --}}
            <div class="relative rounded-2xl bg-white border border-slate-200/70 shadow-sm overflow-hidden dark:bg-slate-900 dark:border-slate-800">

                {{-- Header --}}
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200/70 px-5 py-3.5 bg-gradient-to-r from-slate-50/80 via-white to-slate-50/80 dark:border-slate-800 dark:from-slate-900 dark:via-slate-900 dark:to-slate-900">
                    <div class="flex items-center gap-2.5">
                        <div class="h-8 w-8 rounded-lg bg-primary/10 text-primary grid place-items-center dark:bg-primary/20"><i class="fas fa-clock-rotate-left text-xs"></i></div>
                        <div>
                            <p class="text-sm font-bold text-slate-900 leading-none dark:text-white">{{ __('Broadcast History') }}</p>
                            <p class="font-mono text-[10px] uppercase tracking-widest text-slate-400 mt-1">{{ __(':n records', ['n' => $broadcastAll]) }} · {{ __('last 10') }}</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        @if(! $broadcastsAvailable)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 text-amber-700 px-2.5 py-1 text-[10px] font-bold border border-amber-100">
                                <i class="fas fa-triangle-exclamation"></i> {{ __('Table not installed') }}
                            </span>
                        @endif
                        {{-- Status filter pills --}}
                        @php
                            $pillBase = 'inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold uppercase tracking-wider transition';
                            $pillOn = 'bg-primary text-white shadow-sm';
                            $pillOff = 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800';
                            $countChipOn = 'ml-0.5 rounded-full bg-white/20 px-1 text-[9px]';
                            $countChipOff = 'ml-0.5 rounded-full bg-slate-100 dark:bg-slate-800 px-1 text-[9px]';
                        @endphp
                        <nav class="inline-flex items-center gap-0.5 rounded-xl border border-slate-200 bg-white p-1 shadow-sm dark:border-slate-700 dark:bg-slate-900" aria-label="{{ __('Filter broadcasts by status') }}">
                            <a href="{{ route('admin.email.index', array_filter(['q' => $searchTerm])) }}"
                               class="{{ $pillBase }} {{ $activeStatus === '' ? $pillOn : $pillOff }}">
                                <i class="fas fa-layer-group text-[9px]"></i> {{ __('All') }} <span class="{{ $activeStatus === '' ? $countChipOn : $countChipOff }}">{{ number_format($broadcastAll) }}</span>
                            </a>
                            <a href="{{ route('admin.email.index', array_filter(['status' => 'sent', 'q' => $searchTerm])) }}"
                               class="{{ $pillBase }} {{ $activeStatus === 'sent' ? $pillOn : $pillOff }}">
                                <i class="fas fa-check text-[9px]"></i> {{ __('Sent') }} <span class="{{ $activeStatus === 'sent' ? $countChipOn : $countChipOff }}">{{ number_format($broadcastSentTotal) }}</span>
                            </a>
                            <a href="{{ route('admin.email.index', array_filter(['status' => 'failed', 'q' => $searchTerm])) }}"
                               class="{{ $pillBase }} {{ $activeStatus === 'failed' ? $pillOn : $pillOff }}">
                                <i class="fas fa-xmark text-[9px]"></i> {{ __('Failed') }} <span class="{{ $activeStatus === 'failed' ? $countChipOn : $countChipOff }}">{{ number_format($broadcastFailedTotal) }}</span>
                            </a>
                            <a href="{{ route('admin.email.index', array_filter(['status' => 'pending', 'q' => $searchTerm])) }}"
                               class="{{ $pillBase }} {{ $activeStatus === 'pending' ? $pillOn : $pillOff }}">
                                <i class="fas fa-clock text-[9px]"></i> {{ __('Pending') }} <span class="{{ $activeStatus === 'pending' ? $countChipOn : $countChipOff }}">{{ number_format($broadcastPendingTotal) }}</span>
                            </a>
                        </nav>
                        <a href="{{ route('admin.email.outbox') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                            <i class="fas fa-arrow-up-right-from-square text-[10px]"></i> {{ __('Open Outbox') }}
                        </a>
                    </div>
                </div>

                {{-- Filter bar --}}
                <form method="GET" action="{{ route('admin.email.index') }}" class="border-b border-slate-200/70 px-5 py-3 bg-white dark:bg-slate-900 dark:border-slate-800">
                    @if($activeStatus !== '')
                        <input type="hidden" name="status" value="{{ $activeStatus }}">
                    @endif
                    <div class="relative">
                        <i class="fas fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="search"
                               name="q"
                               value="{{ $searchTerm }}"
                               maxlength="100"
                               placeholder="{{ __('Search subject...') }}"
                               class="w-full rounded-xl border border-slate-200 bg-slate-50 pl-9 pr-24 py-2 text-sm text-slate-900 focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/20 transition dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        @if($searchTerm !== '')
                            <a href="{{ route('admin.email.index', array_filter(['status' => $activeStatus])) }}"
                               class="absolute right-14 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-500 hover:text-rose-600">
                                <i class="fas fa-xmark"></i> {{ __('Clear') }}
                            </a>
                        @endif
                        <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg bg-primary px-3 py-1 text-[11px] font-bold text-white hover:bg-primary-hover">
                            {{ __('Search') }}
                        </button>
                    </div>
                </form>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-[10px] uppercase tracking-widest text-slate-500 font-bold bg-slate-50/40 dark:bg-slate-900 dark:text-slate-400">
                            <tr class="border-b border-slate-200/70 dark:border-slate-800">
                                <th class="px-5 py-3 text-left">{{ __('Campaign / Subject') }}</th>
                                <th class="px-5 py-3 text-left">{{ __('Audience') }}</th>
                                <th class="px-5 py-3 text-left">{{ __('Status') }}</th>
                                <th class="px-5 py-3 text-right">{{ __('Sent') }}</th>
                                <th class="px-5 py-3 text-right">{{ __('Failed') }}</th>
                                <th class="px-5 py-3 text-left">{{ __('Date') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($recentBroadcasts as $broadcast)
                            @php
                                $statusKey = $broadcast->status;
                                $statusStyle = $broadcastStatusClasses[$statusKey] ?? ['cls' => 'bg-slate-100 text-slate-700 border-slate-200', 'icon' => 'fa-circle'];
                                $audienceKey = $broadcast->audience_type;
                                $audienceLabel = $audienceLabels[$audienceKey] ?? ucfirst($audienceKey);
                                $audienceAvatar = $audienceAvatarClasses[$audienceKey] ?? 'bg-slate-100 text-slate-700';
                                $audienceLetter = strtoupper(mb_substr($audienceLabel, 0, 1));
                                $purposeLabel = $broadcast->purpose === \App\Models\EmailBroadcast::PURPOSE_PROMOTIONAL ? __('promotional') : __('operational');
                            @endphp
                            <tr class="group hover:bg-slate-50/60 transition dark:hover:bg-slate-800/40 {{ in_array($statusKey, ['queued','sending']) ? 'border-l-2 border-amber-400 bg-amber-50/20 dark:bg-amber-950/20' : '' }}">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="h-8 w-8 rounded-lg bg-gradient-to-br from-primary to-indigo-700 text-white grid place-items-center shadow-sm"><i class="fas fa-bullhorn text-[11px]"></i></span>
                                        <div class="min-w-0">
                                            <p class="font-bold text-slate-900 text-[13px] truncate max-w-xs dark:text-slate-100" title="{{ $broadcast->subject }}">{{ $broadcast->subject }}</p>
                                            <p class="text-[11px] text-slate-400 font-mono">{{ $purposeLabel }}@if($broadcast->action_url) · {{ __('with CTA') }}@endif</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="h-7 w-7 rounded-full {{ $audienceAvatar }} grid place-items-center text-[10px] font-black">{{ $audienceLetter }}</span>
                                        <div>
                                            <span class="inline-flex rounded-md bg-slate-100 text-slate-700 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider dark:bg-slate-800 dark:text-slate-200">
                                                {{ $audienceLabel }}@if($broadcast->audience_role) · {{ $audienceRoles[$broadcast->audience_role] ?? $broadcast->audience_role }}@endif
                                            </span>
                                            <p class="mt-0.5 text-[11px] text-slate-400 font-mono">{{ number_format($broadcast->recipient_count) }} {{ __('recipients') }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center gap-1 rounded-full {{ $statusStyle['cls'] }} px-2.5 py-1 text-[11px] font-bold border">
                                        @if(in_array($statusKey, ['queued','sending']))
                                            <span class="inline-flex h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                        @else
                                            <i class="fas {{ $statusStyle['icon'] }} text-[9px]"></i>
                                        @endif
                                        {{ __(ucfirst($statusKey)) }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <span class="font-mono font-bold text-slate-700 dark:text-slate-200">{{ number_format($broadcast->sent_count) }}</span>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    @if($broadcast->failed_count > 0)
                                        <span class="font-mono font-bold text-rose-600">{{ number_format($broadcast->failed_count) }}</span>
                                    @else
                                        <span class="font-mono text-slate-400">0</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3">
                                    <div class="text-slate-500 font-mono text-[12px] dark:text-slate-400">{{ optional($broadcast->created_at)->format('M d') }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ optional($broadcast->created_at)->format('H:i') }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center">
                                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                                        <i class="fas fa-bullhorn"></i>
                                    </span>
                                    @if($activeStatus !== '' || $searchTerm !== '')
                                        <p class="mt-3 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('No broadcasts match this filter') }}</p>
                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Try a different status or clear the search.') }}</p>
                                        <a href="{{ route('admin.email.index') }}" class="mt-4 inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                                            <i class="fas fa-rotate-left text-[10px]"></i> {{ __('Reset filters') }}
                                        </a>
                                    @else
                                        <p class="mt-3 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('No broadcasts yet') }}</p>
                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Use Create Broadcast to send your first one.') }}</p>
                                        <a href="{{ route('admin.email.broadcasts.create') }}" class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-primary px-3 py-2 text-xs font-bold text-white hover:bg-primary-hover">
                                            <i class="fas fa-plus text-[10px]"></i> {{ __('Create Broadcast') }}
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ============== Two columns: Quick Test + Readiness ============== --}}
            <div class="grid gap-6 xl:grid-cols-2">

                {{-- Quick Delivery Test --}}
                <form method="POST" action="{{ route('admin.email.test') }}" class="rounded-2xl bg-white border border-slate-200/70 shadow-sm overflow-hidden dark:bg-slate-900 dark:border-slate-800">
                    @csrf
                    <div class="flex items-center justify-between border-b border-slate-200/70 px-5 py-3.5 bg-slate-50/60 dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-paper-plane text-amber-500 text-sm"></i>
                            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Quick Delivery Test') }}</p>
                        </div>
                        <span class="font-mono text-[10px] uppercase tracking-widest text-slate-400">{{ __('one-off send') }}</span>
                    </div>
                    <div class="p-5 space-y-4">
                        <div>
                            <label for="recipient" class="block text-xs font-bold text-slate-700 mb-1 dark:text-slate-300">{{ __('Recipient') }}</label>
                            <input id="recipient" type="email" name="recipient" value="{{ old('recipient', auth()->user()?->email) }}" required
                                   class="w-full rounded-xl border-slate-300 bg-slate-50 text-slate-900 focus:border-primary focus:ring-2 focus:ring-primary/30 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                            @error('recipient')<p class="mt-1 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="subject" class="block text-xs font-bold text-slate-700 mb-1 dark:text-slate-300">{{ __('Subject') }}</label>
                            <input id="subject" type="text" name="subject" value="{{ old('subject', 'YallaSpare test email') }}" required
                                   class="w-full rounded-xl border-slate-300 bg-slate-50 text-slate-900 focus:border-primary focus:ring-2 focus:ring-primary/30 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                            @error('subject')<p class="mt-1 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="test_body" class="block text-xs font-bold text-slate-700 mb-1 dark:text-slate-300">{{ __('Message') }}</label>
                            <textarea id="test_body" name="body" rows="3" placeholder="{{ __('Optional custom text for this test email.') }}"
                                      class="w-full rounded-xl border-slate-300 bg-slate-50 text-slate-900 focus:border-primary focus:ring-2 focus:ring-primary/30 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">{{ old('body') }}</textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="mailer" class="block text-xs font-bold text-slate-700 mb-1 dark:text-slate-300">{{ __('Mailer') }}</label>
                                <select id="mailer" name="mailer" class="w-full rounded-xl border-slate-300 bg-slate-50 text-slate-900 focus:border-primary focus:ring-2 focus:ring-primary/30 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                    @foreach($mailers as $mailer)
                                        <option value="{{ $mailer }}" @selected(old('mailer', $summary['default_mailer'] ?? '') === $mailer)>{{ $mailer }}</option>
                                    @endforeach
                                </select>
                                @error('mailer')<p class="mt-1 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
                            </div>
                            <div class="flex items-end">
                                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-bold text-white shadow-md hover:bg-primary-hover transition">
                                    <i class="fas fa-paper-plane"></i> {{ __('Send Test Email') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                {{-- Readiness Checks --}}
                <div class="rounded-2xl bg-white border border-slate-200/70 shadow-sm overflow-hidden dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between border-b border-slate-200/70 px-5 py-3.5 bg-slate-50/60 dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-shield-halved text-primary text-sm dark:text-indigo-300"></i>
                            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Readiness Checks') }}</p>
                        </div>
                        <span class="rounded-full border px-2.5 py-1 text-xs font-bold {{ $healthClasses['badge'] }}">{{ $health['label'] }} · {{ $health['score'] }}/100</span>
                    </div>
                    <div class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($checks as $check)
                            <div class="flex items-start gap-3 px-5 py-3">
                                <span class="mt-0.5 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg {{ $check['ok'] ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-200' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-200' }}">
                                    <i class="fas {{ $check['ok'] ? 'fa-check' : 'fa-screwdriver-wrench' }} text-[10px]"></i>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <p class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ $check['label'] }}</p>
                                        <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $check['ok'] ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300' }}">
                                            {{ $check['ok'] ? __('OK') : __('Action') }}
                                        </span>
                                    </div>
                                    <p class="mt-0.5 font-mono text-[11px] text-slate-500 dark:text-slate-400">{{ $check['value'] }}</p>
                                    <p class="mt-1 text-xs text-slate-600 leading-snug dark:text-slate-300">{{ $check['detail'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- ============== Recent Activity (mail logs) ============== --}}
            <div class="rounded-2xl bg-white border border-slate-200/70 shadow-sm overflow-hidden dark:bg-slate-900 dark:border-slate-800">
                <div class="flex items-center justify-between border-b border-slate-200/70 px-5 py-3.5 bg-slate-50/60 dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-inbox text-slate-400 text-sm"></i>
                        <p class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Recent Activity') }}</p>
                        <span class="font-mono text-[10px] uppercase tracking-widest text-slate-400">{{ __('mail log') }}</span>
                    </div>
                    <a href="{{ route('admin.email.outbox') }}" class="text-xs font-bold text-primary hover:underline dark:text-indigo-300">{{ __('View all') }} &rarr;</a>
                </div>
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($recentLogs as $log)
                        <div class="grid gap-3 px-5 py-3 sm:grid-cols-[130px_1fr_auto] sm:items-center">
                            <div>
                                <p class="font-mono text-xs text-slate-500 dark:text-slate-400">{{ optional($log->created_at)->format('M d, H:i') }}</p>
                                <p class="mt-0.5 text-[10px] text-slate-400 dark:text-slate-500">{{ optional($log->created_at)->diffForHumans() }}</p>
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-slate-900 dark:text-slate-100" title="{{ $log->subject }}">{{ $log->subject ?: __('No subject') }}</p>
                                <p class="mt-0.5 text-[11px] text-slate-500 font-mono dark:text-slate-400">
                                    {{ $log->recipient_domain ?: '-' }} <span class="mx-1 text-slate-300">/</span> {{ $log->mailer ?: '-' }}
                                </p>
                            </div>
                            <span class="inline-flex w-fit items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-bold {{ $statusClasses[$log->status] ?? 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200' }}">
                                <i class="fas {{ $log->status === 'sent' ? 'fa-check' : 'fa-xmark' }} text-[9px]"></i>
                                {{ __(ucfirst($log->status)) }}
                            </span>
                        </div>
                    @empty
                        <div class="px-5 py-10 text-center">
                            <span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                                <i class="fas fa-inbox"></i>
                            </span>
                            <p class="mt-3 text-sm font-bold text-slate-900 dark:text-slate-100">{{ __('No mail activity yet') }}</p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Send a test email to create the first outbox record.') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- ============== Template Examples ============== --}}
            <div class="rounded-2xl bg-white border border-slate-200/70 shadow-sm overflow-hidden dark:bg-slate-900 dark:border-slate-800">
                <div class="flex flex-col gap-3 border-b border-slate-200/70 px-5 py-3.5 bg-slate-50/60 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-palette text-primary text-sm dark:text-indigo-300"></i>
                        <div>
                            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Template Examples') }}</p>
                            <p class="font-mono text-[10px] uppercase tracking-widest text-slate-400 mt-0.5">{{ __('live previews from blade templates') }}</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-1 text-[10px] font-bold uppercase tracking-wider">
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-slate-600 dark:bg-slate-800 dark:text-slate-300">EN</span>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-slate-600 dark:bg-slate-800 dark:text-slate-300">AR</span>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-slate-600 dark:bg-slate-800 dark:text-slate-300">KU</span>
                    </div>
                </div>
                <div class="grid gap-4 p-5 lg:grid-cols-3">
                    @foreach($previewShowcase as $template)
                        <article class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/40 dark:border-slate-800 dark:bg-slate-950">
                            <div class="flex items-start justify-between gap-3 border-b border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl border {{ $toneClasses[$template['tone']] ?? $toneClasses['slate'] }}">
                                            <i class="fas {{ $template['icon'] }} text-xs"></i>
                                        </span>
                                        <p class="truncate text-sm font-bold text-slate-900 dark:text-slate-100">{{ $template['title'] }}</p>
                                    </div>
                                    <p class="mt-2 text-xs leading-5 text-slate-500 dark:text-slate-400">{{ $template['description'] }}</p>
                                </div>
                                <a href="{{ route('admin.email.preview', ['template' => $template['key'], 'locale' => app()->getLocale()]) }}" target="_blank" rel="noopener"
                                   class="shrink-0 rounded-lg border border-slate-200 bg-white px-2 py-1 text-[10px] font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:bg-slate-800">
                                    {{ __('Open') }}
                                </a>
                            </div>
                            <div class="bg-slate-200/60 p-3 dark:bg-slate-950">
                                <div class="mx-auto max-w-sm overflow-hidden rounded-lg border border-slate-300 bg-white shadow-sm dark:border-slate-700">
                                    <div class="flex items-center justify-between bg-primary px-3 py-2 text-white">
                                        <span class="text-[10px] font-bold tracking-wide">YALLASPARE</span>
                                        <span class="font-mono text-[9px] uppercase tracking-[0.16em] text-amber-300">{{ $template['sample']['spec'] }}</span>
                                    </div>
                                    <div class="h-0.5 bg-amber-500"></div>
                                    <div class="space-y-2 p-3">
                                        <span class="inline-flex rounded-full border px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider {{ $toneClasses[$template['tone']] ?? $toneClasses['slate'] }}">
                                            {{ $template['badges'][0] ?? __('Email') }}
                                        </span>
                                        <p class="mt-1 text-sm font-bold leading-5 text-slate-900">{{ $template['sample']['subject'] }}</p>
                                        <p class="text-[11px] leading-5 text-slate-600">{{ $template['sample']['body'] }}</p>
                                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-2 py-1.5">
                                            <p class="font-mono text-[10px] text-slate-600">{{ $template['sample']['meta'] }}</p>
                                        </div>
                                        <div class="h-7 rounded-lg bg-primary"></div>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>

            {{-- ============== Template Library + Mail Config ============== --}}
            <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
                <div class="rounded-2xl bg-white border border-slate-200/70 shadow-sm overflow-hidden dark:bg-slate-900 dark:border-slate-800">
                    <div class="border-b border-slate-200/70 px-5 py-3.5 bg-slate-50/60 dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-layer-group text-primary text-sm dark:text-indigo-300"></i>
                            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Template Library') }}</p>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5 dark:text-slate-400">{{ __('Open any email in English, Arabic, or Kurdish sample data.') }}</p>
                    </div>
                    <div class="grid gap-3 p-5 md:grid-cols-2">
                        @foreach($templateCards as $template)
                            <article class="rounded-2xl border border-slate-200 p-4 transition hover:shadow-md dark:border-slate-800">
                                <div class="flex items-start gap-3">
                                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border {{ $toneClasses[$template['tone']] ?? $toneClasses['slate'] }}">
                                        <i class="fas {{ $template['icon'] }} text-xs"></i>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ $template['title'] }}</p>
                                        <p class="mt-0.5 text-xs leading-5 text-slate-600 dark:text-slate-300">{{ $template['description'] }}</p>
                                        <div class="mt-2 flex flex-wrap gap-1">
                                            @foreach($template['badges'] as $badge)
                                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $badge }}</span>
                                            @endforeach
                                        </div>
                                        <div class="mt-3 flex flex-wrap gap-1.5">
                                            @foreach(['en' => 'EN', 'ar' => 'AR', 'ku' => 'KU'] as $locale => $label)
                                                <a href="{{ route('admin.email.preview', ['template' => $template['key'], 'locale' => $locale]) }}" target="_blank" rel="noopener"
                                                   class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-2 py-1 text-[10px] font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                                                    <i class="fas fa-up-right-from-square text-[8px] text-slate-400"></i> {{ $label }}
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-2xl bg-white border border-slate-200/70 shadow-sm overflow-hidden dark:bg-slate-900 dark:border-slate-800">
                    <div class="border-b border-slate-200/70 px-5 py-3.5 bg-slate-50/60 dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-gears text-primary text-sm dark:text-indigo-300"></i>
                            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Mail Configuration') }}</p>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5 dark:text-slate-400">{{ __('Sensitive values are masked and must be changed from environment configuration.') }}</p>
                    </div>
                    <div class="grid gap-2 p-5 sm:grid-cols-2">
                        @foreach($summary as $label => $value)
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-950">
                                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __(str_replace('_', ' ', $label)) }}</p>
                                <p class="mt-1 break-words text-sm font-bold text-slate-900 font-mono dark:text-slate-100">{{ $value !== '' ? $value : '-' }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
