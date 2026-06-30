<x-app-layout>

@php
    // === Sparkline helper for SVG paths derived from $monthCounts ===
    $sparkPoints = collect($monthCounts ?? [])->map(fn ($v) => (int) $v)->values()->all();
    $sparkMax = max($sparkPoints ?: [1]);
    $sparkMax = $sparkMax > 0 ? $sparkMax : 1;
    $svgW = 600; $svgH = 90;
    $sparkCoords = [];
    if (count($sparkPoints) > 1) {
        $step = $svgW / (count($sparkPoints) - 1);
        foreach ($sparkPoints as $i => $v) {
            $x = round($i * $step, 2);
            $y = round($svgH - max(4, ($v / $sparkMax) * ($svgH - 10)), 2);
            $sparkCoords[] = "$x,$y";
        }
    }
    $sparkLine = $sparkCoords ? 'M ' . implode(' L ', $sparkCoords) : '';
    $sparkArea = $sparkCoords ? "M 0,$svgH L " . implode(' L ', $sparkCoords) . " L $svgW,$svgH Z" : '';

    // Mini sparkline (smaller, for stat cards) — same data, smaller dimensions
    $miniW = 200; $miniH = 36;
    $miniCoords = [];
    if (count($sparkPoints) > 1) {
        $mstep = $miniW / (count($sparkPoints) - 1);
        foreach ($sparkPoints as $i => $v) {
            $x = round($i * $mstep, 2);
            $y = round($miniH - max(2, ($v / $sparkMax) * ($miniH - 4)), 2);
            $miniCoords[] = "$x,$y";
        }
    }
    $miniLine = $miniCoords ? 'M ' . implode(' L ', $miniCoords) : '';
    $miniArea = $miniCoords ? "M 0,$miniH L " . implode(' L ', $miniCoords) . " L $miniW,$miniH Z" : '';

    // Low stock gauge severity (0..100)
    $lowStockPct = (int) min(100, max(8, ($lowStockCount > 0 && $totalProducts > 0)
        ? round(($lowStockCount / max($totalProducts, 1)) * 100 * 6)
        : 12));
    $gaugeTone = $lowStockPct >= 60 ? 'rose' : ($lowStockPct >= 30 ? 'amber' : 'emerald');
    $gaugeColor = $lowStockPct >= 60 ? '#f43f5e' : ($lowStockPct >= 30 ? '#f97316' : '#10b981');

    // Today vs yesterday progress (capped) for Today's Sales card
    $todayPct = (int) min(100, max(3, abs((int) $salesChangePercent)));

    // Total Revenue: yearly progress visualization (% of max month vs sum)
    $sparkSum = array_sum($sparkPoints) ?: 1;
@endphp

<style>
    .bento-stripes {
        background-image: repeating-linear-gradient(135deg, rgba(255,255,255,0.06) 0px, rgba(255,255,255,0.06) 1px, transparent 1px, transparent 14px);
    }
    .bento-stripes-soft {
        background-image: repeating-linear-gradient(135deg, rgba(7,7,64,0.04) 0px, rgba(7,7,64,0.04) 1px, transparent 1px, transparent 14px);
    }
    .bento-shadow { box-shadow: 0 1px 2px rgba(7,7,64,0.04), 0 4px 16px rgba(7,7,64,0.06); }
    .bento-shadow:hover { box-shadow: 0 2px 6px rgba(7,7,64,0.08), 0 16px 36px rgba(7,7,64,0.10); }
    .bento-shadow-lg { box-shadow: 0 10px 30px rgba(7,7,64,0.18), 0 30px 60px rgba(7,7,64,0.20); }

    /* Industrial corner brackets — pure CSS */
    .corner-brackets::before,
    .corner-brackets::after {
        content: ""; position: absolute; width: 14px; height: 14px;
        border-color: rgba(255,255,255,0.35); border-style: solid; border-width: 0;
        pointer-events: none;
    }
    .corner-brackets::before { top: 14px; left: 14px; border-top-width: 1.5px; border-left-width: 1.5px; }
    .corner-brackets::after { bottom: 14px; right: 14px; border-bottom-width: 1.5px; border-right-width: 1.5px; }

    .corner-brackets-dark::before,
    .corner-brackets-dark::after {
        content: ""; position: absolute; width: 12px; height: 12px;
        border-color: rgba(7,7,64,0.22); border-style: solid; border-width: 0;
        pointer-events: none;
    }
    .corner-brackets-dark::before { top: 12px; left: 12px; border-top-width: 1.5px; border-left-width: 1.5px; }
    .corner-brackets-dark::after { bottom: 12px; right: 12px; border-bottom-width: 1.5px; border-right-width: 1.5px; }

    .ridge-top::before {
        content: ""; position: absolute; top: 0; left: 0; right: 0; height: 1px;
        background: linear-gradient(90deg, transparent, rgba(7,7,64,0.18), transparent);
    }

    .num-display { font-feature-settings: "tnum" 1, "lnum" 1; letter-spacing: -0.025em; }

    .pulse-dot { position: relative; }
    .pulse-dot::after {
        content: ""; position: absolute; inset: -4px; border-radius: 999px;
        border: 2px solid currentColor; opacity: 0.35; animation: pulse-ring 1.6s ease-out infinite;
    }
    @keyframes pulse-ring { 0% { transform: scale(0.6); opacity: 0.6; } 100% { transform: scale(1.5); opacity: 0; } }

    /* Tick marks for industrial feel on the gauge */
    .gauge-ticks {
        background-image:
            conic-gradient(from -90deg, transparent 0deg 4deg, rgba(7,7,64,0.18) 4deg 5deg, transparent 5deg 18deg);
        mask: radial-gradient(circle, transparent 38%, black 41%, black 50%, transparent 52%);
        -webkit-mask: radial-gradient(circle, transparent 38%, black 41%, black 50%, transparent 52%);
    }
</style>

<div class="bg-[#f3f4f7] dark:bg-slate-950 min-h-screen">
<div class="py-6">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    {{-- ================= TIME RANGE CONTROL ================= --}}
    <div class="mb-8 rounded-2xl bg-white border border-slate-200/70 dark:bg-slate-900 dark:border-slate-800 px-4 py-3 bento-shadow flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <div class="h-9 w-9 rounded-xl bg-primary/10 text-primary dark:bg-primary/20 grid place-items-center">
                <i class="far fa-calendar-days text-sm"></i>
            </div>
            <div>
                <p class="text-[10px] uppercase tracking-widest text-slate-400 font-bold leading-none">{{ __('Time Range') }}</p>
                <p class="text-sm font-bold text-primary dark:text-slate-100 leading-tight mt-0.5">{{ __('Analytics Period') }} <span class="font-mono text-[11px] text-slate-400">· {{ __('Last :n days', ['n' => $analyticsDays]) }}</span></p>
            </div>
        </div>

        <div class="flex items-center gap-1.5">
            <span class="hidden sm:inline text-[10px] uppercase tracking-widest text-slate-400 font-bold mr-1">{{ __('Preset') }}</span>
            @foreach($allowedAnalyticsDays as $dayOption)
                @php $isActive = (int) $analyticsDays === (int) $dayOption; @endphp
                <a href="{{ route('admin.dashboard', ['analytics_days' => $dayOption]) }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold transition
                          {{ $isActive
                              ? 'bg-gradient-to-r from-primary to-indigo-700 text-white shadow-md'
                              : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-200 dark:border-slate-700' }}">
                    @if($isActive)
                        <span class="relative inline-flex h-1.5 w-1.5">
                            <span class="absolute inset-0 rounded-full bg-emerald-300 animate-ping opacity-60"></span>
                            <span class="relative h-1.5 w-1.5 rounded-full bg-emerald-300"></span>
                        </span>
                    @endif
                    {{ $dayOption }}<span class="opacity-70">D</span>
                </a>
            @endforeach
            @if(request()->has('analytics_days'))
                <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-1.5 ml-1 px-3 py-2 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-700 hover:bg-slate-100 transition dark:text-slate-400 dark:hover:bg-slate-800">
                    <i class="fas fa-rotate-left text-[10px]"></i> {{ __('Reset') }}
                </a>
            @endif
        </div>
    </div>

    {{-- ================= HERO BENTO GRID ================= --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">

        {{-- ============ TOTAL REVENUE — HERO 2x2 ============ --}}
        <div id="admin-revenue-section" class="relative sm:col-span-2 lg:col-span-2 lg:row-span-2 rounded-3xl bg-primary text-white p-7 overflow-hidden bento-shadow-lg corner-brackets scroll-mt-24" style="background: linear-gradient(135deg, #04042a 0%, #070740 50%, #0a0d3f 100%);">
            <div class="absolute inset-0 bento-stripes pointer-events-none"></div>
            <div class="absolute -top-32 -right-32 h-96 w-96 rounded-full bg-indigo-500/25 blur-[100px]"></div>
            <div class="absolute -bottom-32 -left-20 h-80 w-80 rounded-full bg-cyan-500/15 blur-[100px]"></div>
            <div class="absolute top-0 left-0 right-0 h-[2px]" style="background: linear-gradient(90deg, #22d3ee, #818cf8, #e879f9);"></div>

            <div class="relative flex items-start justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] uppercase tracking-widest text-white/60 font-bold">{{ __('Total Revenue') }}</span>
                        <span class="text-[10px] font-mono text-cyan-300/80 px-1.5 py-0.5 rounded bg-cyan-400/10 border border-cyan-400/20">YS · LIVE</span>
                    </div>
                    <p class="mt-1.5 text-xs text-white/50">{{ __('Completed orders · all time') }}</p>
                </div>
                <div class="h-12 w-12 rounded-2xl bg-white/10 border border-white/15 grid place-items-center backdrop-blur-sm shadow-inner">
                    <i class="fas fa-coins text-white/95 text-lg"></i>
                </div>
            </div>

            <div class="relative mt-8">
                <div class="flex items-baseline gap-2 flex-wrap">
                    <span class="text-sm font-bold text-cyan-300/80">{{ $currencyLabel }}</span>
                    <span class="num-display text-5xl md:text-6xl lg:text-7xl font-black leading-none">{{ number_format($totalRevenue, $currencyDecimals) }}</span>
                </div>
                <div class="mt-5 flex items-center gap-3 text-xs flex-wrap">
                    @if($revenueGrowth > 0)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-400/15 text-emerald-300 px-3 py-1.5 font-bold border border-emerald-400/20">
                            <i class="fas fa-arrow-trend-up"></i> +{{ number_format(abs($revenueGrowth), 1) }}%
                        </span>
                    @elseif($revenueGrowth < 0)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-400/15 text-rose-300 px-3 py-1.5 font-bold border border-rose-400/20">
                            <i class="fas fa-arrow-trend-down"></i> {{ number_format(abs($revenueGrowth), 1) }}%
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 text-white/85 px-3 py-1.5 font-bold">
                            <i class="fas fa-minus"></i> 0%
                        </span>
                    @endif
                    <span class="text-white/55">{{ __('vs previous month') }}</span>
                </div>
            </div>

            {{-- SVG Sparkline (proper line + gradient area) --}}
            @if($sparkLine)
                <div class="relative mt-7">
                    <svg viewBox="0 0 {{ $svgW }} {{ $svgH }}" preserveAspectRatio="none" class="w-full h-24">
                        <defs>
                            <linearGradient id="heroSparkGrad" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#22d3ee" stop-opacity="0.55"/>
                                <stop offset="100%" stop-color="#22d3ee" stop-opacity="0"/>
                            </linearGradient>
                            <linearGradient id="heroSparkStroke" x1="0" y1="0" x2="1" y2="0">
                                <stop offset="0%" stop-color="#a5f3fc"/>
                                <stop offset="100%" stop-color="#ffffff"/>
                            </linearGradient>
                        </defs>
                        <path d="{{ $sparkArea }}" fill="url(#heroSparkGrad)" />
                        <path d="{{ $sparkLine }}" fill="none" stroke="url(#heroSparkStroke)" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>
                    </svg>
                    <div class="mt-2 flex items-center justify-between text-[10px] text-white/40 uppercase tracking-widest">
                        @foreach($monthLabels as $label)
                            <span>{{ \Illuminate\Support\Str::limit($label, 3, '') }}</span>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Footer mini-stats inside hero --}}
            <div class="relative mt-6 grid grid-cols-2 gap-3 pt-5 border-t border-white/10">
                <div>
                    <p class="text-[10px] uppercase tracking-[0.22em] text-white/40 font-bold">{{ __("Today's Sales") }}</p>
                    <p class="mt-1 text-base font-bold text-white/95">
                        <span class="text-xs text-white/55">{{ $currencyLabel }}</span> {{ number_format($todaySales, $currencyDecimals) }}
                    </p>
                </div>
                <div>
                    <p class="text-[10px] uppercase tracking-[0.22em] text-white/40 font-bold">{{ __('Pending Orders') }}</p>
                    <p class="mt-1 text-base font-bold text-white/95 flex items-center gap-2">
                        {{ number_format($pendingOrders) }}
                        @if($pendingOrders > 0)
                            <span class="pulse-dot inline-flex h-2 w-2 rounded-full bg-amber-400 text-amber-400"></span>
                        @endif
                    </p>
                </div>
            </div>
        </div>

        {{-- ============ TOTAL ORDERS ============ --}}
        <div class="relative rounded-3xl bg-white p-6 bento-shadow transition-shadow border border-slate-200/70 overflow-hidden dark:bg-slate-900 dark:border-slate-800">
            <div class="absolute top-0 left-0 bottom-0 w-1 bg-gradient-to-b from-indigo-500 to-indigo-600"></div>
            <div class="absolute -bottom-8 -right-8 h-32 w-32 rounded-full bg-indigo-500/5 blur-2xl"></div>
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[10px] uppercase tracking-[0.22em] text-slate-500 font-bold dark:text-slate-400">{{ __('Total Orders') }}</p>
                    <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">{{ __('All-time fulfilled') }}</p>
                </div>
                <div class="h-10 w-10 rounded-xl bg-indigo-50 text-indigo-600 grid place-items-center dark:bg-indigo-900/30 dark:text-indigo-300">
                    <i class="fas fa-bag-shopping"></i>
                </div>
            </div>
            <p class="mt-6 num-display text-4xl font-black text-primary dark:text-slate-100">{{ number_format($totalOrders) }}</p>
            <div class="mt-2 flex items-center gap-2 text-xs">
                @if($ordersGrowth > 0)
                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 text-emerald-700 px-2 py-0.5 font-bold border border-emerald-100">
                        <i class="fas fa-arrow-up text-[10px]"></i> {{ number_format(abs($ordersGrowth), 1) }}%
                    </span>
                @elseif($ordersGrowth < 0)
                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 text-rose-700 px-2 py-0.5 font-bold border border-rose-100">
                        <i class="fas fa-arrow-down text-[10px]"></i> {{ number_format(abs($ordersGrowth), 1) }}%
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 text-slate-600 px-2 py-0.5 font-bold">0%</span>
                @endif
                <span class="text-slate-500 dark:text-slate-400">{{ __('vs last month') }}</span>
            </div>
            @if($miniLine)
                <div class="mt-4 -mx-1">
                    <svg viewBox="0 0 {{ $miniW }} {{ $miniH }}" preserveAspectRatio="none" class="w-full h-8">
                        <defs>
                            <linearGradient id="oGrad" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#6366f1" stop-opacity="0.30"/>
                                <stop offset="100%" stop-color="#6366f1" stop-opacity="0"/>
                            </linearGradient>
                        </defs>
                        <path d="{{ $miniArea }}" fill="url(#oGrad)"/>
                        <path d="{{ $miniLine }}" fill="none" stroke="#6366f1" stroke-width="1.8" stroke-linejoin="round" stroke-linecap="round"/>
                    </svg>
                </div>
            @endif
        </div>

        {{-- ============ TOTAL PRODUCTS ============ --}}
        <div class="relative rounded-3xl bg-white p-6 bento-shadow transition-shadow border border-slate-200/70 overflow-hidden dark:bg-slate-900 dark:border-slate-800">
            <div class="absolute top-0 left-0 bottom-0 w-1 bg-gradient-to-b from-fuchsia-500 to-violet-600"></div>
            <div class="absolute -bottom-8 -right-8 h-32 w-32 rounded-full bg-violet-500/5 blur-2xl"></div>
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[10px] uppercase tracking-[0.22em] text-slate-500 font-bold dark:text-slate-400">{{ __('Total Products') }}</p>
                    <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">{{ __('Active catalog') }}</p>
                </div>
                <div class="h-10 w-10 rounded-xl bg-violet-50 text-violet-600 grid place-items-center dark:bg-violet-900/30 dark:text-violet-300">
                    <i class="fas fa-screwdriver-wrench"></i>
                </div>
            </div>
            <p class="mt-6 num-display text-4xl font-black text-primary dark:text-slate-100">{{ number_format($totalProducts) }}</p>
            <div class="mt-2 flex items-center gap-2 text-xs">
                @if($productsTrendPercent > 0)
                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 text-emerald-700 px-2 py-0.5 font-bold border border-emerald-100">
                        <i class="fas fa-arrow-up text-[10px]"></i> {{ number_format(abs($productsTrendPercent), 1) }}%
                    </span>
                @elseif($productsTrendPercent < 0)
                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 text-rose-700 px-2 py-0.5 font-bold border border-rose-100">
                        <i class="fas fa-arrow-down text-[10px]"></i> {{ number_format(abs($productsTrendPercent), 1) }}%
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 text-slate-600 px-2 py-0.5 font-bold">0%</span>
                @endif
                <span class="text-slate-500 dark:text-slate-400">{{ __('vs last month') }}</span>
            </div>
            {{-- Stock health mini bar --}}
            @php
                $healthyPct = $totalProducts > 0 ? max(0, 100 - round((($lowStockCount + $outOfStockCount) / max($totalProducts, 1)) * 100)) : 0;
            @endphp
            <div class="mt-4">
                <div class="flex items-center justify-between text-[10px] uppercase tracking-widest text-slate-400 font-bold mb-1.5">
                    <span>{{ __('Stock Health') }}</span>
                    <span class="text-violet-600 dark:text-violet-300">{{ $healthyPct }}%</span>
                </div>
                <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden dark:bg-slate-800">
                    <div class="h-full bg-gradient-to-r from-violet-500 to-fuchsia-500 rounded-full" style="width: {{ $healthyPct }}%"></div>
                </div>
            </div>
        </div>

        {{-- ============ TODAY'S SALES ============ --}}
        <div class="relative rounded-3xl bg-white p-6 bento-shadow transition-shadow border border-slate-200/70 overflow-hidden dark:bg-slate-900 dark:border-slate-800">
            <div class="absolute top-0 left-0 bottom-0 w-1 bg-gradient-to-b from-emerald-400 to-teal-600"></div>
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[10px] uppercase tracking-[0.22em] text-slate-500 font-bold dark:text-slate-400">{{ __("Today's Sales") }}</p>
                    <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">{{ __('Since 00:00') }}</p>
                </div>
                <div class="h-10 w-10 rounded-xl bg-emerald-50 text-emerald-600 grid place-items-center dark:bg-emerald-900/30 dark:text-emerald-300">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
            <p class="mt-6 num-display text-3xl font-black text-primary dark:text-slate-100">
                <span class="text-base font-bold text-slate-400 dark:text-slate-500">{{ $currencyLabel }}</span>
                {{ number_format($todaySales, $currencyDecimals) }}
            </p>
            <div class="mt-3">
                <div class="flex items-center justify-between text-xs mb-1">
                    @if($salesChangePercent > 0)
                        <span class="text-emerald-600 font-bold dark:text-emerald-400"><i class="fas fa-arrow-up text-[10px]"></i> {{ number_format(abs($salesChangePercent), 1) }}%</span>
                    @elseif($salesChangePercent < 0)
                        <span class="text-rose-600 font-bold dark:text-rose-400"><i class="fas fa-arrow-down text-[10px]"></i> {{ number_format(abs($salesChangePercent), 1) }}%</span>
                    @else
                        <span class="text-slate-500 font-bold">0%</span>
                    @endif
                    <span class="text-slate-400 text-[11px]">{{ __('vs yesterday') }}</span>
                </div>
                <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden dark:bg-slate-800">
                    <div class="h-full {{ $salesChangePercent >= 0 ? 'bg-gradient-to-r from-emerald-400 to-teal-500' : 'bg-gradient-to-r from-rose-400 to-rose-600' }} rounded-full" style="width: {{ $todayPct }}%"></div>
                </div>
            </div>
        </div>

        {{-- ============ PENDING ORDERS ============ --}}
        <div class="relative rounded-3xl bg-white p-6 bento-shadow transition-shadow border border-slate-200/70 overflow-hidden dark:bg-slate-900 dark:border-slate-800">
            <div class="absolute top-0 left-0 bottom-0 w-1 bg-gradient-to-b from-amber-400 to-orange-500"></div>
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[10px] uppercase tracking-[0.22em] text-slate-500 font-bold dark:text-slate-400">{{ __('Pending Orders') }}</p>
                    <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">{{ __('Awaiting action') }}</p>
                </div>
                <div class="h-10 w-10 rounded-xl bg-amber-50 text-amber-600 grid place-items-center dark:bg-amber-900/30 dark:text-amber-300">
                    <i class="far fa-hourglass-half"></i>
                </div>
            </div>
            <p class="mt-6 num-display text-4xl font-black text-primary dark:text-slate-100">{{ number_format($pendingOrders) }}</p>
            <div class="mt-3 flex items-center justify-between">
                <p class="text-xs text-amber-700 font-bold inline-flex items-center gap-1.5 dark:text-amber-300">
                    @if($pendingOrders > 0)
                        <span class="pulse-dot text-amber-500 inline-flex h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                    @else
                        <span class="inline-flex h-1.5 w-1.5 rounded-full bg-slate-300"></span>
                    @endif
                    {{ __('Needs attention') }}
                </p>
                @if($unpaidOrders > 0)
                    <span class="text-[10px] font-bold text-rose-600 uppercase tracking-widest">{{ $unpaidOrders }} {{ __('unpaid') }}</span>
                @endif
            </div>
        </div>

    </div>

    {{-- ================= SYSTEM HEALTH ROW ================= --}}
    <div class="grid grid-cols-2 lg:grid-cols-6 gap-4 mb-8">

        {{-- Total Users with breakdown bar --}}
        <div class="relative rounded-3xl bg-white p-5 bento-shadow border border-slate-200/70 lg:col-span-2 overflow-hidden transition-shadow dark:bg-slate-900 dark:border-slate-800">
            <div class="absolute top-0 left-0 bottom-0 w-1 bg-gradient-to-b from-cyan-400 to-blue-600"></div>
            <div class="flex items-center justify-between">
                <p class="text-[10px] uppercase tracking-[0.22em] text-slate-500 font-bold dark:text-slate-400">{{ __('Total Users') }}</p>
                <div class="h-9 w-9 rounded-xl bg-cyan-50 text-cyan-600 grid place-items-center dark:bg-cyan-900/30 dark:text-cyan-300">
                    <i class="fas fa-users"></i>
                </div>
            </div>
            <p class="mt-3 num-display text-3xl font-black text-primary dark:text-slate-100">{{ number_format($totalUsers) }}</p>
            <div class="mt-2 flex items-center gap-2 text-xs">
                @if($usersGrowth > 0)
                    <span class="text-emerald-600 font-bold dark:text-emerald-400"><i class="fas fa-arrow-up text-[10px]"></i> {{ number_format(abs($usersGrowth), 1) }}%</span>
                @elseif($usersGrowth < 0)
                    <span class="text-rose-600 font-bold dark:text-rose-400"><i class="fas fa-arrow-down text-[10px]"></i> {{ number_format(abs($usersGrowth), 1) }}%</span>
                @endif
                <span class="text-slate-500 dark:text-slate-400">{{ __('Registered accounts') }}</span>
            </div>
        </div>

        {{-- Unpaid --}}
        <div class="relative rounded-3xl bg-white p-5 bento-shadow border border-slate-200/70 lg:col-span-1 overflow-hidden transition-shadow dark:bg-slate-900 dark:border-slate-800">
            <div class="absolute top-0 left-0 bottom-0 w-1 bg-gradient-to-b from-rose-400 to-pink-600"></div>
            <div class="flex items-center justify-between">
                <p class="text-[10px] uppercase tracking-[0.22em] text-slate-500 font-bold dark:text-slate-400">{{ __('Unpaid') }}</p>
                <div class="h-9 w-9 rounded-xl bg-rose-50 text-rose-600 grid place-items-center dark:bg-rose-900/30 dark:text-rose-300">
                    <i class="far fa-credit-card"></i>
                </div>
            </div>
            <p class="mt-3 num-display text-3xl font-black text-primary dark:text-slate-100">{{ number_format($unpaidOrders) }}</p>
            <p class="mt-2 text-xs text-rose-600 font-bold dark:text-rose-400 inline-flex items-center gap-1.5">
                @if($unpaidOrders > 0)
                    <span class="pulse-dot text-rose-500 inline-flex h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                @endif
                {{ __('Payment pending') }}
            </p>
        </div>

        {{-- New Customers --}}
        <div class="relative rounded-3xl bg-white p-5 bento-shadow border border-slate-200/70 lg:col-span-1 overflow-hidden transition-shadow dark:bg-slate-900 dark:border-slate-800">
            <div class="absolute top-0 left-0 bottom-0 w-1 bg-gradient-to-b from-sky-400 to-indigo-500"></div>
            <div class="flex items-center justify-between">
                <p class="text-[10px] uppercase tracking-[0.22em] text-slate-500 font-bold dark:text-slate-400">{{ __('New') }}</p>
                <div class="h-9 w-9 rounded-xl bg-sky-50 text-sky-600 grid place-items-center dark:bg-sky-900/30 dark:text-sky-300">
                    <i class="fas fa-user-plus"></i>
                </div>
            </div>
            <p class="mt-3 num-display text-3xl font-black text-primary dark:text-slate-100">{{ number_format($newCustomers) }}</p>
            <p class="mt-2 text-xs text-sky-600 font-bold dark:text-sky-400">{{ __('This month') }}</p>
        </div>

        {{-- Low Stock with BIG gauge --}}
        <div class="relative rounded-3xl bg-white p-5 bento-shadow border border-amber-200 lg:col-span-2 overflow-hidden transition-shadow dark:bg-slate-900 dark:border-amber-900/50 corner-brackets-dark">
            <div class="absolute inset-0 bento-stripes-soft pointer-events-none"></div>
            <div class="relative flex items-center justify-between">
                <p class="text-[10px] uppercase tracking-[0.22em] font-bold inline-flex items-center gap-1.5 {{ $gaugeTone === 'rose' ? 'text-rose-700 dark:text-rose-300' : ($gaugeTone === 'amber' ? 'text-amber-700 dark:text-amber-300' : 'text-emerald-700 dark:text-emerald-300') }}">
                    <i class="fas fa-triangle-exclamation"></i> {{ __('Stock Alert') }}
                </p>
                <span class="text-[10px] uppercase tracking-widest font-bold {{ $gaugeTone === 'rose' ? 'text-rose-600' : ($gaugeTone === 'amber' ? 'text-amber-600' : 'text-emerald-600') }}">{{ __('Threshold :count', ['count' => $lowStockThreshold]) }}</span>
            </div>
            <div class="relative mt-3 flex items-center gap-5">
                <div class="relative h-24 w-24 shrink-0">
                    {{-- Outer tick ring --}}
                    <div class="absolute inset-0 rounded-full gauge-ticks"></div>
                    {{-- Gauge --}}
                    <div class="absolute inset-1 rounded-full"
                         style="background: conic-gradient({{ $gaugeColor }} 0% {{ $lowStockPct }}%, #e5e7eb {{ $lowStockPct }}% 100%);">
                        <div class="absolute inset-2 rounded-full bg-white grid place-items-center dark:bg-slate-900">
                            <div class="text-center leading-tight">
                                <p class="num-display text-2xl font-black {{ $gaugeTone === 'rose' ? 'text-rose-700 dark:text-rose-300' : ($gaugeTone === 'amber' ? 'text-amber-700 dark:text-amber-300' : 'text-emerald-700 dark:text-emerald-300') }}">{{ number_format($lowStockCount) }}</p>
                                <p class="text-[8px] uppercase tracking-widest text-slate-400 font-bold dark:text-slate-500">{{ __('SKUs') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] uppercase tracking-widest text-slate-400 font-bold dark:text-slate-500">{{ __('Inventory health') }}</p>
                    <p class="mt-1 text-sm font-bold truncate {{ $gaugeTone === 'rose' ? 'text-rose-800 dark:text-rose-300' : ($gaugeTone === 'amber' ? 'text-amber-800 dark:text-amber-300' : 'text-emerald-800 dark:text-emerald-300') }}">{{ __(':count parts below threshold', ['count' => $lowStockCount]) }}</p>
                    @if($outOfStockCount > 0)
                        <p class="mt-1 text-xs text-rose-600 font-bold dark:text-rose-400">+ {{ $outOfStockCount }} {{ __('out of stock') }}</p>
                    @endif
                    @if(Route::has('admin.products.index'))
                        <a href="{{ route('admin.products.index', ['low_stock' => 1]) }}" class="mt-2 inline-flex items-center gap-1 text-xs font-bold {{ $gaugeTone === 'rose' ? 'text-rose-700 hover:text-rose-900 dark:text-rose-300' : ($gaugeTone === 'amber' ? 'text-amber-700 hover:text-amber-900 dark:text-amber-300' : 'text-emerald-700 hover:text-emerald-900 dark:text-emerald-300') }}">
                            {{ __('View low stock') }} <i class="fas fa-arrow-right text-[10px]"></i>
                        </a>
                    @endif
                </div>
            </div>
        </div>

    </div>

    {{-- ================= SITE ANALYTICS SNAPSHOT ================= --}}
    <div class="mb-8 rounded-3xl border border-slate-200/70 bg-white p-5 bento-shadow dark:border-slate-800 dark:bg-slate-900 sm:p-6">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">{{ __('Site Analytics') }}</p>
                <h3 class="mt-1 text-xl font-black text-primary dark:text-slate-100">{{ __('Visitor activity snapshot') }}</h3>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Last :n days', ['n' => $siteAnalyticsDays]) }}</p>
            </div>
            @if(Route::has('admin.analytics.index'))
                <a href="{{ route('admin.analytics.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                    {{ __('Open analytics') }} <i class="fas fa-arrow-right text-[10px]"></i>
                </a>
            @endif
        </div>

        @if(! $siteAnalyticsHasData)
            <div class="mb-5 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-5 py-6 text-center dark:border-slate-700 dark:bg-slate-950/50">
                <div class="mx-auto grid h-11 w-11 place-items-center rounded-xl bg-white text-slate-500 shadow-sm dark:bg-slate-900 dark:text-slate-400">
                    <i class="fas fa-chart-simple"></i>
                </div>
                <p class="mt-3 text-sm font-bold text-slate-700 dark:text-slate-200">No analytics data yet. Data will appear after visitors use the website.</p>
            </div>
        @endif

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-7">
            @foreach($siteAnalyticsCards as $card)
                @php
                    $tone = $card['tone'] ?? 'slate';
                    $toneClasses = match ($tone) {
                        'indigo' => ['from-indigo-500 to-indigo-600', 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-300'],
                        'cyan' => ['from-cyan-400 to-cyan-600', 'bg-cyan-50 text-cyan-600 dark:bg-cyan-900/30 dark:text-cyan-300'],
                        'rose' => ['from-rose-400 to-rose-600', 'bg-rose-50 text-rose-600 dark:bg-rose-900/30 dark:text-rose-300'],
                        'amber' => ['from-amber-400 to-orange-500', 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'],
                        'blue' => ['from-sky-400 to-blue-600', 'bg-sky-50 text-sky-600 dark:bg-sky-900/30 dark:text-sky-300'],
                        'violet' => ['from-violet-500 to-fuchsia-600', 'bg-violet-50 text-violet-600 dark:bg-violet-900/30 dark:text-violet-300'],
                        'emerald' => ['from-emerald-400 to-teal-600', 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-300'],
                        default => ['from-slate-400 to-slate-600', 'bg-slate-50 text-slate-600 dark:bg-slate-800 dark:text-slate-300'],
                    };
                @endphp
                <div class="relative overflow-hidden rounded-2xl border border-slate-200/70 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/40">
                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b {{ $toneClasses[0] }}"></div>
                    <div class="flex items-start justify-between gap-3 pl-1">
                        <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ $card['label'] }}</p>
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg {{ $toneClasses[1] }}">
                            <i class="{{ $card['icon'] }} text-xs"></i>
                        </span>
                    </div>
                    <p class="num-display mt-4 pl-1 text-2xl font-black text-primary dark:text-slate-100">{{ number_format((int) $card['value']) }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-5 grid gap-4 lg:grid-cols-2">
            <div class="rounded-2xl border border-slate-200/70 bg-slate-50 dark:border-slate-800 dark:bg-slate-950/40">
                <div class="flex items-center justify-between border-b border-slate-200/70 px-4 py-3 dark:border-slate-800">
                    <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-600 dark:text-slate-300">{{ __('Most viewed products') }}</p>
                    <i class="far fa-eye text-indigo-500"></i>
                </div>
                @if($siteAnalyticsTopViewed->isEmpty())
                    <p class="px-4 py-6 text-center text-xs font-semibold text-slate-400">{{ __('No product views yet.') }}</p>
                @else
                    <div class="divide-y divide-slate-200/70 dark:divide-slate-800">
                        @foreach($siteAnalyticsTopViewed->take(5) as $row)
                            <div class="flex items-center justify-between gap-3 px-4 py-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold text-slate-700 dark:text-slate-200">{{ $row['name'] }}</p>
                                    @if(($row['sku'] ?? '') !== '')
                                        <p class="font-mono text-[10px] text-slate-400">{{ $row['sku'] }}</p>
                                    @endif
                                </div>
                                <span class="num-display shrink-0 text-sm font-black text-primary dark:text-slate-100">{{ number_format((int) $row['count']) }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="rounded-2xl border border-slate-200/70 bg-slate-50 dark:border-slate-800 dark:bg-slate-950/40">
                <div class="flex items-center justify-between border-b border-slate-200/70 px-4 py-3 dark:border-slate-800">
                    <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-600 dark:text-slate-300">{{ __('Search keywords') }}</p>
                    <i class="fas fa-magnifying-glass text-sky-500"></i>
                </div>
                @if($siteAnalyticsTopSearches->isEmpty())
                    <p class="px-4 py-6 text-center text-xs font-semibold text-slate-400">{{ __('No searches recorded yet.') }}</p>
                @else
                    <div class="flex flex-wrap gap-2 px-4 py-4">
                        @foreach($siteAnalyticsTopSearches->take(10) as $row)
                            <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                <span class="font-mono">{{ $row['keyword'] }}</span>
                                <span class="rounded bg-slate-50 px-1.5 py-0.5 text-[10px] font-black text-slate-500 dark:bg-slate-800 dark:text-slate-400">{{ number_format((int) $row['count']) }}</span>
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ================= OPERATIONS QUEUE — COCKPIT REDESIGN ================= --}}
    @php
        $opTotal = collect($operationsQueue)->sum(fn($x) => (int)($x['count'] ?? 0));
        $opMax = max(collect($operationsQueue)->pluck('count')->map(fn($x) => (int) $x)->all() ?: [1]);
        $opMax = $opMax > 0 ? $opMax : 1;
    @endphp
    <div class="mb-8 relative rounded-3xl overflow-hidden bento-shadow-lg corner-brackets p-5 sm:p-7" style="background: linear-gradient(135deg, #04042a 0%, #070740 50%, #0a0d3f 100%);">
        <div class="absolute inset-0 bento-stripes pointer-events-none"></div>
        <div class="absolute -top-32 -right-32 h-96 w-96 rounded-full bg-amber-500/15 blur-[100px]"></div>
        <div class="absolute -bottom-24 -left-20 h-80 w-80 rounded-full bg-indigo-500/15 blur-[100px]"></div>
        <div class="absolute top-0 left-0 right-0 h-[2px]" style="background: linear-gradient(90deg, #f59e0b, #f43f5e, #818cf8, #22d3ee);"></div>

        {{-- Header --}}
        <div class="relative flex items-center justify-between gap-4 flex-wrap text-white mb-6">
            <div class="min-w-0">
                <div class="text-[10px] uppercase tracking-widest text-white/60 font-bold inline-flex items-center gap-2">
                    <span class="pulse-dot text-amber-400 inline-flex h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                    {{ __('Operations · Mission Control') }}
                    <span class="font-mono text-amber-300/80 px-1.5 py-0.5 rounded bg-amber-400/10 border border-amber-400/20">{{ $opTotal }} {{ __('OPEN') }}</span>
                </div>
                <h3 class="mt-1 text-xl sm:text-2xl font-bold tracking-tight">{{ __('Work that needs attention') }}</h3>
            </div>
            @if(Route::has('admin.orders.index'))
                <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/[0.06] backdrop-blur-sm px-4 py-2 text-xs font-bold text-white hover:bg-white/15 transition">
                    {{ __('Open orders') }} <i class="fas fa-arrow-right text-[10px]"></i>
                </a>
            @endif
        </div>

        {{-- Queue gauges grid --}}
        <div class="relative grid grid-cols-2 lg:grid-cols-4 gap-3">
            @foreach($operationsQueue as $item)
                @php
                    $tone = $item['tone'] ?? 'slate';
                    $count = (int) ($item['count'] ?? 0);
                    $opPct = $opMax > 0 ? max(0, min(100, round(($count / $opMax) * 100))) : 0;
                    $hasItems = $count > 0;
                    $toneHex = match ($tone) {
                        'amber' => '#f59e0b',
                        'blue' => '#3b82f6',
                        'rose' => '#f43f5e',
                        'indigo' => '#818cf8',
                        default => '#94a3b8',
                    };
                    $toneGlow = match ($tone) {
                        'amber' => 'rgba(251,191,36,0.25)',
                        'blue' => 'rgba(59,130,246,0.25)',
                        'rose' => 'rgba(244,63,94,0.25)',
                        'indigo' => 'rgba(129,140,248,0.25)',
                        default => 'rgba(148,163,184,0.15)',
                    };
                @endphp
                <a href="{{ $item['url'] }}" class="group relative rounded-2xl bg-white/[0.04] hover:bg-white/[0.08] border border-white/10 hover:border-white/20 backdrop-blur-sm p-4 transition hover:-translate-y-0.5 overflow-hidden">
                    {{-- Color glow accent --}}
                    <div class="absolute -top-12 -right-12 h-32 w-32 rounded-full blur-3xl pointer-events-none {{ $hasItems ? 'opacity-100' : 'opacity-30' }}" style="background: {{ $toneGlow }};"></div>
                    {{-- LED indicator --}}
                    <div class="absolute top-3 right-3">
                        @if($hasItems)
                            <span class="relative inline-flex h-2 w-2">
                                <span class="absolute inset-0 rounded-full animate-ping opacity-60" style="background-color: {{ $toneHex }};"></span>
                                <span class="relative h-2 w-2 rounded-full" style="background-color: {{ $toneHex }}; box-shadow: 0 0 8px {{ $toneHex }};"></span>
                            </span>
                        @else
                            <span class="inline-flex h-2 w-2 rounded-full bg-white/15"></span>
                        @endif
                    </div>

                    {{-- Circular gauge with number --}}
                    <div class="relative flex items-center gap-3.5">
                        <div class="relative h-16 w-16 shrink-0 rounded-full grid place-items-center"
                             style="background: conic-gradient({{ $toneHex }} 0deg {{ $opPct * 3.6 }}deg, rgba(255,255,255,0.06) {{ $opPct * 3.6 }}deg 360deg);">
                            <div class="absolute inset-1.5 rounded-full grid place-items-center" style="background: linear-gradient(135deg, #04042a, #0a0d3f);">
                                <span class="num-display text-2xl font-black {{ $hasItems ? '' : 'text-white/40' }}" style="{{ $hasItems ? 'color: ' . $toneHex . ';' : '' }}">{{ number_format($count) }}</span>
                            </div>
                        </div>
                        <div class="min-w-0 flex-1">
                            <i class="fas {{ $item['icon'] }} text-sm {{ $hasItems ? '' : 'text-white/40' }}" style="{{ $hasItems ? 'color: ' . $toneHex . ';' : '' }}"></i>
                            <p class="mt-1 text-sm font-bold text-white leading-snug">{{ __($item['label']) }}</p>
                        </div>
                    </div>

                    <p class="relative mt-3 text-[11px] leading-snug text-white/55">{{ __($item['description']) }}</p>

                    <div class="relative mt-3 flex items-center justify-between">
                        <span class="text-[10px] font-mono uppercase tracking-widest {{ $hasItems ? 'text-white/70' : 'text-white/30' }}">
                            {{ $hasItems ? __('Handle now') : __('Clear') }}
                        </span>
                        <i class="fas fa-arrow-right text-[10px] text-white/40 group-hover:text-white group-hover:translate-x-0.5 transition"></i>
                    </div>
                </a>
            @endforeach
        </div>

        @if($operationOrders->isNotEmpty())
            <div class="relative mt-6 rounded-2xl border border-white/10 bg-white/[0.04] backdrop-blur-sm overflow-hidden">
                <div class="flex items-center justify-between border-b border-white/10 px-4 py-3">
                    <p class="text-xs font-bold text-white inline-flex items-center gap-2">
                        <span class="pulse-dot inline-flex h-1.5 w-1.5 rounded-full bg-amber-400 text-amber-400"></span>
                        <span class="font-mono text-[10px] text-amber-300">⌖</span>
                        {{ __('Priority orders') }}
                        <span class="ml-1 text-[10px] font-mono text-white/60 bg-white/10 px-1.5 py-0.5 rounded">{{ $operationOrders->count() }}</span>
                    </p>
                    <span class="text-[11px] text-white/55">{{ __('Cancellation, shipping, and today pending') }}</span>
                </div>
                <div class="divide-y divide-white/10">
                    @foreach($operationOrders as $order)
                        @php
                            $isCancellation = filled($order->cancellation_requested_at);
                            $statusLabel = $isCancellation ? __('Cancellation request') : \App\Models\Order::statusMeta((string) $order->status)['label'];
                            $badgeStyle = $isCancellation
                                ? 'background: rgba(244,63,94,0.15); color: #fda4af; border: 1px solid rgba(244,63,94,0.3);'
                                : ((string) $order->status === \App\Models\Order::STATUS_PROCESSING
                                    ? 'background: rgba(59,130,246,0.15); color: #93c5fd; border: 1px solid rgba(59,130,246,0.3);'
                                    : 'background: rgba(251,191,36,0.15); color: #fcd34d; border: 1px solid rgba(251,191,36,0.3);');
                        @endphp
                        <a href="{{ route('admin.orders.show', $order) }}" class="flex flex-col gap-2 px-4 py-3 transition hover:bg-white/[0.04] sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-xl border border-white/15 bg-white/[0.06] text-white font-bold grid place-items-center text-[10px]">
                                    #{{ \Illuminate\Support\Str::limit((string) $order->id, 4, '') }}
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-white">{{ $order->order_number }}</p>
                                    <p class="mt-0.5 text-xs text-white/55">
                                        {{ $order->user?->name ?? __('Guest') }} · {{ optional($order->created_at)->format('M d, H:i') }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 sm:justify-end">
                                <span class="rounded-full px-2.5 py-1 text-[11px] font-bold" style="{{ $badgeStyle }}">{{ $statusLabel }}</span>
                                <span class="num-display text-sm font-bold text-white">{{ $currencyLabel }} {{ number_format((float) $order->total_amount, $currencyDecimals) }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- ================= CHARTS ROW (REDESIGNED) ================= --}}
    @php
        $monthSum = (int) array_sum($monthCounts ?? []);
        $monthMax = max($monthCounts ?: [0]);
        $monthAvg = count(array_filter($monthCounts ?? [])) > 0 ? round($monthSum / max(count(array_filter($monthCounts ?? [])), 1)) : 0;
        $monthPeakIdx = $monthMax > 0 ? array_search($monthMax, $monthCounts) : null;
        $monthPeakLabel = $monthPeakIdx !== null ? ($monthLabels[$monthPeakIdx] ?? '—') : '—';
        // Current month index (last) and previous for growth calc
        $cur = !empty($monthCounts) ? (int) end($monthCounts) : 0;
        $prev = count($monthCounts ?? []) > 1 ? (int) $monthCounts[count($monthCounts) - 2] : 0;
        $monthChange = $prev > 0 ? round((($cur - $prev) / $prev) * 100, 1) : 0;

        $catTotal = array_sum($categoryCounts ?? []);
        $catCount = count($categoryNames ?? []);
        $catPalette = ['#4f46e5', '#06b6d4', '#a855f7', '#f59e0b', '#10b981', '#ec4899', '#14b8a6', '#f97316'];
        // Sort categories by count desc for ranking
        $sortedCats = [];
        if ($catCount > 0) {
            foreach ($categoryNames as $i => $name) {
                $sortedCats[] = ['name' => $name, 'count' => (int) ($categoryCounts[$i] ?? 0), 'color' => $catPalette[$i % count($catPalette)]];
            }
            usort($sortedCats, fn($a, $b) => $b['count'] - $a['count']);
        }
        $catMax = $sortedCats ? max(array_column($sortedCats, 'count')) : 1;
    @endphp

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 mb-8">

        {{-- ============ MONTHLY ORDERS TREND — COMPACT ============ --}}
        <div class="xl:col-span-2 relative rounded-3xl overflow-hidden bento-shadow-lg corner-brackets p-5" style="background: linear-gradient(135deg, #04042a 0%, #070740 50%, #0a0d3f 100%);">
            <div class="absolute inset-0 bento-stripes pointer-events-none"></div>
            <div class="absolute -top-24 -right-24 h-72 w-72 rounded-full bg-indigo-500/20 blur-[100px]"></div>
            <div class="absolute -bottom-16 -left-12 h-56 w-56 rounded-full bg-cyan-500/15 blur-[80px]"></div>
            <div class="absolute top-0 left-0 right-0 h-[2px]" style="background: linear-gradient(90deg, #22d3ee, #818cf8, #e879f9);"></div>

            {{-- Compact header with inline stats --}}
            <div class="relative text-white">
                <div class="flex items-start justify-between gap-4 flex-wrap">
                    <div class="min-w-0">
                        <div class="text-[10px] uppercase tracking-widest text-white/60 font-bold inline-flex items-center gap-2">
                            <span class="pulse-dot text-emerald-400 inline-flex h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                            {{ __('Performance · Live') }}
                            <span class="font-mono text-cyan-300/80 px-1.5 py-0.5 rounded bg-cyan-400/10 border border-cyan-400/20">YTD · {{ date('Y') }}</span>
                        </div>
                        <h3 class="mt-1 text-xl font-bold tracking-tight">{{ __('Monthly Orders Trend') }}</h3>
                    </div>

                    {{-- Inline stat strip — compact --}}
                    <div class="flex items-center gap-3 text-xs">
                        <div class="text-center">
                            <p class="text-[9px] uppercase tracking-widest text-white/45 font-bold leading-none">{{ __('Total') }}</p>
                            <p class="num-display text-lg font-black text-white mt-0.5 leading-none">{{ number_format($monthSum) }}</p>
                        </div>
                        <span class="h-8 w-px bg-white/10"></span>
                        <div class="text-center">
                            <p class="text-[9px] uppercase tracking-widest text-white/45 font-bold leading-none">{{ __('Peak') }}</p>
                            <p class="text-lg font-black text-cyan-300 mt-0.5 leading-none">{{ $monthPeakLabel }}</p>
                        </div>
                        <span class="h-8 w-px bg-white/10"></span>
                        <div class="text-center">
                            <p class="text-[9px] uppercase tracking-widest text-white/45 font-bold leading-none">{{ __('Avg') }}</p>
                            <p class="num-display text-lg font-black text-indigo-300 mt-0.5 leading-none">{{ number_format($monthAvg) }}</p>
                        </div>
                        @if($monthChange != 0)
                            <span class="h-8 w-px bg-white/10"></span>
                            @if($monthChange > 0)
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-400/15 text-emerald-300 px-2 py-1 text-[11px] font-bold border border-emerald-400/20">
                                    <i class="fas fa-arrow-up text-[9px]"></i> {{ $monthChange }}%
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-rose-400/15 text-rose-300 px-2 py-1 text-[11px] font-bold border border-rose-400/20">
                                    <i class="fas fa-arrow-down text-[9px]"></i> {{ abs($monthChange) }}%
                                </span>
                            @endif
                        @endif
                    </div>
                </div>

                {{-- Chart area — BIGGER, no inset frame --}}
                @if(count($monthLabels) > 0 && array_sum($monthCounts) > 0)
                    <div class="mt-4" style="height: 280px"><canvas id="ordersChart"></canvas></div>
                @else
                    <div class="h-72 flex flex-col items-center justify-center text-white/40">
                        <i class="fas fa-chart-line text-5xl mb-3 opacity-30"></i>
                        <p class="text-sm font-bold">{{ __('No orders data available') }}</p>
                        <p class="text-[11px] mt-1 font-mono text-white/30">— awaiting first orders —</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- ============ PRODUCTS DISTRIBUTION — COMPACT TOP 5 ============ --}}
        <div class="relative rounded-3xl overflow-hidden bento-shadow-lg corner-brackets p-5" style="background: linear-gradient(135deg, #04042a 0%, #070740 60%, #0a0d3f 100%);">
            <div class="absolute inset-0 bento-stripes pointer-events-none"></div>
            <div class="absolute -top-16 -right-16 h-48 w-48 rounded-full bg-fuchsia-500/20 blur-[70px]"></div>
            <div class="absolute -bottom-16 -left-10 h-48 w-48 rounded-full bg-violet-500/15 blur-[70px]"></div>
            <div class="absolute top-0 left-0 right-0 h-[2px]" style="background: linear-gradient(90deg, #e879f9, #a78bfa, #818cf8);"></div>

            <div class="relative text-white">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="text-[10px] uppercase tracking-widest text-white/60 font-bold inline-flex items-center gap-2">
                            <span class="pulse-dot text-fuchsia-400 inline-flex h-1.5 w-1.5 rounded-full bg-fuchsia-400"></span>
                            {{ __('Catalog') }}
                        </div>
                        <h3 class="mt-1 text-xl font-bold tracking-tight">{{ __('Products Distribution') }}</h3>
                    </div>
                    <span class="text-[10px] font-mono font-bold text-fuchsia-300/80 px-2 py-1 rounded bg-fuchsia-400/10 border border-fuchsia-400/20 whitespace-nowrap">{{ $catCount }} {{ __('CAT') }}</span>
                </div>

                @if(count($categoryNames) > 0)
                    {{-- Doughnut with stat OVERLAY in center --}}
                    <div class="relative mt-4 mx-auto" style="width: 180px; height: 180px;">
                        <canvas id="categoryChart"></canvas>
                        <div class="absolute inset-0 grid place-items-center pointer-events-none">
                            <div class="text-center">
                                <p class="num-display text-3xl font-black text-white leading-none">{{ number_format($catTotal) }}</p>
                                <p class="text-[9px] uppercase tracking-widest text-white/50 mt-1 font-bold">{{ __('Products') }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- TOP 5 categories only --}}
                    <div class="mt-4">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-[9px] uppercase tracking-widest text-white/45 font-bold">{{ __('Top Categories') }}</p>
                            <p class="text-[9px] uppercase tracking-widest font-mono text-white/40">TOP 5 / {{ $catCount }}</p>
                        </div>
                        <div class="space-y-1.5">
                            @foreach(array_slice($sortedCats, 0, 5) as $i => $cat)
                                @php $pct = $catMax > 0 ? round(($cat['count'] / $catMax) * 100) : 0; @endphp
                                <div class="flex items-center gap-2.5">
                                    <span class="num-display text-[9px] font-bold font-mono text-white/40 w-4 text-right">{{ $i + 1 }}</span>
                                    <span class="h-2 w-2 rounded-full shrink-0" style="background-color: {{ $cat['color'] }}"></span>
                                    <span class="text-xs text-white/80 font-semibold truncate flex-1">{{ $cat['name'] }}</span>
                                    <span class="num-display text-xs font-bold text-white whitespace-nowrap">{{ $cat['count'] }}</span>
                                </div>
                            @endforeach
                        </div>

                        @if($catCount > 5 && Route::has('admin.categories.index'))
                            <a href="{{ route('admin.categories.index') }}" class="mt-3 inline-flex items-center gap-1.5 text-[11px] font-bold text-fuchsia-300 hover:text-white transition">
                                <i class="fas fa-arrow-right text-[9px]"></i>
                                {{ __('View all :n categories', ['n' => $catCount]) }}
                            </a>
                        @endif
                    </div>
                @else
                    <div class="h-60 flex flex-col items-center justify-center text-white/40">
                        <i class="fas fa-chart-pie text-5xl mb-3 opacity-30"></i>
                        <p class="text-base font-bold">{{ __('No categories') }}</p>
                    </div>
                @endif
            </div>
        </div>

    </div>

    {{-- ================= OPERATIONAL KPI (30d) ================= --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 mb-8">

        <div class="relative rounded-3xl bg-white p-5 bento-shadow border border-slate-200/70 overflow-hidden dark:bg-slate-900 dark:border-slate-800">
            <div class="absolute top-0 left-0 bottom-0 w-1 bg-gradient-to-b from-rose-400 to-rose-600"></div>
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[10px] uppercase tracking-[0.22em] text-slate-500 font-bold dark:text-slate-400">{{ __('Return Rate (30d)') }}</p>
                    <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">{{ __('Quality indicator') }}</p>
                </div>
                <div class="h-10 w-10 rounded-xl bg-rose-50 text-rose-600 grid place-items-center dark:bg-rose-900/30 dark:text-rose-300">
                    <i class="fas fa-rotate-left"></i>
                </div>
            </div>
            <p class="mt-4 num-display text-3xl font-black text-primary dark:text-slate-100">{{ number_format($returnRatePercent, 1) }}%</p>
            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                {{ __(':returns returns / :delivered delivered orders', [
                    'returns' => number_format($returnRequests30d),
                    'delivered' => number_format($deliveredOrders30d),
                ]) }}
            </p>
        </div>

        <div class="relative rounded-3xl bg-white p-5 bento-shadow border border-slate-200/70 overflow-hidden dark:bg-slate-900 dark:border-slate-800">
            <div class="absolute top-0 left-0 bottom-0 w-1 bg-gradient-to-b from-blue-400 to-blue-600"></div>
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[10px] uppercase tracking-[0.22em] text-slate-500 font-bold dark:text-slate-400">{{ __('Avg Ship Time (30d)') }}</p>
                    <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">{{ __('Processing → Shipped') }}</p>
                </div>
                <div class="h-10 w-10 rounded-xl bg-blue-50 text-blue-600 grid place-items-center dark:bg-blue-900/30 dark:text-blue-300">
                    <i class="fas fa-truck-fast"></i>
                </div>
            </div>
            <p class="mt-4 num-display text-3xl font-black text-primary dark:text-slate-100">
                @if($avgShipHours === null)
                    <span class="text-slate-400">—</span>
                @elseif($avgShipHours >= 24)
                    {{ number_format($avgShipHours / 24, 1) }}<span class="text-base font-bold text-slate-500">{{ __('d') }}</span>
                @else
                    {{ number_format($avgShipHours, 1) }}<span class="text-base font-bold text-slate-500">{{ __('h') }}</span>
                @endif
            </p>
            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ __('Median over the last 30 days') }}</p>
        </div>

        <div class="relative rounded-3xl bg-white p-5 bento-shadow border border-slate-200/70 overflow-hidden dark:bg-slate-900 dark:border-slate-800">
            <div class="absolute top-0 left-0 bottom-0 w-1 bg-gradient-to-b from-emerald-400 to-emerald-600"></div>
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[10px] uppercase tracking-[0.22em] text-slate-500 font-bold dark:text-slate-400">{{ __('Top Margin Products') }}</p>
                    <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">{{ __('Highest profit') }}</p>
                </div>
                <div class="h-10 w-10 rounded-xl bg-emerald-50 text-emerald-600 grid place-items-center dark:bg-emerald-900/30 dark:text-emerald-300">
                    <i class="fas fa-trophy"></i>
                </div>
            </div>
            @if($topProfitable->isEmpty())
                <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">
                    {{ __('No products with a configured dealer_price yet — set it to see margin ranking.') }}
                </p>
            @else
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach($topProfitable->take(3) as $i => $product)
                        <li class="flex items-center justify-between gap-2">
                            <span class="inline-flex items-center gap-2 truncate text-slate-700 dark:text-slate-200" title="{{ $product->name_en }}">
                                <span class="font-mono text-[10px] text-slate-400">0{{ $i + 1 }}</span>
                                {{ \Illuminate\Support\Str::limit((string) $product->name_en, 20) }}
                            </span>
                            <span class="num-display font-bold text-emerald-700 dark:text-emerald-300 whitespace-nowrap text-xs">
                                {{ number_format((float) $product->margin_total, $currencyDecimals) }} {{ $currencyLabel }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

    </div>

    {{-- ================= INVENTORY HEALTH ================= --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">

        <div class="relative rounded-3xl bg-white p-5 bento-shadow border border-slate-200/70 overflow-hidden dark:bg-slate-900 dark:border-slate-800">
            <div class="absolute top-0 left-0 bottom-0 w-1 bg-gradient-to-b from-amber-400 to-orange-500"></div>
            <div class="flex items-center justify-between">
                <p class="text-[10px] uppercase tracking-[0.22em] text-amber-700 font-bold dark:text-amber-300">{{ __('Low Stock') }}</p>
                <span class="inline-flex items-center px-2 py-1 text-[10px] rounded-full bg-amber-100 text-amber-700 font-bold dark:bg-amber-900/30 dark:text-amber-300">{{ __('Threshold :count', ['count' => $lowStockThreshold]) }}</span>
            </div>
            <p class="mt-4 num-display text-3xl font-black text-amber-800 dark:text-amber-300">{{ number_format($lowStockCount) }}</p>
            <p class="mt-2 text-xs {{ $lowStockTrendPercent > 0 ? 'text-rose-600' : ($lowStockTrendPercent < 0 ? 'text-emerald-600' : 'text-slate-500') }} font-bold">
                <i class="fas fa-arrow-{{ $lowStockTrendPercent > 0 ? 'up' : ($lowStockTrendPercent < 0 ? 'down' : 'right') }} mr-1"></i>
                {{ __(':percent% vs previous month', ['percent' => number_format(abs($lowStockTrendPercent), 1)]) }}
            </p>
        </div>

        <div class="relative rounded-3xl bg-white p-5 bento-shadow border border-slate-200/70 overflow-hidden dark:bg-slate-900 dark:border-slate-800">
            <div class="absolute top-0 left-0 bottom-0 w-1 bg-gradient-to-b from-rose-400 to-rose-600"></div>
            <div class="flex items-center justify-between">
                <p class="text-[10px] uppercase tracking-[0.22em] text-rose-700 font-bold dark:text-rose-300">{{ __('Out Of Stock') }}</p>
                <span class="inline-flex items-center px-2 py-1 text-[10px] rounded-full bg-rose-100 text-rose-700 font-bold dark:bg-rose-900/30 dark:text-rose-300">{{ __('Critical') }}</span>
            </div>
            <p class="mt-4 num-display text-3xl font-black text-rose-800 dark:text-rose-300">{{ number_format($outOfStockCount) }}</p>
            <p class="mt-2 text-xs {{ $outOfStockTrendPercent > 0 ? 'text-rose-600' : ($outOfStockTrendPercent < 0 ? 'text-emerald-600' : 'text-slate-500') }} font-bold">
                <i class="fas fa-arrow-{{ $outOfStockTrendPercent > 0 ? 'up' : ($outOfStockTrendPercent < 0 ? 'down' : 'right') }} mr-1"></i>
                {{ __(':percent% vs previous month', ['percent' => number_format(abs($outOfStockTrendPercent), 1)]) }}
            </p>
        </div>

        <div class="relative rounded-3xl bg-white p-5 bento-shadow border border-slate-200/70 overflow-hidden dark:bg-slate-900 dark:border-slate-800">
            <div class="absolute top-0 left-0 bottom-0 w-1 bg-gradient-to-b from-emerald-400 to-emerald-600"></div>
            <div class="flex items-center justify-between">
                <p class="text-[10px] uppercase tracking-[0.22em] text-emerald-700 font-bold dark:text-emerald-300">{{ __('Recent Products') }}</p>
                <span class="inline-flex items-center px-2 py-1 text-[10px] rounded-full bg-emerald-100 text-emerald-700 font-bold dark:bg-emerald-900/30 dark:text-emerald-300">{{ __('This month') }}</span>
            </div>
            <p class="mt-4 num-display text-3xl font-black text-emerald-800 dark:text-emerald-300">{{ number_format($recentProductsCount) }}</p>
            <p class="mt-2 text-xs {{ $recentProductsTrendPercent > 0 ? 'text-emerald-600' : ($recentProductsTrendPercent < 0 ? 'text-rose-600' : 'text-slate-500') }} font-bold">
                <i class="fas fa-arrow-{{ $recentProductsTrendPercent > 0 ? 'up' : ($recentProductsTrendPercent < 0 ? 'down' : 'right') }} mr-1"></i>
                {{ __(':percent% vs previous month', ['percent' => number_format(abs($recentProductsTrendPercent), 1)]) }}
            </p>
        </div>

    </div>

    {{-- ================= STOCK TREND + MOVEMENT ================= --}}
    @php
        $stockNet = array_sum($stockTrendValues ?? []);
        $stockIn = array_sum($movementInValues ?? []);
        $stockOut = array_sum($movementOutValues ?? []);
    @endphp
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mb-8">
        <div class="relative rounded-3xl bg-white p-6 bento-shadow border border-slate-200/70 dark:bg-slate-900 dark:border-slate-800 overflow-hidden corner-brackets-dark">
            <div class="absolute top-0 left-0 right-0 h-[2px]" style="background: linear-gradient(90deg, #10b981, #14b8a6, #06b6d4);"></div>
            <div class="absolute -top-12 -right-12 h-40 w-40 rounded-full bg-emerald-500/5 blur-3xl"></div>
            <div class="flex items-start justify-between mb-3">
                <div>
                    <div class="text-[10px] uppercase tracking-widest text-primary font-bold inline-flex items-center gap-2">
                        <span class="pulse-dot text-emerald-500 inline-flex h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        {{ __('Inventory · Live') }}
                    </div>
                    <h3 class="mt-1 text-lg font-bold text-primary dark:text-slate-100 tracking-tight">
                        <i class="fas fa-wave-square mr-1 text-emerald-500"></i> {{ __('Stock Trend (Net Movement)') }}
                    </h3>
                    <p class="text-xs text-slate-500 mt-1 dark:text-slate-400">{{ __('Last :n days (in minus out)', ['n' => $analyticsDays]) }}</p>
                </div>
                <div class="text-right">
                    <p class="text-[9px] uppercase tracking-widest text-slate-400 font-bold">{{ __('Net') }}</p>
                    <p class="num-display text-xl font-black {{ $stockNet >= 0 ? 'text-emerald-600 dark:text-emerald-300' : 'text-rose-600 dark:text-rose-300' }}">{{ $stockNet >= 0 ? '+' : '' }}{{ number_format($stockNet) }}</p>
                </div>
            </div>
            @if(count($stockTrendLabels) > 0 && array_sum(array_map('abs', $stockTrendValues)) > 0)
                <div class="mt-2" style="height: 260px"><canvas id="stockTrendChart"></canvas></div>
            @else
                <div class="h-64 flex flex-col items-center justify-center text-slate-400 dark:text-slate-500">
                    <div class="relative">
                        <i class="fas fa-chart-line text-5xl opacity-20"></i>
                        <span class="absolute inset-0 grid place-items-center"><span class="h-2 w-2 rounded-full bg-emerald-500/40"></span></span>
                    </div>
                    <p class="mt-3 text-sm font-bold">{{ __('No stock movement trend data yet') }}</p>
                    <p class="text-[11px] mt-1 font-mono text-slate-400">— awaiting signal —</p>
                </div>
            @endif
        </div>

        <div class="relative rounded-3xl bg-white p-6 bento-shadow border border-slate-200/70 dark:bg-slate-900 dark:border-slate-800 overflow-hidden corner-brackets-dark">
            <div class="absolute top-0 left-0 right-0 h-[2px]" style="background: linear-gradient(90deg, #06b6d4, #38bdf8, #818cf8);"></div>
            <div class="absolute -top-12 -right-12 h-40 w-40 rounded-full bg-cyan-500/5 blur-3xl"></div>
            <div class="flex items-start justify-between mb-3">
                <div>
                    <div class="text-[10px] uppercase tracking-widest text-primary font-bold inline-flex items-center gap-2">
                        <span class="pulse-dot text-cyan-500 inline-flex h-1.5 w-1.5 rounded-full bg-cyan-500"></span>
                        {{ __('Inventory · Live') }}
                    </div>
                    <h3 class="mt-1 text-lg font-bold text-primary dark:text-slate-100 tracking-tight">
                        <i class="fas fa-arrows-up-down mr-1 text-cyan-500"></i> {{ __('Recent Inventory Movements') }}
                    </h3>
                    <p class="text-xs text-slate-500 mt-1 dark:text-slate-400">{{ __('Stock in vs stock out (last :n days)', ['n' => $analyticsDays]) }}</p>
                </div>
            </div>

            {{-- Mini In/Out summary --}}
            <div class="grid grid-cols-2 gap-2 mb-3">
                <div class="rounded-xl bg-emerald-50 dark:bg-emerald-900/15 border border-emerald-200/60 dark:border-emerald-800/40 px-3 py-2 flex items-center justify-between">
                    <span class="text-[10px] uppercase tracking-widest text-emerald-700 dark:text-emerald-300 font-bold"><i class="fas fa-arrow-down"></i> {{ __('Stock In') }}</span>
                    <span class="num-display text-base font-black text-emerald-700 dark:text-emerald-300">{{ number_format($stockIn) }}</span>
                </div>
                <div class="rounded-xl bg-rose-50 dark:bg-rose-900/15 border border-rose-200/60 dark:border-rose-800/40 px-3 py-2 flex items-center justify-between">
                    <span class="text-[10px] uppercase tracking-widest text-rose-700 dark:text-rose-300 font-bold"><i class="fas fa-arrow-up"></i> {{ __('Stock Out') }}</span>
                    <span class="num-display text-base font-black text-rose-700 dark:text-rose-300">{{ number_format($stockOut) }}</span>
                </div>
            </div>

            @if(count($movementLabels) > 0 && (array_sum($movementInValues) > 0 || array_sum($movementOutValues) > 0))
                <div class="mt-2" style="height: 230px"><canvas id="movementChart"></canvas></div>
            @else
                <div class="h-56 flex flex-col items-center justify-center text-slate-400 dark:text-slate-500">
                    <div class="relative">
                        <i class="fas fa-warehouse text-5xl opacity-20"></i>
                        <span class="absolute inset-0 grid place-items-center"><span class="h-2 w-2 rounded-full bg-cyan-500/40"></span></span>
                    </div>
                    <p class="mt-3 text-sm font-bold">{{ __('No recent inventory movement data') }}</p>
                    <p class="text-[11px] mt-1 font-mono text-slate-400">— awaiting signal —</p>
                </div>
            @endif
        </div>
    </div>

    {{-- ================= LOW STOCK ALERTS ================= --}}
    <div class="rounded-3xl bg-white bento-shadow border border-rose-200 mb-8 dark:border-rose-900/50 dark:bg-slate-900 overflow-hidden">
        {{-- Dark navy alert header band --}}
        <div class="relative p-6 text-white overflow-hidden" style="background: linear-gradient(135deg, #2a0510 0%, #4c0519 50%, #2a0510 100%);">
            <div class="absolute inset-0 bento-stripes pointer-events-none"></div>
            <div class="absolute -top-12 -right-12 h-48 w-48 rounded-full bg-rose-500/25 blur-3xl"></div>
            <div class="absolute top-0 left-0 right-0 h-[2px]" style="background: linear-gradient(90deg, #f43f5e, #f97316, #f59e0b);"></div>

            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] uppercase tracking-widest text-rose-200 font-bold inline-flex items-center gap-2">
                        <span class="pulse-dot text-rose-400 inline-flex h-1.5 w-1.5 rounded-full bg-rose-400"></span>
                        {{ __('Alerts') }}
                        <span class="font-mono text-cyan-300/80 px-1.5 py-0.5 rounded bg-rose-400/10 border border-rose-400/20">{{ $lowStockProducts->count() }} {{ __('CRITICAL') }}</span>
                    </div>
                    <h3 class="mt-1 text-2xl font-bold tracking-tight">
                        <i class="fas fa-triangle-exclamation mr-1 text-rose-400"></i> {{ __('Low Stock Alerts') }}
                    </h3>
                    <p class="mt-1 text-xs text-rose-200/70">{{ __('Inventory items below threshold of :n units', ['n' => $lowStockThreshold]) }}</p>
                </div>
                @if(Route::has('admin.products.index'))
                    <a href="{{ route('admin.products.index', ['low_stock' => 1]) }}" class="inline-flex items-center justify-center rounded-xl border border-rose-300/30 bg-white/10 backdrop-blur-sm px-4 py-2.5 text-sm font-bold text-white transition hover:bg-white/20">
                        {{ __('View All') }} <i class="fas fa-arrow-right ml-2 text-xs"></i>
                    </a>
                @endif
            </div>
        </div>

        <div class="p-6">
            @if($lowStockProducts->count() > 0)
                <div class="space-y-3">
                    @foreach($lowStockProducts as $product)
                        @php
                            $stockPct = $lowStockThreshold > 0 ? min(100, max(4, ($product->stock_quantity / max($lowStockThreshold, 1)) * 100)) : 4;
                            $severity = $product->stock_quantity <= 1 ? 'critical' : ($product->stock_quantity <= ceil($lowStockThreshold / 2) ? 'high' : 'medium');
                            $sevColor = $severity === 'critical' ? '#ef4444' : ($severity === 'high' ? '#f97316' : '#f59e0b');
                            $sevText = $severity === 'critical' ? 'CRITICAL' : ($severity === 'high' ? 'HIGH' : 'MEDIUM');
                            $sevBg = $severity === 'critical' ? 'bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/30' : ($severity === 'high' ? 'bg-orange-50 hover:bg-orange-100 dark:bg-orange-950/30' : 'bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/30');
                            $sevBorder = $severity === 'critical' ? 'border-rose-200 dark:border-rose-800/50' : ($severity === 'high' ? 'border-orange-200 dark:border-orange-800/50' : 'border-amber-200 dark:border-amber-800/50');
                        @endphp
                        <div class="rounded-2xl border {{ $sevBorder }} {{ $sevBg }} p-4 transition">
                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 rounded-xl flex items-center justify-center text-white shrink-0" style="background: linear-gradient(135deg, {{ $sevColor }}, {{ $sevColor }}aa);">
                                    <i class="fas fa-box"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <p class="font-bold text-primary dark:text-slate-100 truncate">{{ $product->name }}</p>
                                        <span class="text-[10px] font-bold font-mono px-1.5 py-0.5 rounded" style="background-color: {{ $sevColor }}25; color: {{ $sevColor }};">{{ $sevText }}</span>
                                    </div>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ __('SKU:') }} <span class="font-mono">{{ $product->sku }}</span></p>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="num-display text-2xl font-black" style="color: {{ $sevColor }};">{{ $product->stock_quantity }}</p>
                                    <p class="text-[10px] uppercase tracking-widest text-slate-400 font-bold">{{ __('left') }}</p>
                                </div>
                            </div>
                            {{-- Threshold proximity bar --}}
                            <div class="mt-3 flex items-center gap-3">
                                <div class="flex-1 h-1.5 bg-white/60 dark:bg-slate-800 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full" style="width: {{ $stockPct }}%; background: linear-gradient(90deg, {{ $sevColor }}, {{ $sevColor }}aa);"></div>
                                </div>
                                <span class="text-[10px] font-mono text-slate-400 whitespace-nowrap">{{ $product->stock_quantity }} / {{ $lowStockThreshold }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="h-24 flex flex-col items-center justify-center text-slate-400 dark:text-slate-500">
                    <i class="fas fa-check-circle text-3xl mb-2 text-emerald-500"></i>
                    <p class="text-sm font-bold">{{ __('All products have healthy stock') }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- ================= RECENT PRODUCTS — FULL HERO ================= --}}
    <div class="relative mb-8 rounded-3xl overflow-hidden bento-shadow-lg corner-brackets p-5 sm:p-6" style="background: linear-gradient(135deg, #04042a 0%, #070740 50%, #0a0d3f 100%);">
        <div class="absolute inset-0 bento-stripes pointer-events-none"></div>
        <div class="absolute -top-24 -right-24 h-80 w-80 rounded-full bg-emerald-500/15 blur-[100px]"></div>
        <div class="absolute -bottom-20 -left-16 h-64 w-64 rounded-full bg-cyan-500/10 blur-[80px]"></div>
        <div class="absolute top-0 left-0 right-0 h-[2px]" style="background: linear-gradient(90deg, #10b981, #14b8a6, #06b6d4);"></div>

        <div class="relative text-white">
            <div class="flex items-center justify-between gap-3 flex-wrap mb-5">
                <div class="min-w-0">
                    <div class="text-[10px] uppercase tracking-widest text-white/60 font-bold inline-flex items-center gap-2">
                        <span class="pulse-dot text-emerald-400 inline-flex h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                        {{ __('Catalog · New Arrivals') }}
                        <span class="font-mono text-emerald-300/80 px-1.5 py-0.5 rounded bg-emerald-400/10 border border-emerald-400/20">{{ $recentProducts->count() }} {{ __('NEW') }}</span>
                    </div>
                    <h3 class="mt-1 text-xl sm:text-2xl font-bold tracking-tight">
                        <i class="fas fa-box-open mr-1 text-emerald-400"></i> {{ __('Recent Products') }}
                    </h3>
                </div>
                @if(Route::has('admin.products.index'))
                    <a href="{{ route('admin.products.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/[0.06] backdrop-blur-sm px-3 py-2 text-xs font-bold text-white hover:bg-white/15 transition">
                        {{ __('View All') }} <i class="fas fa-arrow-right text-[10px]"></i>
                    </a>
                @endif
            </div>

            @if($recentProducts->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-3">
                    @foreach($recentProducts as $product)
                        @php
                            $isLow = $product->stock_quantity <= $lowStockThreshold;
                            $isOut = $product->stock_quantity <= 0;
                            $isFresh = $product->created_at && $product->created_at->diffInDays(now()) <= 7;
                            $stockColor = $isOut ? '#f43f5e' : ($isLow ? '#fb923c' : '#34d399');
                            $stockLabel = $isOut ? 'OUT' : ($isLow ? 'LOW' : 'OK');
                            $stockGlow = $isOut ? 'rgba(244,63,94,0.25)' : ($isLow ? 'rgba(251,146,60,0.25)' : 'rgba(52,211,153,0.20)');
                        @endphp
                        <div class="group relative rounded-2xl bg-white/[0.04] hover:bg-white/[0.08] border border-white/10 hover:border-white/20 backdrop-blur-sm p-4 transition hover:-translate-y-0.5 overflow-hidden">
                            {{-- Color glow per stock state --}}
                            <div class="absolute -top-12 -right-12 h-32 w-32 rounded-full blur-3xl pointer-events-none" style="background: {{ $stockGlow }};"></div>
                            {{-- LED stock indicator --}}
                            <div class="absolute top-3 right-3">
                                <span class="relative inline-flex h-2 w-2">
                                    <span class="absolute inset-0 rounded-full animate-ping opacity-50" style="background-color: {{ $stockColor }};"></span>
                                    <span class="relative h-2 w-2 rounded-full" style="background-color: {{ $stockColor }}; box-shadow: 0 0 8px {{ $stockColor }};"></span>
                                </span>
                            </div>

                            <div class="relative">
                                {{-- Image + NEW badge overlay --}}
                                <div class="relative w-12 h-12 rounded-xl overflow-hidden mb-3" style="background: linear-gradient(135deg, {{ $stockColor }}, {{ $stockColor }}88);">
                                    <div class="absolute inset-0 grid place-items-center">
                                        <i class="fas fa-box text-white text-base"></i>
                                    </div>
                                    @if($isFresh)
                                        <span class="absolute -bottom-1 -right-1 inline-flex items-center text-[7px] uppercase tracking-widest font-bold font-mono px-1 py-px rounded ring-2 ring-[#0a0d3f]" style="background: #fbbf24; color: #422006;">NEW</span>
                                    @endif
                                </div>

                                <p class="font-bold text-white truncate text-sm">{{ $product->name }}</p>
                                <p class="text-[10px] text-white/50 mt-0.5 font-mono truncate">{{ $product->sku ?? __('N/A') }}</p>

                                <div class="mt-3 flex items-center justify-between gap-2">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold font-mono" style="background-color: {{ $stockColor }}25; color: {{ $stockColor }};">
                                        <span class="h-1 w-1 rounded-full" style="background-color: {{ $stockColor }};"></span>
                                        {{ $stockLabel }} · {{ $product->stock_quantity }}
                                    </span>
                                    <span class="text-[10px] text-white/40 whitespace-nowrap">{{ optional($product->created_at)->diffForHumans(null, true) }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="h-32 flex flex-col items-center justify-center text-white/40">
                    <i class="fas fa-box text-4xl mb-2 opacity-30"></i>
                    <p class="text-sm font-bold">{{ __('No recent products added yet') }}</p>
                    <p class="text-[11px] mt-1 font-mono text-white/30">— awaiting first product —</p>
                </div>
            @endif
        </div>
    </div>

    {{-- ================= RECENT ORDERS + TOP PRODUCTS ================= --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">

        {{-- ============ RECENT ORDERS — FULL HERO ============ --}}
        <div class="relative rounded-3xl overflow-hidden bento-shadow-lg corner-brackets p-5 sm:p-6" style="background: linear-gradient(135deg, #04042a 0%, #070740 50%, #0a0d3f 100%);">
            <div class="absolute inset-0 bento-stripes pointer-events-none"></div>
            <div class="absolute -top-24 -right-24 h-72 w-72 rounded-full bg-purple-500/15 blur-[100px]"></div>
            <div class="absolute -bottom-20 -left-16 h-56 w-56 rounded-full bg-indigo-500/10 blur-[80px]"></div>
            <div class="absolute top-0 left-0 right-0 h-[2px]" style="background: linear-gradient(90deg, #a855f7, #818cf8, #60a5fa);"></div>

            <div class="relative text-white">
                <div class="flex items-center justify-between gap-3 flex-wrap mb-5">
                    <div class="min-w-0">
                        <div class="text-[10px] uppercase tracking-widest text-white/60 font-bold inline-flex items-center gap-2">
                            <span class="pulse-dot text-purple-400 inline-flex h-1.5 w-1.5 rounded-full bg-purple-400"></span>
                            {{ __('Operations · Live Feed') }}
                            <span class="font-mono text-purple-300/80 px-1.5 py-0.5 rounded bg-purple-400/10 border border-purple-400/20">{{ $recentOrders->count() }} {{ __('LATEST') }}</span>
                        </div>
                        <h3 class="mt-1 text-xl sm:text-2xl font-bold tracking-tight">
                            <i class="fas fa-clock mr-1 text-purple-400"></i> {{ __('Recent Orders') }}
                        </h3>
                    </div>
                    @if(Route::has('admin.orders.index'))
                        <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/[0.06] backdrop-blur-sm px-3 py-2 text-xs font-bold text-white hover:bg-white/15 transition">
                            {{ __('View All') }} <i class="fas fa-arrow-right text-[10px]"></i>
                        </a>
                    @endif
                </div>

                @if($recentOrders->count() > 0)
                    <div class="space-y-2">
                        @foreach($recentOrders as $order)
                            @php
                                $statusColor = match($order->status) {
                                    'completed' => '#34d399',
                                    'pending' => '#fbbf24',
                                    'processing' => '#60a5fa',
                                    default => '#f43f5e',
                                };
                                $statusBg = match($order->status) {
                                    'completed' => 'rgba(52,211,153,0.15)',
                                    'pending' => 'rgba(251,191,36,0.15)',
                                    'processing' => 'rgba(96,165,250,0.15)',
                                    default => 'rgba(244,63,94,0.15)',
                                };
                                $statusBorder = match($order->status) {
                                    'completed' => 'rgba(52,211,153,0.3)',
                                    'pending' => 'rgba(251,191,36,0.3)',
                                    'processing' => 'rgba(96,165,250,0.3)',
                                    default => 'rgba(244,63,94,0.3)',
                                };
                                $customerInitial = strtoupper(substr($order->user->name ?? 'G', 0, 1));
                            @endphp
                            <a href="#" class="group relative flex items-center gap-3 p-3 rounded-xl bg-white/[0.04] hover:bg-white/[0.08] border border-white/10 hover:border-white/20 backdrop-blur-sm transition">
                                {{-- Customer avatar with status color ring --}}
                                <div class="relative w-11 h-11 shrink-0">
                                    <div class="absolute inset-0 rounded-xl" style="background: linear-gradient(135deg, {{ $statusColor }}, {{ $statusColor }}88);"></div>
                                    <div class="absolute inset-0.5 rounded-[10px] grid place-items-center bg-[#0a0d3f]">
                                        <span class="text-sm font-bold text-white">{{ $customerInitial }}</span>
                                    </div>
                                </div>

                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <p class="font-bold text-white truncate">{{ $order->user->name ?? __('Guest') }}</p>
                                        <span class="text-[10px] font-mono font-bold text-white/40">#{{ $order->id }}</span>
                                    </div>
                                    <p class="text-[11px] text-white/55 mt-0.5">{{ $order->created_at->diffForHumans() }}</p>
                                </div>

                                <div class="text-right shrink-0">
                                    <p class="num-display text-sm font-black text-white leading-none">
                                        {{ number_format($order->total_amount, $currencyDecimals) }}
                                    </p>
                                    <p class="text-[9px] uppercase tracking-widest text-white/40 font-bold mt-0.5">{{ $currencyLabel }}</p>
                                </div>

                                <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-1 text-[10px] font-bold font-mono shrink-0" style="background-color: {{ $statusBg }}; color: {{ $statusColor }}; border: 1px solid {{ $statusBorder }};">
                                    <span class="h-1 w-1 rounded-full" style="background-color: {{ $statusColor }};"></span>
                                    {{ strtoupper(\App\Models\Order::statusMeta((string) $order->status)['label']) }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="h-56 flex flex-col items-center justify-center text-white/40">
                        <i class="fas fa-shopping-cart text-5xl mb-3 opacity-30"></i>
                        <p class="text-base font-bold">{{ __('No recent orders') }}</p>
                        <p class="text-[11px] mt-1 font-mono text-white/30">— awaiting first order —</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- ============ TOP SELLING PRODUCTS — HALL OF FAME ============ --}}
        <div class="relative rounded-3xl overflow-hidden bento-shadow-lg corner-brackets p-5 sm:p-6" style="background: linear-gradient(135deg, #04042a 0%, #070740 50%, #0a0d3f 100%);">
            <div class="absolute inset-0 bento-stripes pointer-events-none"></div>
            <div class="absolute -top-24 -right-24 h-80 w-80 rounded-full bg-amber-500/15 blur-[100px]"></div>
            <div class="absolute -bottom-24 -left-16 h-64 w-64 rounded-full bg-orange-500/10 blur-[80px]"></div>
            <div class="absolute top-0 left-0 right-0 h-[2px]" style="background: linear-gradient(90deg, #fbbf24, #fb923c, #f43f5e);"></div>

            <div class="relative text-white">
                <div class="flex items-center justify-between gap-3 flex-wrap mb-5">
                    <div class="min-w-0">
                        <div class="text-[10px] uppercase tracking-widest text-white/60 font-bold inline-flex items-center gap-2">
                            <span class="pulse-dot text-amber-400 inline-flex h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                            {{ __('Catalog · Bestsellers') }}
                            <span class="font-mono text-amber-300/80 px-1.5 py-0.5 rounded bg-amber-400/10 border border-amber-400/20">TOP {{ $topProducts->count() }}</span>
                        </div>
                        <h3 class="mt-1 text-xl sm:text-2xl font-bold tracking-tight">
                            <i class="fas fa-trophy mr-1 text-amber-400"></i> {{ __('Top Selling Products') }}
                        </h3>
                    </div>
                    @if(Route::has('admin.products.index'))
                        <a href="{{ route('admin.products.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/[0.06] backdrop-blur-sm px-3 py-2 text-xs font-bold text-white hover:bg-white/15 transition">
                            {{ __('View All') }} <i class="fas fa-arrow-right text-[10px]"></i>
                        </a>
                    @endif
                </div>

                @if($topProducts->count() > 0)
                    @php
                        $maxSold = $topProducts->max('total_sold') ?: 1;
                        $maxRev = $topProducts->max('total_revenue') ?: 1;
                        $champion = $topProducts->first();
                        $champPct = $maxSold > 0 ? (($champion->total_sold ?? 0) / $maxSold) * 100 : 0;
                        $hasImage = isset($champion->image) && $champion->image && file_exists(public_path('storage/' . $champion->image));
                    @endphp

                    {{-- ============ #1 CHAMPION CARD ============ --}}
                    <div class="relative rounded-2xl overflow-hidden p-5 mb-3 group" style="background: linear-gradient(135deg, rgba(251,191,36,0.10) 0%, rgba(244,63,94,0.05) 100%); border: 1px solid rgba(251,191,36,0.25);">
                        {{-- Crown corner badge --}}
                        <div class="absolute top-3 right-3 inline-flex items-center gap-1 text-[9px] uppercase tracking-widest font-bold font-mono px-2 py-1 rounded-md" style="background: linear-gradient(135deg, #fbbf24, #f59e0b); color: #422006;">
                            <i class="fas fa-crown text-[10px]"></i> #1 CHAMPION
                        </div>

                        <div class="flex items-start gap-4">
                            {{-- Big image/icon --}}
                            <div class="relative w-20 h-20 sm:w-24 sm:h-24 shrink-0 rounded-2xl overflow-hidden ring-2 ring-amber-400/40" style="background: linear-gradient(135deg, #fbbf24, #fb923c);">
                                @if($hasImage)
                                    <img src="{{ asset('storage/' . $champion->image) }}" alt="{{ $champion->name ?? __('Product') }}" class="w-full h-full object-cover">
                                @else
                                    <div class="absolute inset-0 grid place-items-center">
                                        <i class="fas fa-box text-3xl text-white drop-shadow"></i>
                                    </div>
                                @endif
                                {{-- Rank glow ring --}}
                                <div class="absolute inset-0 rounded-2xl ring-2 ring-amber-400 ring-offset-2 ring-offset-[#0a0d3f] opacity-0 group-hover:opacity-60 transition"></div>
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="text-[10px] uppercase tracking-widest text-amber-300 font-bold">{{ __('Best Selling') }}</p>
                                <p class="mt-0.5 text-base sm:text-lg font-bold text-white truncate">{{ $champion->name ?? __('Product') }}</p>

                                <div class="mt-3 grid grid-cols-2 gap-3">
                                    <div>
                                        <p class="text-[9px] uppercase tracking-widest text-white/45 font-bold">{{ __('Total Sales') }}</p>
                                        <p class="mt-0.5 num-display text-2xl font-black text-amber-300 leading-none">{{ number_format($champion->total_sold ?? 0) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[9px] uppercase tracking-widest text-white/45 font-bold">{{ __('Revenue') }}</p>
                                        <p class="mt-0.5 num-display text-xl font-black text-white leading-none">
                                            <span class="text-xs text-white/55">{{ $currencyLabel }}</span>
                                            {{ number_format($champion->total_revenue ?? 0, $currencyDecimals) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ============ #2 .. #N RANKED LIST ============ --}}
                    @if($topProducts->count() > 1)
                        <div class="space-y-2">
                            @foreach($topProducts->slice(1) as $idx => $product)
                                @php
                                    $rank = $idx + 2;
                                    $percentage = $maxSold > 0 ? (($product->total_sold ?? 0) / $maxSold) * 100 : 0;
                                    $pImg = isset($product->image) && $product->image && file_exists(public_path('storage/' . $product->image));
                                    $medalColor = $rank === 2 ? '#cbd5e1' : ($rank === 3 ? '#fb923c' : 'rgba(255,255,255,0.5)');
                                @endphp
                                <div class="group relative rounded-xl bg-white/[0.04] hover:bg-white/[0.08] border border-white/10 hover:border-white/20 px-3 py-2.5 backdrop-blur-sm transition">
                                    <div class="flex items-center gap-3">
                                        {{-- Rank --}}
                                        <span class="num-display text-sm font-bold font-mono w-6 text-center shrink-0" style="color: {{ $medalColor }};">
                                            {{ str_pad($rank, 2, '0', STR_PAD_LEFT) }}
                                        </span>

                                        {{-- Image --}}
                                        <div class="w-10 h-10 shrink-0 rounded-lg overflow-hidden" style="background: linear-gradient(135deg, rgba(251,191,36,0.5), rgba(244,63,94,0.5));">
                                            @if($pImg)
                                                <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name ?? __('Product') }}" class="w-full h-full object-cover">
                                            @else
                                                <div class="w-full h-full grid place-items-center"><i class="fas fa-box text-white text-xs"></i></div>
                                            @endif
                                        </div>

                                        {{-- Name + bar --}}
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-bold text-white/95 truncate">{{ \Illuminate\Support\Str::limit($product->name ?? __('Product'), 28) }}</p>
                                            <div class="mt-1.5 flex items-center gap-2">
                                                <div class="h-1 flex-1 bg-white/10 rounded-full overflow-hidden">
                                                    <div class="h-full rounded-full" style="width: {{ $percentage }}%; background: linear-gradient(90deg, #fbbf24, #fb923c);"></div>
                                                </div>
                                                <span class="num-display text-[11px] font-bold text-white/70 whitespace-nowrap">{{ $product->total_sold ?? 0 }}</span>
                                            </div>
                                        </div>

                                        {{-- Revenue --}}
                                        <div class="text-right shrink-0">
                                            <p class="num-display text-sm font-bold text-white leading-none">
                                                {{ number_format($product->total_revenue ?? 0, $currencyDecimals) }}
                                            </p>
                                            <p class="text-[9px] uppercase tracking-widest text-white/40 font-bold mt-0.5">{{ $currencyLabel }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @else
                    <div class="h-56 flex flex-col items-center justify-center text-white/40">
                        <i class="fas fa-trophy text-5xl mb-3 opacity-30"></i>
                        <p class="text-base font-bold">{{ __('No product sales yet') }}</p>
                        <p class="text-[11px] mt-1 font-mono text-white/30">— awaiting first sale —</p>
                    </div>
                @endif
            </div>
        </div>

    </div>

</div>
</div>
</div>

{{-- ================= FONT AWESOME ================= --}}
<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
      integrity="sha384-iw3OoTErCYJJB9mCa8LNS2hbsQ7M3C0EpIsO/H5+EGAkPGc6rk+V8i04oW/K5xq0"
      crossorigin="anonymous"
      referrerpolicy="no-referrer">

{{-- ================= CHART JS ================= --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"
        integrity="sha384-e6nUZLBkQ86NJ6TVVKAeSaK8jWa3NhkYWZFomE39AvDbQWeie9PlQqM3pmYW5d1g"
        crossorigin="anonymous"
        referrerpolicy="no-referrer"></script>

<script>
const isDark = document.documentElement.classList.contains('dark');
const gridColor = isDark ? 'rgba(75, 85, 99, 0.25)' : 'rgba(238, 240, 244, 1)';
const textColor = isDark ? '#94a3b8' : '#94a3b8';
const tooltipBg = isDark ? '#0f172a' : '#ffffff';
const tooltipTitle = isDark ? '#F9FAFB' : '#070740';
const tooltipBody = isDark ? '#cbd5e1' : '#334155';
const tooltipBorder = isDark ? '#1e293b' : '#e2e8f0';
const productsLabel = @json(__('products'));
const ordersCountLabel = @json(__('Orders: :count'));
const upFromLastMonthLabel = @json(__('Up +:percent% from last month'));
const downFromLastMonthLabel = @json(__('Down :percent% from last month'));
const noChangeLabel = @json(__('No change'));

@if(count($categoryNames) > 0)
const categoryCtx = document.getElementById('categoryChart').getContext('2d');
new Chart(categoryCtx, {
    type: 'doughnut',
    data: {
        labels: @json($categoryNames),
        datasets: [{
            data: @json($categoryCounts),
            backgroundColor: ['#818cf8', '#22d3ee', '#c084fc', '#fbbf24', '#34d399', '#f472b6', '#2dd4bf', '#fb923c'],
            borderWidth: 3,
            borderColor: '#070740',
            hoverOffset: 10,
            hoverBorderWidth: 4
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#0a0d3f', titleColor: '#ffffff', bodyColor: '#cbd5e1', borderColor: 'rgba(255,255,255,0.15)', borderWidth: 1,
                padding: 12, boxPadding: 6, usePointStyle: true,
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.parsed || 0;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((value / total) * 100).toFixed(1);
                        return `${label}: ${value} ${productsLabel} (${percentage}%)`;
                    }
                }
            }
        },
        animation: { animateRotate: true, animateScale: true, duration: 1200, easing: 'easeInOutQuart' },
        cutout: '72%'
    }
});
@endif

@if(count($monthLabels) > 0 && array_sum($monthCounts) > 0)
const ordersCtx = document.getElementById('ordersChart').getContext('2d');
new Chart(ordersCtx, {
    type: 'line',
    data: {
        labels: @json($monthLabels),
        datasets: [{
            label: @json(__('Orders')),
            data: @json($monthCounts),
            borderColor: (ctx) => {
                const g = ctx.chart.ctx.createLinearGradient(0, 0, ctx.chart.width, 0);
                g.addColorStop(0, '#22d3ee'); g.addColorStop(0.5, '#818cf8'); g.addColorStop(1, '#e879f9');
                return g;
            },
            backgroundColor: (ctx) => {
                const g = ctx.chart.ctx.createLinearGradient(0, 0, 0, 240);
                g.addColorStop(0, 'rgba(129, 140, 248, 0.45)'); g.addColorStop(1, 'rgba(34, 211, 238, 0)');
                return g;
            },
            fill: true, tension: 0.35,
            pointRadius: 0, pointHoverRadius: 7,
            pointHoverBackgroundColor: '#ffffff', pointHoverBorderColor: '#818cf8', pointHoverBorderWidth: 3,
            borderWidth: 3
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#0a0d3f', titleColor: '#ffffff', bodyColor: '#cbd5e1', borderColor: 'rgba(255,255,255,0.15)', borderWidth: 1,
                padding: 14, displayColors: false, boxPadding: 6,
                callbacks: {
                    title: function(context) { return `${context[0].label} {{ date('Y') }}`; },
                    label: function(context) { return ordersCountLabel.replace(':count', context.parsed.y); },
                    afterLabel: function(context) {
                        const currentValue = context.parsed.y;
                        const previousValue = context.dataset.data[context.dataIndex - 1] || currentValue;
                        const change = currentValue - previousValue;
                        const percentage = previousValue !== 0 ? ((change / previousValue) * 100).toFixed(1) : 0;
                        if (change > 0) return upFromLastMonthLabel.replace(':percent', percentage);
                        else if (change < 0) return downFromLastMonthLabel.replace(':percent', percentage);
                        return noChangeLabel;
                    }
                }
            }
        },
        scales: {
            x: { grid: { display: false }, ticks: { color: 'rgba(255,255,255,0.5)', font: { size: 11, weight: '600' }, padding: 8 } },
            y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.06)' }, border: { display: false }, ticks: { color: 'rgba(255,255,255,0.5)', font: { size: 11 }, padding: 8, precision: 0 } }
        },
        animation: { duration: 1200, easing: 'easeInOutQuart' }
    }
});
@endif

@if(count($stockTrendLabels) > 0 && array_sum(array_map('abs', $stockTrendValues)) > 0)
const stockTrendCtx = document.getElementById('stockTrendChart').getContext('2d');
new Chart(stockTrendCtx, {
    type: 'line',
    data: {
        labels: @json($stockTrendLabels),
        datasets: [{
            label: @json(__('Net Stock Movement')),
            data: @json($stockTrendValues),
            borderColor: '#10b981', backgroundColor: 'rgba(16, 185, 129, 0.12)',
            fill: true, tension: 0.35, borderWidth: 3,
            pointRadius: 0, pointHoverRadius: 5,
            pointHoverBackgroundColor: '#10b981', pointHoverBorderColor: '#ffffff', pointHoverBorderWidth: 2
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: { backgroundColor: tooltipBg, titleColor: tooltipTitle, bodyColor: tooltipBody, borderColor: tooltipBorder, borderWidth: 1, padding: 12 }
        },
        scales: {
            x: { grid: { display: false }, ticks: { color: textColor, font: { size: 11 } } },
            y: { beginAtZero: true, grid: { color: gridColor }, border: { display: false }, ticks: { color: textColor, font: { size: 11 } } }
        }
    }
});
@endif

@if(count($movementLabels) > 0 && (array_sum($movementInValues) > 0 || array_sum($movementOutValues) > 0))
const movementCtx = document.getElementById('movementChart').getContext('2d');
new Chart(movementCtx, {
    type: 'bar',
    data: {
        labels: @json($movementLabels),
        datasets: [
            { label: @json(__('Stock In')), data: @json($movementInValues), backgroundColor: 'rgba(16, 185, 129, 0.75)', borderColor: '#10b981', borderWidth: 1, borderRadius: 6 },
            { label: @json(__('Stock Out')), data: @json($movementOutValues), backgroundColor: 'rgba(244, 63, 94, 0.75)', borderColor: '#f43f5e', borderWidth: 1, borderRadius: 6 }
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
            legend: { position: 'top', labels: { color: textColor, usePointStyle: true, pointStyle: 'circle' } },
            tooltip: { backgroundColor: tooltipBg, titleColor: tooltipTitle, bodyColor: tooltipBody, borderColor: tooltipBorder, borderWidth: 1 }
        },
        scales: {
            x: { grid: { display: false }, ticks: { color: textColor, font: { size: 11 } } },
            y: { beginAtZero: true, grid: { color: gridColor }, border: { display: false }, ticks: { color: textColor, font: { size: 11 } } }
        }
    }
});
@endif
</script>

</x-app-layout>
