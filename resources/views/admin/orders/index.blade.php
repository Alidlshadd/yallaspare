<x-app-layout>
    <x-slot name="header">{{ __('Orders Management') }}</x-slot>

    @php
        $currencyLabel = (string) ($systemSettings['currency_label'] ?? 'IQD');
        $currencyDecimals = (int) ($systemSettings['currency_decimals'] ?? 0);
        $hasActiveFilters = request()->hasAny(['search', 'status', 'association', 'from', 'to']);

        // Pragmatic view-level lookups for stats the controller does not expose yet.
        // Wrapped defensively so a schema mismatch never breaks the page.
        try {
            $todayCount = \App\Models\Order::whereDate('created_at', today())->count();
        } catch (\Throwable $e) { $todayCount = null; }

        try {
            $todayRevenue = (float) \App\Models\Order::whereDate('created_at', today())
                ->where('status', \App\Models\Order::STATUS_DELIVERED)
                ->sum('total_amount');
        } catch (\Throwable $e) { $todayRevenue = null; }

        try {
            $unpaidCount = \App\Models\Order::whereIn('payment_status', ['pending_payment', 'pending-payment', 'failed'])->count();
        } catch (\Throwable $e) { $unpaidCount = null; }

        $needAttention = (int) ($stats['pending'] ?? 0) + (int) ($unpaidCount ?? 0);

        $pillClass = function (?string $key) {
            return match ((string) $key) {
                'pending'                              => 'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:border-amber-500/30',
                'processing'                           => 'bg-violet-100 text-violet-800 border-violet-200 dark:bg-violet-500/10 dark:text-violet-300 dark:border-violet-500/30',
                'shipped'                              => 'bg-sky-100 text-sky-800 border-sky-200 dark:bg-sky-500/10 dark:text-sky-300 dark:border-sky-500/30',
                'delivered', 'paid'                    => 'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:border-emerald-500/30',
                'cancelled', 'failed'                  => 'bg-rose-100 text-rose-700 border-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:border-rose-500/30',
                'pending_payment', 'pending-payment'   => 'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:border-amber-500/30',
                'refunded'                             => 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-700/40 dark:text-slate-300 dark:border-slate-600',
                default                                => 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-700/40 dark:text-slate-200 dark:border-slate-600',
            };
        };

        $dotColor = function (?string $key) {
            return match ((string) $key) {
                'pending', 'pending_payment', 'pending-payment' => 'bg-amber-500',
                'processing'                                    => 'bg-violet-500',
                'shipped'                                       => 'bg-sky-500',
                'delivered', 'paid'                             => 'bg-emerald-500',
                'cancelled', 'failed'                           => 'bg-rose-500',
                'refunded'                                      => 'bg-slate-400',
                default                                         => 'bg-slate-400',
            };
        };
    @endphp

    {{-- Local decoration utilities (mirror the dashboard's style pattern) and Alpine-teleport dropdown skin. --}}
    <style>
        .bento-stripes {
            background-image: repeating-linear-gradient(135deg, rgba(255,255,255,0.06) 0 1px, transparent 1px 14px);
        }
        .bento-shadow { box-shadow: 0 1px 2px rgba(7,7,64,0.04), 0 4px 16px rgba(7,7,64,0.06); }
        .bento-shadow:hover { box-shadow: 0 2px 6px rgba(7,7,64,0.08), 0 16px 36px rgba(7,7,64,0.10); }
        .bento-shadow-lg { box-shadow: 0 10px 30px rgba(7,7,64,0.18), 0 30px 60px rgba(7,7,64,0.20); }
        .corner-brackets::before,
        .corner-brackets::after {
            content: ""; position: absolute; width: 14px; height: 14px;
            border-color: rgba(255,255,255,0.35); border-style: solid; border-width: 0;
            pointer-events: none;
        }
        .corner-brackets::before { top: 14px; left: 14px; border-top-width: 1.5px; border-left-width: 1.5px; }
        .corner-brackets::after { bottom: 14px; right: 14px; border-bottom-width: 1.5px; border-right-width: 1.5px; }
        .num-display { font-feature-settings: "tnum" 1, "lnum" 1; letter-spacing: -0.025em; }

        /* Alpine-teleported dropdowns need positioning + styling without relying on parent context. */
        .op-menu {
            position: fixed;
            width: min(270px, calc(100vw - 16px));
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 24px 48px -28px rgba(15,23,42,0.32), 0 1px 0 rgba(255,255,255,0.85) inset;
            padding: 8px;
            z-index: 60;
            color: #0f172a;
            font-family: 'Figtree', system-ui, sans-serif;
        }
        .dark .op-menu {
            background: #1e293b;
            border-color: #334155;
            color: #f8fafc;
            box-shadow: 0 24px 48px -28px rgba(0,0,0,0.75), 0 1px 0 rgba(255,255,255,0.04) inset;
        }
        .op-menu .head {
            font-size: 10.5px;
            text-transform: uppercase;
            letter-spacing: .14em;
            color: #64748b;
            padding: 8px 10px 6px;
            font-weight: 800;
        }
        .dark .op-menu .head { color: #94a3b8; }
        .op-menu form { display: flex; align-items: center; gap: 8px; padding: 4px 6px 8px; }
        .op-menu select {
            flex: 1 1 auto; min-width: 0;
            background: #f8fafc; border: 1px solid #cbd5e1; color: #0f172a;
            height: 38px; padding: 0 10px; border-radius: 8px; font-size: 12.5px;
            color-scheme: light;
        }
        .dark .op-menu select {
            background: #0f172a; border-color: #334155; color: #f8fafc; color-scheme: dark;
        }
        .op-menu button[type="submit"] {
            background: #04042a; color: #fcd34d;
            height: 38px; padding: 0 14px; border-radius: 8px;
            font-size: 12.5px; font-weight: 800;
            border: 1px solid #04042a; cursor: pointer;
        }
        .op-menu button[type="submit"]:hover { background: #07073a; }
        .op-menu hr { border: 0; border-top: 1px solid #e2e8f0; margin: 4px 0; }
        .dark .op-menu hr { border-top-color: #334155; }
        .op-menu .danger {
            display: block; width: 100%;
            text-align: left;
            padding: 10px 12px; border-radius: 8px;
            background: transparent; color: #b91c1c;
            font-size: 12.5px; font-weight: 700;
            border: 0; cursor: pointer;
        }
        .dark .op-menu .danger { color: #fca5a5; }
        [dir='rtl'] .op-menu .danger { text-align: right; }
        .op-menu .danger:hover { background: #fef2f2; }
        .dark .op-menu .danger:hover { background: rgba(239,68,68,0.10); }

        .op-invoice-menu { width: min(218px, calc(100vw - 16px)); padding: 8px; }
        .op-invoice-menu .invoice-lang {
            display: flex; align-items: center; justify-content: space-between;
            gap: 10px; padding: 10px 12px; border-radius: 8px;
            color: #0f172a; font-size: 12.5px; font-weight: 700;
            text-decoration: none;
        }
        .dark .op-invoice-menu .invoice-lang { color: #f8fafc; }
        .op-invoice-menu .invoice-lang:hover { background: #fffbeb; color: #04042a; }
        .dark .op-invoice-menu .invoice-lang:hover { background: rgba(251,191,36,0.12); color: #fcd34d; }
        .op-invoice-menu .invoice-code {
            display: inline-flex; align-items: center; justify-content: center;
            width: 34px; height: 24px; border-radius: 999px;
            background: #fef3c7; color: #b45309; font-size: 11px; letter-spacing: .04em;
            font-weight: 800;
        }
        .dark .op-invoice-menu .invoice-code { background: rgba(251,191,36,0.16); color: #fcd34d; }

        @keyframes ys-pulse {
            0%   { box-shadow: 0 0 0 0   rgba(251,191,36,0.55); }
            100% { box-shadow: 0 0 0 12px rgba(251,191,36,0);   }
        }
        .ys-pulse-dot { animation: ys-pulse 1.6s ease-out infinite; }
    </style>

    <div class="bg-[#f3f4f7] dark:bg-slate-950 min-h-screen">
    <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- ─────────────── Flash messages ─────────────── --}}
        @if(session('success'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
                {{ session('error') }}
            </div>
        @endif

        {{-- ═════════════════════════════════════════════════════════════ --}}
        {{-- BENTO GRID: Hero (Today) + 4 attention tiles + 2 footer tiles --}}
        {{-- ═════════════════════════════════════════════════════════════ --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">

            {{-- ═══ HERO 2x2 — Today's Revenue ═══ --}}
            <div class="relative sm:col-span-2 lg:col-span-2 lg:row-span-2 rounded-3xl text-white p-7 overflow-hidden bento-shadow-lg corner-brackets"
                 style="background: linear-gradient(135deg, #04042a 0%, #070740 50%, #0a0d3f 100%);">
                <div class="absolute inset-0 bento-stripes pointer-events-none"></div>
                <div class="absolute -top-24 -right-24 h-80 w-80 rounded-full bg-amber-400/20 blur-[80px] pointer-events-none"></div>
                <div class="absolute -bottom-24 -left-12 h-64 w-64 rounded-full bg-cyan-400/15 blur-[80px] pointer-events-none"></div>
                <div class="absolute top-0 left-0 right-0 h-[2px]" style="background: linear-gradient(90deg, #22d3ee, #fbbf24, #f59e0b);"></div>

                <div class="relative flex items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] uppercase tracking-widest font-mono text-white/55 font-bold">{{ __('Today') }}</span>
                            <span class="inline-flex items-center gap-1.5 text-[10px] font-mono font-bold text-amber-300 px-1.5 py-0.5 rounded bg-amber-400/10 border border-amber-400/20">
                                <span class="relative inline-flex h-1.5 w-1.5">
                                    <span class="absolute inset-0 rounded-full bg-amber-300 ys-pulse-dot"></span>
                                    <span class="relative h-1.5 w-1.5 rounded-full bg-amber-300"></span>
                                </span>
                                LIVE
                            </span>
                        </div>
                        <h2 class="mt-2 text-[13px] font-bold uppercase tracking-[0.15em] text-white/55">{{ __("Today's Revenue") }}</h2>
                    </div>
                    <div class="h-12 w-12 rounded-2xl bg-white/10 border border-white/15 grid place-items-center backdrop-blur-sm shadow-inner">
                        <svg class="w-5 h-5 text-amber-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                </div>

                <div class="relative mt-7">
                    @if($todayRevenue !== null)
                        <div class="flex items-baseline gap-2 flex-wrap">
                            <span class="text-sm font-bold text-amber-300">{{ $currencyLabel }}</span>
                            <span class="num-display text-5xl md:text-6xl font-black leading-none">{{ number_format($todayRevenue, $currencyDecimals) }}</span>
                        </div>
                    @else
                        <div class="flex items-baseline gap-2 flex-wrap">
                            <span class="num-display text-5xl md:text-6xl font-black leading-none text-white/40">—</span>
                        </div>
                    @endif
                    <p class="mt-3 text-xs text-white/55">{{ __('Sum of delivered orders today') }}</p>
                </div>

                <div class="relative mt-6 pt-5 border-t border-dashed border-white/15 grid grid-cols-3 gap-4">
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-[0.15em] text-white/50">{{ __('Total') }}</div>
                        <div class="num-display text-xl font-black mt-1">{{ number_format($stats['total'] ?? 0) }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-[0.15em] text-white/50">{{ __('Today') }}</div>
                        <div class="num-display text-xl font-black mt-1">{{ $todayCount !== null ? number_format($todayCount) : '—' }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-[0.15em] text-white/50">{{ __('Attention') }}</div>
                        <div class="num-display text-xl font-black mt-1 text-amber-300">{{ number_format($needAttention) }}</div>
                    </div>
                </div>

                <div class="relative mt-6 flex justify-end">
                    <a href="{{ route('admin.orders.export-excel', array_filter([
                            'search'      => request('search'),
                            'from'        => request('from'),
                            'to'          => request('to'),
                            'status'      => request('status'),
                            'association' => request('association'),
                            'attention'   => request('attention'),
                        ], fn ($v) => $v !== null && $v !== '')) }}"
                       class="inline-flex items-center gap-2 h-10 px-4 rounded-xl text-xs font-bold border border-white/15 bg-white/10 text-white hover:bg-white/15 transition backdrop-blur-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
                        </svg>
                        {{ __('Export Excel (.xlsx)') }}
                    </a>
                </div>
            </div>

            {{-- ═══ 4 attention tiles ═══ --}}
            @php
                $attentionTiles = [
                    [
                        'label' => __('Pending'),
                        'value' => $stats['pending'] ?? 0,
                        'stripe' => 'from-amber-400 to-amber-500',
                        'ic_bg' => 'bg-amber-100 dark:bg-amber-500/10',
                        'ic_fg' => 'text-amber-700 dark:text-amber-300',
                        'dot' => 'bg-amber-500',
                        'foot' => __('Awaits review'),
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                    ],
                    [
                        'label' => __('Processing'),
                        'value' => $stats['processing'] ?? 0,
                        'stripe' => 'from-violet-400 to-violet-500',
                        'ic_bg' => 'bg-violet-100 dark:bg-violet-500/10',
                        'ic_fg' => 'text-violet-700 dark:text-violet-300',
                        'dot' => 'bg-violet-500',
                        'foot' => __('In workshop'),
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
                    ],
                    [
                        'label' => __('Shipped'),
                        'value' => $stats['shipped'] ?? 0,
                        'stripe' => 'from-sky-400 to-sky-500',
                        'ic_bg' => 'bg-sky-100 dark:bg-sky-500/10',
                        'ic_fg' => 'text-sky-700 dark:text-sky-300',
                        'dot' => 'bg-sky-500',
                        'foot' => __('On the road'),
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>',
                    ],
                    [
                        'label' => __('Unpaid'),
                        'value' => $unpaidCount,
                        'stripe' => 'from-rose-400 to-rose-500',
                        'ic_bg' => 'bg-rose-100 dark:bg-rose-500/10',
                        'ic_fg' => 'text-rose-700 dark:text-rose-300',
                        'dot' => 'bg-rose-500',
                        'foot' => __('Action needed'),
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>',
                        'foot_class' => 'text-rose-600 dark:text-rose-300 font-semibold',
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
                    <div class="num-display text-3xl font-black text-slate-900 dark:text-white mt-4">
                        {{ $t['value'] === null ? '—' : number_format((int) $t['value']) }}
                    </div>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1.5 {{ $t['foot_class'] ?? '' }}">
                        <span class="inline-block w-1.5 h-1.5 rounded-full {{ $t['dot'] }}"></span> {{ $t['foot'] }}
                    </div>
                </div>
            @endforeach

            {{-- ═══ Delivered + Cancelled (bottom 2 tiles) ═══ --}}
            @php
                $closingTiles = [
                    [
                        'label' => __('Delivered'),
                        'value' => $stats['delivered'] ?? 0,
                        'stripe' => 'from-emerald-400 to-emerald-500',
                        'ic_bg' => 'bg-emerald-100 dark:bg-emerald-500/10',
                        'ic_fg' => 'text-emerald-700 dark:text-emerald-300',
                        'dot' => 'bg-emerald-500',
                        'foot' => __('Completed orders'),
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>',
                    ],
                    [
                        'label' => __('Cancelled'),
                        'value' => $stats['cancelled'] ?? 0,
                        'stripe' => 'from-slate-400 to-slate-500',
                        'ic_bg' => 'bg-slate-100 dark:bg-slate-700/40',
                        'ic_fg' => 'text-slate-600 dark:text-slate-300',
                        'dot' => 'bg-slate-400',
                        'foot' => __('Cancelled orders'),
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>',
                    ],
                ];
            @endphp

            @foreach($closingTiles as $t)
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

        {{-- ─────────────── Attention quick-chips ─────────────── --}}
        @php
            $currentAttention = $attention ?? '';
            $attentionChips = [
                ''                       => __('All'),
                'today_pending'          => __('Today pending orders'),
                'needs_shipping'         => __('Needs shipping'),
                'cancellation_requests'  => __('Cancellation requests'),
                'open_returns'           => __('Return requests'),
            ];
        @endphp
        <div class="flex flex-wrap gap-2 mb-4">
            @foreach($attentionChips as $value => $label)
                @php $isActive = $currentAttention === $value; @endphp
                <a href="{{ request()->fullUrlWithQuery(['attention' => $value === '' ? null : $value, 'page' => null]) }}"
                   class="inline-flex items-center gap-2 px-3.5 py-2 rounded-full text-[11.5px] font-bold transition border
                          {{ $isActive
                              ? 'bg-[#04042a] text-amber-300 border-[#04042a] dark:bg-amber-400 dark:text-[#04042a] dark:border-amber-400'
                              : 'bg-white text-slate-600 border-slate-200 hover:border-amber-300 hover:bg-amber-50 hover:text-slate-900 dark:bg-slate-900 dark:text-slate-300 dark:border-slate-700 dark:hover:bg-slate-800' }}">
                    @if($isActive)
                        <span class="relative inline-flex h-1.5 w-1.5">
                            <span class="absolute inset-0 rounded-full bg-amber-300 ys-pulse-dot opacity-70"></span>
                            <span class="relative h-1.5 w-1.5 rounded-full bg-amber-300"></span>
                        </span>
                    @endif
                    {{ $label }}
                </a>
            @endforeach
        </div>

        {{-- ═════════════════════════════════════════════════════════════ --}}
        {{-- INLINE FILTER ROW                                              --}}
        {{-- ═════════════════════════════════════════════════════════════ --}}
        <form method="GET" action="{{ route('admin.orders.index') }}"
              class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 rounded-2xl p-4 bento-shadow mb-4
                     grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
            @if($currentAttention !== '')
                <input type="hidden" name="attention" value="{{ $currentAttention }}">
            @endif

            <div class="lg:col-span-4">
                <label class="block text-[10.5px] font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-1.5" for="filter-search">{{ __('Search') }}</label>
                <div class="relative">
                    <span class="absolute inset-y-0 start-0 flex items-center ps-3 text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 100-15 7.5 7.5 0 000 15z"/>
                        </svg>
                    </span>
                    <input id="filter-search" type="text" name="search" value="{{ request('search') }}"
                           placeholder="{{ __('Search order #, city, phone, user...') }}"
                           class="w-full h-11 ps-10 pe-3 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-900 placeholder:text-slate-400
                                  focus:outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-400/30 focus:bg-white
                                  dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:bg-slate-900">
                </div>
            </div>

            <div class="lg:col-span-2">
                <label class="block text-[10.5px] font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-1.5" for="filter-status">{{ __('Status') }}</label>
                <select id="filter-status" name="status"
                        class="w-full h-11 px-3 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-900
                               focus:outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-400/30 focus:bg-white
                               dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100 dark:focus:bg-slate-900">
                    <option value="">{{ __('All Statuses') }}</option>
                    @foreach($statusOptions as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>
                            {{ \App\Models\Order::statusMeta((string) $status)['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-2">
                <label class="block text-[10.5px] font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-1.5" for="filter-assoc">{{ __('User Type') }}</label>
                <select id="filter-assoc" name="association"
                        class="w-full h-11 px-3 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-900
                               focus:outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-400/30 focus:bg-white
                               dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100 dark:focus:bg-slate-900">
                    <option value="">{{ __('All Users') }}</option>
                    <option value="user" @selected(($association ?? '') === 'user')>{{ __('Retail Users') }}</option>
                    <option value="dealer" @selected(($association ?? '') === 'dealer')>{{ __('Dealers') }}</option>
                </select>
            </div>

            <div class="lg:col-span-2">
                <label class="block text-[10.5px] font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-1.5" for="filter-from">{{ __('From') }}</label>
                <input id="filter-from" type="date" name="from" value="{{ request('from') }}"
                       class="w-full h-11 px-3 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-900
                              focus:outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-400/30 focus:bg-white
                              dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100 dark:focus:bg-slate-900"
                       style="color-scheme: light dark;">
            </div>

            <div class="lg:col-span-2">
                <label class="block text-[10.5px] font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-1.5" for="filter-to">{{ __('To') }}</label>
                <input id="filter-to" type="date" name="to" value="{{ request('to') }}"
                       class="w-full h-11 px-3 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-900
                              focus:outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-400/30 focus:bg-white
                              dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100 dark:focus:bg-slate-900"
                       style="color-scheme: light dark;">
            </div>

            <div class="lg:col-span-12 flex flex-wrap items-center justify-end gap-2 pt-2 border-t border-dashed border-slate-200 dark:border-slate-800 mt-1">
                @if($hasActiveFilters)
                    <a href="{{ route('admin.orders.index', $currentAttention !== '' ? ['attention' => $currentAttention] : []) }}"
                       class="inline-flex items-center gap-2 h-10 px-4 rounded-xl text-xs font-bold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700 dark:hover:bg-slate-700 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        {{ __('Clear') }}
                    </a>
                @endif
                <button type="submit"
                        class="inline-flex items-center gap-2 h-10 px-5 rounded-xl text-xs font-bold text-[#04042a] border border-amber-500/20 transition shadow-md shadow-amber-500/30 hover:brightness-105"
                        style="background: linear-gradient(180deg, #fbbf24, #f59e0b);">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    {{ __('Apply Filters') }}
                </button>
            </div>
        </form>

        {{-- ═════════════════════════════════════════════════════════════ --}}
        {{-- ORDERS LIST CARD                                                --}}
        {{-- ═════════════════════════════════════════════════════════════ --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 rounded-2xl overflow-hidden bento-shadow"
             x-data="{
                selected: [],
                allIds: @js($orders->pluck('id')->all()),
                toggleAll(e) { this.selected = e.target.checked ? [...this.allIds] : []; },
                allSelected() { return this.allIds.length > 0 && this.selected.length === this.allIds.length; },
             }">

            {{-- List header --}}
            <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-slate-200/70 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="h-9 w-9 rounded-xl bg-[#04042a] text-amber-300 grid place-items-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Recent Orders') }}</div>
                        <div class="text-[11px] font-mono text-slate-500 dark:text-slate-400 mt-0.5">
                            {{ __(':from–:to of :total orders', [
                                'from' => $orders->firstItem() ?? 0,
                                'to' => $orders->lastItem() ?? 0,
                                'total' => $orders->total(),
                            ]) }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bulk action bar --}}
            <div x-show="selected.length > 0" x-cloak x-transition.opacity
                 class="flex flex-wrap items-center gap-3 px-5 py-3 border-b border-amber-200/70 bg-amber-50/80 dark:border-amber-500/30 dark:bg-amber-500/10">
                <span class="inline-flex items-center px-3 h-8 rounded-full bg-[#04042a] text-amber-300 text-xs font-bold border border-[#04042a] dark:bg-amber-400 dark:text-[#04042a] dark:border-amber-400">
                    <span x-text="selected.length"></span>&nbsp;{{ __('selected') }}
                </span>
                <form method="POST" action="{{ route('admin.orders.bulk-status') }}"
                      class="flex flex-wrap items-end gap-2 ms-auto"
                      data-loading-form
                      data-loading-button-text="Processing..."
                      @submit="if (!confirm('{{ __('Apply this status change to the selected orders?') }}')) $event.preventDefault()">
                    @csrf
                    <template x-for="id in selected" :key="id">
                        <input type="hidden" name="order_ids[]" :value="id">
                    </template>
                    <div class="grid gap-1 min-w-[220px]">
                        <label for="bulk-status-select" class="text-[10px] font-bold uppercase tracking-widest text-amber-800 dark:text-amber-300">{{ __('Bulk status') }}</label>
                        <select id="bulk-status-select" name="status" required
                                class="h-10 px-3 rounded-lg border border-amber-300 bg-white text-sm font-semibold text-slate-900 dark:bg-slate-800 dark:border-amber-500/30 dark:text-slate-100">
                            <option value="">{{ __('Choose status') }}</option>
                            @foreach($statusOptions as $status)
                                <option value="{{ $status }}">{{ \App\Models\Order::statusMeta((string) $status)['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit"
                            class="inline-flex items-center gap-2 h-10 px-4 rounded-lg text-xs font-bold text-[#04042a] border border-amber-500/20 shadow-md shadow-amber-500/30"
                            style="background: linear-gradient(180deg, #fbbf24, #f59e0b);">
                        {{ __('Apply') }}
                    </button>
                    <button type="button" @click="selected = []"
                            class="inline-flex items-center gap-2 h-10 px-4 rounded-lg text-xs font-bold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700 dark:hover:bg-slate-700">
                        {{ __('Clear') }}
                    </button>
                </form>
            </div>

            {{-- Table (full-width, no horizontal scroll) --}}
            <div>
                <table class="w-full table-fixed border-collapse text-sm">
                    <colgroup>
                        <col style="width: 36px">
                        <col style="width: 17%">
                        <col style="width: 22%">
                        <col style="width: 10%">
                        <col style="width: 13%">
                        <col style="width: 11%">
                        <col class="hidden md:table-column" style="width: 11%">
                        <col style="width: 152px">
                    </colgroup>
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200/70 dark:border-slate-800">
                            <th class="text-start px-3 py-3">
                                <input type="checkbox"
                                       class="w-4 h-4 rounded accent-amber-500"
                                       @change="toggleAll($event)"
                                       :checked="allSelected()"
                                       aria-label="{{ __('Select all') }}">
                            </th>
                            <th class="text-start px-3 py-3 text-[10px] font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400">{{ __('Order') }}</th>
                            <th class="text-start px-3 py-3 text-[10px] font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400">{{ __('User / Dealer') }}</th>
                            <th class="text-end px-3 py-3 text-[10px] font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400 whitespace-nowrap">{{ __('Total') }}</th>
                            <th class="text-start px-3 py-3 text-[10px] font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400 whitespace-nowrap">{{ __('Payment') }}</th>
                            <th class="text-start px-3 py-3 text-[10px] font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400 whitespace-nowrap">{{ __('Status') }}</th>
                            <th class="text-start px-3 py-3 text-[10px] font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400 hidden md:table-cell whitespace-nowrap">{{ __('Date') }}</th>
                            <th class="text-end px-3 py-3 text-[10px] font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400 whitespace-nowrap">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            @php
                                $isDealer = $order->user && $order->user->role === \App\Models\User::ROLE_DEALER;
                                $statusMeta = \App\Models\Order::statusMeta((string) $order->status);
                                $paymentMeta = \App\Models\Order::paymentStatusMeta((string) $order->payment_status);
                                $allowedTransitions = $transitionOptions[$order->id] ?? [$order->status];
                                $canArchive = auth()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN;
                            @endphp
                            <tr :class="selected.includes({{ $order->id }})
                                            ? 'bg-amber-50/70 dark:bg-amber-500/5'
                                            : 'hover:bg-amber-50/40 dark:hover:bg-slate-800/40'"
                                class="border-b border-slate-100 dark:border-slate-800/60 transition">
                                <td class="px-4 py-4 align-middle">
                                    <input type="checkbox"
                                           value="{{ $order->id }}"
                                           x-model.number="selected"
                                           class="w-4 h-4 rounded accent-amber-500"
                                           aria-label="{{ __('Select order #:order', ['order' => $order->order_number]) }}">
                                </td>
                                <td class="px-3 py-4 align-middle">
                                    <div class="font-mono font-bold text-slate-900 dark:text-white text-[11.5px] leading-tight break-all">{{ $order->order_number }}</div>
                                    <div class="font-mono text-[10px] text-slate-400 dark:text-slate-500 mt-1">#{{ $order->id }} · {{ $order->items_count }} {{ __('items') }}</div>
                                    @if($order->cancellation_requested_at && $order->status !== \App\Models\Order::STATUS_CANCELLED)
                                        <div class="inline-flex items-center gap-1 mt-2 px-2 py-0.5 rounded text-[9.5px] font-bold uppercase tracking-wide bg-rose-100 text-rose-700 border border-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:border-rose-500/30">
                                            <span aria-hidden="true">!</span>{{ __('Cancellation Requested') }}
                                        </div>
                                    @endif
                                    @if(($order->open_returns_count ?? 0) > 0)
                                        <div class="inline-flex items-center gap-1 mt-2 px-2 py-0.5 rounded text-[9.5px] font-bold uppercase tracking-wide bg-amber-100 text-amber-700 border border-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:border-amber-500/30">
                                            <span aria-hidden="true">R</span>{{ __('Return requests') }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-3 py-4 align-middle">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <div class="h-9 w-9 rounded-xl grid place-items-center text-amber-300 font-black text-[12px] shrink-0"
                                             style="background: linear-gradient(135deg, #04042a, #0a0d3f);">
                                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($order->user?->name ?? __('G'), 0, 2)) }}
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="font-semibold text-slate-900 dark:text-white text-[12.5px] truncate">{{ $order->user?->name ?? __('Guest customer') }}</div>
                                            <div class="text-[10.5px] text-slate-500 dark:text-slate-400 truncate">{{ $order->user?->email ?? '-' }}</div>
                                            @if($order->user)
                                                @if($isDealer)
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wide mt-1 bg-violet-100 text-violet-700 border border-violet-200 dark:bg-violet-500/10 dark:text-violet-300 dark:border-violet-500/30">{{ __('Dealer') }}</span>
                                                @else
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wide mt-1 bg-sky-100 text-sky-700 border border-sky-200 dark:bg-sky-500/10 dark:text-sky-300 dark:border-sky-500/30">{{ __('User') }}</span>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-4 align-middle text-end whitespace-nowrap">
                                    <span class="font-bold text-slate-900 dark:text-white tabular-nums text-[12.5px]">{{ number_format((float) $order->total_amount, $currencyDecimals) }}</span>
                                    <span class="text-[9.5px] text-slate-400 dark:text-slate-500 ms-1">{{ $currencyLabel }}</span>
                                </td>
                                <td class="px-3 py-4 align-middle">
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-[10px] font-bold leading-none border whitespace-nowrap {{ $pillClass($order->payment_status) }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $dotColor($order->payment_status) }} shrink-0"></span>
                                        <span class="truncate">{{ $paymentMeta['label'] }}</span>
                                    </span>
                                    @if($order->payment_method)
                                        <div class="text-[9px] font-mono text-slate-400 dark:text-slate-500 mt-1 truncate">{{ $order->payment_method }}</div>
                                    @endif
                                </td>
                                <td class="px-3 py-4 align-middle">
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-[10px] font-bold leading-none border whitespace-nowrap {{ $pillClass($order->status) }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $dotColor($order->status) }} shrink-0"></span>
                                        <span class="truncate">{{ $statusMeta['label'] }}</span>
                                    </span>
                                </td>
                                <td class="px-3 py-4 align-middle hidden md:table-cell whitespace-nowrap">
                                    <div class="text-[11.5px] text-slate-700 dark:text-slate-200 tabular-nums">{{ $order->created_at?->format('d M') }}</div>
                                    <div class="text-[10px] text-slate-400 dark:text-slate-500 tabular-nums">{{ $order->created_at?->format('H:i') }}</div>
                                </td>
                                <td class="px-3 py-4 align-middle text-end whitespace-nowrap">
                                    <div class="flex items-center gap-1 justify-end rtl:justify-start flex-nowrap">
                                        {{-- View --}}
                                        <a href="{{ route('admin.orders.show', $order) }}"
                                           title="{{ __('View') }}" aria-label="{{ __('View order') }}"
                                           class="w-9 h-9 shrink-0 inline-flex items-center justify-center rounded-lg bg-slate-50 border border-slate-200 text-slate-600 hover:border-amber-300 hover:bg-amber-50 hover:text-amber-700 transition dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:border-amber-500/40 dark:hover:text-amber-300">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>

                                        {{-- Invoice (dropdown) --}}
                                        <div x-data="{
                                                open: false, x: 0, y: 0,
                                                toggle(ev) {
                                                    if (this.open) { this.open = false; return; }
                                                    const r = ev.currentTarget.getBoundingClientRect();
                                                    const menuW = 218;
                                                    const menuH = 174;
                                                    let left = r.right - menuW;
                                                    if (document.documentElement.dir === 'rtl') { left = r.left; }
                                                    if (left < 8) left = 8;
                                                    if (left + menuW > window.innerWidth - 8) left = window.innerWidth - menuW - 8;
                                                    let top = r.bottom + 6;
                                                    if (top + menuH > window.innerHeight - 8) top = r.top - menuH - 6;
                                                    this.x = left; this.y = top;
                                                    this.open = true;
                                                }
                                             }"
                                             @keydown.escape.window="open = false"
                                             @scroll.window="open = false"
                                             @resize.window="open = false">
                                            <button type="button" @click.stop="toggle($event)"
                                                    title="{{ __('Invoice') }}" aria-label="{{ __('Choose invoice language') }}"
                                                    class="w-9 h-9 shrink-0 inline-flex items-center justify-center rounded-lg bg-slate-50 border border-slate-200 text-slate-600 hover:border-amber-300 hover:bg-amber-50 hover:text-amber-700 transition dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:border-amber-500/40 dark:hover:text-amber-300">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                            </button>
                                            <template x-teleport="body">
                                                <div class="op-menu op-invoice-menu"
                                                     x-show="open" x-cloak
                                                     x-transition.opacity.duration.120ms
                                                     :style="`top:${y}px; left:${x}px;`"
                                                     @click.outside="open = false">
                                                    <div class="head">{{ __('Invoice language') }}</div>
                                                    <a class="invoice-lang" href="{{ route('admin.orders.invoice', ['order' => $order, 'lang' => 'en']) }}">
                                                        <span>{{ __('English') }}</span>
                                                        <span class="invoice-code">EN</span>
                                                    </a>
                                                    <a class="invoice-lang" href="{{ route('admin.orders.invoice', ['order' => $order, 'lang' => 'ar']) }}">
                                                        <span>{{ __('Arabic') }}</span>
                                                        <span class="invoice-code">AR</span>
                                                    </a>
                                                    <a class="invoice-lang" href="{{ route('admin.orders.invoice', ['order' => $order, 'lang' => 'ku']) }}">
                                                        <span>{{ __('Kurdish') }}</span>
                                                        <span class="invoice-code">KU</span>
                                                    </a>
                                                </div>
                                            </template>
                                        </div>

                                        {{-- More (status update + archive) --}}
                                        <div x-data="{
                                                open: false, x: 0, y: 0,
                                                toggle(ev) {
                                                    if (this.open) { this.open = false; return; }
                                                    const r = ev.currentTarget.getBoundingClientRect();
                                                    const menuW = 270;
                                                    const menuH = {{ $canArchive ? 220 : 160 }};
                                                    let left = r.right - menuW;
                                                    if (document.documentElement.dir === 'rtl') { left = r.left; }
                                                    if (left < 8) left = 8;
                                                    if (left + menuW > window.innerWidth - 8) left = window.innerWidth - menuW - 8;
                                                    let top = r.bottom + 6;
                                                    if (top + menuH > window.innerHeight - 8) top = r.top - menuH - 6;
                                                    this.x = left; this.y = top;
                                                    this.open = true;
                                                }
                                             }"
                                             @keydown.escape.window="open = false"
                                             @scroll.window="open = false"
                                             @resize.window="open = false">
                                            <button type="button" @click.stop="toggle($event)"
                                                    title="{{ __('More') }}" aria-label="{{ __('More actions') }}"
                                                    class="w-9 h-9 shrink-0 inline-flex items-center justify-center rounded-lg bg-slate-50 border border-slate-200 text-slate-600 hover:border-amber-300 hover:bg-amber-50 hover:text-amber-700 transition dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:border-amber-500/40 dark:hover:text-amber-300">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <circle cx="5" cy="12" r="1.2"/><circle cx="12" cy="12" r="1.2"/><circle cx="19" cy="12" r="1.2"/>
                                                </svg>
                                            </button>
                                            <template x-teleport="body">
                                                <div class="op-menu"
                                                     x-show="open" x-cloak
                                                     x-transition.opacity.duration.120ms
                                                     :style="`top:${y}px; left:${x}px;`"
                                                     @click.outside="open = false">
                                                    <div class="head">{{ __('Update Status') }}</div>
                                                    <form method="POST" action="{{ route('admin.orders.update-status', $order) }}" data-loading-form data-loading-button-text="Saving...">
                                                        @csrf
                                                        @method('PATCH')
                                                        <select name="status">
                                                            @foreach($statusOptions as $status)
                                                                <option value="{{ $status }}"
                                                                        @selected($order->status === $status)
                                                                        @disabled(!in_array($status, $allowedTransitions, true))>
                                                                    {{ \App\Models\Order::statusMeta((string) $status)['label'] }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <button type="submit">{{ __('Save') }}</button>
                                                    </form>
                                                    @if($canArchive)
                                                        <hr>
                                                        <form method="POST" action="{{ route('admin.orders.destroy', $order) }}"
                                                              data-danger-confirm
                                                              data-danger-title="{{ __('Archive Order') }}"
                                                              data-danger-description="{{ __('The order will be hidden from the active order list but kept for financial history and audit review.') }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="danger">{{ __('Archive Order') }}</button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
                                    <div class="py-14 px-4 text-center">
                                        <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-slate-50 border border-slate-200 grid place-items-center text-slate-400 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-500">
                                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0l-2.293 5.16a1 1 0 01-.914.59H7.207a1 1 0 01-.914-.59L4 13m16 0h-5.114a1 1 0 00-.894.553l-.829 1.658a1 1 0 01-.894.553h-2.538a1 1 0 01-.894-.553l-.829-1.658A1 1 0 008.114 13H4"/>
                                            </svg>
                                        </div>
                                        <div class="text-base font-bold text-slate-900 dark:text-white">{{ __('No orders found') }}</div>
                                        <div class="text-[13px] text-slate-500 dark:text-slate-400 mt-1.5">{{ __('Try adjusting your filters or clearing them to see all orders.') }}</div>
                                        @if($hasActiveFilters)
                                            <a href="{{ route('admin.orders.index', $currentAttention !== '' ? ['attention' => $currentAttention] : []) }}"
                                               class="inline-flex items-center gap-2 h-10 px-4 mt-4 rounded-xl text-xs font-bold text-amber-300 bg-[#04042a] hover:bg-[#07073a] transition">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                                {{ __('Clear') }}
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($orders->hasPages())
                <div class="flex flex-wrap justify-between items-center gap-3 px-5 py-3.5 border-t border-slate-200/70 dark:border-slate-800 bg-slate-50/40 dark:bg-slate-900/40">
                    <span class="text-[12px] text-slate-500 dark:text-slate-400">
                        {{ __('Showing :from-:to of :total orders', [
                            'from' => $orders->firstItem() ?? 0,
                            'to' => $orders->lastItem() ?? 0,
                            'total' => $orders->total(),
                        ]) }}
                    </span>
                    <div class="orders-pagination">
                        {{ $orders->links() }}
                    </div>
                </div>
            @endif
        </div>

    </div>
    </div>
    </div>

    {{-- Pagination links color match (the default Laravel pagination view rendered above) --}}
    <style>
        .orders-pagination nav { display: flex; }
        .orders-pagination ul,
        .orders-pagination .pagination {
            display: flex; flex-wrap: wrap; gap: 4px; list-style: none; margin: 0; padding: 0;
        }
        .orders-pagination a,
        .orders-pagination span {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 34px; height: 34px; padding: 0 10px;
            border-radius: 9px; background: #fff;
            border: 1px solid #e2e8f0; color: #475569;
            font-size: 12px; font-weight: 700; text-decoration: none;
            transition: all .15s ease;
        }
        .orders-pagination a:hover { color: #0f172a; border-color: #cbd5e1; background: #f8fafc; }
        .orders-pagination .active span,
        .orders-pagination span[aria-current="page"] {
            background: #04042a; color: #fcd34d; border-color: #04042a;
        }
        .orders-pagination .disabled span,
        .orders-pagination span[aria-disabled="true"] { opacity: 0.45; cursor: not-allowed; }
        .dark .orders-pagination a,
        .dark .orders-pagination span {
            background: #0f172a; border-color: #334155; color: #cbd5e1;
        }
        .dark .orders-pagination a:hover { background: #1e293b; color: #fff; border-color: #475569; }
        .dark .orders-pagination .active span,
        .dark .orders-pagination span[aria-current="page"] {
            background: #fbbf24; color: #04042a; border-color: #fbbf24;
        }
    </style>
</x-app-layout>
