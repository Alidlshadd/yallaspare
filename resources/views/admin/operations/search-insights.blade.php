<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold text-slate-800 dark:text-slate-100">{{ __('Search Insights') }}</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('See what customers search for and which searches need better product coverage.') }}</p>
        </div>
    </x-slot>

    @php
        $pageRows = $keywords->getCollection();
        $pageMaxCount = max(1, (int) $pageRows->max('search_count'));
        $pulseMax = max(1, (int) collect($demandPulse)->max('count'));
        $canManageProducts = auth()->user()?->can(\App\Models\User::PERMISSION_PRODUCTS_MANAGE) ?? false;
        $chipUrl = fn (array $overrides) => route('admin.search-insights.index', array_filter(array_merge([
            'search' => $search !== '' ? $search : null,
            'sort' => $sort !== 'search_count' ? $sort : null,
            'dir' => $direction !== 'desc' ? $direction : null,
            'window' => $window !== 'all' ? $window : null,
            'zero_hit' => $zeroHitOnly ? 1 : null,
        ], $overrides), fn ($param) => $param !== null));
        $sortLabels = ['search_count' => __('Search count'), 'last_searched_at' => __('Last searched'), 'keyword' => __('Keyword')];
    @endphp

    <style>
        .si-hero {
            position: relative; overflow: hidden;
            background: linear-gradient(135deg, #04042a, #10104a);
            border-radius: 16px; color: #fff;
        }
        .si-hero::after {
            content: ""; position: absolute; inset: 0;
            background-image: repeating-linear-gradient(135deg, rgba(255,255,255,0.05) 0 1px, transparent 1px 14px);
        }
        .si-hero > * { position: relative; z-index: 1; }
        .si-mono { font-family: ui-monospace, 'JetBrains Mono', Consolas, monospace; font-variant-numeric: tabular-nums; }
    </style>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">

            {{-- ===== Navy hero: terminal bar + ticker ===== --}}
            <section class="si-hero">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-white/10 px-5 py-3 text-[11px] font-semibold uppercase tracking-[0.14em] text-white/55">
                    <span><span class="text-amber-400">&#9679;</span> Yalla Spare &mdash; <span class="text-white">{{ __('Search Terminal') }}</span></span>
                    <span>{{ __('Sort') }}: <span class="text-white">{{ $sortLabels[$sort] ?? $sort }} {{ $direction === 'desc' ? '↓' : '↑' }}</span> &middot; <span class="text-white si-mono">{{ now()->format('H:i') }}</span></span>
                </div>
                <div class="flex gap-9 overflow-x-auto whitespace-nowrap px-5 py-4">
                    <div>
                        <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-white/50">{{ __('Keywords') }}</p>
                        <p class="si-mono text-xl font-black text-white">{{ number_format($summary['keywords']) }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-white/50">{{ __('Total Searches') }}</p>
                        <p class="si-mono text-xl font-black text-white">{{ number_format($summary['searches']) }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-white/50">{{ __('Zero-Hit On Page') }}</p>
                        <p class="si-mono text-xl font-black {{ $summary['zero_result_on_page'] > 0 ? 'text-rose-300' : 'text-emerald-300' }}">{{ number_format($summary['zero_result_on_page']) }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-white/50">{{ __('Missed Demand On Page') }}</p>
                        <p class="si-mono text-xl font-black {{ $summary['missed_demand_on_page'] > 0 ? 'text-rose-300' : 'text-emerald-300' }}">{{ number_format($summary['missed_demand_on_page']) }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-white/50">{{ __('Coverage On Page') }}</p>
                        <p class="si-mono text-xl font-black text-emerald-300">{{ number_format($summary['coverage_on_page'], 1) }}%</p>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-white/50">{{ __('Top Keyword') }}</p>
                        <p class="max-w-[220px] truncate text-xl font-black text-amber-400">{{ $summary['top_keyword'] ?: __('N/A') }}</p>
                    </div>
                </div>
            </section>

            {{-- ===== Chips + search/sort form ===== --}}
            <section class="flex flex-wrap items-center gap-2">
                <a href="{{ $chipUrl(['window' => null, 'zero_hit' => null]) }}"
                   class="inline-flex items-center gap-2 rounded-full border px-3.5 py-1.5 text-xs font-bold transition {{ (!$zeroHitOnly && $window === 'all') ? 'border-[#04042a] bg-[#04042a] text-amber-400' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300' }}">
                    {{ __('All') }}
                    <span class="rounded-full px-2 py-px text-[10px] {{ (!$zeroHitOnly && $window === 'all') ? 'bg-amber-400/20' : 'bg-slate-900/5 dark:bg-white/10' }}">{{ number_format($windowCounts['all']) }}</span>
                </a>
                <a href="{{ $chipUrl(['zero_hit' => $zeroHitOnly ? null : 1]) }}"
                   class="inline-flex items-center gap-2 rounded-full border px-3.5 py-1.5 text-xs font-bold transition {{ $zeroHitOnly ? 'border-rose-700 bg-rose-700 text-white' : 'border-rose-200 bg-rose-50 text-rose-700 hover:border-rose-300 dark:border-rose-900/60 dark:bg-rose-950/30 dark:text-rose-300' }}">
                    &#9888; {{ __('Zero-Hit Only') }}
                    @if ($zeroHitOnly)
                        <span class="rounded-full bg-white/20 px-2 py-px text-[10px]">{{ number_format($keywords->total()) }}</span>
                    @endif
                </a>
                <a href="{{ $chipUrl(['window' => $window === '7' ? null : '7']) }}"
                   class="inline-flex items-center gap-2 rounded-full border px-3.5 py-1.5 text-xs font-bold transition {{ $window === '7' ? 'border-[#04042a] bg-[#04042a] text-amber-400' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300' }}">
                    {{ __('Last 7 days') }}
                    <span class="rounded-full px-2 py-px text-[10px] {{ $window === '7' ? 'bg-amber-400/20' : 'bg-slate-900/5 dark:bg-white/10' }}">{{ number_format($windowCounts['7']) }}</span>
                </a>
                <a href="{{ $chipUrl(['window' => $window === '30' ? null : '30']) }}"
                   class="inline-flex items-center gap-2 rounded-full border px-3.5 py-1.5 text-xs font-bold transition {{ $window === '30' ? 'border-[#04042a] bg-[#04042a] text-amber-400' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300' }}">
                    {{ __('Last 30 days') }}
                    <span class="rounded-full px-2 py-px text-[10px] {{ $window === '30' ? 'bg-amber-400/20' : 'bg-slate-900/5 dark:bg-white/10' }}">{{ number_format($windowCounts['30']) }}</span>
                </a>

                <form method="GET" action="{{ route('admin.search-insights.index') }}" class="ml-auto flex flex-wrap items-center gap-2">
                    @if ($window !== 'all')<input type="hidden" name="window" value="{{ $window }}">@endif
                    @if ($zeroHitOnly)<input type="hidden" name="zero_hit" value="1">@endif
                    <input type="search" name="search" value="{{ $search }}" placeholder="{{ __('Search keyword') }}"
                           class="h-9 rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                    <select name="sort" class="h-9 rounded-xl border-slate-300 py-0 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        @foreach($sortLabels as $value => $label)
                            <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="dir" class="h-9 rounded-xl border-slate-300 py-0 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        <option value="desc" @selected($direction === 'desc')>{{ __('Descending') }}</option>
                        <option value="asc" @selected($direction === 'asc')>{{ __('Ascending') }}</option>
                    </select>
                    <button class="h-9 rounded-xl bg-[#04042a] px-4 text-sm font-semibold text-amber-400 transition hover:bg-[#10104a]">{{ __('Filter') }}</button>
                </form>
            </section>

            {{-- ===== Register + gap panels ===== --}}
            <section class="grid gap-5 xl:grid-cols-[1.65fr_1fr]">
                <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex flex-wrap items-baseline justify-between gap-2 px-5 pt-4 pb-2">
                        <p class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">{{ __('Keyword Register') }} <span class="text-amber-600">&mdash; {{ __('by demand') }}</span></p>
                        <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">{{ number_format($keywords->total()) }} {{ __('records') }}</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-[10px] font-extrabold uppercase tracking-[0.14em] text-slate-500 dark:bg-slate-950/40 dark:text-slate-400">
                                <tr>
                                    <th class="w-12 px-5 py-3 text-left">#</th>
                                    <th class="px-5 py-3 text-left">{{ __('Keyword') }}</th>
                                    <th class="px-5 py-3 text-right">{{ __('Search Count') }}</th>
                                    <th class="px-5 py-3 text-right">{{ __('Matching Products') }}</th>
                                    <th class="px-5 py-3 text-right">{{ __('Last Searched') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @forelse($keywords as $index => $keyword)
                                    @php
                                        $isZero = (int) $keyword->matching_products_count === 0;
                                        $demandShare = max(4, (int) round((((int) $keyword->search_count) / $pageMaxCount) * 100));
                                    @endphp
                                    <tr class="{{ $isZero ? 'bg-rose-50/60 dark:bg-rose-950/15' : '' }}">
                                        <td class="si-mono px-5 py-3 text-xs text-slate-400">{{ str_pad($keywords->firstItem() + $index, 2, '0', STR_PAD_LEFT) }}</td>
                                        <td class="px-5 py-3">
                                            <span class="block font-bold text-slate-900 dark:text-slate-100">{{ $keyword->keyword }}</span>
                                            <span class="mt-1.5 block h-1 max-w-[260px] rounded-full {{ $isZero ? 'bg-gradient-to-r from-rose-600 to-rose-400' : 'bg-gradient-to-r from-amber-500 to-amber-300' }}" style="width: {{ $demandShare }}%"></span>
                                        </td>
                                        <td class="si-mono px-5 py-3 text-right font-bold text-slate-800 dark:text-slate-200">{{ number_format((int) $keyword->search_count) }}</td>
                                        <td class="px-5 py-3 text-right">
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-extrabold {{ $isZero ? 'bg-rose-100 text-rose-700 dark:bg-rose-950/40 dark:text-rose-200' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-200' }}">
                                                {{ $isZero ? __('0 HIT') : number_format((int) $keyword->matching_products_count) }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-3 text-right text-xs text-slate-500">{{ optional($keyword->last_searched_at)->diffForHumans() ?? __('N/A') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="px-5 py-12 text-center text-slate-500">{{ __('No search analytics found.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $keywords->links() }}</div>
                </article>

                <div class="grid content-start gap-5">
                    {{-- Coverage gap register --}}
                    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-baseline justify-between gap-3">
                            <p class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">{{ __('Coverage Gap Register') }}</p>
                            <p class="text-[11px] font-extrabold uppercase tracking-[0.12em] {{ $summary['zero_result_on_page'] > 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ number_format($summary['zero_result_on_page']) }} {{ __('on page') }}</p>
                        </div>
                        <div class="mt-3 space-y-2">
                            @forelse ($coverageGaps as $gap)
                                <div class="flex items-center justify-between gap-3 rounded-xl border border-rose-100 bg-rose-50/70 px-3 py-2.5 dark:border-rose-900/50 dark:bg-rose-950/20">
                                    <span class="min-w-0 truncate text-sm font-bold text-slate-800 dark:text-slate-100">{{ $gap->keyword }}</span>
                                    <span class="si-mono shrink-0 text-sm font-extrabold text-rose-600 dark:text-rose-300">{{ number_format((int) $gap->search_count) }}</span>
                                    @if ($canManageProducts)
                                        <a href="{{ route('admin.products.create', ['name' => $gap->keyword]) }}"
                                           class="shrink-0 rounded-lg bg-[#04042a] px-2.5 py-1.5 text-[11px] font-extrabold text-amber-400 transition hover:bg-[#10104a]">{{ __('Add Product') }}</a>
                                    @endif
                                </div>
                            @empty
                                <div class="rounded-xl border border-dashed border-emerald-200 bg-emerald-50/60 px-4 py-5 text-center text-sm font-semibold text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/20 dark:text-emerald-300">
                                    {{ __('Every keyword on this page matches at least one product.') }}
                                </div>
                            @endforelse
                        </div>
                        <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-950/40">
                            <div class="flex items-baseline justify-between">
                                <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-slate-400">{{ __('Coverage Score') }}</p>
                                <p class="si-mono text-sm font-black text-emerald-600 dark:text-emerald-400">{{ number_format($summary['coverage_on_page'], 1) }}%</p>
                            </div>
                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-rose-100 dark:bg-rose-950/50">
                                <div class="h-full rounded-full bg-gradient-to-r from-emerald-600 to-emerald-400" style="width: {{ number_format($summary['coverage_on_page'], 1) }}%"></div>
                            </div>
                        </div>
                    </article>

                    {{-- Demand pulse --}}
                    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-baseline justify-between gap-3">
                            <p class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">{{ __('Demand Pulse') }} <span class="text-amber-600">&mdash; {{ __('7 days') }}</span></p>
                            <p class="text-[10px] font-bold uppercase tracking-[0.1em] text-slate-400">{{ __('Active keywords / day') }}</p>
                        </div>
                        <div class="mt-4 flex h-24 items-end gap-1.5">
                            @foreach ($demandPulse as $pulse)
                                @php
                                    $pulseHeight = max(6, (int) round(($pulse['count'] / $pulseMax) * 100));
                                    $isToday = $pulse['date'] === now()->toDateString();
                                @endphp
                                <div class="flex flex-1 flex-col items-center gap-1.5">
                                    <div class="flex h-20 w-full items-end">
                                        <div class="w-full rounded-t-md {{ $isToday ? 'bg-gradient-to-t from-[#04042a] to-[#2a2a7a]' : 'bg-gradient-to-t from-amber-500 to-amber-300' }}"
                                             style="height: {{ $pulseHeight }}%"
                                             title="{{ $pulse['date'] }} &mdash; {{ number_format($pulse['count']) }} {{ __('keywords') }}"></div>
                                    </div>
                                    <span class="text-[9px] font-bold uppercase tracking-[0.1em] {{ $isToday ? 'text-slate-800 dark:text-slate-100' : 'text-slate-400' }}">{{ $pulse['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </article>
                </div>
            </section>

            {{-- ===== Footer strip ===== --}}
            <section class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400 dark:border-slate-800 dark:bg-slate-900">
                <span>{{ __('Zero-hit rows are highlighted in rose') }}</span>
                <span>{{ __('Page') }} {{ $keywords->currentPage() }} / {{ max(1, $keywords->lastPage()) }} &middot; {{ number_format($keywords->total()) }} {{ __('records') }}</span>
            </section>
        </div>
    </div>
</x-app-layout>
