<x-app-layout>

<style>
    .bento-shadow { box-shadow: var(--admin-shadow-soft); }
    .kicker { font-size: 10px; font-weight: 700; letter-spacing: 0.22em; text-transform: uppercase; color: var(--text-muted); }
    .num-display { font-feature-settings: "tnum" 1, "lnum" 1; letter-spacing: -0.025em; }
    .rank { display:inline-grid; place-items:center; width:22px; height:22px; border-radius:6px; font-size:11px; font-weight: 700; background:var(--admin-surface-strong); color:var(--admin-text); margin-right:8px; font-family: ui-monospace, monospace; }
    .rank-1 { background:#fef3c7; color:#92400e; }
    .rank-2 { background:#e0e7ff; color:#3730a3; }
    .rank-3 { background:#fce7f3; color:#9d174d; }
    :where(.dark) .rank-1 { background:rgb(251 191 36 / .14); color:#fcd34d; }
    :where(.dark) .rank-2 { background:rgb(129 140 248 / .14); color:#a5b4fc; }
    :where(.dark) .rank-3 { background:rgb(244 114 182 / .14); color:#f9a8d4; }
</style>

<div class="px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    {{-- Breadcrumb / back --}}
    <div class="flex items-center gap-2 text-xs font-bold">
        <a href="{{ route('admin.analytics.index', ['days' => $days]) }}" class="inline-flex items-center gap-1.5 text-slate-500 hover:text-info transition">
            <i class="fas fa-arrow-left text-[10px] rtl:rotate-180" aria-hidden="true"></i>
            {{ __('Site Analytics') }}
        </a>
        <span class="text-slate-300">/</span>
        <span class="text-slate-900">{{ __('Top searched keywords') }}</span>
    </div>

    {{-- Time range selector --}}
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4 bento-shadow">
        <div class="flex items-center gap-3">
            <div class="grid h-10 w-10 place-items-center rounded-xl bg-info/10 text-info">
                <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
            </div>
            <div>
                <div class="kicker">{{ __('Time Range') }}</div>
                <div class="mt-0.5 text-sm font-bold text-slate-900">
                    {{ __('Top :n keywords', ['n' => number_format(\App\Services\Analytics\AnalyticsQueryService::SEARCHES_PAGE_LIMIT)]) }}
                    <span class="ml-1 text-xs font-semibold text-muted">· {{ __('Last :n days', ['n' => $days]) }}</span>
                </div>
            </div>
        </div>
        <div class="flex gap-2">
            @foreach($allowedDays as $option)
                <a
                    href="{{ route('admin.analytics.searches', ['days' => $option]) }}"
                    class="rounded-lg border px-3 py-1.5 text-xs font-bold transition
  {{ $option === $days
  ? 'border-info/30 bg-info/10 text-info'
  : 'border-slate-200 bg-white text-slate-500 hover:border-slate-300 hover:text-slate-700' }}"
                >
                    {{ $option === 365 ? '1Y' : ($option . 'D') }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white bento-shadow">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <h3 class="text-sm font-bold text-slate-900">
                <i class="fas fa-magnifying-glass mr-1 text-info" aria-hidden="true"></i>
                {{ __('Top searched keywords') }}
            </h3>
            <span class="kicker">{{ number_format($rows->count()) }} {{ __('keywords') }} · {{ number_format($totalSearches) }} {{ __('Searches') }}</span>
        </div>
        @if($rows->isEmpty())
            <div class="px-5 py-10 text-center text-xs text-muted">{{ __('No searches recorded in this period.') }}</div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <th class="px-5 py-3">{{ __('Keyword') }}</th>
                        <th class="px-5 py-3 text-right">{{ __('Searches') }}</th>
                        <th class="px-5 py-3 text-right">{{ __('Share') }}</th>
                        <th class="px-5 py-3 text-right">{{ __('Last seen') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $i => $row)
                        @php $share = $totalSearches > 0 ? ($row['count'] / $totalSearches) * 100 : 0; @endphp
                        <tr class="border-t border-slate-100">
                            <td class="px-5 py-2.5">
                                <span class="rank {{ $i === 0 ? 'rank-1' : ($i === 1 ? 'rank-2' : ($i === 2 ? 'rank-3' : '')) }}">{{ $i + 1 }}</span>
                                <span class="font-mono text-slate-700">{{ $row['keyword'] }}</span>
                            </td>
                            <td class="px-5 py-2.5 text-right num-display font-bold text-slate-900">{{ number_format($row['count']) }}</td>
                            <td class="px-5 py-2.5 text-right text-xs text-slate-500">{{ number_format($share, 1) }}%</td>
                            <td class="px-5 py-2.5 text-right text-xs text-slate-500" title="{{ $row['last_searched_at']->format('Y-m-d H:i') }}">
                                {{ $row['last_searched_at']->diffForHumans() }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <p class="text-right text-[11px] text-muted">
        {{ __('Last refreshed :time', ['time' => $generatedAt->diffForHumans()]) }}
    </p>
</div>

</x-app-layout>
