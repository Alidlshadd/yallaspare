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
            $peakDay = $dailyCollection->sortByDesc('amount')->first();
            $peakDate = (string) data_get($peakDay, 'date', '');
            $chartPoints = $dailyCollection->count() > 30
                ? $dailyCollection->slice(-30)->values()
                : $dailyCollection->values();
            $chartDaysCount = $chartPoints->count();

            $topProductsCollection = collect($topProducts)->values();
            $topCustomersCollection = collect($topCustomers)->values();
            $topDealersCollection = collect($topDealers)->values();
            $topProductRevenueMax = max((float) $topProductsCollection->max('revenue_total'), 1);
            $topCustomerRevenueMax = max((float) $topCustomersCollection->max('revenue_total'), 1);
            $topDealerRevenueMax = max((float) $topDealersCollection->max('revenue_total'), 1);

            $paidAmount = (float) data_get($statusCards, 'paid.amount', 0);
            $pendingAmount = (float) data_get($statusCards, 'pending.amount', 0);
            $cancelledAmount = (float) data_get($statusCards, 'cancelled.amount', 0);
            $refundedAmount = (float) data_get($statusCards, 'refunded.amount', 0);
            $paidRate = (float) data_get($conversionSummary, 'paid_rate', 0);

            $growthUp = $growthPercent > 0;
            $growthFlat = $growthPercent == 0.0;
            $growthClass = $growthUp ? 'text-[#4ade80]' : ($growthFlat ? 'text-[#64748b]' : 'text-[#f87171]');
            $growthArrow = $growthUp ? '&#9650;' : ($growthFlat ? '&#9644;' : '&#9660;');

            $recentOrdersCollection = collect($recentPaidOrders);
            $recentOrdersTotal = (float) $recentOrdersCollection->sum('total_amount');

            $fmt = fn ($value) => number_format((float) $value, $currencyDecimals);
        @endphp

        <section class="overflow-hidden rounded-xl border border-[#1d2733] bg-[#070b11] font-mono shadow-[0_24px_60px_rgba(2,6,12,0.55)]">

            {{-- ===== Terminal top bar ===== --}}
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#1d2733] bg-[#04070c] px-4 py-2.5 text-[11px] tracking-[0.14em] text-[#64748b]">
                <span class="font-semibold text-[#8b96a5]">YALLA SPARE &mdash; {{ strtoupper(__('Revenue')) }} TERMINAL</span>
                <span class="uppercase">
                    {{ __('Window') }}: <span class="text-[#e8eef5]">{{ $start->format('M d') }} &rarr; {{ $end->format('M d, Y') }}</span>
                    &middot; {{ $rangeDays }}D
                    &middot; <span class="text-[#e8eef5]">{{ $now->format('H:i') }}</span>
                </span>
            </div>

            {{-- ===== Ticker strip ===== --}}
            <div class="flex gap-7 overflow-x-auto whitespace-nowrap border-b border-[#1d2733] bg-[#04070c] px-4 py-2 text-[11px] text-[#8b96a5]">
                <span class="uppercase tracking-[0.08em]">{{ __('Today') }} <b class="ml-1 font-semibold text-[#e8eef5]">{{ $currencyLabel }} {{ $fmt($todayRevenue) }}</b></span>
                <span class="uppercase tracking-[0.08em]">{{ __('Period') }} <b class="ml-1 font-semibold text-[#e8eef5]">{{ $currencyLabel }} {{ $fmt($periodRevenue) }}</b> <span class="{{ $growthClass }}">{!! $growthArrow !!} {{ number_format(abs($growthPercent), 1) }}%</span></span>
                <span class="uppercase tracking-[0.08em]">AOV <b class="ml-1 font-semibold text-[#e8eef5]">{{ $currencyLabel }} {{ $fmt($averageOrderValue) }}</b></span>
                <span class="uppercase tracking-[0.08em]">{{ __('Paid Rate') }} <b class="ml-1 font-semibold text-[#e8eef5]">{{ number_format($paidRate, 1) }}%</b></span>
                <span class="uppercase tracking-[0.08em]">{{ __('Lifetime') }} <b class="ml-1 font-semibold text-[#e8eef5]">{{ $currencyLabel }} {{ $fmt($totalRevenue) }}</b></span>
                <span class="uppercase tracking-[0.08em]">{{ __('Refunds') }} <b class="ml-1 font-semibold text-[#fbbf24]">{{ $currencyLabel }} {{ $fmt($refundedAmount) }}</b></span>
            </div>

            {{-- ===== Filter row ===== --}}
            <div class="flex flex-wrap items-center gap-3 border-b border-[#1d2733] px-4 py-3">
                <form method="GET" action="{{ route('admin.revenue.index') }}" class="flex items-center gap-1 rounded border border-[#1d2733] bg-[#0a0f16] p-1">
                    @foreach ($allowedDays as $option)
                        <button
                            type="submit"
                            name="days"
                            value="{{ $option }}"
                            class="rounded px-3 py-1 text-[11px] font-semibold tracking-[0.1em] transition focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-[#4ade80] {{ ($days === $option && !$customRange) ? 'bg-[#14532d] text-[#4ade80]' : 'text-[#64748b] hover:bg-[#111827] hover:text-[#d5dce4]' }}"
                        >{{ $option }}D</button>
                    @endforeach
                </form>

                <form method="GET" action="{{ route('admin.revenue.index') }}" class="flex flex-wrap items-center gap-2">
                    <input type="hidden" name="days" value="{{ $days }}">
                    <label class="flex items-center gap-2 text-[10px] uppercase tracking-[0.14em] text-[#5b6b7d]">
                        {{ __('From') }}
                        <input id="revenue_from" type="date" name="from" value="{{ $from }}" class="h-8 rounded border border-[#1d2733] bg-[#0a0f16] px-2 font-mono text-[11px] text-[#d5dce4] outline-none [color-scheme:dark] focus:border-[#4ade80] focus:ring-0">
                    </label>
                    <label class="flex items-center gap-2 text-[10px] uppercase tracking-[0.14em] text-[#5b6b7d]">
                        {{ __('To') }}
                        <input id="revenue_to" type="date" name="to" value="{{ $to }}" class="h-8 rounded border border-[#1d2733] bg-[#0a0f16] px-2 font-mono text-[11px] text-[#d5dce4] outline-none [color-scheme:dark] focus:border-[#4ade80] focus:ring-0">
                    </label>
                    <button type="submit" class="h-8 rounded border border-[#14532d] bg-[#0c2818] px-3 text-[11px] font-semibold tracking-[0.1em] text-[#4ade80] transition hover:bg-[#14532d] focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-[#4ade80]">{{ strtoupper(__('Apply')) }}</button>
                    <a href="{{ route('admin.revenue.index', ['days' => $days]) }}" class="inline-flex h-8 items-center rounded border border-[#1d2733] px-3 text-[11px] font-semibold tracking-[0.1em] text-[#64748b] transition hover:bg-[#111827] hover:text-[#d5dce4]">{{ strtoupper(__('Reset')) }}</a>
                    @if ($customRange)
                        <span class="text-[10px] uppercase tracking-[0.14em] text-[#fbbf24]">&#9679; {{ __('Custom Range Active') }}</span>
                    @endif
                </form>

                <a
                    href="{{ route('admin.revenue.export', request()->query()) }}"
                    class="ml-auto inline-flex h-8 items-center gap-2 rounded border border-[#1d2733] bg-[#0a0f16] px-3 text-[11px] font-semibold tracking-[0.1em] text-[#8b96a5] transition hover:border-[#4ade80] hover:text-[#4ade80] focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-[#4ade80]"
                >&#8681; {{ strtoupper(__('Export CSV')) }}</a>
            </div>

            {{-- ===== Main grid: chart + movers ===== --}}
            <div class="grid xl:grid-cols-[1.6fr_1fr]">
                <div class="border-b border-[#1d2733] p-4 xl:border-b-0 xl:border-r">
                    <p class="text-[10px] uppercase tracking-[0.2em] text-[#5b6b7d]">{{ __('Daily Revenue') }} &mdash; {{ __('Last :count sessions', ['count' => $chartDaysCount]) }}</p>
                    <p class="mt-2 text-2xl font-semibold tracking-tight text-[#f1f5f9]">
                        {{ $currencyLabel }} {{ $fmt($periodRevenue) }}
                        <span class="ml-2 text-sm {{ $growthClass }}">{!! $growthArrow !!} {{ number_format(abs($growthPercent), 1) }}% {{ __('vs prev') }}</span>
                    </p>

                    <div class="mt-4 flex h-28 items-end gap-[3px]">
                        @foreach ($chartPoints as $point)
                            @php
                                $isPeak = data_get($point, 'date') === $peakDate && (float) data_get($point, 'amount', 0) > 0;
                                $barHeight = max(4, (int) round((((float) data_get($point, 'amount', 0)) / $maxDailyAmount) * 100));
                            @endphp
                            <div
                                class="flex-1 {{ $isPeak ? 'border-t-2 border-[#fbbf24] bg-[#365314]' : 'border-t-2 border-[#4ade80] bg-[#14532d]' }}"
                                style="height: {{ $barHeight }}%"
                                title="{{ data_get($point, 'label') }} &mdash; {{ $currencyLabel }} {{ $fmt(data_get($point, 'amount', 0)) }}"
                            ></div>
                        @endforeach
                    </div>
                    <div class="mt-1.5 flex justify-between text-[9px] uppercase tracking-[0.12em] text-[#3f4c5c]">
                        <span>{{ data_get($chartPoints->first(), 'label', '-') }}</span>
                        <span class="text-[#fbbf24]">{{ __('Peak') }} {{ data_get($peakDay, 'label', '-') }} = {{ $currencyLabel }} {{ $fmt(data_get($peakDay, 'amount', 0)) }}</span>
                        <span>{{ data_get($chartPoints->last(), 'label', '-') }}</span>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-2 lg:grid-cols-4">
                        <div class="border border-[#1d2733] border-l-[3px] border-l-[#4ade80] bg-[#0a0f16] px-3 py-2">
                            <p class="text-[9px] uppercase tracking-[0.16em] text-[#5b6b7d]">{{ __('Paid') }}</p>
                            <p class="mt-1 text-sm font-semibold text-[#e8eef5]">{{ $fmt($paidAmount) }}</p>
                            <p class="text-[9px] text-[#5b6b7d]">{{ number_format((int) data_get($statusCards, 'paid.orders', 0)) }} {{ strtoupper(__('orders')) }}</p>
                        </div>
                        <div class="border border-[#1d2733] border-l-[3px] border-l-[#fbbf24] bg-[#0a0f16] px-3 py-2">
                            <p class="text-[9px] uppercase tracking-[0.16em] text-[#5b6b7d]">{{ __('Pending') }}</p>
                            <p class="mt-1 text-sm font-semibold text-[#e8eef5]">{{ $fmt($pendingAmount) }}</p>
                            <p class="text-[9px] text-[#5b6b7d]">{{ number_format((int) data_get($statusCards, 'pending.orders', 0)) }} {{ strtoupper(__('orders')) }}</p>
                        </div>
                        <div class="border border-[#1d2733] border-l-[3px] border-l-[#f87171] bg-[#0a0f16] px-3 py-2">
                            <p class="text-[9px] uppercase tracking-[0.16em] text-[#5b6b7d]">{{ __('Cancelled') }}</p>
                            <p class="mt-1 text-sm font-semibold text-[#e8eef5]">{{ $fmt($cancelledAmount) }}</p>
                            <p class="text-[9px] text-[#5b6b7d]">{{ number_format((int) data_get($statusCards, 'cancelled.orders', 0)) }} {{ strtoupper(__('orders')) }}</p>
                        </div>
                        <div class="border border-[#1d2733] border-l-[3px] border-l-[#a78bfa] bg-[#0a0f16] px-3 py-2">
                            <p class="text-[9px] uppercase tracking-[0.16em] text-[#5b6b7d]">{{ __('Refunded') }}</p>
                            <p class="mt-1 text-sm font-semibold text-[#e8eef5]">{{ $fmt($refundedAmount) }}</p>
                            <p class="text-[9px] text-[#5b6b7d]">{{ number_format((int) data_get($statusCards, 'refunded.orders', 0)) }} {{ strtoupper(__('orders')) }}</p>
                        </div>
                    </div>
                </div>

                <div class="p-4">
                    <p class="text-[10px] uppercase tracking-[0.2em] text-[#5b6b7d]">{{ __('Top Movers') }} &mdash; {{ __('Products') }}</p>
                    <table class="mt-2 w-full text-[11px]">
                        <tbody>
                            @forelse ($topProductsCollection->take(5) as $index => $product)
                                <tr class="border-b border-dotted border-[#1d2733]">
                                    <td class="py-1.5 pr-2 text-[#64748b]">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</td>
                                    <td class="max-w-0 truncate py-1.5 pr-3 text-[#a8b3c1]">{{ $product->name }}</td>
                                    <td class="py-1.5 text-right font-semibold {{ $index === 0 ? 'text-[#4ade80]' : 'text-[#e8eef5]' }}">{{ $fmt($product->revenue_total) }}</td>
                                </tr>
                            @empty
                                <tr><td class="py-4 text-center text-[#3f4c5c]">{{ strtoupper(__('No revenue data in this period.')) }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>

                    <p class="mt-5 text-[10px] uppercase tracking-[0.2em] text-[#5b6b7d]">{{ __('Top Accounts') }}</p>
                    <table class="mt-2 w-full text-[11px]">
                        <tbody>
                            @forelse ($topCustomersCollection->take(5) as $customer)
                                <tr class="border-b border-dotted border-[#1d2733]">
                                    <td class="max-w-0 truncate py-1.5 pr-3 text-[#a8b3c1]">
                                        {{ $customer->name }}
                                        @if (($customer->role ?? null) === 'dealer')
                                            <span class="ml-1 text-[9px] font-semibold text-[#fbbf24]">DLR</span>
                                        @endif
                                    </td>
                                    <td class="py-1.5 text-right font-semibold text-[#e8eef5]">{{ $fmt($customer->revenue_total) }}</td>
                                </tr>
                            @empty
                                <tr><td class="py-4 text-center text-[#3f4c5c]">{{ strtoupper(__('No customer revenue data.')) }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-5 border border-[#1d2733] bg-[#0a0f16] px-3 py-2.5">
                        <p class="text-[9px] uppercase tracking-[0.16em] text-[#5b6b7d]">{{ __('Daily Average') }}</p>
                        <p class="mt-1 text-lg font-semibold text-[#e8eef5]">{{ $currencyLabel }} {{ $fmt($avgDailyAmount) }}</p>
                        <p class="text-[9px] text-[#5b6b7d]">{{ strtoupper(__('across :count tracked days', ['count' => $chartDaysCount])) }}</p>
                    </div>
                </div>
            </div>

            {{-- ===== Sectors: revenue by category ===== --}}
            @php
                $topCategoriesCollection = collect($topCategories)->values();
                $categoryPool = max((float) $topCategoriesCollection->sum('revenue_total'), 1);
                $categoryColors = ['#4ade80', '#22c55e', '#16a34a', '#15803d', '#166534', '#14532d', '#0f3d24', '#0a2e1b'];
            @endphp
            @if ($topCategoriesCollection->isNotEmpty())
                <div class="border-t border-[#1d2733] p-4">
                    <p class="text-[10px] uppercase tracking-[0.2em] text-[#5b6b7d]">{{ __('Sectors') }} &mdash; {{ __('Revenue by Category') }}</p>
                    <div class="mt-3 flex h-3 overflow-hidden rounded-sm border border-[#1d2733]">
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
                            <div class="flex items-center gap-2 border-b border-dotted border-[#1d2733] py-1.5 text-[11px]">
                                <span class="h-2 w-2 shrink-0" style="background: {{ $categoryColors[$index % count($categoryColors)] }}"></span>
                                <span class="min-w-0 flex-1 truncate text-[#a8b3c1]">{{ $category->category_name }}</span>
                                <span class="text-[#64748b]">{{ number_format($categoryShare, 1) }}%</span>
                                <span class="font-semibold text-[#e8eef5]">{{ $fmt($category->revenue_total) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- ===== Leaderboards ===== --}}
            <div class="grid border-t border-[#1d2733] lg:grid-cols-3">
                <div class="border-b border-[#1d2733] p-4 lg:border-b-0 lg:border-r">
                    <p class="text-[10px] uppercase tracking-[0.2em] text-[#5b6b7d]">{{ __('Top Products By Revenue') }}</p>
                    <table class="mt-2 w-full text-[11px]">
                        <thead>
                            <tr class="border-b border-[#1d2733] text-left text-[9px] uppercase tracking-[0.16em] text-[#3f4c5c]">
                                <th class="py-1.5 pr-2 font-semibold">#</th>
                                <th class="py-1.5 pr-3 font-semibold">{{ __('Product') }}</th>
                                <th class="py-1.5 pr-3 text-right font-semibold">{{ __('Units') }}</th>
                                <th class="py-1.5 text-right font-semibold">{{ __('Revenue') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($topProducts as $index => $product)
                                @php $productShare = max(4, (int) round((((float) $product->revenue_total) / $topProductRevenueMax) * 100)); @endphp
                                <tr class="border-b border-dotted border-[#1d2733] hover:bg-[#0a0f16]">
                                    <td class="py-2 pr-2 align-top text-[#64748b]">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</td>
                                    <td class="max-w-0 py-2 pr-3">
                                        <span class="block truncate text-[#a8b3c1]">{{ $product->name }}</span>
                                        <span class="mt-1 block h-[3px] bg-[#14532d]" style="width: {{ $productShare }}%"></span>
                                    </td>
                                    <td class="py-2 pr-3 text-right align-top text-[#64748b]">{{ number_format((int) $product->units_sold) }}</td>
                                    <td class="py-2 text-right align-top font-semibold {{ $index === 0 ? 'text-[#4ade80]' : 'text-[#e8eef5]' }}">{{ $fmt($product->revenue_total) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-6 text-center text-[#3f4c5c]">{{ strtoupper(__('No revenue data in this period.')) }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-b border-[#1d2733] p-4 lg:border-b-0 lg:border-r">
                    <p class="text-[10px] uppercase tracking-[0.2em] text-[#5b6b7d]">{{ __('Top Customers By Revenue') }}</p>
                    <table class="mt-2 w-full text-[11px]">
                        <thead>
                            <tr class="border-b border-[#1d2733] text-left text-[9px] uppercase tracking-[0.16em] text-[#3f4c5c]">
                                <th class="py-1.5 pr-2 font-semibold">#</th>
                                <th class="py-1.5 pr-3 font-semibold">{{ __('Customer') }}</th>
                                <th class="py-1.5 pr-3 text-right font-semibold">{{ __('Orders') }}</th>
                                <th class="py-1.5 text-right font-semibold">{{ __('Revenue') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($topCustomers as $index => $customer)
                                @php $customerShare = max(4, (int) round((((float) $customer->revenue_total) / $topCustomerRevenueMax) * 100)); @endphp
                                <tr class="border-b border-dotted border-[#1d2733] hover:bg-[#0a0f16]">
                                    <td class="py-2 pr-2 align-top text-[#64748b]">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</td>
                                    <td class="max-w-0 py-2 pr-3">
                                        <span class="block truncate text-[#a8b3c1]">
                                            @if (Route::has('admin.users.show') && auth()->user()?->can('manage-users'))
                                                <a href="{{ route('admin.users.show', $customer->id) }}" class="transition hover:text-[#4ade80] focus-visible:outline-none focus-visible:text-[#4ade80]">{{ $customer->name }}</a>
                                            @else
                                                {{ $customer->name }}
                                            @endif
                                            @if (($customer->role ?? null) === 'dealer')
                                                <span class="ml-1 text-[9px] font-semibold text-[#fbbf24]">DLR</span>
                                            @endif
                                        </span>
                                        <span class="mt-1 block h-[3px] bg-[#14532d]" style="width: {{ $customerShare }}%"></span>
                                    </td>
                                    <td class="py-2 pr-3 text-right align-top text-[#64748b]">{{ number_format((int) $customer->order_count) }}</td>
                                    <td class="py-2 text-right align-top font-semibold {{ $index === 0 ? 'text-[#4ade80]' : 'text-[#e8eef5]' }}">{{ $fmt($customer->revenue_total) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-6 text-center text-[#3f4c5c]">{{ strtoupper(__('No customer revenue data.')) }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-4">
                    <p class="text-[10px] uppercase tracking-[0.2em] text-[#5b6b7d]">{{ __('Top Dealers By Revenue') }}</p>
                    <table class="mt-2 w-full text-[11px]">
                        <thead>
                            <tr class="border-b border-[#1d2733] text-left text-[9px] uppercase tracking-[0.16em] text-[#3f4c5c]">
                                <th class="py-1.5 pr-2 font-semibold">#</th>
                                <th class="py-1.5 pr-3 font-semibold">{{ __('Dealer') }}</th>
                                <th class="py-1.5 pr-3 text-right font-semibold">{{ __('Orders') }}</th>
                                <th class="py-1.5 text-right font-semibold">{{ __('Revenue') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($topDealers as $index => $dealer)
                                @php $dealerShare = max(4, (int) round((((float) $dealer->revenue_total) / $topDealerRevenueMax) * 100)); @endphp
                                <tr class="border-b border-dotted border-[#1d2733] hover:bg-[#0a0f16]">
                                    <td class="py-2 pr-2 align-top text-[#64748b]">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</td>
                                    <td class="max-w-0 py-2 pr-3">
                                        <span class="block truncate text-[#a8b3c1]">{{ $dealer->name }}</span>
                                        <span class="mt-1 block h-[3px] bg-[#14532d]" style="width: {{ $dealerShare }}%"></span>
                                    </td>
                                    <td class="py-2 pr-3 text-right align-top text-[#64748b]">{{ number_format((int) $dealer->order_count) }}</td>
                                    <td class="py-2 text-right align-top font-semibold {{ $index === 0 ? 'text-[#4ade80]' : 'text-[#e8eef5]' }}">{{ $fmt($dealer->revenue_total) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-6 text-center text-[#3f4c5c]">{{ strtoupper(__('No dealer revenue data.')) }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ===== Daily log + order ledger ===== --}}
            <div class="grid border-t border-[#1d2733] xl:grid-cols-2">
                <div class="border-b border-[#1d2733] p-4 xl:border-b-0 xl:border-r">
                    <div class="flex items-baseline justify-between gap-3">
                        <p class="text-[10px] uppercase tracking-[0.2em] text-[#5b6b7d]">{{ __('Daily Log') }}</p>
                        <p class="text-[10px] text-[#3f4c5c]">MAX {{ $currencyLabel }} {{ $fmt($maxDailyAmount) }}</p>
                    </div>
                    <div class="mt-2 max-h-80 overflow-y-auto pr-1">
                        <table class="w-full text-[11px]">
                            <tbody>
                                @foreach ($dailyCollection->reverse() as $row)
                                    @php
                                        $rowShare = max(2, (int) round((((float) $row['amount']) / $maxDailyAmount) * 100));
                                        $isPeakRow = ($row['date'] ?? null) === $peakDate && (float) $row['amount'] > 0;
                                    @endphp
                                    <tr class="border-b border-dotted border-[#1d2733] hover:bg-[#0a0f16]">
                                        <td class="w-20 py-1.5 pr-3 text-[#64748b]">{{ $row['label'] }}</td>
                                        <td class="py-1.5 pr-3">
                                            <span class="block h-[5px] {{ $isPeakRow ? 'bg-[#fbbf24]' : 'bg-[#14532d]' }}" style="width: {{ $rowShare }}%"></span>
                                        </td>
                                        <td class="w-32 py-1.5 text-right font-semibold {{ $isPeakRow ? 'text-[#fbbf24]' : 'text-[#e8eef5]' }}">{{ $fmt($row['amount']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="p-4">
                    <div class="flex items-baseline justify-between gap-3">
                        <p class="text-[10px] uppercase tracking-[0.2em] text-[#5b6b7d]">{{ __('Order Ledger') }} &mdash; {{ __('Recent Paid Orders') }}</p>
                        <p class="text-[10px] text-[#3f4c5c]">&Sigma; {{ $currencyLabel }} {{ $fmt($recentOrdersTotal) }}</p>
                    </div>
                    <div class="mt-2 max-h-80 overflow-y-auto pr-1">
                        <table class="w-full text-[11px]">
                            <thead>
                                <tr class="border-b border-[#1d2733] text-left text-[9px] uppercase tracking-[0.16em] text-[#3f4c5c]">
                                    <th class="py-1.5 pr-3 font-semibold">{{ __('Order') }}</th>
                                    <th class="py-1.5 pr-3 font-semibold">{{ __('Customer') }}</th>
                                    <th class="py-1.5 pr-3 font-semibold">{{ __('Status') }}</th>
                                    <th class="py-1.5 pr-3 font-semibold">{{ __('Date') }}</th>
                                    <th class="py-1.5 text-right font-semibold">{{ __('Amount') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentPaidOrders as $order)
                                    <tr class="border-b border-dotted border-[#1d2733] hover:bg-[#0a0f16]">
                                        <td class="py-2 pr-3 text-[#64748b]">#{{ $order->id }}</td>
                                        <td class="max-w-0 truncate py-2 pr-3 text-[#a8b3c1]">{{ $order->user?->name ?? __('Guest') }}</td>
                                        <td class="py-2 pr-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-[#4ade80]">{{ $order->status }}</td>
                                        <td class="py-2 pr-3 text-[#64748b]">{{ optional($order->created_at)->format('m-d H:i') }}</td>
                                        <td class="py-2 text-right font-semibold text-[#e8eef5]">{{ $fmt($order->total_amount) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="py-6 text-center text-[#3f4c5c]">{{ strtoupper(__('No paid orders found.')) }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ===== Terminal footer ===== --}}
            <div class="flex flex-wrap items-center justify-between gap-2 border-t border-[#1d2733] bg-[#04070c] px-4 py-2 text-[9px] uppercase tracking-[0.16em] text-[#3f4c5c]">
                <span>{{ __('Paid statuses') }}: DELIVERED + COMPLETED</span>
                <span>{{ $chartDaysCount }} {{ __('sessions') }} &middot; {{ number_format((int) data_get($conversionSummary, 'total_orders', 0)) }} {{ __('orders in window') }}</span>
            </div>
        </section>
    </div>
</div>
</x-app-layout>
