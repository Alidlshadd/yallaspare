<x-app-layout>
    <x-slot name="header">{{ __('Returns & Refunds') }}</x-slot>

    @php
        $currencyLabel = (string) ($systemSettings['currency_label'] ?? 'IQD');
        $currencyDecimals = (int) ($systemSettings['currency_decimals'] ?? 0);
        $currentStatus = (string) request('status', '');
        $currentSearch = (string) request('search', '');

        $statusMeta = [
            'requested' => [
                'label' => __('Requested'),
                'pill'  => 'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:border-amber-500/30',
                'dot'   => 'bg-amber-500',
                'hex'   => '#f59e0b',
            ],
            'approved' => [
                'label' => __('Approved'),
                'pill'  => 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-500/10 dark:text-blue-300 dark:border-blue-500/30',
                'dot'   => 'bg-blue-500',
                'hex'   => '#2563eb',
            ],
            'rejected' => [
                'label' => __('Rejected'),
                'pill'  => 'bg-rose-100 text-rose-700 border-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:border-rose-500/30',
                'dot'   => 'bg-rose-500',
                'hex'   => '#dc2626',
            ],
            'received' => [
                'label' => __('Received'),
                'pill'  => 'bg-cyan-100 text-cyan-800 border-cyan-200 dark:bg-cyan-500/10 dark:text-cyan-300 dark:border-cyan-500/30',
                'dot'   => 'bg-cyan-500',
                'hex'   => '#0891b2',
            ],
            'refunded' => [
                'label' => __('Refunded'),
                'pill'  => 'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:border-emerald-500/30',
                'dot'   => 'bg-emerald-500',
                'hex'   => '#16a34a',
            ],
            'closed' => [
                'label' => __('Closed'),
                'pill'  => 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-700/40 dark:text-slate-300 dark:border-slate-600',
                'dot'   => 'bg-slate-500',
                'hex'   => '#64748b',
            ],
        ];

        $typeMeta = [
            'return'   => ['label' => __('Return'),   'class' => 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-700/40 dark:text-slate-200'],
            'exchange' => ['label' => __('Exchange'), 'class' => 'bg-violet-100 text-violet-700 border-violet-200 dark:bg-violet-500/10 dark:text-violet-300'],
            'refund'   => ['label' => __('Refund'),   'class' => 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300'],
        ];

        $hasActiveFilters = ($currentSearch !== '') || ($currentStatus !== '');
    @endphp

    {{-- Local decoration utilities (matches dashboard / orders pattern) --}}
    <style>
        .bento-stripes { background-image: repeating-linear-gradient(135deg, rgba(255,255,255,0.06) 0 1px, transparent 1px 14px); }
        .bento-shadow { box-shadow: 0 1px 2px rgba(7,7,64,0.04), 0 4px 16px rgba(7,7,64,0.06); }
        .bento-shadow:hover { box-shadow: 0 2px 6px rgba(7,7,64,0.08), 0 16px 36px rgba(7,7,64,0.10); }
        .num-display { font-feature-settings: "tnum" 1, "lnum" 1; letter-spacing: -0.025em; }

        @keyframes ys-pulse {
            0%   { box-shadow: 0 0 0 0 rgba(251,191,36,0.55); }
            100% { box-shadow: 0 0 0 8px rgba(251,191,36,0); }
        }
        .ys-pulse-dot { animation: ys-pulse 1.6s ease-out infinite; }

        /* Stat card identity colors via CSS vars */
        .ret-stat {
            position: relative; overflow: hidden;
            background: #fff; border: 1px solid #e3e9f1; border-radius: 16px;
            padding: 18px;
            box-shadow: 0 1px 2px rgba(7,7,64,0.04), 0 4px 16px rgba(7,7,64,0.06);
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
        }
        .ret-stat:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(7,7,64,0.10); border-color: #cbd5e1; }
        .ret-stat::before {
            content: "";
            position: absolute; top: 0; left: 0; right: 0; height: 3px;
            background: var(--stat-grad, linear-gradient(90deg, #94a3b8, #64748b));
        }
        .ret-stat::after {
            content: "";
            position: absolute; right: -30px; bottom: -30px;
            width: 110px; height: 110px; border-radius: 50%;
            background: var(--stat-glow, rgba(148,163,184,0.10));
            filter: blur(20px); pointer-events: none;
        }
        .dark .ret-stat { background: #0f172a; border-color: #1e293b; }
        .dark .ret-stat:hover { border-color: #334155; }

        .ret-stat.tot       { --stat-grad: linear-gradient(90deg, #1e293b, #04042a); --stat-glow: rgba(4,4,42,0.08); }
        .ret-stat.open      { --stat-grad: linear-gradient(90deg, #fbbf24, #f59e0b); --stat-glow: rgba(245,158,11,0.16); }
        .ret-stat.refunded  { --stat-grad: linear-gradient(90deg, #4ade80, #16a34a); --stat-glow: rgba(16,163,74,0.12); }
        .ret-stat.closed    { --stat-grad: linear-gradient(90deg, #cbd5e1, #64748b); --stat-glow: rgba(100,116,139,0.10); }
        .ret-stat.money {
            --stat-grad: linear-gradient(90deg, #fbbf24, #f59e0b);
            --stat-glow: rgba(245,158,11,0.20);
            background: linear-gradient(180deg, #fffbeb, #fef3c7);
            border-color: #fde68a;
        }
        .ret-stat.money::after { background: rgba(245,158,11,0.22); }
        .dark .ret-stat.money { background: linear-gradient(180deg, rgba(245,158,11,0.10), rgba(245,158,11,0.05)); border-color: rgba(245,158,11,0.30); }

        /* Quick-filter chips */
        .ret-chip {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 6px 12px; border-radius: 999px;
            font-size: 11.5px; font-weight: 700; line-height: 1;
            border: 1px solid #e2e8f0; background: #fff; color: #475569;
            text-decoration: none;
            transition: all .15s ease;
        }
        .ret-chip:hover { background: var(--chip-hover-bg, #f8fafc); border-color: var(--chip-hover-bd, #cbd5e1); color: var(--chip-hover-fg, #04042a); }
        .ret-chip .dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
        .ret-chip .cnt {
            background: rgba(15,23,42,0.06);
            padding: 1px 7px; border-radius: 999px;
            font-size: 10.5px; font-family: ui-monospace, 'JetBrains Mono', monospace;
            color: #475569; font-weight: 800; letter-spacing: -0.01em;
        }
        .ret-chip.c-all { --chip-hover-bg: #f8fafc; --chip-hover-bd: #cbd5e1; --chip-hover-fg: #04042a; }
        .ret-chip.c-req { --chip-hover-bg: #fffbeb; --chip-hover-bd: #fbbf24; --chip-hover-fg: #92400e; }
        .ret-chip.c-app { --chip-hover-bg: #eff6ff; --chip-hover-bd: #93c5fd; --chip-hover-fg: #1e3a8a; }
        .ret-chip.c-rej { --chip-hover-bg: #fef2f2; --chip-hover-bd: #fca5a5; --chip-hover-fg: #7f1d1d; }
        .ret-chip.c-rcv { --chip-hover-bg: #ecfeff; --chip-hover-bd: #67e8f9; --chip-hover-fg: #155e75; }
        .ret-chip.c-rfd { --chip-hover-bg: #f0fdf4; --chip-hover-bd: #86efac; --chip-hover-fg: #14532d; }
        .ret-chip.c-cls { --chip-hover-bg: #f8fafc; --chip-hover-bd: #cbd5e1; --chip-hover-fg: #1e293b; }
        .ret-chip.on {
            background: #04042a; color: #fcd34d; border-color: #04042a;
            box-shadow: 0 6px 14px -8px rgba(4,4,42,0.40);
        }
        .ret-chip.on .cnt { background: rgba(252,211,77,0.18); color: #fcd34d; }
        .dark .ret-chip { background: #1e293b; border-color: #334155; color: #cbd5e1; }
        .dark .ret-chip .cnt { background: rgba(255,255,255,0.06); color: #cbd5e1; }
        .dark .ret-chip:hover { background: #334155; color: #fff; }
        .dark .ret-chip.on { background: #fbbf24; color: #04042a; border-color: #fbbf24; }
        .dark .ret-chip.on .cnt { background: rgba(4,4,42,0.18); color: #04042a; }

        /* Custom select chevron (amber) */
        .ret-select {
            appearance: none;
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%23f59e0b' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'><polyline points='6 9 12 15 18 9'/></svg>");
            background-repeat: no-repeat; background-position: right 14px center;
            padding-right: 40px;
        }
        [dir='rtl'] .ret-select { background-position: left 14px center; padding-right: 14px; padding-left: 40px; }

        /* Pagination — match Orders page */
        .ret-pagination nav { display: flex; }
        .ret-pagination ul,
        .ret-pagination .pagination {
            display: flex; flex-wrap: wrap; gap: 4px; list-style: none; margin: 0; padding: 0;
        }
        .ret-pagination a,
        .ret-pagination span {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 34px; height: 34px; padding: 0 10px;
            border-radius: 9px; background: #fff;
            border: 1px solid #e2e8f0; color: #475569;
            font-size: 12px; font-weight: 700; text-decoration: none;
            transition: all .15s ease;
        }
        .ret-pagination a:hover { color: #0f172a; border-color: #cbd5e1; background: #f8fafc; }
        .ret-pagination .active span,
        .ret-pagination span[aria-current="page"] {
            background: #04042a; color: #fcd34d; border-color: #04042a;
        }
        .ret-pagination .disabled span,
        .ret-pagination span[aria-disabled="true"] { opacity: 0.45; cursor: not-allowed; }
        .dark .ret-pagination a,
        .dark .ret-pagination span { background: #0f172a; border-color: #334155; color: #cbd5e1; }
        .dark .ret-pagination a:hover { background: #1e293b; color: #fff; border-color: #475569; }
        .dark .ret-pagination .active span,
        .dark .ret-pagination span[aria-current="page"] {
            background: #fbbf24; color: #04042a; border-color: #fbbf24;
        }
    </style>

    <div class="bg-[#f3f4f7] dark:bg-slate-950 min-h-screen">
    <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- ─────────────── Flash + errors ─────────────── --}}
        @if(session('success'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- ═════════════ Hero ═════════════ --}}
        <div class="relative overflow-hidden rounded-2xl mb-4 p-6 text-white"
             style="background: linear-gradient(135deg, #04042a 0%, #070740 50%, #0a0d3f 100%);">
            <div class="absolute inset-0 bento-stripes pointer-events-none opacity-50"></div>
            <div class="absolute top-0 bottom-0 left-0 w-[3px]" style="background: linear-gradient(180deg, #fbbf24 0%, #f59e0b 100%);"></div>
            <div class="absolute -top-16 -right-16 h-64 w-64 rounded-full bg-amber-400/10 blur-[60px] pointer-events-none"></div>

            <div class="relative flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="font-mono text-[10px] font-extrabold uppercase tracking-[0.28em] text-amber-300">{{ __('Returns · Workflow') }}</div>
                    <h1 class="text-3xl font-black mt-2 leading-tight">{{ __('Returns & Refunds') }}</h1>
                    <p class="text-sm text-white/65 mt-2">
                        {{ __('Showing :total requests', ['total' => number_format((int) ($stats['total'] ?? 0))]) }}
                        @if(($stats['open'] ?? 0) > 0)
                            <span class="text-white/35 mx-1">·</span>
                            <b class="text-amber-300">{{ __(':n open', ['n' => number_format((int) $stats['open'])]) }}</b>
                        @endif
                        <span class="text-white/35 mx-1">·</span>
                        {{ __('live') }}
                    </p>
                </div>
                <span class="inline-flex items-center gap-2 px-3 h-10 rounded-full bg-white/10 border border-white/15 text-xs font-bold text-white/80 backdrop-blur-sm">
                    <span class="relative inline-flex h-1.5 w-1.5">
                        <span class="absolute inset-0 rounded-full bg-amber-300 ys-pulse-dot"></span>
                        <span class="relative h-1.5 w-1.5 rounded-full bg-amber-300"></span>
                    </span>
                    {{ __('Operations') }}
                </span>
            </div>
        </div>

        {{-- ═════════════ Stat cards ═════════════ --}}
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-4">
            {{-- Total Requests --}}
            <div class="ret-stat tot">
                <div class="relative z-10 flex items-center justify-between gap-2">
                    <div class="h-9 w-9 rounded-xl grid place-items-center bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    </div>
                    <span class="inline-flex items-center gap-1 text-[10px] font-extrabold font-mono px-2 py-1 rounded-full bg-slate-100/80 text-slate-600 border border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700">
                        {{ __('All') }}
                    </span>
                </div>
                <div class="relative z-10 text-[10px] font-extrabold uppercase tracking-widest text-slate-500 dark:text-slate-400 mt-3.5">{{ __('Total Requests') }}</div>
                <div class="relative z-10 num-display text-3xl font-black text-slate-900 dark:text-white mt-1 leading-tight">{{ number_format((int) ($stats['total'] ?? 0)) }}</div>
                <div class="relative z-10 text-[11px] text-slate-500 dark:text-slate-400 mt-2 pt-2 border-t border-dashed border-slate-200 dark:border-slate-800 flex items-center gap-1.5">
                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-slate-400"></span>{{ __('Matching current view') }}
                </div>
            </div>

            {{-- Open Workflow --}}
            <div class="ret-stat open">
                <div class="relative z-10 flex items-center justify-between gap-2">
                    <div class="h-9 w-9 rounded-xl grid place-items-center bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="inline-flex items-center gap-1 text-[10px] font-extrabold font-mono px-2 py-1 rounded-full bg-amber-100 text-amber-800 border border-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:border-amber-500/30">
                        <i class="fas fa-circle-exclamation text-[9px]"></i> {{ __('Review') }}
                    </span>
                </div>
                <div class="relative z-10 text-[10px] font-extrabold uppercase tracking-widest text-slate-500 dark:text-slate-400 mt-3.5">{{ __('Open Workflow') }}</div>
                <div class="relative z-10 num-display text-3xl font-black text-slate-900 dark:text-white mt-1 leading-tight">{{ number_format((int) ($stats['open'] ?? 0)) }}</div>
                <div class="relative z-10 text-[11px] text-slate-500 dark:text-slate-400 mt-2 pt-2 border-t border-dashed border-slate-200 dark:border-slate-800 flex items-center gap-1.5">
                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-amber-500"></span>{{ __('Requested / Approved / Received') }}
                </div>
            </div>

            {{-- Refunded --}}
            <div class="ret-stat refunded">
                <div class="relative z-10 flex items-center justify-between gap-2">
                    <div class="h-9 w-9 rounded-xl grid place-items-center bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <span class="inline-flex items-center gap-1 text-[10px] font-extrabold font-mono px-2 py-1 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:border-emerald-500/30">
                        <i class="fas fa-check text-[9px]"></i> {{ __('Paid') }}
                    </span>
                </div>
                <div class="relative z-10 text-[10px] font-extrabold uppercase tracking-widest text-slate-500 dark:text-slate-400 mt-3.5">{{ __('Refunded') }}</div>
                <div class="relative z-10 num-display text-3xl font-black text-slate-900 dark:text-white mt-1 leading-tight">{{ number_format((int) ($stats['refunded'] ?? 0)) }}</div>
                <div class="relative z-10 text-[11px] text-slate-500 dark:text-slate-400 mt-2 pt-2 border-t border-dashed border-slate-200 dark:border-slate-800 flex items-center gap-1.5">
                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-500"></span>{{ __('Requests paid back') }}
                </div>
            </div>

            {{-- Closed --}}
            <div class="ret-stat closed">
                <div class="relative z-10 flex items-center justify-between gap-2">
                    <div class="h-9 w-9 rounded-xl grid place-items-center bg-slate-100 text-slate-600 dark:bg-slate-700/40 dark:text-slate-300">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                    </div>
                    <span class="inline-flex items-center gap-1 text-[10px] font-extrabold font-mono px-2 py-1 rounded-full bg-slate-100 text-slate-700 border border-slate-200 dark:bg-slate-700/40 dark:text-slate-300 dark:border-slate-600">
                        {{ __('Done') }}
                    </span>
                </div>
                <div class="relative z-10 text-[10px] font-extrabold uppercase tracking-widest text-slate-500 dark:text-slate-400 mt-3.5">{{ __('Closed') }}</div>
                <div class="relative z-10 num-display text-3xl font-black text-slate-900 dark:text-white mt-1 leading-tight">{{ number_format((int) ($stats['closed'] ?? 0)) }}</div>
                <div class="relative z-10 text-[11px] text-slate-500 dark:text-slate-400 mt-2 pt-2 border-t border-dashed border-slate-200 dark:border-slate-800 flex items-center gap-1.5">
                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-slate-400"></span>{{ __('Rejected + Refunded + Closed') }}
                </div>
            </div>

            {{-- Refund Value (amber hero stat) --}}
            <div class="ret-stat money">
                <div class="relative z-10 flex items-center justify-between gap-2">
                    <div class="h-9 w-9 rounded-xl grid place-items-center text-amber-800" style="background: linear-gradient(135deg, #fef3c7, #fde68a);">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="inline-flex items-center gap-1 text-[10px] font-extrabold font-mono px-2 py-1 rounded-full bg-amber-200/60 text-amber-900 border border-amber-300/60 dark:bg-amber-500/20 dark:text-amber-200 dark:border-amber-500/40">
                        <span class="relative inline-flex h-1 w-1">
                            <span class="absolute inset-0 rounded-full bg-amber-600 ys-pulse-dot opacity-70"></span>
                            <span class="relative h-1 w-1 rounded-full bg-amber-600"></span>
                        </span>
                        LIVE
                    </span>
                </div>
                <div class="relative z-10 text-[10px] font-extrabold uppercase tracking-widest text-amber-800 dark:text-amber-300 mt-3.5">{{ __('Refund Value') }}</div>
                <div class="relative z-10 num-display text-3xl font-black text-slate-900 dark:text-white mt-1 leading-tight">
                    {{ number_format((float) ($stats['refund_total'] ?? 0), $currencyDecimals) }}<span class="text-xs text-amber-700 dark:text-amber-400 font-bold ms-1">{{ $currencyLabel }}</span>
                </div>
                <div class="relative z-10 text-[11px] text-slate-600 dark:text-slate-300 mt-2 pt-2 border-t border-dashed border-amber-300/60 dark:border-amber-500/30 flex items-center gap-1.5">
                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-amber-500"></span>{{ __('Total approved refunds') }}
                </div>
            </div>
        </div>

        {{-- ═════════════ Filter card ═════════════ --}}
        <form method="GET" action="{{ route('admin.returns.index') }}" class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 rounded-2xl p-5 bento-shadow mb-4">
            <div class="flex items-center gap-2.5 mb-4">
                <div class="h-9 w-9 rounded-xl bg-[#04042a] text-amber-300 grid place-items-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                </div>
                <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ __('Filter Requests') }}</h3>
            </div>

            <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_auto] items-end">
                <div>
                    <label for="filter-search" class="block text-[10.5px] font-extrabold uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-1.5">{{ __('Search') }}</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 start-0 flex items-center ps-3 text-slate-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 100-15 7.5 7.5 0 000 15z"/></svg>
                        </span>
                        <input id="filter-search" name="search" value="{{ $currentSearch }}"
                               placeholder="{{ __('Search order, customer, email, or reason...') }}"
                               class="h-11 w-full ps-10 pe-3 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-900 placeholder:text-slate-400 transition focus:outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-400/30 focus:bg-white dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:bg-slate-900">
                    </div>
                </div>
                <div>
                    <label for="filter-status" class="block text-[10.5px] font-extrabold uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-1.5">{{ __('Status') }}</label>
                    <select id="filter-status" name="status"
                            class="ret-select h-11 w-full px-3 rounded-xl border border-slate-200 bg-slate-50 text-sm font-semibold text-slate-900 transition focus:outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-400/30 focus:bg-white dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100 dark:focus:bg-slate-900">
                        <option value="">{{ __('All Statuses') }}</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status }}" @selected($currentStatus === $status)>
                                {{ $statusMeta[$status]['label'] ?? __(ucfirst(str_replace('_', ' ', (string) $status))) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2 justify-end">
                    @if($hasActiveFilters)
                        <a href="{{ route('admin.returns.index') }}"
                           class="inline-flex items-center gap-2 h-11 px-4 rounded-xl text-xs font-bold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700 dark:hover:bg-slate-700 transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            {{ __('Reset') }}
                        </a>
                    @endif
                    <button type="submit" class="inline-flex items-center gap-2 h-11 px-5 rounded-xl text-xs font-bold text-amber-300 bg-[#04042a] hover:bg-[#07073a] transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                        {{ __('Apply Filters') }}
                    </button>
                </div>
            </div>
        </form>

        {{-- ═════════════ List card with status band + rows ═════════════ --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 rounded-2xl overflow-hidden bento-shadow">

            {{-- Status quick filter band --}}
            <div class="flex flex-wrap items-center gap-4 px-5 py-4 border-b border-slate-200/70 dark:border-slate-800 bg-gradient-to-b from-slate-50 to-white dark:from-slate-900/60 dark:to-slate-900">
                <span class="inline-flex items-center gap-2 font-mono text-[10px] font-extrabold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400 shrink-0">
                    <span class="relative inline-flex h-1.5 w-1.5">
                        <span class="absolute inset-0 rounded-full bg-amber-500 ys-pulse-dot"></span>
                        <span class="relative h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                    </span>
                    {{ __('Quick Filter · Status') }}
                </span>
                <div class="flex flex-wrap gap-1.5">
                    <a href="{{ route('admin.returns.index', array_filter(['search' => $currentSearch])) }}"
                       class="ret-chip c-all {{ $currentStatus === '' ? 'on' : '' }}">
                        {{ __('All') }} <span class="cnt">{{ number_format((int) ($stats['total'] ?? 0)) }}</span>
                    </a>
                    @foreach($statuses as $status)
                        @php
                            $count = (int) ($statusCounts->get($status, 0) ?? 0);
                            $meta = $statusMeta[$status] ?? ['label' => __(ucfirst((string) $status)), 'dot' => 'bg-slate-400', 'hex' => '#94a3b8'];
                            $chipClass = match ($status) {
                                'requested' => 'c-req',
                                'approved'  => 'c-app',
                                'rejected'  => 'c-rej',
                                'received'  => 'c-rcv',
                                'refunded'  => 'c-rfd',
                                'closed'    => 'c-cls',
                                default     => '',
                            };
                        @endphp
                        <a href="{{ route('admin.returns.index', array_filter(['search' => $currentSearch, 'status' => $status])) }}"
                           class="ret-chip {{ $chipClass }} {{ $currentStatus === $status ? 'on' : '' }}">
                            <span class="dot" style="background: {{ $meta['hex'] }};"></span>
                            {{ $meta['label'] }} <span class="cnt">{{ number_format($count) }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- List card head --}}
            <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-slate-200/70 dark:border-slate-800">
                <div class="text-sm font-extrabold text-slate-900 dark:text-white flex items-center gap-2.5">
                    <span class="inline-block w-1 h-4 rounded-full" style="background: linear-gradient(180deg, #fbbf24, #f59e0b);"></span>
                    {{ __('Requests') }}
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400">
                        ({{ __('showing :from–:to of :total', [
                            'from'  => $requests->firstItem() ?? 0,
                            'to'    => $requests->lastItem() ?? 0,
                            'total' => $requests->total(),
                        ]) }})
                    </span>
                </div>
                <div class="text-[11.5px] font-mono text-slate-500 dark:text-slate-400 hidden sm:block">
                    {{ __('Updated :time · auto-refresh 30s', ['time' => now()->format('H:i')]) }}
                </div>
            </div>

            {{-- Rows --}}
            @forelse($requests as $requestRow)
                @php
                    $rowStatusMeta = $statusMeta[$requestRow->status] ?? [
                        'label' => __(ucfirst(str_replace('_', ' ', (string) $requestRow->status))),
                        'pill'  => 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-700/40 dark:text-slate-300 dark:border-slate-600',
                        'dot'   => 'bg-slate-500',
                        'hex'   => '#94a3b8',
                    ];
                    $rowTypeMeta = $typeMeta[$requestRow->type] ?? [
                        'label' => __(ucfirst(str_replace('_', ' ', (string) $requestRow->type))),
                        'class' => 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-700/40 dark:text-slate-200',
                    ];
                    $order = $requestRow->order;
                    $customer = $requestRow->user;
                    $paymentMeta = $order ? \App\Models\Order::paymentStatusMeta((string) $order->payment_status) : null;
                @endphp
                <div class="grid grid-cols-1 lg:grid-cols-[220px_minmax(0,1fr)_260px_minmax(0,1fr)_280px] gap-4 px-5 py-5 border-b border-slate-100 dark:border-slate-800/60 hover:bg-amber-50/40 dark:hover:bg-slate-800/40 transition">

                    {{-- Col 1: Request meta --}}
                    <div>
                        <div class="flex flex-wrap items-center gap-1.5 mb-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[9.5px] font-extrabold uppercase tracking-wide border {{ $rowTypeMeta['class'] }}">
                                {{ $rowTypeMeta['label'] }}
                            </span>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold leading-none border {{ $rowStatusMeta['pill'] }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $rowStatusMeta['dot'] }}"></span>
                                {{ $rowStatusMeta['label'] }}
                            </span>
                        </div>
                        <div class="font-mono text-[13px] font-extrabold text-slate-900 dark:text-white">#R-{{ $requestRow->id }}</div>
                        <div class="font-mono text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">
                            {{ $requestRow->requested_at?->format('M d, Y') ?? '-' }}
                            @if($requestRow->requested_at)
                                <span class="text-slate-300 dark:text-slate-600">/</span>
                                {{ $requestRow->requested_at->format('h:i A') }}
                            @endif
                        </div>
                        @if($requestRow->resolved_at)
                            <div class="font-mono text-[10.5px] text-emerald-700 dark:text-emerald-400 mt-1.5">
                                {{ __('Resolved') }}: {{ $requestRow->resolved_at->format('M d, Y') }}
                            </div>
                        @endif
                    </div>

                    {{-- Col 2: Order --}}
                    <div>
                        @if($order)
                            <a href="{{ route('admin.orders.show', $order) }}"
                               class="inline-block font-mono text-[13px] font-extrabold text-blue-700 hover:text-blue-800 dark:text-blue-300 dark:hover:text-blue-200">
                                {{ $order->order_number }}
                            </a>
                            <div class="num-display text-[14px] font-extrabold text-slate-900 dark:text-white mt-1.5">
                                {{ number_format((float) $order->total_amount, $currencyDecimals) }}
                                <span class="text-[10px] text-slate-400 font-medium ms-1">{{ $currencyLabel }}</span>
                            </div>
                            @if($paymentMeta)
                                <div class="mt-2">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-[10.5px] font-bold border {{ $paymentMeta['class'] }}">
                                        {{ $paymentMeta['label'] }}
                                    </span>
                                </div>
                            @endif
                        @else
                            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ __('Order unavailable') }}</p>
                        @endif
                    </div>

                    {{-- Col 3: Customer --}}
                    <div>
                        <div>
                            @if($customer && Route::has('admin.users.show'))
                                <a href="{{ route('admin.users.show', $customer) }}"
                                   class="block max-w-full truncate font-semibold text-[13px] text-blue-700 hover:text-blue-800 dark:text-blue-300 dark:hover:text-blue-200">
                                    {{ $customer->name }}
                                </a>
                            @else
                                <p class="font-semibold text-[13px] text-slate-900 dark:text-white truncate">{{ $customer?->name ?? __('Guest customer') }}</p>
                            @endif
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 truncate">{{ $customer?->email ?? '-' }}</p>
                        </div>
                        <dl class="grid gap-1.5 text-[11px] mt-2.5 rounded-lg bg-slate-50 dark:bg-slate-950/40 p-2.5 border border-slate-100 dark:border-slate-800">
                            <div class="flex items-start justify-between gap-3">
                                <dt class="font-bold text-slate-500 dark:text-slate-400">{{ __('Account phone') }}</dt>
                                <dd class="text-right font-semibold text-slate-800 dark:text-slate-200">{{ $customer?->phone ?: '-' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <dt class="font-bold text-slate-500 dark:text-slate-400">{{ __('Delivery phone') }}</dt>
                                <dd class="text-right font-semibold text-slate-800 dark:text-slate-200">{{ $order?->delivery_phone ?: '-' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <dt class="font-bold text-slate-500 dark:text-slate-400">{{ __('City') }}</dt>
                                <dd class="text-right font-semibold text-slate-800 dark:text-slate-200">{{ $order?->delivery_city ?: '-' }}</dd>
                            </div>
                            @if($order?->delivery_address)
                                <div>
                                    <dt class="font-bold text-slate-500 dark:text-slate-400">{{ __('Address') }}</dt>
                                    <dd class="mt-1 line-clamp-2 text-slate-800 dark:text-slate-200">{{ $order->delivery_address }}</dd>
                                </div>
                            @endif
                            @if($customer)
                                <div class="flex items-start justify-between gap-3">
                                    <dt class="font-bold text-slate-500 dark:text-slate-400">{{ __('Role') }}</dt>
                                    <dd class="text-right font-semibold capitalize text-slate-800 dark:text-slate-200">{{ str_replace('_', ' ', (string) $customer->role) }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>

                    {{-- Col 4: Reason --}}
                    <div>
                        <p class="text-[12.5px] leading-relaxed text-slate-700 dark:text-slate-300 line-clamp-5">{{ $requestRow->reason }}</p>
                        @if($requestRow->admin_note)
                            <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-[11px] leading-relaxed text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
                                <span class="font-extrabold">{{ __('Admin note') }}:</span>
                                {{ $requestRow->admin_note }}
                            </div>
                        @endif
                    </div>

                    {{-- Col 5: Action form --}}
                    <div>
                        <form method="POST" action="{{ route('admin.returns.update', $requestRow) }}" class="space-y-2">
                            @csrf
                            @method('PATCH')

                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[10px] font-extrabold uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-1">{{ __('Status') }}</label>
                                    <select name="status"
                                            class="ret-select h-10 w-full px-2.5 rounded-lg border border-slate-200 bg-white text-[12.5px] font-semibold text-slate-900 transition focus:outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-400/30 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100">
                                        @foreach($statuses as $status)
                                            <option value="{{ $status }}" @selected($requestRow->status === $status)>
                                                {{ $statusMeta[$status]['label'] ?? __(ucfirst(str_replace('_', ' ', (string) $status))) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-extrabold uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-1">{{ __('Refund') }}</label>
                                    <input name="refund_amount" type="number" step="0.01" min="0"
                                           value="{{ $requestRow->refund_amount }}" placeholder="0"
                                           class="h-10 w-full px-2.5 rounded-lg border border-slate-200 bg-white text-[12.5px] font-semibold text-slate-900 transition focus:outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-400/30 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100">
                                </div>
                            </div>

                            <div>
                                <label class="block text-[10px] font-extrabold uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-1">{{ __('Internal note') }}</label>
                                <textarea name="admin_note" rows="2" placeholder="{{ __('Add handling note for the team') }}"
                                          class="w-full p-2.5 rounded-lg border border-slate-200 bg-white text-[12.5px] text-slate-900 transition focus:outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-400/30 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100 resize-none">{{ $requestRow->admin_note }}</textarea>
                            </div>

                            <button type="submit"
                                    class="inline-flex items-center justify-center gap-2 w-full h-10 rounded-lg text-[12.5px] font-extrabold text-[#04042a] border border-amber-500/20 shadow-md shadow-amber-500/30 transition hover:brightness-105"
                                    style="background: linear-gradient(180deg, #fbbf24, #f59e0b);">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                                {{ __('Save Workflow') }}
                            </button>
                        </form>
                    </div>

                </div>
            @empty
                <div class="py-14 px-4 text-center">
                    <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-slate-50 border border-slate-200 grid place-items-center text-slate-400 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-500">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                    </div>
                    <div class="text-base font-bold text-slate-900 dark:text-white">{{ __('No return requests found.') }}</div>
                    <div class="text-[13px] text-slate-500 dark:text-slate-400 mt-1.5">{{ __('Try changing the search or status filter.') }}</div>
                    @if($hasActiveFilters)
                        <a href="{{ route('admin.returns.index') }}"
                           class="inline-flex items-center gap-2 h-10 px-4 mt-4 rounded-xl text-xs font-bold text-amber-300 bg-[#04042a] hover:bg-[#07073a] transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            {{ __('Reset filters') }}
                        </a>
                    @endif
                </div>
            @endforelse

            {{-- Pagination --}}
            @if($requests->hasPages())
                <div class="flex flex-wrap justify-between items-center gap-3 px-5 py-3.5 border-t border-slate-200/70 dark:border-slate-800 bg-slate-50/40 dark:bg-slate-900/40">
                    <span class="text-[12px] text-slate-500 dark:text-slate-400">
                        {{ __('Showing :from–:to of :total', [
                            'from'  => $requests->firstItem() ?? 0,
                            'to'    => $requests->lastItem() ?? 0,
                            'total' => $requests->total(),
                        ]) }}
                    </span>
                    <div class="ret-pagination">
                        {{ $requests->links() }}
                    </div>
                </div>
            @endif
        </div>

    </div>
    </div>
    </div>
</x-app-layout>
