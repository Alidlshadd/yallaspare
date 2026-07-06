<x-app-layout>
<x-slot name="header">
    <span>{{ __('Revenue Analytics') }}</span>
</x-slot>

<div class="py-10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        @php
            $dailyCollection = collect($dailyRevenue);
            $maxDailyAmount = max((float) $dailyCollection->max('amount'), 1);
            $avgDailyAmount = $dailyCollection->count() > 0
                ? (float) ($dailyCollection->sum('amount') / $dailyCollection->count())
                : 0.0;
            $dailyRevenueTotal = (float) $dailyCollection->sum('amount');
            $peakDay = $dailyCollection->sortByDesc('amount')->first();
            $peakDate = (string) data_get($peakDay, 'date', '');
            $chartPoints = $dailyCollection->count() > 30
                ? $dailyCollection->slice(-30)->values()
                : $dailyCollection->values();
            $chartDaysCount = $chartPoints->count();

            $topProductsCollection = collect($topProducts)->values();
            $topCustomersCollection = collect($topCustomers)->values();
            $topDealersCollection = collect($topDealers)->values();
            $topProduct = $topProductsCollection->first();
            $topCustomer = $topCustomersCollection->first();
            $topDealer = $topDealersCollection->first();
            $productRevenuePool = (float) $topProductsCollection->sum('revenue_total');
            $productUnitsPool = (int) $topProductsCollection->sum('units_sold');
            $customerRevenuePool = (float) $topCustomersCollection->sum('revenue_total');
            $customerOrdersPool = (int) $topCustomersCollection->sum('order_count');
            $dealerRevenuePool = (float) $topDealersCollection->sum('revenue_total');
            $dealerOrdersPool = (int) $topDealersCollection->sum('order_count');
            $topProductRevenueMax = max((float) $topProductsCollection->max('revenue_total'), 1);
            $topCustomerRevenueMax = max((float) $topCustomersCollection->max('revenue_total'), 1);
            $topDealerRevenueMax = max((float) $topDealersCollection->max('revenue_total'), 1);
            $productLeaderShare = $productRevenuePool > 0 ? (((float) data_get($topProduct, 'revenue_total', 0)) / $productRevenuePool) * 100 : 0.0;
            $customerLeaderShare = $customerRevenuePool > 0 ? (((float) data_get($topCustomer, 'revenue_total', 0)) / $customerRevenuePool) * 100 : 0.0;
            $dealerLeaderShare = $dealerRevenuePool > 0 ? (((float) data_get($topDealer, 'revenue_total', 0)) / $dealerRevenuePool) * 100 : 0.0;

            $paidAmount = (float) data_get($statusCards, 'paid.amount', 0);
            $pendingAmount = (float) data_get($statusCards, 'pending.amount', 0);
            $cancelledAmount = (float) data_get($statusCards, 'cancelled.amount', 0);
            $refundedAmount = (float) data_get($statusCards, 'refunded.amount', 0);
            $paidRate = (float) data_get($conversionSummary, 'paid_rate', 0);
            $cancelRate = (float) data_get($conversionSummary, 'cancel_rate', 0);
            $windowOrders = (int) data_get($conversionSummary, 'total_orders', 0);

            $growthUp = $growthPercent > 0;
            $growthFlat = $growthPercent == 0.0;
            $growthClass = $growthUp ? 'text-[#34d399]' : ($growthFlat ? 'text-[#7c84b3]' : 'text-[#fb7185]');
            $growthArrow = $growthUp ? '&#9650;' : ($growthFlat ? '&#9644;' : '&#9660;');

            $recentOrdersCollection = collect($recentPaidOrders);
            $recentOrdersTotal = (float) $recentOrdersCollection->sum('total_amount');
            $recentOrdersAverage = $recentOrdersCollection->count() > 0
                ? (float) ($recentOrdersTotal / $recentOrdersCollection->count())
                : 0.0;

            $fmt = fn ($value) => number_format((float) $value, $currencyDecimals);
        @endphp

        <section class="overflow-hidden rounded-2xl border border-[#23246b] bg-[linear-gradient(160deg,#04042a_0%,#060634_55%,#0a0d3f_100%)] font-mono shadow-[0_28px_70px_rgba(2,2,20,0.6)]">

            {{-- ===== Terminal top bar ===== --}}
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#23246b] bg-[#03031e]/80 px-4 py-2.5 text-[13px] tracking-[0.14em] text-[#7c84b3]">
                <span class="font-semibold text-[#c0c5e8]">
                    <span class="text-[#fbbf24]">&#9679;</span>
                    YALLA SPARE &mdash; {{ strtoupper(__('Revenue')) }} TERMINAL
                </span>
                <span class="uppercase">
                    {{ __('Window') }}: <span class="text-[#f2f3ff]">{{ $start->format('M d') }} &rarr; {{ $end->format('M d, Y') }}</span>
                    &middot; <span class="text-[#fbbf24]">{{ $rangeDays }}D</span>
                    &middot; <span class="text-[#f2f3ff]">{{ $now->format('H:i') }}</span>
                </span>
            </div>

            {{-- ===== Ticker strip ===== --}}
            <div class="flex gap-7 overflow-x-auto whitespace-nowrap border-b border-[#23246b] bg-[#03031e]/60 px-4 py-2.5 text-[13px] text-[#7c84b3]">
                <span class="uppercase tracking-[0.08em]">{{ __('Today') }} <b class="ml-1 font-semibold text-[#f2f3ff]">{{ $currencyLabel }} {{ $fmt($todayRevenue) }}</b></span>
                <span class="uppercase tracking-[0.08em]">{{ __('Period') }} <b class="ml-1 font-semibold text-[#f2f3ff]">{{ $currencyLabel }} {{ $fmt($periodRevenue) }}</b> <span class="{{ $growthClass }}">{!! $growthArrow !!} {{ number_format(abs($growthPercent), 1) }}%</span></span>
                <span class="uppercase tracking-[0.08em]">AOV <b class="ml-1 font-semibold text-[#f2f3ff]">{{ $currencyLabel }} {{ $fmt($averageOrderValue) }}</b></span>
                <span class="uppercase tracking-[0.08em]">{{ __('Paid Rate') }} <b class="ml-1 font-semibold text-[#34d399]">{{ number_format($paidRate, 1) }}%</b></span>
                <span class="uppercase tracking-[0.08em]">{{ __('Lifetime') }} <b class="ml-1 font-semibold text-[#fbbf24]">{{ $currencyLabel }} {{ $fmt($totalRevenue) }}</b></span>
                <span class="uppercase tracking-[0.08em]">{{ __('Refunds') }} <b class="ml-1 font-semibold text-[#a78bfa]">{{ $currencyLabel }} {{ $fmt($refundedAmount) }}</b></span>
            </div>

            {{-- ===== Filter row ===== --}}
            <div class="flex flex-wrap items-center gap-3 border-b border-[#23246b] px-4 py-3">
                <form method="GET" action="{{ route('admin.revenue.index') }}" class="flex items-center gap-1 rounded-lg border border-[#23246b] bg-[#0a0a3a] p-1">
                    @foreach ($allowedDays as $option)
                        <button
                            type="submit"
                            name="days"
                            value="{{ $option }}"
                            class="rounded-md px-4 py-1.5 text-[13px] font-semibold tracking-[0.1em] transition focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-[#fbbf24] {{ ($days === $option && !$customRange) ? 'bg-gradient-to-b from-[#fbbf24] to-[#f59e0b] text-[#221703] shadow-[0_2px_10px_rgba(251,191,36,0.35)]' : 'text-[#7c84b3] hover:bg-white/5 hover:text-[#e6e9ff]' }}"
                        >{{ $option }}D</button>
                    @endforeach
                </form>

                <form method="GET" action="{{ route('admin.revenue.index') }}" class="flex flex-wrap items-center gap-2">
                    <input type="hidden" name="days" value="{{ $days }}">
                    <label class="flex items-center gap-2 text-xs uppercase tracking-[0.14em] text-[#666fa3]">
                        {{ __('From') }}
                        <input id="revenue_from" type="date" name="from" value="{{ $from }}" class="h-10 rounded-md border border-[#23246b] bg-[#0a0a3a] px-2 font-mono text-[13px] text-[#e6e9ff] outline-none [color-scheme:dark] focus:border-[#fbbf24] focus:ring-0">
                    </label>
                    <label class="flex items-center gap-2 text-xs uppercase tracking-[0.14em] text-[#666fa3]">
                        {{ __('To') }}
                        <input id="revenue_to" type="date" name="to" value="{{ $to }}" class="h-10 rounded-md border border-[#23246b] bg-[#0a0a3a] px-2 font-mono text-[13px] text-[#e6e9ff] outline-none [color-scheme:dark] focus:border-[#fbbf24] focus:ring-0">
                    </label>
                    <button type="submit" class="h-10 rounded-md bg-gradient-to-b from-[#fbbf24] to-[#f59e0b] px-3 text-[13px] font-bold tracking-[0.1em] text-[#221703] shadow-[0_2px_10px_rgba(251,191,36,0.3)] transition hover:brightness-110 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-[#fde68a]">{{ strtoupper(__('Apply')) }}</button>
                    <a href="{{ route('admin.revenue.index', ['days' => $days]) }}" class="inline-flex h-10 items-center rounded-md border border-[#23246b] px-3 text-[13px] font-semibold tracking-[0.1em] text-[#7c84b3] transition hover:bg-white/5 hover:text-[#e6e9ff]">{{ strtoupper(__('Reset')) }}</a>
                    @if ($customRange)
                        <span class="text-xs uppercase tracking-[0.14em] text-[#fbbf24]">&#9679; {{ __('Custom Range Active') }}</span>
                    @endif
                </form>

                <a
                    href="{{ route('admin.revenue.export', request()->query()) }}"
                    class="ml-auto inline-flex h-10 items-center gap-2 rounded-md border border-[#23246b] bg-[#0a0a3a] px-3 text-[13px] font-semibold tracking-[0.1em] text-[#a9b0d6] transition hover:border-[#fbbf24] hover:text-[#fbbf24] focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-[#fbbf24]"
                >&#8681; {{ strtoupper(__('Export CSV')) }}</a>
            </div>

            {{-- ===== Main grid: chart + side panels ===== --}}
            <div class="grid xl:grid-cols-[1.65fr_1fr]">
                <div class="border-b border-[#23246b] p-5 xl:border-b-0 xl:border-r">
                    <p class="text-xs uppercase tracking-[0.2em] text-[#fbbf24]">{{ __('Daily Revenue') }} <span class="text-[#666fa3]">&mdash; {{ __('Last :count sessions', ['count' => $chartDaysCount]) }}</span></p>
                    <p class="mt-2 text-4xl font-semibold tracking-tight text-[#f2f3ff]">
                        {{ $currencyLabel }} {{ $fmt($periodRevenue) }}
                        <span class="ml-2 text-base {{ $growthClass }}">{!! $growthArrow !!} {{ number_format(abs($growthPercent), 1) }}% {{ __('vs prev') }}</span>
                    </p>

                    <div class="mt-5 flex h-44 items-end gap-[3px]">
                        @foreach ($chartPoints as $point)
                            @php
                                $isPeak = data_get($point, 'date') === $peakDate && (float) data_get($point, 'amount', 0) > 0;
                                $barHeight = max(4, (int) round((((float) data_get($point, 'amount', 0)) / $maxDailyAmount) * 100));
                            @endphp
                            <div
                                class="flex-1 rounded-t-[2px] {{ $isPeak ? 'border-t-2 border-[#fde68a] bg-gradient-to-b from-[#f59e0b]/80 to-[#78350f]/60 shadow-[0_0_14px_rgba(251,191,36,0.35)]' : 'border-t-2 border-[#fbbf24]/80 bg-gradient-to-b from-[#b45309]/45 to-[#451a03]/30 hover:border-[#fde68a]' }}"
                                style="height: {{ $barHeight }}%"
                                title="{{ data_get($point, 'label') }} &mdash; {{ $currencyLabel }} {{ $fmt(data_get($point, 'amount', 0)) }} &middot; {{ (int) data_get($point, 'orders', 0) }} {{ __('orders') }}"
                            ></div>
                        @endforeach
                    </div>
                    <div class="mt-1.5 flex justify-between text-[11px] uppercase tracking-[0.12em] text-[#4a5288]">
                        <span>{{ data_get($chartPoints->first(), 'label', '-') }}</span>
                        <span class="text-[#fbbf24]">{{ __('Peak') }} {{ data_get($peakDay, 'label', '-') }} = {{ $currencyLabel }} {{ $fmt(data_get($peakDay, 'amount', 0)) }}</span>
                        <span>{{ data_get($chartPoints->last(), 'label', '-') }}</span>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-2 lg:grid-cols-4">
                        <div class="rounded-md border border-[#23246b] border-l-[3px] border-l-[#34d399] bg-[#0a0a3a]/80 px-3 py-2.5">
                            <p class="text-[11px] uppercase tracking-[0.16em] text-[#666fa3]">{{ __('Paid') }}</p>
                            <p class="mt-1 text-base font-semibold text-[#34d399]">{{ $fmt($paidAmount) }}</p>
                            <p class="text-[11px] text-[#666fa3]">{{ number_format((int) data_get($statusCards, 'paid.orders', 0)) }} {{ strtoupper(__('orders')) }}</p>
                        </div>
                        <div class="rounded-md border border-[#23246b] border-l-[3px] border-l-[#fbbf24] bg-[#0a0a3a]/80 px-3 py-2.5">
                            <p class="text-[11px] uppercase tracking-[0.16em] text-[#666fa3]">{{ __('Pending') }}</p>
                            <p class="mt-1 text-base font-semibold text-[#fbbf24]">{{ $fmt($pendingAmount) }}</p>
                            <p class="text-[11px] text-[#666fa3]">{{ number_format((int) data_get($statusCards, 'pending.orders', 0)) }} {{ strtoupper(__('orders')) }}</p>
                        </div>
                        <div class="rounded-md border border-[#23246b] border-l-[3px] border-l-[#fb7185] bg-[#0a0a3a]/80 px-3 py-2.5">
                            <p class="text-[11px] uppercase tracking-[0.16em] text-[#666fa3]">{{ __('Cancelled') }}</p>
                            <p class="mt-1 text-base font-semibold text-[#fb7185]">{{ $fmt($cancelledAmount) }}</p>
                            <p class="text-[11px] text-[#666fa3]">{{ number_format((int) data_get($statusCards, 'cancelled.orders', 0)) }} {{ strtoupper(__('orders')) }}</p>
                        </div>
                        <div class="rounded-md border border-[#23246b] border-l-[3px] border-l-[#a78bfa] bg-[#0a0a3a]/80 px-3 py-2.5">
                            <p class="text-[11px] uppercase tracking-[0.16em] text-[#666fa3]">{{ __('Refunded') }}</p>
                            <p class="mt-1 text-base font-semibold text-[#a78bfa]">{{ $fmt($refundedAmount) }}</p>
                            <p class="text-[11px] text-[#666fa3]">{{ number_format((int) data_get($statusCards, 'refunded.orders', 0)) }} {{ strtoupper(__('orders')) }}</p>
                        </div>
                    </div>

                    <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-3">
                        <div class="rounded-md border border-[#23246b] bg-[#0a0a3a]/60 px-3 py-2.5">
                            <p class="text-[11px] uppercase tracking-[0.16em] text-[#666fa3]">{{ __('Range Total') }}</p>
                            <p class="mt-1 text-lg font-semibold text-[#f2f3ff]">{{ $currencyLabel }} {{ $fmt($dailyRevenueTotal) }}</p>
                        </div>
                        <div class="rounded-md border border-[#23246b] bg-[#0a0a3a]/60 px-3 py-2.5">
                            <p class="text-[11px] uppercase tracking-[0.16em] text-[#666fa3]">{{ __('Peak Session') }}</p>
                            <p class="mt-1 text-lg font-semibold text-[#fbbf24]">{{ data_get($peakDay, 'label', '-') }} &middot; {{ $fmt(data_get($peakDay, 'amount', 0)) }}</p>
                        </div>
                        <div class="rounded-md border border-[#23246b] bg-[#0a0a3a]/60 px-3 py-2.5">
                            <p class="text-[11px] uppercase tracking-[0.16em] text-[#666fa3]">{{ __('Daily Average') }}</p>
                            <p class="mt-1 text-lg font-semibold text-[#f2f3ff]">{{ $currencyLabel }} {{ $fmt($avgDailyAmount) }}</p>
                        </div>
                    </div>
                </div>

                <div class="p-5">
                    <p class="text-xs uppercase tracking-[0.2em] text-[#fbbf24]">{{ __('Top Movers') }} <span class="text-[#666fa3]">&mdash; {{ __('Products') }}</span></p>
                    <table class="mt-2 w-full text-[13px]">
                        <tbody>
                            @forelse ($topProductsCollection->take(5) as $index => $product)
                                <tr class="border-b border-dotted border-[#23246b]">
                                    <td class="py-2 pr-2 text-[#666fa3]">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</td>
                                    <td class="max-w-0 truncate py-2 pr-3 text-[#c0c5e8]">{{ $product->name }}</td>
                                    <td class="py-2 pr-3 text-right text-xs text-[#666fa3]">{{ number_format((int) $product->units_sold) }}u</td>
                                    <td class="py-2 text-right font-semibold {{ $index === 0 ? 'text-[#fbbf24]' : 'text-[#f2f3ff]' }}">{{ $fmt($product->revenue_total) }}</td>
                                </tr>
                            @empty
                                <tr><td class="py-4 text-center text-[#4a5288]">{{ strtoupper(__('No revenue data in this period.')) }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>

                    <p class="mt-5 text-xs uppercase tracking-[0.2em] text-[#fbbf24]">{{ __('Top Accounts') }}</p>
                    <table class="mt-2 w-full text-[13px]">
                        <tbody>
                            @forelse ($topCustomersCollection->take(5) as $customer)
                                <tr class="border-b border-dotted border-[#23246b]">
                                    <td class="max-w-0 truncate py-2 pr-3 text-[#c0c5e8]">
                                        {{ $customer->name }}
                                        @if (($customer->role ?? null) === 'dealer')
                                            <span class="ml-1 rounded-sm bg-[#fbbf24]/15 px-1 text-[11px] font-semibold text-[#fbbf24]">DLR</span>
                                        @endif
                                    </td>
                                    <td class="py-2 pr-3 text-right text-xs text-[#666fa3]">{{ number_format((int) $customer->order_count) }} {{ strtoupper(__('ord')) }}</td>
                                    <td class="py-2 text-right font-semibold text-[#f2f3ff]">{{ $fmt($customer->revenue_total) }}</td>
                                </tr>
                            @empty
                                <tr><td class="py-4 text-center text-[#4a5288]">{{ strtoupper(__('No customer revenue data.')) }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-5 rounded-md border border-[#23246b] bg-[#0a0a3a]/60 px-3 py-3">
                        <p class="text-xs uppercase tracking-[0.2em] text-[#fbbf24]">{{ __('Conversion Register') }}</p>
                        <dl class="mt-2 space-y-1.5 text-[13px]">
                            <div class="flex items-center justify-between gap-3 border-b border-dotted border-[#23246b] pb-1.5">
                                <dt class="uppercase tracking-[0.08em] text-[#7c84b3]">{{ __('Orders in Window') }}</dt>
                                <dd class="font-semibold text-[#f2f3ff]">{{ number_format($windowOrders) }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3 border-b border-dotted border-[#23246b] pb-1.5">
                                <dt class="uppercase tracking-[0.08em] text-[#7c84b3]">{{ __('Paid Orders') }}</dt>
                                <dd class="font-semibold text-[#34d399]">{{ number_format($periodPaidOrders) }} &middot; {{ number_format($paidRate, 1) }}%</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3 border-b border-dotted border-[#23246b] pb-1.5">
                                <dt class="uppercase tracking-[0.08em] text-[#7c84b3]">{{ __('Cancel Rate') }}</dt>
                                <dd class="font-semibold {{ $cancelRate > 0 ? 'text-[#fb7185]' : 'text-[#f2f3ff]' }}">{{ number_format($cancelRate, 1) }}%</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="uppercase tracking-[0.08em] text-[#7c84b3]">{{ __('Avg Order Value') }}</dt>
                                <dd class="font-semibold text-[#f2f3ff]">{{ $currencyLabel }} {{ $fmt($averageOrderValue) }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="mt-3 rounded-md border border-[#23246b] bg-[#0a0a3a]/60 px-3 py-2.5">
                        <p class="text-[11px] uppercase tracking-[0.16em] text-[#666fa3]">{{ __('Today') }}</p>
                        <p class="mt-1 text-xl font-semibold text-[#f2f3ff]">{{ $currencyLabel }} {{ $fmt($todayRevenue) }}</p>
                        <p class="text-[11px] uppercase tracking-[0.1em] text-[#666fa3]">{{ __('Lifetime') }}: <span class="text-[#fbbf24]">{{ $currencyLabel }} {{ $fmt($totalRevenue) }}</span></p>
                    </div>
                </div>
            </div>

            {{-- ===== Sectors: revenue by category ===== --}}
            @php
                $topCategoriesCollection = collect($topCategories)->values();
                $categoryPool = max((float) $topCategoriesCollection->sum('revenue_total'), 1);
                $categoryColors = ['#fbbf24', '#f59e0b', '#d97706', '#b45309', '#92400e', '#78350f', '#5b2c0d', '#451a03'];
            @endphp
            @if ($topCategoriesCollection->isNotEmpty())
                <div class="border-t border-[#23246b] p-5">
                    <p class="text-xs uppercase tracking-[0.2em] text-[#fbbf24]">{{ __('Sectors') }} <span class="text-[#666fa3]">&mdash; {{ __('Revenue by Category') }}</span></p>
                    <div class="mt-3 flex h-4 overflow-hidden rounded-full border border-[#23246b] bg-[#0a0a3a]">
                        @foreach ($topCategoriesCollection as $index => $category)
                            @php $categoryShare = (((float) $category->revenue_total) / $categoryPool) * 100; @endphp
                            <div
                                style="width: {{ max(1, $categoryShare) }}%; background: {{ $categoryColors[$index % count($categoryColors)] }}"
                                title="{{ $category->category_name }} &mdash; {{ number_format($categoryShare, 1) }}%"
                            ></div>
                        @endforeach
                    </div>
                    <div class="mt-3 grid gap-x-6 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($topCategoriesCollection as $index => $category)
                            @php $categoryShare = (((float) $category->revenue_total) / $categoryPool) * 100; @endphp
                            <div class="flex items-center gap-2 border-b border-dotted border-[#23246b] py-2 text-[13px]">
                                <span class="h-2 w-2 shrink-0 rounded-sm" style="background: {{ $categoryColors[$index % count($categoryColors)] }}"></span>
                                <span class="min-w-0 flex-1 truncate text-[#c0c5e8]">{{ $category->category_name }}</span>
                                <span class="text-xs text-[#666fa3]">{{ number_format((int) $category->units_sold) }}u</span>
                                <span class="text-[#7c84b3]">{{ number_format($categoryShare, 1) }}%</span>
                                <span class="font-semibold text-[#f2f3ff]">{{ $fmt($category->revenue_total) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- ===== Leaderboards ===== --}}
            <div class="grid border-t border-[#23246b] lg:grid-cols-3">
                <div class="border-b border-[#23246b] p-5 lg:border-b-0 lg:border-r">
                    <p class="text-xs uppercase tracking-[0.2em] text-[#fbbf24]">{{ __('Top Products By Revenue') }}</p>
                    <div class="mt-2 grid grid-cols-3 gap-1.5 text-center">
                        <div class="rounded-md border border-[#23246b] bg-[#0a0a3a]/60 px-2 py-2">
                            <p class="text-[10px] uppercase tracking-[0.12em] text-[#666fa3]">{{ __('Pool') }}</p>
                            <p class="mt-0.5 text-[13px] font-semibold text-[#f2f3ff]">{{ $fmt($productRevenuePool) }}</p>
                        </div>
                        <div class="rounded-md border border-[#23246b] bg-[#0a0a3a]/60 px-2 py-2">
                            <p class="text-[10px] uppercase tracking-[0.12em] text-[#666fa3]">{{ __('Units') }}</p>
                            <p class="mt-0.5 text-[13px] font-semibold text-[#f2f3ff]">{{ number_format($productUnitsPool) }}</p>
                        </div>
                        <div class="rounded-md border border-[#23246b] bg-[#0a0a3a]/60 px-2 py-2">
                            <p class="text-[10px] uppercase tracking-[0.12em] text-[#666fa3]">{{ __('Leader') }}</p>
                            <p class="mt-0.5 text-[13px] font-semibold text-[#fbbf24]">{{ number_format($productLeaderShare, 1) }}%</p>
                        </div>
                    </div>
                    <table class="mt-3 w-full text-[13px]">
                        <thead>
                            <tr class="border-b border-[#23246b] text-left text-[11px] uppercase tracking-[0.16em] text-[#4a5288]">
                                <th class="py-2 pr-2 font-semibold">#</th>
                                <th class="py-2 pr-3 font-semibold">{{ __('Product') }}</th>
                                <th class="py-2 pr-3 text-right font-semibold">{{ __('Units') }}</th>
                                <th class="py-2 text-right font-semibold">{{ __('Revenue') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($topProducts as $index => $product)
                                @php $productShare = max(4, (int) round((((float) $product->revenue_total) / $topProductRevenueMax) * 100)); @endphp
                                <tr class="border-b border-dotted border-[#23246b] hover:bg-white/[0.03]">
                                    <td class="py-2.5 pr-2 align-top text-[#666fa3]">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</td>
                                    <td class="max-w-0 py-2.5 pr-3">
                                        <span class="block truncate text-[#c0c5e8]">{{ $product->name }}</span>
                                        <span class="mt-1 block h-[4px] rounded-full bg-gradient-to-r from-[#f59e0b] to-[#fbbf24]/60" style="width: {{ $productShare }}%"></span>
                                    </td>
                                    <td class="py-2.5 pr-3 text-right align-top text-[#7c84b3]">{{ number_format((int) $product->units_sold) }}</td>
                                    <td class="py-2.5 text-right align-top font-semibold {{ $index === 0 ? 'text-[#fbbf24]' : 'text-[#f2f3ff]' }}">{{ $fmt($product->revenue_total) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-6 text-center text-[#4a5288]">{{ strtoupper(__('No revenue data in this period.')) }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-b border-[#23246b] p-5 lg:border-b-0 lg:border-r">
                    <p class="text-xs uppercase tracking-[0.2em] text-[#fbbf24]">{{ __('Top Customers By Revenue') }}</p>
                    <div class="mt-2 grid grid-cols-3 gap-1.5 text-center">
                        <div class="rounded-md border border-[#23246b] bg-[#0a0a3a]/60 px-2 py-2">
                            <p class="text-[10px] uppercase tracking-[0.12em] text-[#666fa3]">{{ __('Pool') }}</p>
                            <p class="mt-0.5 text-[13px] font-semibold text-[#f2f3ff]">{{ $fmt($customerRevenuePool) }}</p>
                        </div>
                        <div class="rounded-md border border-[#23246b] bg-[#0a0a3a]/60 px-2 py-2">
                            <p class="text-[10px] uppercase tracking-[0.12em] text-[#666fa3]">{{ __('Orders') }}</p>
                            <p class="mt-0.5 text-[13px] font-semibold text-[#f2f3ff]">{{ number_format($customerOrdersPool) }}</p>
                        </div>
                        <div class="rounded-md border border-[#23246b] bg-[#0a0a3a]/60 px-2 py-2">
                            <p class="text-[10px] uppercase tracking-[0.12em] text-[#666fa3]">{{ __('Leader') }}</p>
                            <p class="mt-0.5 text-[13px] font-semibold text-[#fbbf24]">{{ number_format($customerLeaderShare, 1) }}%</p>
                        </div>
                    </div>
                    <table class="mt-3 w-full text-[13px]">
                        <thead>
                            <tr class="border-b border-[#23246b] text-left text-[11px] uppercase tracking-[0.16em] text-[#4a5288]">
                                <th class="py-2 pr-2 font-semibold">#</th>
                                <th class="py-2 pr-3 font-semibold">{{ __('Customer') }}</th>
                                <th class="py-2 pr-3 text-right font-semibold">{{ __('Orders') }}</th>
                                <th class="py-2 text-right font-semibold">{{ __('Revenue') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($topCustomers as $index => $customer)
                                @php $customerShare = max(4, (int) round((((float) $customer->revenue_total) / $topCustomerRevenueMax) * 100)); @endphp
                                <tr class="border-b border-dotted border-[#23246b] hover:bg-white/[0.03]">
                                    <td class="py-2.5 pr-2 align-top text-[#666fa3]">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</td>
                                    <td class="max-w-0 py-2.5 pr-3">
                                        <span class="block truncate text-[#c0c5e8]">
                                            @if (Route::has('admin.users.show') && auth()->user()?->can('manage-users'))
                                                <a href="{{ route('admin.users.show', $customer->id) }}" class="transition hover:text-[#fbbf24] focus-visible:outline-none focus-visible:text-[#fbbf24]">{{ $customer->name }}</a>
                                            @else
                                                {{ $customer->name }}
                                            @endif
                                            @if (($customer->role ?? null) === 'dealer')
                                                <span class="ml-1 rounded-sm bg-[#fbbf24]/15 px-1 text-[11px] font-semibold text-[#fbbf24]">DLR</span>
                                            @endif
                                        </span>
                                        <span class="mt-1 block h-[4px] rounded-full bg-gradient-to-r from-[#f59e0b] to-[#fbbf24]/60" style="width: {{ $customerShare }}%"></span>
                                    </td>
                                    <td class="py-2.5 pr-3 text-right align-top text-[#7c84b3]">{{ number_format((int) $customer->order_count) }}</td>
                                    <td class="py-2.5 text-right align-top font-semibold {{ $index === 0 ? 'text-[#fbbf24]' : 'text-[#f2f3ff]' }}">{{ $fmt($customer->revenue_total) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-6 text-center text-[#4a5288]">{{ strtoupper(__('No customer revenue data.')) }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-5">
                    <p class="text-xs uppercase tracking-[0.2em] text-[#fbbf24]">{{ __('Top Dealers By Revenue') }}</p>
                    <div class="mt-2 grid grid-cols-3 gap-1.5 text-center">
                        <div class="rounded-md border border-[#23246b] bg-[#0a0a3a]/60 px-2 py-2">
                            <p class="text-[10px] uppercase tracking-[0.12em] text-[#666fa3]">{{ __('Pool') }}</p>
                            <p class="mt-0.5 text-[13px] font-semibold text-[#f2f3ff]">{{ $fmt($dealerRevenuePool) }}</p>
                        </div>
                        <div class="rounded-md border border-[#23246b] bg-[#0a0a3a]/60 px-2 py-2">
                            <p class="text-[10px] uppercase tracking-[0.12em] text-[#666fa3]">{{ __('Orders') }}</p>
                            <p class="mt-0.5 text-[13px] font-semibold text-[#f2f3ff]">{{ number_format($dealerOrdersPool) }}</p>
                        </div>
                        <div class="rounded-md border border-[#23246b] bg-[#0a0a3a]/60 px-2 py-2">
                            <p class="text-[10px] uppercase tracking-[0.12em] text-[#666fa3]">{{ __('Leader') }}</p>
                            <p class="mt-0.5 text-[13px] font-semibold text-[#fbbf24]">{{ number_format($dealerLeaderShare, 1) }}%</p>
                        </div>
                    </div>
                    <table class="mt-3 w-full text-[13px]">
                        <thead>
                            <tr class="border-b border-[#23246b] text-left text-[11px] uppercase tracking-[0.16em] text-[#4a5288]">
                                <th class="py-2 pr-2 font-semibold">#</th>
                                <th class="py-2 pr-3 font-semibold">{{ __('Dealer') }}</th>
                                <th class="py-2 pr-3 text-right font-semibold">{{ __('Orders') }}</th>
                                <th class="py-2 text-right font-semibold">{{ __('Revenue') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($topDealers as $index => $dealer)
                                @php $dealerShare = max(4, (int) round((((float) $dealer->revenue_total) / $topDealerRevenueMax) * 100)); @endphp
                                <tr class="border-b border-dotted border-[#23246b] hover:bg-white/[0.03]">
                                    <td class="py-2.5 pr-2 align-top text-[#666fa3]">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</td>
                                    <td class="max-w-0 py-2.5 pr-3">
                                        <span class="block truncate text-[#c0c5e8]">{{ $dealer->name }}</span>
                                        <span class="mt-1 block h-[4px] rounded-full bg-gradient-to-r from-[#f59e0b] to-[#fbbf24]/60" style="width: {{ $dealerShare }}%"></span>
                                    </td>
                                    <td class="py-2.5 pr-3 text-right align-top text-[#7c84b3]">{{ number_format((int) $dealer->order_count) }}</td>
                                    <td class="py-2.5 text-right align-top font-semibold {{ $index === 0 ? 'text-[#fbbf24]' : 'text-[#f2f3ff]' }}">{{ $fmt($dealer->revenue_total) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-6 text-center text-[#4a5288]">{{ strtoupper(__('No dealer revenue data.')) }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ===== Daily log + order ledger ===== --}}
            <div class="grid border-t border-[#23246b] xl:grid-cols-2">
                <div class="border-b border-[#23246b] p-5 xl:border-b-0 xl:border-r">
                    <div class="flex items-baseline justify-between gap-3">
                        <p class="text-xs uppercase tracking-[0.2em] text-[#fbbf24]">{{ __('Daily Log') }}</p>
                        <p class="text-xs text-[#4a5288]">MAX {{ $currencyLabel }} {{ $fmt($maxDailyAmount) }}</p>
                    </div>
                    <div class="mt-2 max-h-96 overflow-y-auto pr-1">
                        <table class="w-full text-[13px]">
                            <thead>
                                <tr class="border-b border-[#23246b] text-left text-[11px] uppercase tracking-[0.16em] text-[#4a5288]">
                                    <th class="py-2 pr-3 font-semibold">{{ __('Date') }}</th>
                                    <th class="py-2 pr-3 text-right font-semibold">{{ __('Orders') }}</th>
                                    <th class="py-2 pr-3 font-semibold"></th>
                                    <th class="py-2 text-right font-semibold">{{ __('Revenue') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($dailyCollection->reverse() as $row)
                                    @php
                                        $rowShare = max(2, (int) round((((float) $row['amount']) / $maxDailyAmount) * 100));
                                        $isPeakRow = ($row['date'] ?? null) === $peakDate && (float) $row['amount'] > 0;
                                    @endphp
                                    <tr class="border-b border-dotted border-[#23246b] hover:bg-white/[0.03]">
                                        <td class="w-20 py-2 pr-3 {{ $isPeakRow ? 'text-[#fbbf24]' : 'text-[#7c84b3]' }}">{{ $row['label'] }}</td>
                                        <td class="w-14 py-2 pr-3 text-right text-[#666fa3]">{{ number_format((int) ($row['orders'] ?? 0)) }}</td>
                                        <td class="py-2 pr-3">
                                            <span class="block h-[7px] rounded-full {{ $isPeakRow ? 'bg-gradient-to-r from-[#fbbf24] to-[#fde68a]' : 'bg-gradient-to-r from-[#b45309] to-[#f59e0b]/70' }}" style="width: {{ $rowShare }}%"></span>
                                        </td>
                                        <td class="w-32 py-2 text-right font-semibold {{ $isPeakRow ? 'text-[#fbbf24]' : 'text-[#f2f3ff]' }}">{{ $fmt($row['amount']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="p-5">
                    <div class="flex items-baseline justify-between gap-3">
                        <p class="text-xs uppercase tracking-[0.2em] text-[#fbbf24]">{{ __('Order Ledger') }} <span class="text-[#666fa3]">&mdash; {{ __('Recent Paid Orders') }}</span></p>
                        <p class="text-xs text-[#4a5288]">&Sigma; {{ $currencyLabel }} {{ $fmt($recentOrdersTotal) }} &middot; {{ __('Avg') }} {{ $fmt($recentOrdersAverage) }}</p>
                    </div>
                    <div class="mt-2 max-h-96 overflow-y-auto pr-1">
                        <table class="w-full text-[13px]">
                            <thead>
                                <tr class="border-b border-[#23246b] text-left text-[11px] uppercase tracking-[0.16em] text-[#4a5288]">
                                    <th class="py-2 pr-3 font-semibold">{{ __('Order') }}</th>
                                    <th class="py-2 pr-3 font-semibold">{{ __('Customer') }}</th>
                                    <th class="py-2 pr-3 font-semibold">{{ __('Status') }}</th>
                                    <th class="py-2 pr-3 font-semibold">{{ __('Date') }}</th>
                                    <th class="py-2 text-right font-semibold">{{ __('Amount') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentPaidOrders as $order)
                                    <tr class="border-b border-dotted border-[#23246b] hover:bg-white/[0.03]">
                                        <td class="py-2.5 pr-3 text-[#666fa3]">#{{ $order->id }}</td>
                                        <td class="max-w-0 truncate py-2.5 pr-3 text-[#c0c5e8]">{{ $order->user?->name ?? __('Guest') }}</td>
                                        <td class="py-2.5 pr-3">
                                            <span class="rounded-sm bg-[#34d399]/10 px-1.5 py-0.5 text-[11px] font-semibold uppercase tracking-[0.08em] text-[#34d399]">{{ $order->status }}</span>
                                        </td>
                                        <td class="py-2.5 pr-3 text-[#7c84b3]">{{ optional($order->created_at)->format('m-d H:i') }}</td>
                                        <td class="py-2.5 text-right font-semibold text-[#f2f3ff]">{{ $fmt($order->total_amount) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="py-6 text-center text-[#4a5288]">{{ strtoupper(__('No paid orders found.')) }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ===== Terminal footer ===== --}}
            <div class="flex flex-wrap items-center justify-between gap-2 border-t border-[#23246b] bg-[#03031e]/80 px-4 py-2.5 text-[11px] uppercase tracking-[0.16em] text-[#4a5288]">
                <span>{{ __('Paid statuses') }}: <span class="text-[#34d399]">DELIVERED + COMPLETED</span></span>
                <span>{{ $chartDaysCount }} {{ __('sessions') }} &middot; {{ number_format($windowOrders) }} {{ __('orders in window') }}</span>
            </div>
        </section>
    </div>
</div>
</x-app-layout>
