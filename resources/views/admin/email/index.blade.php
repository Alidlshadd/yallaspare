<x-app-layout>

@php
    $totalSent7d = (int) ($emailStats['total_7d'] ?? 0);
    $sent7d = (int) ($emailStats['sent_7d'] ?? 0);
    $sent24h = (int) ($emailStats['sent_24h'] ?? 0);
    $failed24h = (int) ($emailStats['failed_24h'] ?? 0);
    $total24h = (int) ($emailStats['total_24h'] ?? 0);
    $successRate = $emailStats['success_rate_24h'];
    $successRate7d = $emailStats['success_rate_7d'];
    $failureRate = $total24h > 0 ? max(0, 100 - (int) ($successRate ?? 0)) : 0;

    $broadcastFilters = $broadcastFilters ?? ['status' => '', 'q' => ''];
    $activeStatus = $broadcastFilters['status'] ?? '';
    $searchTerm = $broadcastFilters['q'] ?? '';
    $broadcastAll = (int) ($broadcastCounts['all'] ?? 0);
    $broadcastSentTotal = (int) ($broadcastCounts['sent'] ?? 0);
    $broadcastFailedTotal = (int) ($broadcastCounts['failed'] ?? 0);
    $broadcastPendingTotal = (int) ($broadcastCounts['pending'] ?? 0);
    $queuedCount = $broadcastPendingTotal;
    $pendingShare = $broadcastAll > 0 ? min(100, (int) round(($broadcastPendingTotal / $broadcastAll) * 100)) : 0;

    $audienceCounts = $audienceCounts ?? ['customers' => 0, 'dealers' => 0, 'admins' => 0, 'total' => 0];

    $audienceLabels = [
        \App\Models\EmailBroadcast::AUDIENCE_ALL => __('All'),
        \App\Models\EmailBroadcast::AUDIENCE_ROLE => __('Role'),
        \App\Models\EmailBroadcast::AUDIENCE_USER => __('User'),
    ];
    $audienceAvatarClasses = [
        \App\Models\EmailBroadcast::AUDIENCE_ALL => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-200',
        \App\Models\EmailBroadcast::AUDIENCE_ROLE => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-200',
        \App\Models\EmailBroadcast::AUDIENCE_USER => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200',
    ];
    $broadcastStatusClasses = [
        'sent' => ['cls' => 'bg-emerald-50 text-emerald-700 border-emerald-100 dark:bg-emerald-900/40 dark:text-emerald-200 dark:border-emerald-900/60', 'icon' => 'fa-check'],
        'sending' => ['cls' => 'bg-sky-50 text-sky-700 border-sky-100 dark:bg-sky-900/40 dark:text-sky-200 dark:border-sky-900/60', 'icon' => 'fa-paper-plane'],
        'queued' => ['cls' => 'bg-amber-50 text-amber-700 border-amber-100 dark:bg-amber-900/40 dark:text-amber-200 dark:border-amber-900/60', 'icon' => 'fa-clock'],
        'failed' => ['cls' => 'bg-rose-50 text-rose-700 border-rose-100 dark:bg-rose-900/40 dark:text-rose-200 dark:border-rose-900/60', 'icon' => 'fa-xmark'],
    ];
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
    $healthClasses = [
        'green' => ['badge' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-200', 'bar' => 'bg-gradient-to-r from-emerald-500 to-emerald-600'],
        'amber' => ['badge' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-200', 'bar' => 'bg-gradient-to-r from-amber-400 to-amber-500'],
        'rose' => ['badge' => 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-200', 'bar' => 'bg-gradient-to-r from-rose-500 to-rose-600'],
    ][$health['tone']] ?? ['badge' => 'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200', 'bar' => 'bg-slate-500'];
@endphp

<style>
    .num-display { font-feature-settings: "tnum" 1, "lnum" 1; letter-spacing: -0.025em; }
    .bento-shadow-em { box-shadow: 0 1px 2px rgba(7,7,64,0.04), 0 4px 16px rgba(7,7,64,0.06); }
    .bento-stripes-em {
        background-image: repeating-linear-gradient(135deg, rgba(7,7,64,0.04) 0px, rgba(7,7,64,0.04) 1px, transparent 1px, transparent 14px);
    }
    .corner-brackets-em::before,
    .corner-brackets-em::after {
        content: ""; position: absolute; width: 12px; height: 12px;
        border-color: rgba(7,7,64,0.22); border-style: solid; border-width: 0;
        pointer-events: none;
    }
    .corner-brackets-em::before { top: 12px; left: 12px; border-top-width: 1.5px; border-left-width: 1.5px; }
    .corner-brackets-em::after { bottom: 12px; right: 12px; border-bottom-width: 1.5px; border-right-width: 1.5px; }
</style>

<div class="bg-[#f3f4f7] dark:bg-slate-950 min-h-screen">
<div class="py-6">
<div class="mx-auto max-w-[1600px] px-4 sm:px-6 lg:px-8">

    @if(session('success'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-900/30 dark:text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 dark:border-rose-900/60 dark:bg-rose-900/30 dark:text-rose-200">
            {{ $errors->first() }}
        </div>
    @endif

    @if($emailStats['last_sent_label'] === __('Mail log table is not installed yet'))
        <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200">
            <i class="fas fa-triangle-exclamation mr-1"></i>
            {{ __('Mail log table is not installed yet') }}. {{ __('Run the pending migrations to start recording email activity.') }}
        </div>
    @endif

    <div class="relative rounded-2xl border border-slate-200/70 bg-white overflow-hidden bento-shadow-em dark:bg-slate-900 dark:border-slate-800">
        {{-- Top accent stripe --}}
        <div class="absolute top-0 left-0 right-0 h-[2px] bg-gradient-to-r from-primary via-indigo-500 to-amber-400 z-10"></div>

        <div class="grid grid-cols-1 lg:grid-cols-[260px_1fr]">

            {{-- ============================================================
                 SIDEBAR
                 ============================================================ --}}
            <aside class="relative border-b lg:border-b-0 lg:border-r border-slate-200/70 bg-[#f7f7fb] p-3 dark:bg-slate-950 dark:border-slate-800">
                <div class="absolute inset-0 bento-stripes-em opacity-40 pointer-events-none"></div>

                <div class="relative">
                    {{-- Compose CTA --}}
                    <a href="{{ route('admin.email.broadcasts.create') }}"
                       class="w-full flex items-center gap-2 rounded-xl bg-gradient-to-br from-primary to-indigo-700 text-white px-3 py-2.5 text-sm font-bold shadow-md shadow-primary/20 hover:shadow-lg hover:shadow-primary/30 transition">
                        <i class="fas fa-pen-to-square text-xs"></i> {{ __('New Broadcast') }}
                    </a>

                    {{-- Workspace nav --}}
                    <div class="mt-5">
                        <p class="px-2 text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400">{{ __('Workspace') }}</p>
                        <nav class="mt-1 space-y-0.5">
                            <a href="#overview"
                               class="flex items-center gap-2.5 rounded-lg bg-primary/10 px-2.5 py-2 text-sm font-bold text-primary dark:bg-primary/20 dark:text-indigo-200">
                                <i class="fas fa-house w-4 text-primary text-xs dark:text-indigo-200"></i> {{ __('Overview') }}
                                <span class="ml-auto font-mono text-[10px] text-primary/70 dark:text-indigo-300">{{ number_format($totalSent7d) }}</span>
                            </a>
                            <a href="#broadcasts"
                               class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm font-semibold text-slate-600 hover:bg-white hover:text-primary transition dark:text-slate-300 dark:hover:bg-slate-900 dark:hover:text-white">
                                <i class="fas fa-bullhorn w-4 text-slate-400 text-xs"></i> {{ __('Broadcasts') }}
                                <span class="ml-auto font-mono text-[10px] text-slate-400">{{ number_format($broadcastAll) }}</span>
                            </a>
                            <a href="{{ route('admin.email.outbox') }}"
                               class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm font-semibold text-slate-600 hover:bg-white hover:text-primary transition dark:text-slate-300 dark:hover:bg-slate-900 dark:hover:text-white">
                                <i class="fas fa-inbox w-4 text-slate-400 text-xs"></i> {{ __('Outbox') }}
                                <span class="ml-auto font-mono text-[10px] text-slate-400"><i class="fas fa-arrow-up-right-from-square text-[8px]"></i></span>
                            </a>
                            <a href="#settings"
                               class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm font-semibold text-slate-600 hover:bg-white hover:text-primary transition dark:text-slate-300 dark:hover:bg-slate-900 dark:hover:text-white">
                                <i class="fas fa-gears w-4 text-slate-400 text-xs"></i> {{ __('Settings') }}
                            </a>
                            <a href="#templates"
                               class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm font-semibold text-slate-600 hover:bg-white hover:text-primary transition dark:text-slate-300 dark:hover:bg-slate-900 dark:hover:text-white">
                                <i class="fas fa-file-lines w-4 text-slate-400 text-xs"></i> {{ __('Templates') }}
                                <span class="ml-auto font-mono text-[10px] text-slate-400">{{ count($templateCards) }}</span>
                            </a>
                        </nav>
                    </div>

                    {{-- Audiences --}}
                    <div class="mt-6">
                        <p class="px-2 text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400">{{ __('Audiences') }}</p>
                        <nav class="mt-1 space-y-0.5">
                            <div class="flex items-center gap-2 rounded-lg px-2.5 py-1.5 text-sm text-slate-600 dark:text-slate-300">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> {{ __('Customers') }}
                                <span class="ml-auto font-mono text-[10px] text-slate-400">{{ number_format($audienceCounts['customers']) }}</span>
                            </div>
                            <div class="flex items-center gap-2 rounded-lg px-2.5 py-1.5 text-sm text-slate-600 dark:text-slate-300">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> {{ __('Dealers') }}
                                <span class="ml-auto font-mono text-[10px] text-slate-400">{{ number_format($audienceCounts['dealers']) }}</span>
                            </div>
                            <div class="flex items-center gap-2 rounded-lg px-2.5 py-1.5 text-sm text-slate-600 dark:text-slate-300">
                                <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span> {{ __('Admins') }}
                                <span class="ml-auto font-mono text-[10px] text-slate-400">{{ number_format($audienceCounts['admins']) }}</span>
                            </div>
                        </nav>
                    </div>

                    {{-- Delivery health card --}}
                    <div class="mt-6 relative rounded-xl border border-primary/15 bg-gradient-to-br from-primary/[0.06] to-white p-3 corner-brackets-em dark:from-primary/20 dark:to-slate-900 dark:border-primary/30">
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-primary dark:text-indigo-200">{{ __('Delivery health') }}</p>
                        <div class="mt-2 flex items-baseline gap-2">
                            <span class="num-display text-2xl font-black text-primary dark:text-white">{{ $successRate7d ?? $successRate ?? 0 }}</span>
                            <span class="text-xs font-mono text-slate-500 dark:text-slate-400">%</span>
                        </div>
                        <div class="mt-1.5 h-1 rounded-full bg-slate-200 overflow-hidden dark:bg-slate-800">
                            <div class="h-full rounded-full bg-gradient-to-r from-primary to-indigo-500" style="width: {{ $successRate7d ?? $successRate ?? 0 }}%"></div>
                        </div>
                        <p class="mt-2 text-[10px] font-mono text-slate-500 dark:text-slate-400">{{ __('last 7 days') }}</p>
                    </div>

                    {{-- Readiness score mini --}}
                    <div class="mt-3 rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center justify-between">
                            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">{{ __('Readiness') }}</p>
                            <span class="text-[10px] font-mono font-bold {{ $health['tone'] === 'green' ? 'text-emerald-600' : ($health['tone'] === 'amber' ? 'text-amber-600' : 'text-rose-600') }}">{{ $health['score'] }}/100</span>
                        </div>
                        <div class="mt-2 h-1 rounded-full bg-slate-100 overflow-hidden dark:bg-slate-800">
                            <div class="h-full rounded-full {{ $healthClasses['bar'] }}" style="width: {{ $health['score'] }}%"></div>
                        </div>
                        <p class="mt-2 text-[10px] font-mono text-slate-500 dark:text-slate-400">{{ $health['ok'] }}/{{ $health['total'] }} {{ __('OK') }}</p>
                    </div>
                </div>
            </aside>

            {{-- ============================================================
                 MAIN
                 ============================================================ --}}
            <div class="p-6 lg:p-8 bg-[#f3f4f7]/50 dark:bg-slate-950/50">

                {{-- ==================== OVERVIEW ==================== --}}
                <section id="overview">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-slate-400">{{ __('Overview') }} · YALLASPARE / EMAIL</p>
                            <h2 class="mt-1 text-2xl font-black tracking-tight text-primary dark:text-white">{{ __('Broadcast console') }}</h2>
                            <p class="mt-1 text-sm text-slate-500 font-mono dark:text-slate-400">
                                {{ __('queue: :q', ['q' => $summary['queue'] ?: 'sync']) }} · {{ $emailStats['last_sent_label'] }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.email.preview', ['template' => 'order-status', 'locale' => app()->getLocale()]) }}" target="_blank" rel="noopener"
                               class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                                <i class="fas fa-eye text-[10px]"></i> {{ __('Preview templates') }}
                            </a>
                        </div>
                    </div>

                    {{-- 3 bento stat cards --}}
                    <div class="mt-6 grid gap-3 grid-cols-1 sm:grid-cols-3">
                        {{-- Total Sent 7d --}}
                        <div class="relative rounded-2xl border border-slate-200/70 bg-white p-5 bento-shadow-em overflow-hidden dark:bg-slate-900 dark:border-slate-800">
                            <div class="absolute top-0 left-0 bottom-0 w-1 bg-gradient-to-b from-primary to-indigo-700"></div>
                            <div class="flex items-start justify-between">
                                <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">{{ __('Total Sent') }}</p>
                                <div class="h-7 w-7 rounded-lg bg-primary/10 text-primary grid place-items-center dark:bg-primary/20 dark:text-indigo-200">
                                    <i class="fas fa-envelopes-bulk text-[10px]"></i>
                                </div>
                            </div>
                            <p class="mt-3 num-display text-3xl font-black text-primary dark:text-white">{{ number_format($totalSent7d) }}</p>
                            <div class="mt-2 flex items-center justify-between">
                                <span class="text-[10px] text-slate-400 font-mono">{{ __('last 7 days') }}</span>
                                @if($successRate7d !== null)
                                    <span class="text-[10px] font-bold text-primary dark:text-indigo-300"><i class="fas fa-arrow-up text-[8px]"></i> {{ $successRate7d }}%</span>
                                @endif
                            </div>
                            <div class="mt-2 h-1 rounded-full bg-slate-100 overflow-hidden dark:bg-slate-800">
                                <div class="h-full rounded-full bg-gradient-to-r from-primary to-indigo-500" style="width: {{ $successRate7d ?? 0 }}%"></div>
                            </div>
                        </div>

                        {{-- Delivered 24h --}}
                        <div class="relative rounded-2xl border border-slate-200/70 bg-white p-5 bento-shadow-em overflow-hidden dark:bg-slate-900 dark:border-slate-800">
                            <div class="absolute top-0 left-0 bottom-0 w-1 bg-gradient-to-b from-emerald-500 to-emerald-600"></div>
                            <div class="flex items-start justify-between">
                                <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">{{ __('Delivered') }}</p>
                                <div class="h-7 w-7 rounded-lg bg-emerald-50 text-emerald-600 grid place-items-center dark:bg-emerald-900/40 dark:text-emerald-300">
                                    <i class="fas fa-circle-check text-[10px]"></i>
                                </div>
                            </div>
                            <p class="mt-3 num-display text-3xl font-black text-primary dark:text-white">{{ number_format($sent24h) }}</p>
                            <div class="mt-2 flex items-center justify-between">
                                <span class="text-[10px] font-mono font-bold text-emerald-600">{{ $successRate === null ? '—' : $successRate . '%' }}</span>
                                <span class="text-[10px] font-mono text-slate-400">{{ __('24h') }}</span>
                            </div>
                            <div class="mt-2 h-1 rounded-full bg-slate-100 overflow-hidden dark:bg-slate-800">
                                <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-emerald-600" style="width: {{ $successRate ?? 0 }}%"></div>
                            </div>
                        </div>

                        {{-- Pending --}}
                        <div class="relative rounded-2xl border border-amber-200/70 bg-gradient-to-br from-amber-50/60 to-white p-5 bento-shadow-em overflow-hidden dark:from-amber-950/20 dark:to-slate-900 dark:border-amber-900/50">
                            <div class="absolute top-0 left-0 bottom-0 w-1 bg-gradient-to-b from-amber-400 to-amber-500"></div>
                            <div class="flex items-start justify-between">
                                <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-amber-700 dark:text-amber-300">{{ __('Pending') }}</p>
                                <div class="h-7 w-7 rounded-lg bg-amber-100 text-amber-700 grid place-items-center dark:bg-amber-900/60 dark:text-amber-200">
                                    <i class="fas fa-clock text-[10px]"></i>
                                </div>
                            </div>
                            <p class="mt-3 num-display text-3xl font-black text-primary flex items-center gap-2 dark:text-white">
                                {{ number_format($queuedCount) }}
                                @if($queuedCount > 0)
                                    <span class="inline-flex h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                @endif
                            </p>
                            <div class="mt-2 flex items-center justify-between">
                                <span class="text-[10px] text-amber-700 font-mono font-bold">{{ __('in queue') }}</span>
                                @if($broadcastAll > 0)
                                    <span class="text-[10px] font-mono text-amber-700">{{ $pendingShare }}%</span>
                                @endif
                            </div>
                            <div class="mt-2 h-1 rounded-full bg-amber-100 overflow-hidden dark:bg-amber-950/60">
                                <div class="h-full rounded-full bg-gradient-to-r from-amber-400 to-amber-500 @if($queuedCount > 0) animate-pulse @endif" style="width: {{ $pendingShare }}%"></div>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- ==================== BROADCASTS ==================== --}}
                <section id="broadcasts" class="mt-6">
                    <div class="relative rounded-2xl border border-slate-200/70 bg-white bento-shadow-em overflow-hidden dark:bg-slate-900 dark:border-slate-800">
                        {{-- Header --}}
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200/70 px-5 py-3.5 bg-gradient-to-r from-slate-50/80 via-white to-slate-50/80 dark:border-slate-800 dark:from-slate-900 dark:via-slate-900 dark:to-slate-900">
                            <div class="flex items-center gap-2.5">
                                <div class="h-8 w-8 rounded-lg bg-primary/10 text-primary grid place-items-center dark:bg-primary/20 dark:text-indigo-200">
                                    <i class="fas fa-clock-rotate-left text-xs"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-primary leading-none dark:text-white">{{ __('Broadcast History') }}</p>
                                    <p class="font-mono text-[10px] uppercase tracking-widest text-slate-400 mt-1">{{ __(':n records', ['n' => $broadcastAll]) }} · {{ __('last 10') }}</p>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                @if(! $broadcastsAvailable)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 text-amber-700 px-2.5 py-1 text-[10px] font-bold border border-amber-100 dark:bg-amber-950/40 dark:text-amber-200 dark:border-amber-900/60">
                                        <i class="fas fa-triangle-exclamation"></i> {{ __('Table not installed') }}
                                    </span>
                                @endif
                                @php
                                    $pillBase = 'inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold uppercase tracking-wider transition';
                                    $pillOn = 'bg-primary text-white shadow-sm';
                                    $pillOff = 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800';
                                    $countChipOn = 'ml-0.5 rounded-full bg-white/20 px-1 text-[9px]';
                                    $countChipOff = 'ml-0.5 rounded-full bg-slate-100 dark:bg-slate-800 px-1 text-[9px]';
                                @endphp
                                <nav class="inline-flex items-center gap-0.5 rounded-xl border border-slate-200 bg-white p-1 shadow-sm dark:border-slate-700 dark:bg-slate-900" aria-label="{{ __('Filter broadcasts by status') }}">
                                    <a href="{{ route('admin.email.index', array_filter(['q' => $searchTerm])) }}#broadcasts"
                                       class="{{ $pillBase }} {{ $activeStatus === '' ? $pillOn : $pillOff }}">
                                        <i class="fas fa-layer-group text-[9px]"></i> {{ __('All') }} <span class="{{ $activeStatus === '' ? $countChipOn : $countChipOff }}">{{ number_format($broadcastAll) }}</span>
                                    </a>
                                    <a href="{{ route('admin.email.index', array_filter(['status' => 'sent', 'q' => $searchTerm])) }}#broadcasts"
                                       class="{{ $pillBase }} {{ $activeStatus === 'sent' ? $pillOn : $pillOff }}">
                                        <i class="fas fa-check text-[9px]"></i> {{ __('Sent') }} <span class="{{ $activeStatus === 'sent' ? $countChipOn : $countChipOff }}">{{ number_format($broadcastSentTotal) }}</span>
                                    </a>
                                    <a href="{{ route('admin.email.index', array_filter(['status' => 'failed', 'q' => $searchTerm])) }}#broadcasts"
                                       class="{{ $pillBase }} {{ $activeStatus === 'failed' ? $pillOn : $pillOff }}">
                                        <i class="fas fa-xmark text-[9px]"></i> {{ __('Failed') }} <span class="{{ $activeStatus === 'failed' ? $countChipOn : $countChipOff }}">{{ number_format($broadcastFailedTotal) }}</span>
                                    </a>
                                    <a href="{{ route('admin.email.index', array_filter(['status' => 'pending', 'q' => $searchTerm])) }}#broadcasts"
                                       class="{{ $pillBase }} {{ $activeStatus === 'pending' ? $pillOn : $pillOff }}">
                                        <i class="fas fa-clock text-[9px]"></i> {{ __('Pending') }} <span class="{{ $activeStatus === 'pending' ? $countChipOn : $countChipOff }}">{{ number_format($broadcastPendingTotal) }}</span>
                                    </a>
                                </nav>
                            </div>
                        </div>

                        {{-- Search bar --}}
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
                                    <a href="{{ route('admin.email.index', array_filter(['status' => $activeStatus])) }}#broadcasts"
                                       class="absolute right-14 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-500 hover:text-rose-600">
                                        <i class="fas fa-xmark"></i> {{ __('Clear') }}
                                    </a>
                                @endif
                                <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg bg-primary px-3 py-1 text-[11px] font-bold text-white hover:bg-primary-hover">
                                    {{ __('Search') }}
                                </button>
                            </div>
                        </form>

                        {{-- Broadcast list --}}
                        <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($recentBroadcasts as $broadcast)
                                @php
                                    $statusKey = $broadcast->status;
                                    $statusStyle = $broadcastStatusClasses[$statusKey] ?? ['cls' => 'bg-slate-100 text-slate-700 border-slate-200', 'icon' => 'fa-circle'];
                                    $audienceKey = $broadcast->audience_type;
                                    $audienceLabel = $audienceLabels[$audienceKey] ?? ucfirst($audienceKey);
                                    $audienceAvatar = $audienceAvatarClasses[$audienceKey] ?? 'bg-slate-100 text-slate-700';
                                    $audienceLetter = strtoupper(mb_substr($audienceLabel, 0, 1));
                                    $rowAccent = in_array($statusKey, ['queued','sending']) ? 'border-l-2 border-amber-400 bg-amber-50/20 dark:bg-amber-950/20' : '';
                                @endphp
                                <li class="group flex items-center gap-4 px-5 py-3 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition {{ $rowAccent }}">
                                    <span class="h-9 w-9 shrink-0 rounded-lg {{ $statusStyle['cls'] }} border grid place-items-center">
                                        @if(in_array($statusKey, ['queued','sending']))
                                            <span class="inline-flex h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                        @else
                                            <i class="fas {{ $statusStyle['icon'] }} text-xs"></i>
                                        @endif
                                    </span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-bold text-primary truncate dark:text-slate-100" title="{{ $broadcast->subject }}">{{ $broadcast->subject }}</p>
                                        <p class="text-[11px] text-slate-500 font-mono mt-0.5 dark:text-slate-400 truncate">
                                            <span class="inline-flex items-center gap-1">
                                                <span class="h-4 w-4 rounded-full {{ $audienceAvatar }} grid place-items-center text-[9px] font-black">{{ $audienceLetter }}</span>
                                                {{ $audienceLabel }}@if($broadcast->audience_role) · {{ $audienceRoles[$broadcast->audience_role] ?? $broadcast->audience_role }}@endif
                                            </span>
                                            · {{ number_format($broadcast->recipient_count) }} {{ __('recipients') }}
                                            · {{ optional($broadcast->created_at)->format('M d, H:i') }}
                                        </p>
                                    </div>
                                    <div class="hidden sm:flex flex-col items-end">
                                        <span class="num-display text-sm font-bold text-primary dark:text-slate-100">{{ number_format($broadcast->sent_count) }}</span>
                                        @if($broadcast->failed_count > 0)
                                            <span class="text-[10px] font-mono text-rose-600 font-bold">{{ number_format($broadcast->failed_count) }} {{ __('failed') }}</span>
                                        @else
                                            <span class="text-[10px] font-mono text-slate-400">{{ __('sent') }}</span>
                                        @endif
                                    </div>
                                    <span class="text-[10px] font-mono font-bold uppercase tracking-wider w-16 text-right {{ $statusKey === 'sent' ? 'text-emerald-600' : ($statusKey === 'failed' ? 'text-rose-600' : ($statusKey === 'queued' || $statusKey === 'sending' ? 'text-amber-600' : 'text-slate-500')) }}">
                                        {{ __(ucfirst($statusKey)) }}
                                    </span>
                                </li>
                            @empty
                                <li class="px-5 py-12 text-center">
                                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                                        <i class="fas fa-bullhorn"></i>
                                    </span>
                                    @if($activeStatus !== '' || $searchTerm !== '')
                                        <p class="mt-3 text-sm font-semibold text-primary dark:text-slate-100">{{ __('No broadcasts match this filter') }}</p>
                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Try a different status or clear the search.') }}</p>
                                        <a href="{{ route('admin.email.index') }}#broadcasts" class="mt-4 inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                                            <i class="fas fa-rotate-left text-[10px]"></i> {{ __('Reset filters') }}
                                        </a>
                                    @else
                                        <p class="mt-3 text-sm font-semibold text-primary dark:text-slate-100">{{ __('No broadcasts yet') }}</p>
                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Use Create Broadcast to send your first one.') }}</p>
                                        <a href="{{ route('admin.email.broadcasts.create') }}" class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-primary px-3 py-2 text-xs font-bold text-white hover:bg-primary-hover">
                                            <i class="fas fa-plus text-[10px]"></i> {{ __('Create Broadcast') }}
                                        </a>
                                    @endif
                                </li>
                            @endforelse
                        </ul>
                    </div>
                </section>

                {{-- ==================== ACTIVITY (mail logs) ==================== --}}
                <section id="activity" class="mt-6">
                    <div class="relative rounded-2xl border border-slate-200/70 bg-white bento-shadow-em overflow-hidden dark:bg-slate-900 dark:border-slate-800">
                        <div class="flex items-center justify-between border-b border-slate-200/70 px-5 py-3.5 bg-gradient-to-r from-slate-50/80 via-white to-slate-50/80 dark:border-slate-800 dark:from-slate-900 dark:via-slate-900 dark:to-slate-900">
                            <div class="flex items-center gap-2.5">
                                <div class="h-8 w-8 rounded-lg bg-sky-100 text-sky-700 grid place-items-center dark:bg-sky-900/50 dark:text-sky-200">
                                    <i class="fas fa-inbox text-xs"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-primary leading-none dark:text-white">{{ __('Recent Activity') }}</p>
                                    <p class="font-mono text-[10px] uppercase tracking-widest text-slate-400 mt-1">{{ __('mail log') }} · {{ __(':n records', ['n' => $recentLogs->count()]) }}</p>
                                </div>
                            </div>
                            <a href="{{ route('admin.email.outbox') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                                <i class="fas fa-arrow-up-right-from-square text-[10px]"></i> {{ __('View all') }}
                            </a>
                        </div>
                        <div class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($recentLogs as $log)
                                <div class="group grid gap-3 px-5 py-3 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition sm:grid-cols-[150px_1fr_auto] sm:items-center">
                                    <div>
                                        <p class="font-mono text-xs font-bold text-primary dark:text-slate-200">{{ optional($log->created_at)->format('M d, H:i') }}</p>
                                        <p class="mt-0.5 text-[10px] text-slate-400 font-mono dark:text-slate-500">{{ optional($log->created_at)->diffForHumans() }}</p>
                                    </div>
                                    <div class="min-w-0 flex items-center gap-2.5">
                                        <span class="h-7 w-7 shrink-0 rounded-lg bg-slate-100 text-slate-500 grid place-items-center dark:bg-slate-800 dark:text-slate-300">
                                            <i class="fas fa-envelope text-[10px]"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-bold text-primary dark:text-slate-100" title="{{ $log->subject }}">{{ $log->subject ?: __('No subject') }}</p>
                                            <p class="mt-0.5 text-[11px] text-slate-500 font-mono dark:text-slate-400">
                                                {{ $log->recipient_domain ?: '-' }} <span class="mx-1 text-slate-300 dark:text-slate-600">/</span> {{ $log->mailer ?: '-' }}
                                            </p>
                                        </div>
                                    </div>
                                    <span class="inline-flex w-fit items-center gap-1 rounded-full border px-2.5 py-1 text-[11px] font-bold {{ $log->status === 'sent' ? 'bg-emerald-50 text-emerald-700 border-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-900/50' : 'bg-rose-50 text-rose-700 border-rose-100 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-900/50' }}">
                                        <i class="fas {{ $log->status === 'sent' ? 'fa-check' : 'fa-xmark' }} text-[9px]"></i>
                                        {{ __(ucfirst($log->status)) }}
                                    </span>
                                </div>
                            @empty
                                <div class="px-5 py-12 text-center">
                                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                                        <i class="fas fa-inbox"></i>
                                    </span>
                                    <p class="mt-3 text-sm font-bold text-primary dark:text-slate-100">{{ __('No mail activity yet') }}</p>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Send a test email to create the first outbox record.') }}</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </section>

                {{-- ==================== SETTINGS: Compose Broadcast + Readiness ==================== --}}
                <section id="settings" class="mt-6 grid gap-6 xl:grid-cols-2">

                    {{-- Compose Broadcast (inline · D-style) --}}
                    <form method="POST" action="{{ route('admin.email.broadcast') }}" id="inline-compose"
                          class="relative rounded-2xl border border-slate-200/70 bg-white bento-shadow-em overflow-hidden dark:bg-slate-900 dark:border-slate-800">
                        @csrf
                        <div class="absolute top-0 left-0 right-0 h-[2px] bg-gradient-to-r from-primary via-indigo-500 to-amber-400"></div>

                        {{-- Header with CREATE button --}}
                        <div class="flex items-center justify-between border-b border-slate-200/70 px-5 py-3.5 bg-gradient-to-r from-slate-50/80 via-white to-slate-50/80 dark:border-slate-800 dark:from-slate-900 dark:via-slate-900 dark:to-slate-900">
                            <div class="flex items-center gap-2.5">
                                <div class="h-8 w-8 rounded-lg bg-primary/10 text-primary grid place-items-center dark:bg-primary/20 dark:text-indigo-200">
                                    <i class="fas fa-pen-nib text-xs"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-primary leading-none dark:text-white">{{ __('Create Broadcast') }}</p>
                                    <p class="font-mono text-[10px] uppercase tracking-widest text-slate-400 mt-1">{{ __('to one user or all users') }}</p>
                                </div>
                            </div>
                            <a href="{{ route('admin.email.broadcasts.create') }}"
                               class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-[10px] font-bold text-slate-600 hover:bg-primary hover:text-white hover:border-primary transition dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                                <i class="fas fa-expand text-[9px]"></i> {{ __('Full editor') }}
                            </a>
                        </div>

                        <div class="p-5 space-y-4">
                            {{-- Audience picker --}}
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500 mb-1.5 dark:text-slate-400">{{ __('Recipients') }}</label>
                                <div class="grid grid-cols-2 gap-2" id="ic-audience-tiles">
                                    <button type="button" data-audience="all"
                                            class="ic-tile rounded-xl border-2 border-primary bg-primary/5 text-primary px-3 py-3 text-xs font-bold text-left transition dark:bg-primary/10">
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-users text-base"></i>
                                            <span>{{ __('All users') }}</span>
                                        </div>
                                        <p class="font-mono text-[9px] text-slate-400 mt-1 truncate">{{ number_format($audienceCounts['total']) }} {{ __('verified') }}</p>
                                    </button>
                                    <button type="button" data-audience="user"
                                            class="ic-tile rounded-xl border border-slate-200 px-3 py-3 text-xs font-bold text-slate-600 text-left hover:bg-slate-50 transition dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-user text-base"></i>
                                            <span>{{ __('Specific person') }}</span>
                                        </div>
                                        <p class="font-mono text-[9px] text-slate-400 mt-1 truncate">{{ __('By email address') }}</p>
                                    </button>
                                </div>
                                <input type="hidden" name="audience_type" id="ic-audience-type" value="{{ old('audience_type', 'all') }}">
                                @error('audience_type')<p class="mt-1 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
                            </div>

                            {{-- Conditional email input --}}
                            <div id="ic-user-wrap" style="display:none">
                                <label for="ic-recipient-email" class="block text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500 mb-1.5 dark:text-slate-400">{{ __('Recipient email') }}</label>
                                <input id="ic-recipient-email" type="email" name="recipient_email" value="{{ old('recipient_email') }}" placeholder="customer@example.com" maxlength="255"
                                       class="w-full rounded-xl border border-slate-200 bg-slate-50 text-slate-900 focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/20 transition dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                @error('recipient_email')<p class="mt-1 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
                            </div>

                            {{-- Purpose toggle --}}
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500 mb-1.5 dark:text-slate-400">{{ __('Purpose') }}</label>
                                <div class="inline-flex rounded-xl border border-slate-200 bg-slate-50 p-1 dark:border-slate-700 dark:bg-slate-950" id="ic-purpose-toggle">
                                    <button type="button" data-purpose="promotional"
                                            class="ic-purpose-btn rounded-lg bg-primary text-white px-3 py-1.5 text-xs font-bold transition">{{ __('Promotional') }}</button>
                                    <button type="button" data-purpose="operational"
                                            class="ic-purpose-btn rounded-lg text-slate-600 px-3 py-1.5 text-xs font-bold transition dark:text-slate-300">{{ __('Operational') }}</button>
                                </div>
                                <input type="hidden" name="purpose" id="ic-purpose" value="{{ old('purpose', 'promotional') }}">
                                <p class="mt-1.5 text-[10px] font-mono text-slate-400">{{ __('Promotional broadcasts only go to users who opted into marketing.') }}</p>
                            </div>

                            {{-- Subject --}}
                            <div>
                                <label for="ic-subject" class="block text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500 mb-1.5 dark:text-slate-400">{{ __('Subject') }}</label>
                                <input id="ic-subject" type="text" name="subject" value="{{ old('subject') }}" required maxlength="160" placeholder="{{ __('Happy Newroz from YallaSpare') }}"
                                       class="w-full rounded-xl border border-slate-200 bg-slate-50 text-slate-900 font-semibold focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/20 transition dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                @error('subject')<p class="mt-1 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
                            </div>

                            {{-- Message --}}
                            <div>
                                <label for="ic-message" class="block text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500 mb-1.5 dark:text-slate-400">{{ __('Message') }}</label>
                                <textarea id="ic-message" name="message" rows="5" required maxlength="5000" placeholder="{{ __('Write the email body. Plain text is safest and line breaks are preserved.') }}"
                                          class="w-full rounded-xl border border-slate-200 bg-slate-50 text-slate-900 leading-relaxed focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/20 transition dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">{{ old('message') }}</textarea>
                                @error('message')<p class="mt-1 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
                            </div>

                            {{-- Send button (prominent) --}}
                            <button type="submit" @disabled(! $broadcastsAvailable)
                                    class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-br from-primary to-indigo-700 px-4 py-3 text-sm font-bold text-white shadow-md shadow-primary/20 hover:shadow-lg hover:shadow-primary/30 transition disabled:cursor-not-allowed disabled:from-slate-400 disabled:to-slate-500 disabled:shadow-none">
                                <i class="fas fa-paper-plane"></i> {{ __('Send Broadcast') }}
                            </button>

                            @if(! $broadcastsAvailable)
                                <p class="text-[11px] text-amber-700 font-mono">
                                    <i class="fas fa-triangle-exclamation"></i> {{ __('Email broadcast table is not installed yet. Run the pending migrations before sending broadcasts.') }}
                                </p>
                            @endif
                        </div>
                    </form>

                    {{-- Readiness Checks --}}
                    <div class="relative rounded-2xl border border-slate-200/70 bg-white bento-shadow-em overflow-hidden dark:bg-slate-900 dark:border-slate-800">
                        <div class="absolute top-0 left-0 right-0 h-[2px] {{ $health['tone'] === 'green' ? 'bg-gradient-to-r from-emerald-400 via-emerald-500 to-teal-500' : ($health['tone'] === 'amber' ? 'bg-gradient-to-r from-amber-400 via-amber-500 to-orange-500' : 'bg-gradient-to-r from-rose-400 via-rose-500 to-pink-500') }}"></div>
                        <div class="flex items-center justify-between border-b border-slate-200/70 px-5 py-3.5 bg-gradient-to-r from-slate-50/80 via-white to-slate-50/80 dark:border-slate-800 dark:from-slate-900 dark:via-slate-900 dark:to-slate-900">
                            <div class="flex items-center gap-2.5">
                                <div class="h-8 w-8 rounded-lg {{ $health['tone'] === 'green' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-200' : ($health['tone'] === 'amber' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-200' : 'bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-200') }} grid place-items-center">
                                    <i class="fas fa-shield-halved text-xs"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-primary leading-none dark:text-white">{{ __('Readiness Checks') }}</p>
                                    <p class="font-mono text-[10px] uppercase tracking-widest text-slate-400 mt-1">{{ $health['ok'] }} / {{ $health['total'] }} {{ __('OK') }} · {{ $health['label'] }}</p>
                                </div>
                            </div>
                            <span class="rounded-full border px-2.5 py-1 text-xs font-bold {{ $healthClasses['badge'] }}">{{ $health['score'] }}/100</span>
                        </div>
                        <div class="px-5 pt-3">
                            <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden dark:bg-slate-800">
                                <div class="h-full rounded-full {{ $healthClasses['bar'] }}" style="width: {{ $health['score'] }}%"></div>
                            </div>
                        </div>
                        <div class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach($checks as $check)
                                <div class="group flex items-start gap-3 px-5 py-3 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                                    <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $check['ok'] ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-200' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-200' }}">
                                        <i class="fas {{ $check['ok'] ? 'fa-check' : 'fa-screwdriver-wrench' }} text-[11px]"></i>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <p class="text-sm font-bold text-primary dark:text-slate-100">{{ $check['label'] }}</p>
                                            <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold {{ $check['ok'] ? 'bg-emerald-50 text-emerald-700 border border-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-900/50' : 'bg-amber-50 text-amber-700 border border-amber-100 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-900/50' }}">
                                                <i class="fas {{ $check['ok'] ? 'fa-circle-check' : 'fa-triangle-exclamation' }} text-[9px]"></i>
                                                {{ $check['ok'] ? __('OK') : __('Action') }}
                                            </span>
                                        </div>
                                        <p class="mt-0.5 font-mono text-[11px] text-slate-500 dark:text-slate-400 truncate">{{ $check['value'] }}</p>
                                        <p class="mt-1 text-xs text-slate-600 leading-snug dark:text-slate-300">{{ $check['detail'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>

                {{-- ==================== TEMPLATES ==================== --}}
                <section id="templates" class="mt-6 space-y-6">
                    {{-- Template Library --}}
                    <div class="relative rounded-2xl border border-slate-200/70 bg-white bento-shadow-em overflow-hidden dark:bg-slate-900 dark:border-slate-800">
                        <div class="absolute top-0 left-0 right-0 h-[2px] bg-gradient-to-r from-primary via-indigo-500 to-cyan-400"></div>
                        <div class="border-b border-slate-200/70 px-5 py-3.5 bg-gradient-to-r from-slate-50/80 via-white to-slate-50/80 dark:border-slate-800 dark:from-slate-900 dark:via-slate-900 dark:to-slate-900">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="h-8 w-8 rounded-lg bg-primary/10 text-primary grid place-items-center dark:bg-primary/20 dark:text-indigo-200">
                                        <i class="fas fa-layer-group text-xs"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-primary leading-none dark:text-white">{{ __('Template Library') }}</p>
                                        <p class="font-mono text-[10px] uppercase tracking-widest text-slate-400 mt-1">{{ count($templateCards) }} {{ __('templates') }} · EN · AR · KU</p>
                                    </div>
                                </div>
                                <span class="inline-flex items-center gap-1 rounded-full bg-primary/10 text-primary px-2.5 py-1 text-[10px] font-bold border border-primary/20 dark:bg-primary/20 dark:text-indigo-200 dark:border-primary/40">
                                    <i class="fas fa-envelope-open-text text-[9px]"></i> {{ __('transactional') }}
                                </span>
                            </div>
                        </div>
                        {{-- Compact list: one row per template --}}
                        <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach($templateCards as $template)
                                <li class="group flex items-center gap-3 px-5 py-2.5 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border {{ $toneClasses[$template['tone']] ?? $toneClasses['slate'] }}">
                                        <i class="fas {{ $template['icon'] }} text-[11px]"></i>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2">
                                            <p class="text-sm font-bold text-primary truncate dark:text-slate-100">{{ $template['title'] }}</p>
                                            <span class="font-mono text-[9px] uppercase tracking-widest text-slate-400 shrink-0">{{ $template['sample']['spec'] }}</span>
                                        </div>
                                        <p class="text-[11px] text-slate-500 truncate dark:text-slate-400">{{ $template['description'] }}</p>
                                    </div>
                                    <div class="hidden sm:flex items-center gap-0.5 shrink-0">
                                        @foreach(['en' => 'EN', 'ar' => 'AR', 'ku' => 'KU'] as $locale => $label)
                                            <a href="{{ route('admin.email.preview', ['template' => $template['key'], 'locale' => $locale]) }}" target="_blank" rel="noopener"
                                               class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-bold text-slate-500 hover:bg-primary hover:text-white transition dark:text-slate-400">
                                                {{ $label }}
                                            </a>
                                        @endforeach
                                    </div>
                                    <a href="{{ route('admin.email.preview', ['template' => $template['key'], 'locale' => app()->getLocale()]) }}" target="_blank" rel="noopener"
                                       class="shrink-0 inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2 py-1 text-[10px] font-bold text-slate-700 hover:bg-primary hover:text-white hover:border-primary transition dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                        <i class="fas fa-up-right-from-square text-[8px]"></i> {{ __('Open') }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </section>

            </div>
        </div>
    </div>

</div>
</div>
</div>

<script>
    (function () {
        var form = document.getElementById('inline-compose');
        if (!form) return;

        var audienceTiles = form.querySelectorAll('.ic-tile');
        var audienceInput = form.querySelector('#ic-audience-type');
        var userWrap = form.querySelector('#ic-user-wrap');

        function setAudience(value) {
            audienceInput.value = value;
            audienceTiles.forEach(function (tile) {
                var isActive = tile.dataset.audience === value;
                if (isActive) {
                    tile.classList.add('border-2','border-primary','bg-primary/5','text-primary','dark:bg-primary/10');
                    tile.classList.remove('border','border-slate-200','text-slate-600','hover:bg-slate-50','dark:border-slate-700','dark:text-slate-300','dark:hover:bg-slate-800');
                } else {
                    tile.classList.remove('border-2','border-primary','bg-primary/5','text-primary','dark:bg-primary/10');
                    tile.classList.add('border','border-slate-200','text-slate-600','hover:bg-slate-50','dark:border-slate-700','dark:text-slate-300','dark:hover:bg-slate-800');
                }
            });
            userWrap.style.display = value === 'user' ? '' : 'none';
        }
        audienceTiles.forEach(function (tile) {
            tile.addEventListener('click', function () { setAudience(tile.dataset.audience); });
        });
        setAudience(audienceInput.value || 'all');

        var purposeButtons = form.querySelectorAll('.ic-purpose-btn');
        var purposeInput = form.querySelector('#ic-purpose');
        function setPurpose(value) {
            purposeInput.value = value;
            purposeButtons.forEach(function (btn) {
                var isActive = btn.dataset.purpose === value;
                if (isActive) {
                    btn.classList.add('bg-primary','text-white');
                    btn.classList.remove('text-slate-600','dark:text-slate-300');
                } else {
                    btn.classList.remove('bg-primary','text-white');
                    btn.classList.add('text-slate-600','dark:text-slate-300');
                }
            });
        }
        purposeButtons.forEach(function (btn) {
            btn.addEventListener('click', function () { setPurpose(btn.dataset.purpose); });
        });
        setPurpose(purposeInput.value || 'promotional');
    })();
</script>

</x-app-layout>
