<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-slate-100">{{ __('Activity Logs') }}</h2>
            <p class="text-sm text-gray-500 mt-1 dark:text-slate-400">{{ __('Super admin audit trail') }}</p>
        </div>
    </x-slot>

    @php
        $filters = ['All', 'Product', 'Category', 'Inventory', 'User', 'Order', 'Coupon', 'Discount', 'Setting'];
        $filterUrl = function (string $filter) use ($search) {
            return route('admin.activity-logs.index', array_filter([
                'model' => $filter === 'All' ? null : $filter,
                'q' => $search ?: null,
            ]));
        };
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

            {{-- Insights --}}
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-slate-700/60 dark:bg-slate-900">
                    <p class="text-[11px] font-bold uppercase tracking-widest text-gray-500 dark:text-slate-400">{{ __('Total logged') }}</p>
                    <p class="mt-2 text-2xl font-extrabold tabular-nums text-gray-900 dark:text-white">{{ number_format($totalCount) }}</p>
                    <p class="mt-1 text-[11px] text-gray-400 dark:text-slate-500">{{ __('All recorded events') }}</p>
                </div>
                <div class="rounded-xl border border-emerald-300/70 bg-white p-4 shadow-sm dark:border-emerald-400/35 dark:bg-slate-900">
                    <p class="text-[11px] font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-300">{{ __('Today') }}</p>
                    <p class="mt-2 text-2xl font-extrabold tabular-nums text-emerald-700 dark:text-emerald-300">{{ number_format($todayCount) }}</p>
                    <p class="mt-1 text-[11px] text-gray-400 dark:text-slate-500">{{ __('Events since midnight') }}</p>
                </div>
                <div class="rounded-xl border border-indigo-300/70 bg-white p-4 shadow-sm dark:border-indigo-400/35 dark:bg-slate-900">
                    <p class="text-[11px] font-bold uppercase tracking-widest text-indigo-600 dark:text-indigo-300">{{ __('Most active admin') }}</p>
                    <p class="mt-2 truncate text-2xl font-extrabold text-indigo-700 dark:text-indigo-300">{{ $topCauser['name'] ?? '—' }}</p>
                    <p class="mt-1 text-[11px] text-gray-400 dark:text-slate-500">{{ isset($topCauser['count']) ? __(':count events', ['count' => number_format($topCauser['count'])]) : __('No activity yet') }}</p>
                </div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-gray-500 dark:text-slate-400">
                    {{ __('Showing') }} <span class="font-semibold text-gray-800 dark:text-slate-100">{{ $logs->count() }}</span>
                    {{ __('of') }} <span class="font-semibold text-gray-800 dark:text-slate-100">{{ number_format($logs->total()) }}</span>
                    @if(!empty($model))
                        · <span class="font-semibold text-gray-800 dark:text-slate-100">{{ __($model) }}</span>
                    @endif
                    @if(!empty($search))
                        · "{{ $search }}"
                    @endif
                </p>
                <a
                    href="{{ route('admin.activity-logs.export-excel', array_filter(['model' => $model])) }}"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-amber-400 px-4 py-2 text-sm font-bold text-slate-900 shadow-sm transition hover:bg-amber-300"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v11m0 0 4-4m-4 4-4-4" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 19h14" />
                    </svg>
                    {{ __('Export') }}
                </a>
            </div>

            <div class="grid gap-4 lg:grid-cols-[240px_minmax(0,1fr)] items-start">
                {{-- Facet rail --}}
                <aside class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm dark:border-slate-800 dark:bg-slate-900 lg:sticky lg:top-4">
                    <p class="px-3 pb-2 text-[11px] font-bold uppercase tracking-widest text-gray-400 dark:text-slate-500">{{ __('Filter by model') }}</p>
                    <nav class="space-y-0.5" aria-label="{{ __('Model') }}">
                        @foreach($filters as $filterKey)
                            @php
                                $isActive = ($filterKey === 'All' && empty($model)) || ($filterKey !== 'All' && $model === $filterKey);
                                $count = $filterKey === 'All' ? $totalCount : ($modelCounts[$filterKey] ?? 0);
                            @endphp
                            <a
                                href="{{ $filterUrl($filterKey) }}"
                                @class([
                                    'flex items-center justify-between gap-2 rounded-lg px-3 py-2 text-sm font-semibold transition',
                                    'border-s-2 border-amber-400 bg-amber-50 text-gray-900 dark:bg-amber-400/10 dark:text-white' => $isActive,
                                    'text-gray-600 hover:bg-gray-50 dark:text-slate-300 dark:hover:bg-slate-800/60' => !$isActive,
                                ])
                            >
                                <span class="truncate">{{ __($filterKey) }}</span>
                                <span class="text-xs tabular-nums text-gray-400 dark:text-slate-500">{{ number_format($count) }}</span>
                            </a>
                        @endforeach
                    </nav>
                </aside>

                {{-- Results --}}
                <div class="min-w-0 rounded-xl border border-gray-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center gap-2 border-b border-gray-200 p-3 dark:border-slate-800">
                        <div class="relative w-full max-w-sm">
                            <svg class="pointer-events-none absolute inset-y-0 my-auto ms-3 h-4 w-4 text-gray-400 dark:text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <circle cx="11" cy="11" r="7" />
                                <path stroke-linecap="round" d="m20 20-3.5-3.5" />
                            </svg>
                            <input
                                id="activityLogSearch"
                                type="text"
                                value="{{ $search ?? '' }}"
                                placeholder="{{ __('Search logs...') }}"
                                class="w-full rounded-lg border-gray-300 bg-white ps-9 text-sm text-slate-900 placeholder:text-gray-400 focus:border-amber-400 focus:ring-2 focus:ring-amber-400/30 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500"
                                autocomplete="off"
                            />
                        </div>
                        @if(!empty($search))
                            <a href="{{ route('admin.activity-logs.index', array_filter(['model' => $model])) }}" class="shrink-0 text-xs font-semibold text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-slate-200">
                                {{ __('Clear') }} ✕
                            </a>
                        @endif
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-slate-800 text-gray-600 dark:text-slate-300">
                                <tr>
                                    <th class="w-12 px-3 py-3 text-left font-semibold"></th>
                                    <th class="px-4 py-3 text-left font-semibold">{{ __('Admin') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold">{{ __('Action') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold">{{ __('Target') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold">{{ __('Date') }}</th>
                                </tr>
                            </thead>

                            @forelse($logs as $log)
                                @php
                                    $props = \App\Support\ActivityLogPresenter::properties($log);
                                    $old = (array) ($props['old'] ?? []);
                                    $attributes = (array) ($props['attributes'] ?? []);
                                    $keys = array_unique(array_merge(array_keys($old), array_keys($attributes)));
                                    $ignoreKeys = [
                                        'id',
                                        'created_at',
                                        'updated_at',
                                        'deleted_at',
                                    ];
                                    $keys = array_values(array_filter($keys, function ($key) use ($ignoreKeys) {
                                        if (in_array($key, $ignoreKeys, true)) {
                                            return false;
                                        }
                                        if (str_contains((string) $key, 'password')) {
                                            return false;
                                        }
                                        return true;
                                    }));

                                    $causerInitial = strtoupper(substr((string) ($log->causer?->name ?: 'System'), 0, 1));
                                    $target = \App\Support\ActivityLogPresenter::target($log);
                                    $actorRole = \App\Support\ActivityLogPresenter::actorRole($log);
                                @endphp

                                <tbody x-data="toggle" class="border-b border-gray-100 dark:border-slate-800 last:border-b-0">
                                    <tr class="transition-colors hover:bg-gray-50/70 dark:hover:bg-slate-800/60">
                                        <td class="px-3 py-3">
                                            <button
                                                type="button"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 shadow-sm transition hover:scale-105 hover:border-gray-300 hover:text-gray-700 hover:shadow dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-slate-600 dark:hover:text-white"
                                                @click="toggle()"
                                                :aria-expanded="ariaExpanded"
                                                aria-label="{{ __('Toggle log details') }}"
                                                title="{{ __('Toggle details') }}"
                                            >
                                                <span class="relative block h-4 w-4">
                                                    <svg
                                                        x-show="!open"
                                                        x-transition.opacity.duration.150ms
                                                        class="absolute inset-0 h-4 w-4"
                                                        viewBox="0 0 20 20"
                                                        fill="currentColor"
                                                        aria-hidden="true"
                                                    >
                                                        <path fill-rule="evenodd" d="M7.22 4.97a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 1 1-1.06-1.06L10.94 10 7.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                                    </svg>
                                                    <svg
                                                        x-show="open"
                                                        x-transition.opacity.duration.150ms
                                                        class="absolute inset-0 h-4 w-4"
                                                        viewBox="0 0 20 20"
                                                        fill="currentColor"
                                                        aria-hidden="true"
                                                    >
                                                        <path fill-rule="evenodd" d="M4.97 7.22a.75.75 0 0 1 1.06 0L10 11.19l3.97-3.97a.75.75 0 1 1 1.06 1.06l-4.5 4.5a.75.75 0 0 1-1.06 0l-4.5-4.5a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                                    </svg>
                                                </span>
                                            </button>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2.5">
                                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gray-100 text-[11px] font-extrabold text-gray-600 dark:bg-slate-800 dark:text-slate-300">
                                                    {{ $causerInitial }}
                                                </span>
                                                <div class="min-w-0">
                                                    <div class="truncate font-semibold text-gray-800 dark:text-slate-100">{{ $log->causer?->name ?? __('System') }}</div>
                                                    <div class="truncate text-[11px] text-gray-400 dark:text-slate-500">
                                                        {{ $log->causer?->email ?: $actorRole }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="font-semibold text-gray-700 dark:text-slate-200">{{ \App\Support\ActivityLabelHelper::label($log->description, $log->subject_type) }}</div>
                                            <div class="mt-0.5 text-[11px] text-gray-400 dark:text-slate-500">{{ __('on :type', ['type' => $target['type']]) }}</div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex min-w-[220px] items-center gap-2.5">
                                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-[11px] font-extrabold text-amber-700 ring-1 ring-inset ring-amber-200 dark:bg-amber-400/10 dark:text-amber-300 dark:ring-amber-400/20">
                                                    {{ $target['initial'] }}
                                                </span>
                                                <div class="min-w-0">
                                                    @if($target['url'])
                                                        <a href="{{ $target['url'] }}" class="block truncate font-semibold text-gray-800 hover:text-amber-700 dark:text-slate-100 dark:hover:text-amber-300">{{ $target['name'] }}</a>
                                                    @else
                                                        <div class="truncate font-semibold text-gray-800 dark:text-slate-100">{{ $target['name'] }}</div>
                                                    @endif
                                                    <div class="truncate text-[11px] text-gray-400 dark:text-slate-500">
                                                        {{ $target['type'] }} #{{ $target['id'] ?? '-' }}@if($target['secondary']) · {{ $target['secondary'] }}@endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3">
                                            <div class="font-mono text-xs text-gray-500 dark:text-slate-400">{{ $log->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s') ?? '-' }}</div>
                                            <div class="mt-0.5 text-[11px] text-gray-400 dark:text-slate-500">{{ $log->created_at?->diffForHumans() }}</div>
                                        </td>
                                    </tr>

                                    <tr x-show="open" x-transition.opacity.duration.150ms>
                                        <td colspan="5" class="px-4 pb-4 pt-1">
                                            <div class="rounded-xl border border-gray-200 bg-gray-50/70 p-3 dark:border-slate-800 dark:bg-slate-900/50">
                                                <div class="mb-3 grid gap-2 md:grid-cols-2 xl:grid-cols-4">
                                                    <div class="rounded-lg border border-gray-200 bg-white px-3 py-2.5 dark:border-slate-800 dark:bg-slate-950/50">
                                                        <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-slate-500">{{ __('Performed by') }}</div>
                                                        <div class="mt-1 truncate text-sm font-bold text-gray-800 dark:text-slate-100">{{ $log->causer?->name ?? __('System action') }}</div>
                                                        <div class="truncate text-[11px] text-gray-500 dark:text-slate-400">{{ $actorRole }}@if($log->causer?->email) · {{ $log->causer->email }}@endif</div>
                                                    </div>
                                                    <div class="rounded-lg border border-amber-200 bg-amber-50/60 px-3 py-2.5 dark:border-amber-400/20 dark:bg-amber-400/5">
                                                        <div class="text-[10px] font-bold uppercase tracking-widest text-amber-700/70 dark:text-amber-300/70">{{ __('Affected record') }}</div>
                                                        <div class="mt-1 truncate text-sm font-bold text-gray-800 dark:text-slate-100">{{ $target['name'] }}</div>
                                                        <div class="truncate text-[11px] text-gray-500 dark:text-slate-400">{{ $target['type'] }} #{{ $target['id'] ?? '-' }}@if($target['secondary']) · {{ $target['secondary'] }}@endif</div>
                                                    </div>
                                                    <div class="rounded-lg border border-gray-200 bg-white px-3 py-2.5 dark:border-slate-800 dark:bg-slate-950/50">
                                                        <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-slate-500">{{ __('Exact time') }}</div>
                                                        <div class="mt-1 font-mono text-sm font-bold text-gray-800 dark:text-slate-100">{{ $log->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s') ?? '-' }}</div>
                                                        <div class="text-[11px] text-gray-500 dark:text-slate-400">{{ config('app.timezone') }}</div>
                                                    </div>
                                                    <div class="rounded-lg border border-gray-200 bg-white px-3 py-2.5 dark:border-slate-800 dark:bg-slate-950/50">
                                                        <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-slate-500">{{ __('Event context') }}</div>
                                                        <div class="mt-1 font-mono text-sm font-bold text-gray-800 dark:text-slate-100">#{{ $log->id }} · {{ $log->event ?: $log->description }}</div>
                                                        <div class="truncate text-[11px] text-gray-500 dark:text-slate-400">{{ $log->batch_uuid ? __('Batch: :id', ['id' => $log->batch_uuid]) : __('Single operation') }}</div>
                                                    </div>
                                                </div>

                                                <div class="mb-2 flex items-center justify-between gap-3">
                                                    <div>
                                                        <div class="text-xs font-bold text-gray-700 dark:text-slate-200">{{ __('Changed fields') }}</div>
                                                        <div class="text-[11px] text-gray-400 dark:text-slate-500">{{ __('Previous and new values recorded for this action.') }}</div>
                                                    </div>
                                                    @if($target['url'])
                                                        <a href="{{ $target['url'] }}" class="shrink-0 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-[11px] font-bold text-gray-600 hover:border-amber-300 hover:text-amber-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-amber-400/40 dark:hover:text-amber-300">{{ __('Open target') }}</a>
                                                    @endif
                                                </div>

                                                @if(empty($keys))
                                                    <span class="text-xs text-gray-400 dark:text-slate-500">{{ __('No field-level changes.') }}</span>
                                                @else
                                                    <div class="max-h-64 space-y-1 overflow-y-auto pr-1">
                                                        @foreach($keys as $key)
                                                            @php
                                                                $oldVal = $old[$key] ?? null;
                                                                $newVal = $attributes[$key] ?? null;
                                                                if ($oldVal === $newVal) { continue; }
                                                                $isNumeric = is_numeric($oldVal) && is_numeric($newVal);
                                                                $delta = $isNumeric ? ($newVal - $oldVal) : null;
                                                            @endphp
                                                            <div class="grid grid-cols-[minmax(120px,180px),1fr] items-center gap-2 rounded-md border border-gray-200 bg-white/70 px-2 py-1.5 dark:border-slate-800 dark:bg-slate-900/40">
                                                                <div class="truncate text-[11px] font-semibold text-gray-600 dark:text-slate-300">{{ \App\Support\ActivityLogPresenter::fieldLabel((string) $key) }}</div>
                                                                <div class="flex flex-wrap items-center gap-2 text-[11px]">
                                                                    <span class="text-rose-600 line-through dark:text-rose-400">{{ \App\Support\ActivityLogPresenter::value((string) $key, $oldVal) }}</span>
                                                                    <span class="text-gray-400 dark:text-slate-500">→</span>
                                                                    <span class="text-emerald-600 dark:text-emerald-400">{{ \App\Support\ActivityLogPresenter::value((string) $key, $newVal) }}</span>
                                                                    @if($delta !== null)
                                                                        <span class="ml-2 inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $delta >= 0 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-400/10 dark:text-rose-300' }}">
                                                                            {{ $delta >= 0 ? '+' : '' }}{{ $delta }}
                                                                        </span>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif

                                                <details class="group mt-3">
                                                    <summary class="cursor-pointer text-xs font-semibold text-gray-600 hover:text-gray-800 dark:text-slate-300 dark:hover:text-slate-100">
                                                        {{ __('View raw payload') }}
                                                    </summary>
                                                    <div class="mt-2 max-h-64 overflow-y-auto rounded-lg border border-slate-800 bg-slate-900 p-3 text-xs leading-relaxed text-slate-100">
                                                        <pre class="whitespace-pre-wrap break-words text-slate-200">{{ json_encode($props, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}' }}</pre>
                                                    </div>
                                                </details>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            @empty
                                <tbody>
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-slate-400">
                                            {{ __('No activity logs found.') }}
                                        </td>
                                    </tr>
                                </tbody>
                            @endforelse
                        </table>
                    </div>

                    <div class="border-t border-gray-100 px-4 py-3 dark:border-slate-800">
                        {{ $logs->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script nonce="{{ $cspNonce }}">
        (function () {
            const input = document.getElementById('activityLogSearch');
            if (!input) return;
            let timer = null;
            input.addEventListener('input', () => {
                clearTimeout(timer);
                timer = setTimeout(() => {
                    const url = new URL(window.location.href);
                    const value = input.value.trim();
                    if (value) {
                        url.searchParams.set('q', value);
                    } else {
                        url.searchParams.delete('q');
                    }
                    url.searchParams.delete('page');
                    window.location.href = url.toString();
                }, 400);
            });
        })();
    </script>
</x-app-layout>
