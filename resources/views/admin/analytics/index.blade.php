<x-app-layout>

@php
    $kpi = $kpi ?? [];
    $deltaTone = function (float $delta): array {
        if ($delta > 0.5) {
            return ['icon' => 'fa-arrow-up', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'];
        }
        if ($delta < -0.5) {
            return ['icon' => 'fa-arrow-down', 'class' => 'bg-rose-50 text-rose-700 border-rose-200'];
        }
        return ['icon' => 'fa-minus', 'class' => 'bg-slate-100 text-slate-600 border-slate-200'];
    };
@endphp

<style>
    .bento-shadow { box-shadow: 0 1px 2px rgba(7,7,64,0.04), 0 4px 16px rgba(7,7,64,0.06); }
    .bento-shadow-lg { box-shadow: 0 10px 30px rgba(7,7,64,0.18), 0 30px 60px rgba(7,7,64,0.20); }
    .bento-stripes-soft {
        background-image: repeating-linear-gradient(135deg, rgba(7,7,64,0.04) 0px, rgba(7,7,64,0.04) 1px, transparent 1px, transparent 14px);
    }
    .num-display { font-feature-settings: "tnum" 1, "lnum" 1; letter-spacing: -0.025em; }
    .kicker { font-size: 10px; font-weight: 700; letter-spacing: 0.22em; text-transform: uppercase; color: #64748b; }
    .kicker-w { color: rgba(255,255,255,0.60); }
    .strip { position: absolute; left: 0; top: 0; bottom: 0; width: 4px; }
    .rank { display:inline-grid; place-items:center; width:22px; height:22px; border-radius:6px; font-size:11px; font-weight:800; background:#eef2ff; color:#3730a3; margin-right:8px; font-family: ui-monospace, monospace; }
    .rank-1 { background:#fef3c7; color:#92400e; }
    .rank-2 { background:#e0e7ff; color:#3730a3; }
    .rank-3 { background:#fce7f3; color:#9d174d; }
</style>

<div class="px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    {{-- Time range selector --}}
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4 bento-shadow">
        <div class="flex items-center gap-3">
            <div class="grid h-10 w-10 place-items-center rounded-xl bg-indigo-50 text-indigo-600">
                <i class="far fa-calendar-days"></i>
            </div>
            <div>
                <div class="kicker">{{ __('Time Range') }}</div>
                <div class="mt-0.5 text-sm font-bold text-slate-900">
                    {{ __('Analytics period') }}
                    <span class="ml-1 text-xs font-semibold text-slate-400">· {{ __('Last :n days', ['n' => $days]) }}</span>
                </div>
            </div>
        </div>
        <div class="flex gap-2">
            @foreach($allowedDays as $option)
                <a
                    href="{{ route('admin.analytics.index', ['days' => $option]) }}"
                    class="rounded-lg border px-3 py-1.5 text-xs font-bold transition
                        {{ $option === $days
                            ? 'border-indigo-300 bg-indigo-50 text-indigo-700'
                            : 'border-slate-200 bg-white text-slate-500 hover:border-slate-300 hover:text-slate-700' }}"
                >
                    {{ $option === 365 ? '1Y' : ($option . 'D') }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- KPI strip --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @php $d = $deltaTone((float) $kpi['page_views_delta']); @endphp
        <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 bento-shadow">
            <div class="strip bg-gradient-to-b from-indigo-500 to-indigo-700"></div>
            <div class="flex items-start justify-between pl-2">
                <div>
                    <p class="kicker">{{ __('Page Views') }}</p>
                    <p class="mt-1 text-[11px] text-slate-400">{{ __('All trackable pages') }}</p>
                </div>
                <div class="grid h-9 w-9 place-items-center rounded-lg bg-indigo-50 text-indigo-600"><i class="far fa-eye"></i></div>
            </div>
            <p class="num-display mt-4 pl-2 text-3xl font-extrabold text-slate-900">{{ number_format($kpi['page_views']) }}</p>
            <div class="mt-2 flex items-center gap-2 pl-2">
                <span class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[11px] font-bold {{ $d['class'] }}">
                    <i class="fas {{ $d['icon'] }} text-[9px]"></i> {{ number_format(abs($kpi['page_views_delta']), 1) }}%
                </span>
                <span class="text-[11px] text-slate-500">{{ __('vs previous period') }}</span>
            </div>
        </div>

        @php $d = $deltaTone((float) $kpi['unique_visitors_delta']); @endphp
        <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 bento-shadow">
            <div class="strip bg-gradient-to-b from-cyan-500 to-cyan-700"></div>
            <div class="flex items-start justify-between pl-2">
                <div>
                    <p class="kicker">{{ __('Unique Visitors') }}</p>
                    <p class="mt-1 text-[11px] text-slate-400">{{ __('Distinct sessions') }}</p>
                </div>
                <div class="grid h-9 w-9 place-items-center rounded-lg bg-cyan-50 text-cyan-600"><i class="fas fa-users-line"></i></div>
            </div>
            <p class="num-display mt-4 pl-2 text-3xl font-extrabold text-slate-900">{{ number_format($kpi['unique_visitors']) }}</p>
            <div class="mt-2 flex items-center gap-2 pl-2">
                <span class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[11px] font-bold {{ $d['class'] }}">
                    <i class="fas {{ $d['icon'] }} text-[9px]"></i> {{ number_format(abs($kpi['unique_visitors_delta']), 1) }}%
                </span>
                <span class="text-[11px] text-slate-500">{{ __('vs previous period') }}</span>
            </div>
        </div>

        @php $d = $deltaTone((float) $kpi['cart_adds_delta']); @endphp
        <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 bento-shadow">
            <div class="strip bg-gradient-to-b from-rose-500 to-rose-700"></div>
            <div class="flex items-start justify-between pl-2">
                <div>
                    <p class="kicker">{{ __('Add to Cart') }}</p>
                    <p class="mt-1 text-[11px] text-slate-400">{{ __('Total clicks') }}</p>
                </div>
                <div class="grid h-9 w-9 place-items-center rounded-lg bg-rose-50 text-rose-600"><i class="fas fa-cart-plus"></i></div>
            </div>
            <p class="num-display mt-4 pl-2 text-3xl font-extrabold text-slate-900">{{ number_format($kpi['cart_adds']) }}</p>
            <div class="mt-2 flex items-center gap-2 pl-2">
                <span class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[11px] font-bold {{ $d['class'] }}">
                    <i class="fas {{ $d['icon'] }} text-[9px]"></i> {{ number_format(abs($kpi['cart_adds_delta']), 1) }}%
                </span>
                <span class="text-[11px] text-slate-500">{{ __('vs previous period') }}</span>
            </div>
            <div class="mt-3 pl-2">
                <div class="mb-1 flex justify-between text-[10px] font-bold uppercase tracking-widest text-slate-400">
                    <span>{{ __('Cart conversion') }}</span><span class="text-rose-600">{{ number_format($kpi['cart_conversion_pct'], 1) }}%</span>
                </div>
                <div class="h-1.5 overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full bg-gradient-to-r from-rose-400 to-rose-600" style="width: {{ min(100, max(0, $kpi['cart_conversion_pct'])) }}%"></div>
                </div>
            </div>
        </div>

        @php $d = $deltaTone((float) $kpi['wishlist_clicks_delta']); @endphp
        <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 bento-shadow">
            <div class="strip bg-gradient-to-b from-amber-400 to-amber-600"></div>
            <div class="flex items-start justify-between pl-2">
                <div>
                    <p class="kicker">{{ __('Wishlist Clicks') }}</p>
                    <p class="mt-1 text-[11px] text-slate-400">{{ __('Saved to wishlist') }}</p>
                </div>
                <div class="grid h-9 w-9 place-items-center rounded-lg bg-amber-50 text-amber-700"><i class="far fa-heart"></i></div>
            </div>
            <p class="num-display mt-4 pl-2 text-3xl font-extrabold text-slate-900">{{ number_format($kpi['wishlist_clicks']) }}</p>
            <div class="mt-2 flex items-center gap-2 pl-2">
                <span class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[11px] font-bold {{ $d['class'] }}">
                    <i class="fas {{ $d['icon'] }} text-[9px]"></i> {{ number_format(abs($kpi['wishlist_clicks_delta']), 1) }}%
                </span>
                <span class="text-[11px] text-slate-500">{{ __('vs previous period') }}</span>
            </div>
        </div>
    </div>

    {{-- Funnel + chart --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#04042a] via-[#070740] to-[#0a0d3f] p-6 bento-shadow-lg lg:col-span-2">
            <div class="absolute inset-0 bento-stripes-soft opacity-20 pointer-events-none"></div>
            <div class="relative flex items-start justify-between gap-4 flex-wrap">
                <div>
                    <span class="kicker kicker-w">{{ __('Activity over time') }}</span>
                    <h3 class="mt-1 text-xl font-extrabold tracking-tight text-white">{{ __('Daily event volume') }}</h3>
                </div>
                <span class="rounded-md border border-amber-300/30 bg-amber-300/10 px-2 py-1 font-mono text-[10px] font-bold uppercase tracking-widest text-amber-200">
                    {{ count($dailySeries['labels']) }} {{ __('days') }}
                </span>
            </div>
            <div class="relative mt-6 h-64">
                <canvas
                    id="analyticsTrendChart"
                    data-series="{{ json_encode($dailySeries) }}"
                    data-label-map="{{ json_encode([
                        'page_views' => __('Page views'),
                        'product_views' => __('Product views'),
                        'cart_adds' => __('Cart adds'),
                        'orders' => __('Orders'),
                    ]) }}"
                ></canvas>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 bento-shadow">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-extrabold text-slate-900">{{ __('Conversion funnel') }}</h3>
                <span class="kicker">{{ __('period') }}</span>
            </div>
            @php
                $funnel = [
                    ['label' => __('Page views'), 'value' => $kpi['page_views'], 'color' => 'bg-indigo-500'],
                    ['label' => __('Product views'), 'value' => $kpi['product_views'], 'color' => 'bg-cyan-500'],
                    ['label' => __('Add to cart'), 'value' => $kpi['cart_adds'], 'color' => 'bg-rose-500'],
                    ['label' => __('Checkout started'), 'value' => $kpi['checkout_starts'], 'color' => 'bg-amber-500'],
                    ['label' => __('Orders completed'), 'value' => $kpi['orders_completed'], 'color' => 'bg-emerald-500'],
                ];
                $funnelMax = max(1, max(array_column($funnel, 'value')));
            @endphp
            <ul class="mt-4 space-y-3">
                @foreach($funnel as $row)
                    @php
                        $pct = (int) min(100, max(2, ($row['value'] / $funnelMax) * 100));
                    @endphp
                    <li>
                        <div class="flex justify-between text-[11px] font-bold text-slate-500">
                            <span class="uppercase tracking-wider">{{ $row['label'] }}</span>
                            <span class="num-display text-slate-900">{{ number_format($row['value']) }}</span>
                        </div>
                        <div class="mt-1 h-2 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full {{ $row['color'] }} rounded-full" style="width: {{ $pct }}%"></div>
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="mt-5 grid grid-cols-2 gap-4 border-t border-slate-100 pt-4">
                <div>
                    <p class="kicker">{{ __('Cart rate') }}</p>
                    <p class="num-display mt-1 text-lg font-extrabold text-slate-900">{{ number_format($kpi['cart_conversion_pct'], 1) }}%</p>
                </div>
                <div>
                    <p class="kicker">{{ __('Checkout rate') }}</p>
                    <p class="num-display mt-1 text-lg font-extrabold text-slate-900">{{ number_format($kpi['checkout_conversion_pct'], 1) }}%</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Top tables --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        @php
            $tables = [
                ['title' => __('Top viewed'), 'icon' => 'far fa-eye', 'iconColor' => 'text-indigo-600', 'rows' => $topViewed, 'empty' => __('No product views yet.')],
                ['title' => __('Top cart adds'), 'icon' => 'fas fa-cart-plus', 'iconColor' => 'text-rose-600', 'rows' => $topCartAdds, 'empty' => __('No add-to-cart events yet.')],
                ['title' => __('Top wishlisted'), 'icon' => 'far fa-heart', 'iconColor' => 'text-amber-600', 'rows' => $topWishlisted, 'empty' => __('No wishlist activity yet.')],
            ];
        @endphp
        @foreach($tables as $table)
            <div class="rounded-2xl border border-slate-200 bg-white bento-shadow">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h3 class="text-sm font-extrabold text-slate-900">
                        <i class="{{ $table['icon'] }} mr-1 {{ $table['iconColor'] }}"></i>
                        {{ $table['title'] }}
                    </h3>
                    <span class="kicker">{{ __('Last :n days', ['n' => $days]) }}</span>
                </div>
                @if($table['rows']->isEmpty())
                    <div class="px-5 py-8 text-center text-xs text-slate-400">{{ $table['empty'] }}</div>
                @else
                    <table class="w-full table-fixed text-sm">
                        <tbody>
                            @foreach($table['rows'] as $i => $row)
                                <tr class="border-b border-slate-100 last:border-0">
                                    <td class="px-5 py-2.5 truncate text-slate-700">
                                        <span class="rank {{ $i === 0 ? 'rank-1' : ($i === 1 ? 'rank-2' : ($i === 2 ? 'rank-3' : '')) }}">{{ $i + 1 }}</span>
                                        <span class="font-semibold">{{ $row['name'] }}</span>
                                        @if($row['sku'] !== '')
                                            <span class="ml-1 font-mono text-[10px] text-slate-400">{{ $row['sku'] }}</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-2.5 text-right num-display font-bold text-slate-900">{{ number_format($row['count']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Top searches --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white bento-shadow">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h3 class="text-sm font-extrabold text-slate-900">
                    <i class="fas fa-magnifying-glass mr-1 text-cyan-600"></i>
                    {{ __('Top searched keywords') }}
                </h3>
                <span class="kicker">{{ __('Last :n days', ['n' => $days]) }}</span>
            </div>
            @if($topSearches->isEmpty())
                <div class="px-5 py-8 text-center text-xs text-slate-400">{{ __('No searches recorded in this period.') }}</div>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <th class="px-5 py-3">{{ __('Keyword') }}</th>
                            <th class="px-5 py-3 text-right">{{ __('Searches') }}</th>
                            <th class="px-5 py-3 text-right">{{ __('Last seen') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($topSearches as $i => $row)
                            <tr class="border-t border-slate-100">
                                <td class="px-5 py-2.5">
                                    <span class="rank {{ $i === 0 ? 'rank-1' : ($i === 1 ? 'rank-2' : ($i === 2 ? 'rank-3' : '')) }}">{{ $i + 1 }}</span>
                                    <span class="font-mono text-slate-700">{{ $row['keyword'] }}</span>
                                </td>
                                <td class="px-5 py-2.5 text-right num-display font-bold text-slate-900">{{ number_format($row['count']) }}</td>
                                <td class="px-5 py-2.5 text-right text-xs text-slate-500">{{ $row['last_searched_at']->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white bento-shadow">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h3 class="text-sm font-extrabold text-slate-900">
                    <i class="far fa-clock mr-1 text-slate-500"></i>
                    {{ __('Recent searches') }}
                </h3>
                <span class="kicker">{{ __('All-time') }}</span>
            </div>
            @if($recentSearches->isEmpty())
                <div class="px-5 py-8 text-center text-xs text-slate-400">{{ __('No recent searches.') }}</div>
            @else
                <div class="flex flex-wrap gap-2 px-5 py-4">
                    @foreach($recentSearches as $row)
                        <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs text-slate-700">
                            <span class="font-mono">{{ $row['keyword'] }}</span>
                            <span class="rounded bg-white px-1.5 py-0.5 text-[10px] font-bold text-slate-500 border border-slate-200">{{ $row['count'] }}</span>
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <p class="text-right text-[11px] text-slate-400">
        {{ __('Last refreshed :time', ['time' => $generatedAt->diffForHumans()]) }}
    </p>
</div>

@php
    $analyticsManifestPath = public_path('build/manifest.json');
    $analyticsAssetReady = false;
    if (file_exists($analyticsManifestPath)) {
        $manifest = json_decode((string) file_get_contents($analyticsManifestPath), true) ?: [];
        $analyticsAssetReady = isset($manifest['resources/js/admin-analytics.js']);
    }
@endphp

@if($analyticsAssetReady)
    @vite('resources/js/admin-analytics.js')
@endif

</x-app-layout>
