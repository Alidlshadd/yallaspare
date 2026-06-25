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
        .bento-shadow-lg { box-shadow: 0 10px 30px rgba(7,7,64,0.18), 0 30px 60px rgba(7,7,64,0.20); }
        .num-display { font-feature-settings: "tnum" 1, "lnum" 1; letter-spacing: -0.025em; }

        .corner-brackets::before,
        .corner-brackets::after {
            content: ""; position: absolute; width: 14px; height: 14px;
            border-color: rgba(255,255,255,0.35); border-style: solid; border-width: 0;
            pointer-events: none;
        }
        .corner-brackets::before { top: 14px; left: 14px; border-top-width: 1.5px; border-left-width: 1.5px; }
        .corner-brackets::after { bottom: 14px; right: 14px; border-bottom-width: 1.5px; border-right-width: 1.5px; }

        @keyframes ys-pulse {
            0%   { box-shadow: 0 0 0 0 rgba(251,191,36,0.55); }
            100% { box-shadow: 0 0 0 8px rgba(251,191,36,0); }
        }
        .ys-pulse-dot { animation: ys-pulse 1.6s ease-out infinite; }

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

        {{-- ═════════════════════════════════════════════════════════════ --}}
        {{-- BENTO GRID: Refund Value hero + 4 attention tiles + 2 footer --}}
        {{-- ═════════════════════════════════════════════════════════════ --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">

            {{-- ═══ HERO 2x2 — Total Refund Value ═══ --}}
            <div class="relative sm:col-span-2 lg:col-span-2 lg:row-span-2 rounded-3xl text-white p-7 overflow-hidden bento-shadow-lg corner-brackets"
                 style="background: linear-gradient(135deg, #04042a 0%, #070740 50%, #0a0d3f 100%);">
                <div class="absolute inset-0 bento-stripes pointer-events-none"></div>
                <div class="absolute -top-24 -right-24 h-80 w-80 rounded-full bg-amber-400/20 blur-[80px] pointer-events-none"></div>
                <div class="absolute -bottom-24 -left-12 h-64 w-64 rounded-full bg-cyan-400/15 blur-[80px] pointer-events-none"></div>
                <div class="absolute top-0 left-0 right-0 h-[2px]" style="background: linear-gradient(90deg, #22d3ee, #fbbf24, #f59e0b);"></div>

                <div class="relative flex items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] uppercase tracking-widest font-mono text-white/55 font-bold">{{ __('Refund Value') }}</span>
                            <span class="inline-flex items-center gap-1.5 text-[10px] font-mono font-bold text-amber-300 px-1.5 py-0.5 rounded bg-amber-400/10 border border-amber-400/20">
                                <span class="relative inline-flex h-1.5 w-1.5">
                                    <span class="absolute inset-0 rounded-full bg-amber-300 ys-pulse-dot"></span>
                                    <span class="relative h-1.5 w-1.5 rounded-full bg-amber-300"></span>
                                </span>
                                LIVE
                            </span>
                        </div>
                        <h2 class="mt-2 text-[13px] font-bold uppercase tracking-[0.15em] text-white/55">{{ __('Total Refund Value') }}</h2>
                    </div>
                    <div class="h-12 w-12 rounded-2xl bg-white/10 border border-white/15 grid place-items-center backdrop-blur-sm shadow-inner">
                        <svg class="w-5 h-5 text-amber-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>

                <div class="relative mt-7">
                    <div class="flex items-baseline gap-2 flex-wrap">
                        <span class="text-sm font-bold text-amber-300">{{ $currencyLabel }}</span>
                        <span class="num-display text-5xl md:text-6xl font-black leading-none">{{ number_format((float) ($stats['refund_total'] ?? 0), $currencyDecimals) }}</span>
                    </div>
                    <p class="mt-3 text-xs text-white/55">{{ __('Sum of approved refunds') }}</p>
                </div>

                <div class="relative mt-6 pt-5 border-t border-dashed border-white/15 grid grid-cols-3 gap-4">
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-[0.15em] text-white/50">{{ __('Total Requests') }}</div>
                        <div class="num-display text-xl font-black mt-1">{{ number_format((int) ($stats['total'] ?? 0)) }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-[0.15em] text-white/50">{{ __('Open Workflow') }}</div>
                        <div class="num-display text-xl font-black mt-1 text-amber-300">{{ number_format((int) ($stats['open'] ?? 0)) }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-[0.15em] text-white/50">{{ __('Closed') }}</div>
                        <div class="num-display text-xl font-black mt-1">{{ number_format((int) ($stats['closed'] ?? 0)) }}</div>
                    </div>
                </div>
            </div>

            {{-- ═══ 4 attention tiles ═══ --}}
            @php
                $attentionTiles = [
                    [
                        'label' => __('Requested'),
                        'value' => $statusCounts->get('requested', 0),
                        'stripe' => 'from-amber-400 to-amber-500',
                        'ic_bg' => 'bg-amber-100 dark:bg-amber-500/10',
                        'ic_fg' => 'text-amber-700 dark:text-amber-300',
                        'dot' => 'bg-amber-500',
                        'foot' => __('Awaits decision'),
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                    ],
                    [
                        'label' => __('Approved'),
                        'value' => $statusCounts->get('approved', 0),
                        'stripe' => 'from-blue-400 to-blue-500',
                        'ic_bg' => 'bg-blue-100 dark:bg-blue-500/10',
                        'ic_fg' => 'text-blue-700 dark:text-blue-300',
                        'dot' => 'bg-blue-500',
                        'foot' => __('Awaiting return'),
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                    ],
                    [
                        'label' => __('Received'),
                        'value' => $statusCounts->get('received', 0),
                        'stripe' => 'from-cyan-400 to-cyan-500',
                        'ic_bg' => 'bg-cyan-100 dark:bg-cyan-500/10',
                        'ic_fg' => 'text-cyan-700 dark:text-cyan-300',
                        'dot' => 'bg-cyan-500',
                        'foot' => __('Item inspected'),
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>',
                    ],
                    [
                        'label' => __('Refunded'),
                        'value' => $statusCounts->get('refunded', $stats['refunded'] ?? 0),
                        'stripe' => 'from-emerald-400 to-emerald-500',
                        'ic_bg' => 'bg-emerald-100 dark:bg-emerald-500/10',
                        'ic_fg' => 'text-emerald-700 dark:text-emerald-300',
                        'dot' => 'bg-emerald-500',
                        'foot' => __('Requests paid back'),
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>',
                    ],
                ];
            @endphp

            @foreach($attentionTiles as $t)
                <div class="relative overflow-hidden rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 p-6 bento-shadow transition">
                    <div class="absolute top-0 bottom-0 left-0 w-[3px] bg-gradient-to-b {{ $t['stripe'] }}"></div>
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-xl grid place-items-center {{ $t['ic_bg'] }} {{ $t['ic_fg'] }}">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">{!! $t['icon'] !!}</svg>
                        </div>
                        <div class="text-[10px] uppercase tracking-widest font-bold text-slate-500 dark:text-slate-400">{{ $t['label'] }}</div>
                    </div>
                    <div class="num-display text-3xl font-black text-slate-900 dark:text-white mt-4">{{ number_format((int) $t['value']) }}</div>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1.5">
                        <span class="inline-block w-1.5 h-1.5 rounded-full {{ $t['dot'] }}"></span> {{ $t['foot'] }}
                    </div>
                </div>
            @endforeach

            {{-- ═══ 2 footer tiles: Rejected + Closed ═══ --}}
            @php
                $footerTiles = [
                    [
                        'label' => __('Rejected'),
                        'value' => $statusCounts->get('rejected', 0),
                        'stripe' => 'from-rose-400 to-rose-500',
                        'ic_bg' => 'bg-rose-100 dark:bg-rose-500/10',
                        'ic_fg' => 'text-rose-700 dark:text-rose-300',
                        'dot' => 'bg-rose-500',
                        'foot' => __('Declined requests'),
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>',
                    ],
                    [
                        'label' => __('Closed'),
                        'value' => $statusCounts->get('closed', 0),
                        'stripe' => 'from-slate-400 to-slate-500',
                        'ic_bg' => 'bg-slate-100 dark:bg-slate-700/40',
                        'ic_fg' => 'text-slate-600 dark:text-slate-300',
                        'dot' => 'bg-slate-400',
                        'foot' => __('Archived workflow'),
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>',
                    ],
                ];
            @endphp

            @foreach($footerTiles as $t)
                <div class="relative overflow-hidden rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 p-6 bento-shadow transition">
                    <div class="absolute top-0 bottom-0 left-0 w-[3px] bg-gradient-to-b {{ $t['stripe'] }}"></div>
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-xl grid place-items-center {{ $t['ic_bg'] }} {{ $t['ic_fg'] }}">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">{!! $t['icon'] !!}</svg>
                        </div>
                        <div class="text-[10px] uppercase tracking-widest font-bold text-slate-500 dark:text-slate-400">{{ $t['label'] }}</div>
                    </div>
                    <div class="num-display text-3xl font-black text-slate-900 dark:text-white mt-4">{{ number_format((int) $t['value']) }}</div>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1.5">
                        <span class="inline-block w-1.5 h-1.5 rounded-full {{ $t['dot'] }}"></span> {{ $t['foot'] }}
                    </div>
                </div>
            @endforeach
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
