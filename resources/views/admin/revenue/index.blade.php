<x-app-layout>
<x-slot name="header">
    <span>{{ __('Revenue Analytics') }}</span>
</x-slot>

<div class="py-10">
    <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
        @php
            $dailyCollection = collect($dailyRevenue);
            $maxDailyAmount = max((float) $dailyCollection->max('amount'), 1);
            $avgDailyAmount = $dailyCollection->count() > 0
                ? (float) ($dailyCollection->sum('amount') / $dailyCollection->count())
                : 0.0;
            $dailyRevenueTotal = (float) $dailyCollection->sum('amount');
            $peakDay = $dailyCollection->sortByDesc('amount')->first();
            $chartPoints = $dailyCollection->count() > 30
                ? $dailyCollection->slice(-30)->values()
                : $dailyCollection->values();
            $topProductsCollection = collect($topProducts)->values();
            $topCustomersCollection = collect($topCustomers)->values();
            $topDealersCollection = collect($topDealers)->values();
            $topProduct = $topProductsCollection->first();
            $topCustomer = $topCustomersCollection->first();
            $topDealer = $topDealersCollection->first();
            $productRevenuePool = (float) $topProductsCollection->sum('revenue_total');
            $productUnitsPool = (int) $topProductsCollection->sum('units_sold');
            $topProductRevenueMax = max((float) $topProductsCollection->max('revenue_total'), 1);
            $customerRevenuePool = (float) $topCustomersCollection->sum('revenue_total');
            $customerOrdersPool = (int) $topCustomersCollection->sum('order_count');
            $topCustomerRevenueMax = max((float) $topCustomersCollection->max('revenue_total'), 1);
            $dealerRevenuePool = (float) $topDealersCollection->sum('revenue_total');
            $dealerOrdersPool = (int) $topDealersCollection->sum('order_count');
            $topDealerRevenueMax = max((float) $topDealersCollection->max('revenue_total'), 1);
            $chartDaysCount = $chartPoints->count();
            $heroTrendPoints = $chartPoints->count() > 7 ? $chartPoints->slice(-7)->values() : $chartPoints->values();
            $heroTrendMax = max((float) $heroTrendPoints->max('amount'), 1);
            $growthToneClass = $growthPercent > 0
                ? 'border-emerald-300/40 bg-emerald-400/15 text-emerald-50'
                : ($growthPercent < 0
                    ? 'border-rose-300/40 bg-rose-400/15 text-rose-50'
                    : 'border-white/15 bg-white/10 text-slate-100');
            $growthToneLabel = $growthPercent > 0 ? 'Growth Trend' : ($growthPercent < 0 ? 'Soft Trend' : 'Flat Trend');
            $rangeStateLabel = $customRange ? 'Custom Range Active' : 'Preset Range Active';
        @endphp

        <section class="relative overflow-hidden rounded-[2rem] border border-emerald-200/40 bg-[radial-gradient(circle_at_15%_20%,rgba(52,211,153,0.18),transparent_28%),radial-gradient(circle_at_85%_15%,rgba(16,185,129,0.18),transparent_24%),radial-gradient(circle_at_72%_80%,rgba(34,197,94,0.14),transparent_26%),linear-gradient(135deg,#020617_0%,#052e2b_45%,#14532d_100%)] p-6 text-white shadow-[0_30px_70px_rgba(15,23,42,0.42)] sm:p-8">
            <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-emerald-100/70 to-transparent"></div>
            <div class="pointer-events-none absolute -right-16 top-10 h-56 w-56 rounded-full border border-white/10 bg-emerald-200/10 blur-3xl"></div>
            <div class="pointer-events-none absolute -left-12 bottom-0 h-48 w-48 rounded-full bg-cyan-300/10 blur-3xl"></div>

            <div class="relative grid gap-6 xl:grid-cols-[1.4fr_0.9fr]">
                <div class="space-y-6">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="inline-flex items-center rounded-full border border-emerald-200/30 bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-emerald-50/90 backdrop-blur">{{ __('Revenue Command Center') }}</span>
                        <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold {{ $growthToneClass }}">{{ $growthToneLabel }}</span>
                    </div>

                    <div class="max-w-3xl">
                        <h1 class="text-3xl font-bold tracking-[-0.03em] sm:text-5xl">{{ __('Revenue Analytics') }}</h1>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-emerald-50/78 sm:text-base">
                            {{ __('Track revenue performance across the selected window, spot momentum shifts, and review who drives the highest value across products, customers, and dealers.') }}
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3">
                        <article class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-md">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-emerald-100/75">{{ __('Selected Revenue') }}</p>
                            <p class="mt-3 text-3xl font-bold text-white">{{ $currencyLabel }} {{ number_format($periodRevenue, $currencyDecimals) }}</p>
                            <p class="mt-1 text-xs text-emerald-50/70">{{ number_format(abs($growthPercent), 1) }}% vs previous period</p>
                        </article>
                        <article class="rounded-2xl border border-white/15 bg-slate-950/20 p-4 backdrop-blur-md">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-emerald-100/75">{{ __('Top Product') }}</p>
                            <p class="mt-3 truncate text-2xl font-bold text-white">{{ data_get($topProduct, 'name', __('N/A')) }}</p>
                            <p class="mt-1 text-xs text-emerald-50/70">{{ __('Best revenue contributor in range') }}</p>
                        </article>
                        <article class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-md">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-emerald-100/75">{{ __('Peak Day') }}</p>
                            <p class="mt-3 text-2xl font-bold text-white">{{ data_get($peakDay, 'label', '-') }}</p>
                            <p class="mt-1 text-xs text-emerald-50/70">{{ $currencyLabel }} {{ number_format((float) data_get($peakDay, 'amount', 0), $currencyDecimals) }}</p>
                        </article>
                    </div>

                    <div class="grid gap-4 lg:grid-cols-[1.05fr_0.95fr]">
                        <article class="rounded-3xl border border-white/15 bg-slate-950/20 p-5 backdrop-blur-md">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-100/75">{{ __('7-Day Pulse') }}</p>
                                    <h2 class="mt-1 text-lg font-semibold text-white">{{ __('Revenue momentum') }}</h2>
                                </div>
                                <span class="rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold text-emerald-50/80">{{ $chartDaysCount }} tracked days</span>
                            </div>

                            <div class="mt-5 flex h-32 items-end gap-2">
                                @foreach ($heroTrendPoints as $point)
                                    <div class="flex flex-1 flex-col items-center gap-2">
                                        <div class="flex h-24 w-full items-end">
                                            <div class="w-full rounded-t-2xl bg-gradient-to-t from-emerald-400 via-green-300 to-white/90 shadow-[0_8px_24px_rgba(16,185,129,0.35)]" style="height: {{ max(14, (int) round((((float) data_get($point, 'amount', 0)) / $heroTrendMax) * 100)) }}%"></div>
                                        </div>
                                        <span class="text-[10px] font-semibold uppercase tracking-[0.12em] text-emerald-50/55">{{ \Illuminate\Support\Str::limit((string) data_get($point, 'label', '-'), 3, '') }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </article>

                        <article class="rounded-3xl border border-white/15 bg-white/10 p-5 backdrop-blur-md">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-100/75">{{ __('Current Focus') }}</p>
                            <div class="mt-4 space-y-4">
                                <div class="rounded-2xl border border-white/10 bg-slate-950/20 p-4">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-emerald-100/75">{{ __('Date Window') }}</p>
                                    <p class="mt-2 text-xl font-bold text-white">{{ $start->format('Y-m-d') }} - {{ $end->format('Y-m-d') }}</p>
                                    <p class="mt-1 text-xs text-emerald-50/70">{{ $rangeStateLabel }}</p>
                                </div>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-emerald-100/75">{{ __('Daily Average') }}</p>
                                        <p class="mt-2 text-2xl font-bold text-white">{{ $currencyLabel }} {{ number_format($avgDailyAmount, $currencyDecimals) }}</p>
                                    </div>
                                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-emerald-100/75">{{ __('Top Customer') }}</p>
                                        <p class="mt-2 truncate text-sm font-semibold text-white">{{ data_get($topCustomer, 'name', __('N/A')) }}</p>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>

                <div class="grid gap-4">
                    <article class="rounded-3xl border border-white/15 bg-white/10 p-5 backdrop-blur-md">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-100/75">{{ __('Quick Filters') }}</p>
                        <h2 class="mt-2 text-2xl font-semibold text-white">{{ __('Refine the revenue window') }}</h2>
                        <p class="mt-2 text-sm leading-6 text-emerald-50/74">{{ __('Use preset ranges for fast review or switch to a custom date interval when you need a tighter commercial read.') }}</p>

                        <form method="GET" action="{{ route('admin.revenue.index') }}" class="mt-5">
                            <div class="inline-flex min-h-[3.1rem] w-full flex-wrap items-center gap-1 rounded-2xl border border-white/15 bg-slate-950/20 p-1.5 shadow-inner">
                                @foreach ($allowedDays as $option)
                                    <button
                                        type="submit"
                                        name="days"
                                        value="{{ $option }}"
                                        class="inline-flex h-9 min-w-[3.4rem] items-center justify-center rounded-xl px-3 text-sm font-semibold leading-none transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-300 {{ $days === $option ? 'bg-white text-slate-900 shadow-[0_4px_14px_rgba(255,255,255,0.18)]' : 'text-white/75 hover:bg-white/10 hover:text-white' }}"
                                    >
                                        {{ $option }}d
                                    </button>
                                @endforeach
                            </div>
                        </form>

                        <form method="GET" action="{{ route('admin.revenue.index') }}" class="mt-5 grid gap-3">
                            <input type="hidden" name="days" value="{{ $days }}">
                            <div class="grid gap-3 sm:grid-cols-2">
                                <label class="block">
                                    <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.12em] text-emerald-100/75">{{ __('From') }}</span>
                                    <input id="revenue_from" type="date" name="from" value="{{ $from }}" class="h-11 w-full rounded-2xl border border-white/15 bg-white/10 px-4 text-sm text-white outline-none transition focus:border-emerald-300 focus:ring-2 focus:ring-emerald-200/20">
                                </label>
                                <label class="block">
                                    <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.12em] text-emerald-100/75">{{ __('To') }}</span>
                                    <input id="revenue_to" type="date" name="to" value="{{ $to }}" class="h-11 w-full rounded-2xl border border-white/15 bg-white/10 px-4 text-sm text-white outline-none transition focus:border-emerald-300 focus:ring-2 focus:ring-emerald-200/20">
                                </label>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <button type="submit" class="h-11 rounded-2xl bg-white px-4 text-sm font-semibold text-slate-900 shadow-lg shadow-emerald-950/20 transition hover:bg-emerald-50">{{ __('Apply Range') }}</button>
                                <a href="{{ route('admin.revenue.index', ['days' => $days]) }}" class="inline-flex h-11 items-center justify-center rounded-2xl border border-white/15 bg-slate-950/20 px-4 text-sm font-semibold text-white transition hover:bg-white/10">{{ __('Reset') }}</a>
                            </div>
                        </form>
                    </article>

                    <article class="rounded-3xl border border-white/15 bg-slate-950/20 p-5 backdrop-blur-md">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-100/75">{{ __('Revenue Snapshot') }}</p>
                                <h3 class="mt-2 text-lg font-semibold text-white">{{ __('Commercial state') }}</h3>
                            </div>
                            <span class="rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold text-emerald-50/80">{{ $rangeStateLabel }}</span>
                        </div>

                        <div class="mt-5 space-y-3">
                            <div class="flex items-center justify-between rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                                <span class="text-sm text-emerald-50/72">{{ __('Lifetime Revenue') }}</span>
                                <span class="text-sm font-semibold text-white">{{ $currencyLabel }} {{ number_format($totalRevenue, $currencyDecimals) }}</span>
                            </div>
                            <div class="flex items-center justify-between rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                                <span class="text-sm text-emerald-50/72">{{ __('Today') }}</span>
                                <span class="text-sm font-semibold text-white">{{ $currencyLabel }} {{ number_format($todayRevenue, $currencyDecimals) }}</span>
                            </div>
                            <div class="flex items-center justify-between rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                                <span class="text-sm text-emerald-50/72">{{ __('Top Dealer') }}</span>
                                <span class="truncate pl-3 text-right text-sm font-semibold text-white">{{ data_get($topDealer, 'name', __('N/A')) }}</span>
                            </div>
                        </div>
                    </article>

                    <article class="rounded-3xl border border-emerald-200/20 bg-gradient-to-br from-white/12 to-emerald-300/10 p-5 backdrop-blur-md">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-100/75">{{ __('Why It Feels Better') }}</p>
                        <ul class="mt-4 space-y-3 text-sm text-emerald-50/78">
                            <li class="rounded-2xl border border-white/10 bg-slate-950/20 px-4 py-3">{{ __('A clear cockpit hierarchy puts the headline, revenue pulse, and filters in one place.') }}</li>
                            <li class="rounded-2xl border border-white/10 bg-slate-950/20 px-4 py-3">{{ __('The top section now carries commercial context instead of feeling like a plain filter bar.') }}</li>
                            <li class="rounded-2xl border border-white/10 bg-slate-950/20 px-4 py-3">{{ __('Tables and leaderboards below inherit the same premium visual language.') }}</li>
                        </ul>
                    </article>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="group relative overflow-hidden rounded-3xl border border-emerald-200/70 bg-[linear-gradient(145deg,rgba(236,253,245,0.98),rgba(209,250,229,0.9))] p-5 shadow-[0_18px_40px_rgba(16,185,129,0.12)] transition duration-200 hover:-translate-y-1 hover:shadow-[0_24px_50px_rgba(16,185,129,0.18)] dark:border-emerald-900/50 dark:bg-[linear-gradient(145deg,rgba(6,78,59,0.95),rgba(15,23,42,0.96))]">
                <div class="absolute -right-8 -top-8 h-24 w-24 rounded-full bg-emerald-300/30 blur-2xl"></div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-emerald-700">{{ data_get($statusCards, 'paid.label', 'Paid') }}</p>
                <p class="mt-2 text-2xl font-bold text-emerald-900">{{ $currencyLabel }} {{ number_format((float) data_get($statusCards, 'paid.amount', 0), $currencyDecimals) }}</p>
                <p class="mt-1 text-xs text-emerald-700">{{ number_format((int) data_get($statusCards, 'paid.orders', 0)) }} orders</p>
            </article>
            <article class="group relative overflow-hidden rounded-3xl border border-amber-200/70 bg-[linear-gradient(145deg,rgba(255,251,235,0.98),rgba(254,243,199,0.92))] p-5 shadow-[0_18px_40px_rgba(245,158,11,0.12)] transition duration-200 hover:-translate-y-1 hover:shadow-[0_24px_50px_rgba(245,158,11,0.18)] dark:border-amber-900/50 dark:bg-[linear-gradient(145deg,rgba(120,53,15,0.92),rgba(15,23,42,0.96))]">
                <div class="absolute -left-6 bottom-0 h-24 w-24 rounded-full bg-amber-300/25 blur-2xl"></div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-amber-700">{{ data_get($statusCards, 'pending.label', 'Pending') }}</p>
                <p class="mt-2 text-2xl font-bold text-amber-900">{{ $currencyLabel }} {{ number_format((float) data_get($statusCards, 'pending.amount', 0), $currencyDecimals) }}</p>
                <p class="mt-1 text-xs text-amber-700">{{ number_format((int) data_get($statusCards, 'pending.orders', 0)) }} orders</p>
            </article>
            <article class="group relative overflow-hidden rounded-3xl border border-rose-200/70 bg-[linear-gradient(145deg,rgba(255,241,242,0.98),rgba(255,228,230,0.92))] p-5 shadow-[0_18px_40px_rgba(244,63,94,0.12)] transition duration-200 hover:-translate-y-1 hover:shadow-[0_24px_50px_rgba(244,63,94,0.18)] dark:border-rose-900/50 dark:bg-[linear-gradient(145deg,rgba(127,29,29,0.92),rgba(15,23,42,0.96))]">
                <div class="absolute right-0 top-0 h-24 w-24 rounded-full bg-rose-300/20 blur-2xl"></div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-rose-700">{{ data_get($statusCards, 'cancelled.label', 'Cancelled') }}</p>
                <p class="mt-2 text-2xl font-bold text-rose-900">{{ $currencyLabel }} {{ number_format((float) data_get($statusCards, 'cancelled.amount', 0), $currencyDecimals) }}</p>
                <p class="mt-1 text-xs text-rose-700">{{ number_format((int) data_get($statusCards, 'cancelled.orders', 0)) }} orders</p>
            </article>
            <article class="group relative overflow-hidden rounded-3xl border border-violet-200/70 bg-[linear-gradient(145deg,rgba(245,243,255,0.98),rgba(237,233,254,0.92))] p-5 shadow-[0_18px_40px_rgba(139,92,246,0.12)] transition duration-200 hover:-translate-y-1 hover:shadow-[0_24px_50px_rgba(139,92,246,0.18)] dark:border-violet-900/50 dark:bg-[linear-gradient(145deg,rgba(76,29,149,0.92),rgba(15,23,42,0.96))]">
                <div class="absolute -right-4 bottom-0 h-24 w-24 rounded-full bg-violet-300/25 blur-2xl"></div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-violet-700">{{ data_get($statusCards, 'refunded.label', 'Refunded') }}</p>
                <p class="mt-2 text-2xl font-bold text-violet-900">{{ $currencyLabel }} {{ number_format((float) data_get($statusCards, 'refunded.amount', 0), $currencyDecimals) }}</p>
                <p class="mt-1 text-xs text-violet-700">{{ number_format((int) data_get($statusCards, 'refunded.orders', 0)) }} orders</p>
            </article>
        </section>

        <section class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <article class="overflow-hidden rounded-[2rem] border border-slate-200/80 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(248,250,252,0.98))] shadow-[0_22px_52px_rgba(15,23,42,0.08)] dark:border-slate-800 dark:bg-[linear-gradient(180deg,rgba(15,23,42,0.98),rgba(15,23,42,0.94))]">
                <div class="border-b border-slate-200/80 bg-[radial-gradient(circle_at_top_right,rgba(16,185,129,0.14),transparent_30%),linear-gradient(180deg,rgba(255,255,255,0.96),rgba(248,250,252,0.94))] px-5 py-5 dark:border-slate-800 dark:bg-[radial-gradient(circle_at_top_right,rgba(16,185,129,0.10),transparent_28%),linear-gradient(180deg,rgba(15,23,42,0.98),rgba(15,23,42,0.92))]">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ __('Revenue Stream') }}</p>
                        <h3 class="mt-1 text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('Daily Revenue') }}</h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Day-by-day totals for the selected window.') }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ $chartDaysCount }} days</span>
                        <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300">Avg {{ $currencyLabel }} {{ number_format($avgDailyAmount, $currencyDecimals) }}</span>
                    </div>
                </div>
                </div>
                <div class="p-5">
                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="rounded-[1.4rem] border border-emerald-200/70 bg-emerald-50/80 p-4 dark:border-emerald-900/60 dark:bg-emerald-950/20">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-emerald-700 dark:text-emerald-300">{{ __('Range Total') }}</p>
                        <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-slate-100">{{ $currencyLabel }} {{ number_format($dailyRevenueTotal, $currencyDecimals) }}</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Combined revenue for visible days') }}</p>
                    </div>
                    <div class="rounded-[1.4rem] border border-slate-200/90 bg-white p-4 dark:border-slate-700/80 dark:bg-slate-900/80">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Peak Session') }}</p>
                        <p class="mt-2 text-lg font-bold text-slate-900 dark:text-slate-100">{{ data_get($peakDay, 'label', '-') }}</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $currencyLabel }} {{ number_format((float) data_get($peakDay, 'amount', 0), $currencyDecimals) }}</p>
                    </div>
                    <div class="rounded-[1.4rem] border border-slate-200/90 bg-white p-4 dark:border-slate-700/80 dark:bg-slate-900/80">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Daily Average') }}</p>
                        <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-slate-100">{{ $currencyLabel }} {{ number_format($avgDailyAmount, $currencyDecimals) }}</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $chartDaysCount }} tracked entries</p>
                    </div>
                </div>
                <div class="mt-4 rounded-[1.5rem] border border-slate-200/90 bg-gradient-to-b from-slate-50 to-white p-4 shadow-sm dark:border-slate-700/80 dark:from-slate-900/80 dark:to-slate-900">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">Trend (Last {{ $chartPoints->count() }} Days)</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Max: {{ $currencyLabel }} {{ number_format($maxDailyAmount, $currencyDecimals) }}</p>
                    </div>
                    <div class="h-32 overflow-hidden rounded-[1.5rem] border border-slate-200/90 bg-white px-2 py-2 dark:border-slate-700/80 dark:bg-slate-900">
                        <div class="flex h-full items-end gap-1">
                            @foreach ($chartPoints as $point)
                                @php
                                    $heightPercent = max(4, (int) round((((float) data_get($point, 'amount', 0)) / $maxDailyAmount) * 100));
                                @endphp
                                <div
                                    class="group relative flex-1 rounded-t bg-gradient-to-t from-emerald-600/90 to-emerald-400/80 transition hover:from-emerald-600 hover:to-emerald-400"
                                    style="height: {{ $heightPercent }}%"
                                    title="{{ data_get($point, 'label') }} - {{ $currencyLabel }} {{ number_format((float) data_get($point, 'amount', 0), $currencyDecimals) }}"
                                ></div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="mt-4 max-h-[26rem] overflow-y-auto overflow-x-hidden rounded-[1.5rem] border border-slate-200/90 dark:border-slate-700/80">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700 text-sm">
                        <thead class="sticky top-0 bg-slate-50/95 dark:bg-slate-800/90 backdrop-blur">
                            <tr>
                                <th class="px-5 py-4 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-300">{{ __('Date') }}</th>
                                <th class="px-5 py-4 text-right text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-300">{{ __('Revenue') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100/80 dark:divide-slate-800/80 bg-white dark:bg-slate-900">
                            @foreach ($dailyRevenue as $row)
                                @php
                                    $dailyShare = max(6, (int) round((((float) $row['amount']) / $maxDailyAmount) * 100));
                                @endphp
                                <tr class="group hover:bg-[linear-gradient(90deg,rgba(16,185,129,0.08),transparent)] dark:hover:bg-slate-800/60">
                                    <td class="px-5 py-4 text-slate-700 dark:text-slate-300">
                                        <div class="flex items-center gap-3">
                                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                                {{ \Illuminate\Support\Str::limit((string) $row['label'], 3, '') }}
                                            </span>
                                            <div>
                                                <div class="font-semibold text-slate-900 dark:text-slate-100">{{ $row['label'] }}</div>
                                                <div class="text-xs text-slate-500 dark:text-slate-400">{{ __('Daily close') }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex items-center justify-end gap-3">
                                            <div class="hidden w-24 sm:block">
                                                <div class="h-2 rounded-full bg-slate-100 dark:bg-slate-800">
                                                    <div class="h-2 rounded-full bg-gradient-to-r from-emerald-500 via-teal-400 to-cyan-400" style="width: {{ $dailyShare }}%"></div>
                                                </div>
                                            </div>
                                            <span class="text-right font-semibold text-slate-900 dark:text-slate-100">{{ $currencyLabel }} {{ number_format((float) $row['amount'], $currencyDecimals) }}</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                </div>
            </article>

            <article class="overflow-hidden rounded-[2rem] border border-slate-200/80 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(248,250,252,0.98))] shadow-[0_22px_52px_rgba(15,23,42,0.08)] dark:border-slate-800 dark:bg-[linear-gradient(180deg,rgba(15,23,42,0.98),rgba(15,23,42,0.94))]">
                <div class="border-b border-slate-200/80 bg-[radial-gradient(circle_at_top_right,rgba(16,185,129,0.14),transparent_30%),linear-gradient(180deg,rgba(255,255,255,0.96),rgba(248,250,252,0.94))] px-5 py-5 dark:border-slate-800 dark:bg-[radial-gradient(circle_at_top_right,rgba(16,185,129,0.10),transparent_28%),linear-gradient(180deg,rgba(15,23,42,0.98),rgba(15,23,42,0.92))]">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ __('Leaderboard') }}</p>
                        <h3 class="mt-1 text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('Top Products By Revenue') }}</h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Best contributors in the selected window.') }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ number_format(collect($topProducts)->count()) }} rows</span>
                        <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300">{{ data_get($topProduct, 'name', __('N/A')) }}</span>
                    </div>
                </div>
                </div>
                <div class="p-5">
                <div class="mb-4 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-[1.35rem] border border-emerald-200/70 bg-emerald-50/80 p-4 dark:border-emerald-900/60 dark:bg-emerald-950/20">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-emerald-700 dark:text-emerald-300">{{ __('Revenue Pool') }}</p>
                        <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-slate-100">{{ $currencyLabel }} {{ number_format($productRevenuePool, $currencyDecimals) }}</p>
                    </div>
                    <div class="rounded-[1.35rem] border border-slate-200/90 bg-white p-4 dark:border-slate-700/80 dark:bg-slate-900/80">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Units Sold') }}</p>
                        <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-slate-100">{{ number_format($productUnitsPool) }}</p>
                    </div>
                    <div class="rounded-[1.35rem] border border-slate-200/90 bg-white p-4 dark:border-slate-700/80 dark:bg-slate-900/80">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Leader Share') }}</p>
                        <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-slate-100">{{ $productRevenuePool > 0 ? number_format((((float) data_get($topProduct, 'revenue_total', 0)) / $productRevenuePool) * 100, 1) : '0.0' }}%</p>
                    </div>
                </div>
                <div class="max-h-[26rem] overflow-y-auto overflow-x-hidden rounded-[1.5rem] border border-slate-200/90 dark:border-slate-700/80">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700 text-sm">
                        <thead class="sticky top-0 bg-slate-50/95 dark:bg-slate-800/90 backdrop-blur">
                            <tr>
                                <th class="w-16 px-5 py-4 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-300">#</th>
                                <th class="px-5 py-4 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-300">{{ __('Product') }}</th>
                                <th class="px-5 py-4 text-right text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-300">{{ __('Units') }}</th>
                                <th class="px-5 py-4 text-right text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-300">{{ __('Revenue') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100/80 dark:divide-slate-800/80 bg-white dark:bg-slate-900">
                            @forelse ($topProducts as $index => $product)
                                @php
                                    $productRevenueShare = max(6, (int) round((((float) $product->revenue_total) / $topProductRevenueMax) * 100));
                                    $rankTone = $index === 0
                                        ? 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/60 dark:bg-amber-950/20 dark:text-amber-300'
                                        : ($index === 1
                                            ? 'border-slate-300 bg-slate-100 text-slate-700 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200'
                                            : 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/20 dark:text-emerald-300');
                                @endphp
                                <tr class="group hover:bg-[linear-gradient(90deg,rgba(16,185,129,0.08),transparent)] dark:hover:bg-slate-800/60">
                                    <td class="px-5 py-4">
                                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl border text-xs font-semibold {{ $rankTone }}">
                                            {{ $index + 1 }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 font-medium text-slate-700 dark:text-slate-300">
                                        <div class="min-w-0">
                                            <div class="truncate font-semibold text-slate-900 dark:text-slate-100">{{ $product->name }}</div>
                                            <div class="mt-2 h-1.5 rounded-full bg-slate-100 dark:bg-slate-800">
                                                <div class="h-1.5 rounded-full bg-gradient-to-r from-emerald-500 via-teal-400 to-cyan-400" style="width: {{ $productRevenueShare }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-right text-slate-700 dark:text-slate-300">
                                        <span class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ number_format((int) $product->units_sold) }}</span>
                                    </td>
                                    <td class="px-5 py-4 text-right font-semibold text-slate-900 dark:text-slate-100">{{ $currencyLabel }} {{ number_format((float) $product->revenue_total, $currencyDecimals) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-10 text-center"><div class="mx-auto max-w-sm rounded-[1.6rem] border border-dashed border-slate-200 bg-slate-50/90 px-6 py-6 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-800/70 dark:text-slate-300">{{ __('No revenue data in this period.') }}</div></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                </div>
            </article>
        </section>

        <section class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <article class="overflow-hidden rounded-[2rem] border border-slate-200/80 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(248,250,252,0.98))] shadow-[0_22px_52px_rgba(15,23,42,0.08)] xl:col-span-1 dark:border-slate-800 dark:bg-[linear-gradient(180deg,rgba(15,23,42,0.98),rgba(15,23,42,0.94))]">
                <div class="border-b border-slate-200/80 bg-[radial-gradient(circle_at_top_right,rgba(16,185,129,0.14),transparent_30%),linear-gradient(180deg,rgba(255,255,255,0.96),rgba(248,250,252,0.94))] px-5 py-5 dark:border-slate-800 dark:bg-[radial-gradient(circle_at_top_right,rgba(16,185,129,0.10),transparent_28%),linear-gradient(180deg,rgba(15,23,42,0.98),rgba(15,23,42,0.92))]">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ __('Accounts') }}</p>
                        <h3 class="mt-1 text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('Top Customers By Revenue') }}</h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Highest value customer accounts.') }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ number_format(collect($topCustomers)->count()) }} rows</span>
                        <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300 truncate max-w-[220px]">{{ data_get($topCustomer, 'name', __('N/A')) }}</span>
                    </div>
                </div>
                </div>
                <div class="p-5">
                <div class="mb-4 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-[1.35rem] border border-emerald-200/70 bg-emerald-50/80 p-4 dark:border-emerald-900/60 dark:bg-emerald-950/20">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-emerald-700 dark:text-emerald-300">{{ __('Revenue Pool') }}</p>
                        <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-slate-100">{{ $currencyLabel }} {{ number_format($customerRevenuePool, $currencyDecimals) }}</p>
                    </div>
                    <div class="rounded-[1.35rem] border border-slate-200/90 bg-white p-4 dark:border-slate-700/80 dark:bg-slate-900/80">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Orders') }}</p>
                        <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-slate-100">{{ number_format($customerOrdersPool) }}</p>
                    </div>
                    <div class="rounded-[1.35rem] border border-slate-200/90 bg-white p-4 dark:border-slate-700/80 dark:bg-slate-900/80">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Leading Share') }}</p>
                        <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-slate-100">{{ $customerRevenuePool > 0 ? number_format((((float) data_get($topCustomer, 'revenue_total', 0)) / $customerRevenuePool) * 100, 1) : '0.0' }}%</p>
                    </div>
                </div>
                <div class="max-h-[24rem] overflow-y-auto overflow-x-hidden rounded-[1.5rem] border border-slate-200/90 dark:border-slate-700/80">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700 text-sm">
                        <thead class="sticky top-0 bg-slate-50/95 dark:bg-slate-800/90 backdrop-blur">
                            <tr>
                                <th class="w-14 px-5 py-4 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-300">#</th>
                                <th class="px-5 py-4 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-300">{{ __('Customer') }}</th>
                                <th class="px-5 py-4 text-right text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-300">{{ __('Orders') }}</th>
                                <th class="px-5 py-4 text-right text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-300">{{ __('Revenue') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100/80 dark:divide-slate-800/80 bg-white dark:bg-slate-900">
                            @forelse ($topCustomers as $index => $customer)
                                @php
                                    $customerRevenueShare = max(6, (int) round((((float) $customer->revenue_total) / $topCustomerRevenueMax) * 100));
                                    $customerRankTone = $index === 0
                                        ? 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/60 dark:bg-amber-950/20 dark:text-amber-300'
                                        : ($index === 1
                                            ? 'border-slate-300 bg-slate-100 text-slate-700 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200'
                                            : 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/20 dark:text-emerald-300');
                                @endphp
                                <tr class="group hover:bg-[linear-gradient(90deg,rgba(16,185,129,0.08),transparent)] dark:hover:bg-slate-800/60">
                                    <td class="px-5 py-3">
                                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl border text-xs font-semibold {{ $customerRankTone }}">
                                            {{ $index + 1 }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-slate-700 dark:text-slate-300">
                                        <div class="min-w-0">
                                            <div class="truncate font-semibold text-slate-800 dark:text-slate-100">
                                                @if (Route::has('admin.users.show') && auth()->user()?->can('manage-users'))
                                                    <a href="{{ route('admin.users.show', $customer->id) }}" class="inline-flex max-w-full items-center gap-1 text-slate-800 dark:text-slate-100 transition hover:text-indigo-600">
                                                        <span class="truncate">{{ $customer->name }}</span>
                                                        <i class="fas fa-arrow-up-right-from-square text-[11px]"></i>
                                                    </a>
                                                @else
                                                    {{ $customer->name }}
                                                @endif
                                            </div>
                                            <div class="mt-2 h-1.5 rounded-full bg-slate-100 dark:bg-slate-800">
                                                <div class="h-1.5 rounded-full bg-gradient-to-r from-emerald-500 via-teal-400 to-cyan-400" style="width: {{ $customerRevenueShare }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3 text-right font-medium text-slate-700 dark:text-slate-300">{{ number_format((int) $customer->order_count) }}</td>
                                    <td class="px-5 py-3 text-right font-semibold text-slate-900 dark:text-slate-100">{{ $currencyLabel }} {{ number_format((float) $customer->revenue_total, $currencyDecimals) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-12 text-center">
                                        <div class="mx-auto max-w-sm rounded-2xl border border-dashed border-slate-200 bg-slate-50/90 px-6 py-6 dark:border-slate-700 dark:bg-slate-800/70">
                                            <div class="text-sm font-medium text-slate-600 dark:text-slate-300">{{ __('No customer revenue data.') }}</div>
                                            <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Once paid orders exist, top customers will appear here.') }}</div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                </div>
            </article>

            <article class="overflow-hidden rounded-[2rem] border border-slate-200/80 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(248,250,252,0.98))] shadow-[0_22px_52px_rgba(15,23,42,0.08)] xl:col-span-1 dark:border-slate-800 dark:bg-[linear-gradient(180deg,rgba(15,23,42,0.98),rgba(15,23,42,0.94))]">
                <div class="border-b border-slate-200/80 bg-[radial-gradient(circle_at_top_right,rgba(16,185,129,0.14),transparent_30%),linear-gradient(180deg,rgba(255,255,255,0.96),rgba(248,250,252,0.94))] px-5 py-5 dark:border-slate-800 dark:bg-[radial-gradient(circle_at_top_right,rgba(16,185,129,0.10),transparent_28%),linear-gradient(180deg,rgba(15,23,42,0.98),rgba(15,23,42,0.92))]">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ __('Dealer Network') }}</p>
                        <h3 class="mt-1 text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('Top Dealers By Revenue') }}</h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Dealer performance in selected window.') }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ number_format(collect($topDealers)->count()) }} rows</span>
                        <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300 truncate max-w-[220px]">{{ data_get($topDealer, 'name', __('N/A')) }}</span>
                    </div>
                </div>
                </div>
                <div class="p-5">
                <div class="mb-4 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-[1.35rem] border border-emerald-200/70 bg-emerald-50/80 p-4 dark:border-emerald-900/60 dark:bg-emerald-950/20">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-emerald-700 dark:text-emerald-300">{{ __('Revenue Pool') }}</p>
                        <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-slate-100">{{ $currencyLabel }} {{ number_format($dealerRevenuePool, $currencyDecimals) }}</p>
                    </div>
                    <div class="rounded-[1.35rem] border border-slate-200/90 bg-white p-4 dark:border-slate-700/80 dark:bg-slate-900/80">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Orders') }}</p>
                        <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-slate-100">{{ number_format($dealerOrdersPool) }}</p>
                    </div>
                    <div class="rounded-[1.35rem] border border-slate-200/90 bg-white p-4 dark:border-slate-700/80 dark:bg-slate-900/80">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Leading Share') }}</p>
                        <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-slate-100">{{ $dealerRevenuePool > 0 ? number_format((((float) data_get($topDealer, 'revenue_total', 0)) / $dealerRevenuePool) * 100, 1) : '0.0' }}%</p>
                    </div>
                </div>
                <div class="max-h-[24rem] overflow-y-auto overflow-x-hidden rounded-[1.5rem] border border-slate-200/90 dark:border-slate-700/80">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700 text-sm">
                        <thead class="sticky top-0 bg-slate-50/95 dark:bg-slate-800/90 backdrop-blur">
                            <tr>
                                <th class="w-14 px-5 py-4 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-300">#</th>
                                <th class="px-5 py-4 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-300">{{ __('Dealer') }}</th>
                                <th class="px-5 py-4 text-right text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-300">{{ __('Orders') }}</th>
                                <th class="px-5 py-4 text-right text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-300">{{ __('Revenue') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100/80 dark:divide-slate-800/80 bg-white dark:bg-slate-900">
                            @forelse ($topDealers as $index => $dealer)
                                @php
                                    $dealerRevenueShare = max(6, (int) round((((float) $dealer->revenue_total) / $topDealerRevenueMax) * 100));
                                    $dealerRankTone = $index === 0
                                        ? 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/60 dark:bg-amber-950/20 dark:text-amber-300'
                                        : ($index === 1
                                            ? 'border-slate-300 bg-slate-100 text-slate-700 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200'
                                            : 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/20 dark:text-emerald-300');
                                @endphp
                                <tr class="group hover:bg-[linear-gradient(90deg,rgba(16,185,129,0.08),transparent)] dark:hover:bg-slate-800/60">
                                    <td class="px-5 py-3">
                                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl border text-xs font-semibold {{ $dealerRankTone }}">
                                            {{ $index + 1 }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-slate-700 dark:text-slate-300">
                                        <div class="min-w-0">
                                            <div class="truncate font-semibold text-slate-800 dark:text-slate-100">{{ $dealer->name }}</div>
                                            <div class="mt-2 h-1.5 rounded-full bg-slate-100 dark:bg-slate-800">
                                                <div class="h-1.5 rounded-full bg-gradient-to-r from-emerald-500 via-teal-400 to-cyan-400" style="width: {{ $dealerRevenueShare }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3 text-right font-medium text-slate-700 dark:text-slate-300">{{ number_format((int) $dealer->order_count) }}</td>
                                    <td class="px-5 py-3 text-right font-semibold text-slate-900 dark:text-slate-100">{{ $currencyLabel }} {{ number_format((float) $dealer->revenue_total, $currencyDecimals) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-12 text-center">
                                        <div class="mx-auto max-w-sm rounded-2xl border border-dashed border-slate-200 bg-slate-50/90 px-6 py-6 dark:border-slate-700 dark:bg-slate-800/70">
                                            <div class="text-sm font-medium text-slate-600 dark:text-slate-300">{{ __('No dealer revenue data.') }}</div>
                                            <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Once dealer sales appear, top dealers will be listed here.') }}</div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                </div>
            </article>
        </section>

        @php
            $recentOrdersCollection = collect($recentPaidOrders);
            $recentOrdersCount = $recentOrdersCollection->count();
            $recentOrdersTotal = (float) $recentOrdersCollection->sum('total_amount');
            $recentOrdersAverage = $recentOrdersCount > 0 ? (float) ($recentOrdersTotal / $recentOrdersCount) : 0.0;
            $latestPaidOrder = $recentOrdersCollection->first();
        @endphp
        <section class="relative overflow-hidden rounded-[2rem] border border-slate-200/80 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(248,250,252,0.98))] shadow-[0_22px_52px_rgba(15,23,42,0.08)] dark:border-slate-800 dark:bg-[linear-gradient(180deg,rgba(15,23,42,0.98),rgba(15,23,42,0.94))]">
            <div class="pointer-events-none absolute -right-16 -top-16 h-40 w-40 rounded-full bg-emerald-100/70 blur-3xl"></div>
            <div class="pointer-events-none absolute -left-10 bottom-0 h-28 w-28 rounded-full bg-cyan-100/50 blur-2xl"></div>
            <div class="relative border-b border-slate-200/80 bg-[radial-gradient(circle_at_top_right,rgba(16,185,129,0.14),transparent_30%),linear-gradient(180deg,rgba(255,255,255,0.96),rgba(248,250,252,0.94))] px-5 py-5 dark:border-slate-800 dark:bg-[radial-gradient(circle_at_top_right,rgba(16,185,129,0.10),transparent_28%),linear-gradient(180deg,rgba(15,23,42,0.98),rgba(15,23,42,0.92))] sm:px-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ __('Order Ledger') }}</p>
                        <h3 class="mt-1 text-xl font-semibold tracking-[-0.01em] text-slate-900 dark:text-slate-100">{{ __('Recent Paid Orders') }}</h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Latest completed and delivered transactions.') }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center rounded-full border border-emerald-200/80 bg-emerald-50/90 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-emerald-700">
                            {{ __('Completed & Delivered') }}
                        </span>
                        <span class="inline-flex items-center rounded-full border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-600 dark:text-slate-300">
                            {{ number_format($recentOrdersCount) }} Orders
                        </span>
                        <span class="inline-flex items-center rounded-full border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-600 dark:text-slate-300">
                            {{ $currencyLabel }} {{ number_format($recentOrdersTotal, $currencyDecimals) }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="relative p-5 sm:p-6">
            <div class="mb-4 grid gap-3 sm:grid-cols-3">
                <div class="rounded-[1.35rem] border border-emerald-200/70 bg-emerald-50/80 p-4 dark:border-emerald-900/60 dark:bg-emerald-950/20">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-emerald-700 dark:text-emerald-300">{{ __('Average Ticket') }}</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-slate-100">{{ $currencyLabel }} {{ number_format($recentOrdersAverage, $currencyDecimals) }}</p>
                </div>
                <div class="rounded-[1.35rem] border border-slate-200/90 bg-white p-4 dark:border-slate-700/80 dark:bg-slate-900/80">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Latest Ledger Entry') }}</p>
                    <p class="mt-2 text-lg font-bold text-slate-900 dark:text-slate-100">{{ optional(data_get($latestPaidOrder, 'created_at'))->format('Y-m-d H:i') ?? '-' }}</p>
                </div>
                <div class="rounded-[1.35rem] border border-slate-200/90 bg-white p-4 dark:border-slate-700/80 dark:bg-slate-900/80">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Captured Volume') }}</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-slate-100">{{ $currencyLabel }} {{ number_format($recentOrdersTotal, $currencyDecimals) }}</p>
                </div>
            </div>
            <div class="h-[26rem] overflow-y-auto overflow-x-hidden rounded-[1.5rem] border border-slate-200/90 dark:border-slate-700/80 bg-white dark:bg-slate-900/95 shadow-inner">
                <table class="w-full table-fixed divide-y divide-slate-200 dark:divide-slate-700 text-sm">
                    <thead class="sticky top-0 z-10 bg-slate-50/95 dark:bg-slate-800/95 backdrop-blur">
                        <tr>
                            <th class="w-24 px-5 py-4 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ __('Order') }}</th>
                            <th class="px-5 py-4 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ __('Customer') }}</th>
                            <th class="w-32 px-5 py-4 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ __('Status') }}</th>
                            <th class="w-40 px-5 py-4 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ __('Date') }}</th>
                            <th class="w-40 px-5 py-4 text-right text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ __('Amount') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100/80 dark:divide-slate-800/80 bg-white dark:bg-slate-900">
                        @forelse ($recentPaidOrders as $order)
                            <tr class="group transition hover:bg-[linear-gradient(90deg,rgba(16,185,129,0.08),transparent)] dark:hover:bg-slate-800/60">
                                <td class="px-5 py-4">
                                    <div class="space-y-2">
                                        <span class="inline-flex rounded-xl border border-slate-200 bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">#{{ $order->id }}</span>
                                        <div class="text-[11px] uppercase tracking-[0.12em] text-slate-400 dark:text-slate-500">{{ __('Paid ledger') }}</div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-slate-700 dark:text-slate-300">
                                    <span class="block truncate font-semibold text-slate-800 dark:text-slate-100">{{ $order->user?->name ?? 'Guest' }}</span>
                                    <span class="mt-1 block text-xs text-slate-500 dark:text-slate-400">{{ __('Settlement confirmed') }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/20 dark:text-emerald-300">{{ ucfirst($order->status) }}</span>
                                </td>
                                <td class="px-5 py-4 text-xs font-medium text-slate-600 dark:text-slate-300">
                                    <div>{{ optional($order->created_at)->format('Y-m-d') }}</div>
                                    <div class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">{{ optional($order->created_at)->format('H:i') }}</div>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="inline-flex flex-col items-end rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 dark:border-slate-700 dark:bg-slate-800/80">
                                        <span class="font-semibold text-slate-900 dark:text-slate-100">{{ $currencyLabel }} {{ number_format((float) $order->total_amount, $currencyDecimals) }}</span>
                                        <span class="text-[11px] uppercase tracking-[0.12em] text-slate-400 dark:text-slate-500">{{ __('Gross') }}</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-12 text-center">
                                    <div class="mx-auto max-w-sm rounded-[1.6rem] border border-dashed border-slate-200 bg-slate-50/90 px-6 py-6 dark:border-slate-700 dark:bg-slate-800/70">
                                        <div class="text-sm font-medium text-slate-600 dark:text-slate-300">{{ __('No paid orders found.') }}</div>
                                        <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('When completed or delivered orders appear, they will be listed here.') }}</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            </div>
        </section>
    </div>
</div>
</x-app-layout>


