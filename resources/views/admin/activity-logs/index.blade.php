<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-800 dark:text-slate-100">{{ __('Activity Logs') }}</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Super admin audit trail') }}</p>
            </div>
            <form method="GET" action="{{ route('admin.activity-logs.index') }}" class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                    <i class="fas fa-search text-slate-400"></i>
                    <input
                        id="activityLogSearch"
                        type="text"
                        name="q"
                        value="{{ $search ?? '' }}"
                        placeholder="{{ __('Search logs...') }}"
                        class="w-56 bg-transparent outline-none placeholder:text-slate-400"
                        autocomplete="off"
                    />
                </div>
                <div class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-2 py-2 text-sm text-slate-600 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                    @php
                        $filters = ['All', 'Product', 'Catalog', 'Category', 'Inventory', 'User'];
                    @endphp
                    @foreach($filters as $filter)
                        @php
                            $isActive = ($filter === 'All' && empty($model)) || ($filter !== 'All' && $model === $filter);
                            $query = array_filter([
                                'model' => $filter === 'All' ? null : $filter,
                                'q' => $search ?? null,
                            ]);
                        @endphp
                        <a
                            href="{{ route('admin.activity-logs.index', $query) }}"
                            class="px-3 py-1.5 rounded-md text-xs font-semibold transition {{ $isActive ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800' }}"
                        >
                            {{ $filter }}
                        </a>
                    @endforeach
                </div>
            </form>
        </div>
    </x-slot>

    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                    <tr>
                        <th class="w-28 px-3 py-3 text-left font-semibold">{{ __('Details') }}</th>
                        <th class="px-4 py-3 text-left font-semibold">{{ __('Admin') }}</th>
                        <th class="px-4 py-3 text-left font-semibold">{{ __('Action') }}</th>
                        <th class="px-4 py-3 text-left font-semibold">{{ __('Subject') }}</th>
                        <th class="px-4 py-3 text-left font-semibold">{{ __('Date') }}</th>
                    </tr>
                </thead>

                @forelse($logs as $log)
                    @php
                        $rawProps = $log->properties ?? null;
                        if ($rawProps instanceof \Spatie\Activitylog\ActivityProperties) {
                            $props = $rawProps->toArray();
                        } elseif ($rawProps instanceof \Illuminate\Support\Collection) {
                            $props = $rawProps->toArray();
                        } elseif (is_string($rawProps)) {
                            $decoded = json_decode($rawProps, true);
                            $props = is_array($decoded) ? $decoded : [];
                        } elseif (is_array($rawProps)) {
                            $props = $rawProps;
                        } elseif (is_object($rawProps) && method_exists($rawProps, 'toArray')) {
                            $props = $rawProps->toArray();
                        } else {
                            $props = [];
                        }

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
                            if (str_ends_with((string) $key, '_id')) {
                                return false;
                            }
                            if (str_contains((string) $key, 'password')) {
                                return false;
                            }
                            return true;
                        }));
                    @endphp

                    <tbody x-data="toggle" class="border-b border-slate-100 dark:border-slate-800 last:border-b-0">
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/60 transition-colors">
                            <td class="px-3 py-3 text-slate-400 dark:text-slate-500">
                                <button
                                    type="button"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:scale-105 hover:border-slate-300 hover:text-slate-700 hover:shadow dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-slate-600 dark:hover:text-white"
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
                                <div class="font-medium text-slate-800 dark:text-slate-100">
                                    {{ $log->causer?->name ?? 'System' }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-700 dark:text-slate-200">
                                {{ \App\Support\ActivityLabelHelper::label($log->description, $log->subject_type) }}
                            </td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                                @if($log->subject_type)
                                    {{ class_basename($log->subject_type) }} #{{ $log->subject_id }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                {{ $log->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') ?? '-' }}
                            </td>
                        </tr>

                        <tr x-show="open" x-transition.opacity.duration.150ms>
                            <td colspan="5" class="px-4 pb-4 pt-1">
                                <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-3 dark:border-slate-800 dark:bg-slate-900/50">
                                    @if(empty($keys))
                                        <span class="text-xs text-slate-400">{{ __('No field-level changes.') }}</span>
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
                                                <div class="grid grid-cols-[minmax(120px,180px),1fr] items-center gap-2 rounded-md border border-slate-200 bg-white/70 px-2 py-1.5 dark:border-slate-800 dark:bg-slate-900/40">
                                                    <div class="truncate text-[11px] font-semibold text-slate-600 dark:text-slate-300">{{ \Illuminate\Support\Str::of($key)->replace('_', ' ')->title() }}</div>
                                                    <div class="flex flex-wrap items-center gap-2 text-[11px]">
                                                        <span class="text-rose-600 line-through">{{ is_scalar($oldVal) || $oldVal === null ? ($oldVal ?? '-') : '[...]' }}</span>
                                                        <span class="text-slate-400">→</span>
                                                        <span class="text-emerald-600">{{ is_scalar($newVal) || $newVal === null ? ($newVal ?? '-') : '[...]' }}</span>
                                                        @if($delta !== null)
                                                            <span class="ml-2 inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $delta >= 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                                                {{ $delta >= 0 ? '+' : '' }}{{ $delta }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    <details class="group mt-3">
                                        <summary class="cursor-pointer text-xs font-semibold text-slate-600 hover:text-slate-800 dark:text-slate-300 dark:hover:text-slate-100">
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
                            <td colspan="5" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">
                                {{ __('No activity logs found.') }}
                            </td>
                        </tr>
                    </tbody>
                @endforelse
            </table>
        </div>

        <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-800">
            {{ $logs->links() }}
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
