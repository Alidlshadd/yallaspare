<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
            <div>
                <h2 class="font-semibold text-2xl text-slate-800">{{ __('Bulk Stock Adjustment') }}</h2>
                <p class="text-sm text-slate-500">{{ __('Raise or lower the stock of many products in one go. Nothing is applied until you have seen the review.') }}</p>
            </div>
            <a href="{{ route('admin.inventory.index') }}"
               class="inline-flex w-fit items-center rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600 shadow-sm transition hover:border-slate-300 hover:text-slate-900">
                {{ __('Movement history') }}
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-5 sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 dark:border-red-900/60 dark:bg-red-950/20 dark:text-red-300">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 dark:border-red-900/60 dark:bg-red-950/20 dark:text-red-300">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Step one: the list --}}
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <form method="POST" action="{{ route('admin.inventory.bulk-stock.preview') }}" enctype="multipart/form-data" class="space-y-5">
                    @csrf

                    <div>
                        <label for="reason" class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                            {{ __('Reason') }} <span class="text-red-600">*</span>
                        </label>
                        <input id="reason" name="reason" type="text" required maxlength="200"
                               value="{{ old('reason', $reason) }}"
                               placeholder="{{ __('Stock count, damaged goods, supplier delivery…') }}"
                               class="mt-1 w-full rounded-lg border-slate-300 bg-white text-slate-900 focus:ring-2 focus:ring-accent dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        <p class="mt-1 text-xs text-slate-500">
                            {{ __('Recorded against every line, so the history can answer why these numbers changed.') }}
                        </p>
                    </div>

                    <div class="grid gap-5 lg:grid-cols-2">
                        <div>
                            <label for="rows" class="block text-sm font-medium text-slate-700 dark:text-slate-200">{{ __('Paste rows') }}</label>
                            <textarea id="rows" name="rows" rows="9" spellcheck="false"
                                      placeholder="ABC123 +20&#10;ABC456 +50&#10;ABC789 -10"
                                      class="mt-1 w-full rounded-lg border-slate-300 bg-white font-mono text-sm text-slate-900 focus:ring-2 focus:ring-accent dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">{{ old('rows') }}</textarea>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ __('One product per line: the code, then the change. Anything after the number is kept as a note.') }}
                            </p>
                        </div>

                        <div>
                            <label for="file" class="block text-sm font-medium text-slate-700 dark:text-slate-200">{{ __('Or upload a file') }}</label>
                            <input id="file" name="file" type="file" accept=".csv,.txt,.xlsx,.xls"
                                   class="mt-1 w-full rounded-lg border border-slate-300 bg-white p-2 text-sm text-slate-900 file:mr-3 file:rounded file:border-0 file:bg-slate-100 file:px-3 file:py-1 file:text-sm file:font-semibold dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                            <div class="mt-2 space-y-1 text-xs text-slate-500">
                                <p>{{ __('CSV or Excel. Required columns:') }} <code class="rounded bg-slate-100 px-1 py-0.5 font-mono dark:bg-slate-800">sku</code>, <code class="rounded bg-slate-100 px-1 py-0.5 font-mono dark:bg-slate-800">quantity_change</code></p>
                                <p>{{ __('Optional:') }} <code class="rounded bg-slate-100 px-1 py-0.5 font-mono dark:bg-slate-800">reference</code>, <code class="rounded bg-slate-100 px-1 py-0.5 font-mono dark:bg-slate-800">note</code></p>
                                <p>{{ __('Files written for the older importer, with :columns, still work.', ['columns' => 'product_sku, type, quantity']) }}</p>
                                <p>{{ __('A file takes precedence over pasted rows. At most :max rows at a time.', ['max' => number_format($maxRows)]) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                                class="rounded-lg bg-slate-900 px-5 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2">
                            {{ __('Review changes') }}
                        </button>
                    </div>
                </form>
            </section>

            {{-- Step two: what it would do --}}
            @isset($preview)
                @if(!empty($parseErrors))
                    <section class="rounded-2xl border border-red-200 bg-red-50 p-5 dark:border-red-900/60 dark:bg-red-950/20">
                        <h3 class="text-sm font-semibold text-red-800 dark:text-red-300">
                            {{ __(':count line(s) could not be read', ['count' => count($parseErrors)]) }}
                        </h3>
                        <ul class="mt-2 space-y-1 text-sm text-red-700 dark:text-red-300">
                            @foreach($parseErrors as $error)
                                <li>
                                    <span class="font-mono text-xs">{{ __('Line :line', ['line' => $error['line']]) }}</span>
                                    @if($error['raw'] !== '')
                                        <span class="font-mono text-xs text-red-600/80">“{{ $error['raw'] }}”</span>
                                    @endif
                                    — {{ $error['message'] }}
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                <section class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex flex-wrap items-baseline justify-between gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                        <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-100">
                            {{ __('Review') }} — {{ __(':count row(s)', ['count' => $preview['totals']['rows']]) }}
                        </h3>
                        <p class="text-xs font-medium text-slate-500">
                            {{ __('+:added in, −:removed out', ['added' => number_format($preview['totals']['added']), 'removed' => number_format($preview['totals']['removed'])]) }}
                        </p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-950/40">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-start font-semibold">{{ __('Code') }}</th>
                                    <th scope="col" class="px-4 py-3 text-start font-semibold">{{ __('Product') }}</th>
                                    <th scope="col" class="px-4 py-3 text-end font-semibold">{{ __('Current') }}</th>
                                    <th scope="col" class="px-4 py-3 text-end font-semibold">{{ __('Change') }}</th>
                                    <th scope="col" class="px-4 py-3 text-end font-semibold">{{ __('New') }}</th>
                                    <th scope="col" class="px-4 py-3 text-start font-semibold">{{ __('Status') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach($preview['rows'] as $row)
                                    @php($blocked = $row['status'] !== \App\Services\Inventory\BulkStockAdjustment::STATUS_OK)
                                    <tr class="{{ $blocked ? 'bg-red-50/60 dark:bg-red-950/10' : '' }}">
                                        <td class="whitespace-nowrap px-4 py-3 font-mono font-medium text-slate-800 dark:text-slate-100">
                                            {{ $row['sku'] }}
                                            @if(count($row['lines']) > 1)
                                                <span class="ms-1 rounded bg-slate-100 px-1 py-0.5 text-[10px] font-semibold text-slate-500 dark:bg-slate-800">
                                                    {{ __(':count lines merged', ['count' => count($row['lines'])]) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $row['product_name'] ?? '—' }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-end font-mono tabular-nums text-slate-600 dark:text-slate-400">
                                            {{ $row['stock_before'] ?? '—' }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-end font-mono tabular-nums font-semibold {{ $row['change'] > 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-amber-700 dark:text-amber-400' }}">
                                            {{ $row['change'] > 0 ? '+' : '−' }}{{ abs($row['change']) }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-end font-mono tabular-nums font-semibold text-slate-900 dark:text-slate-100">
                                            {{ $row['stock_after'] ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3">
                                            @if($blocked)
                                                <span class="inline-flex rounded bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700 dark:bg-red-950/40 dark:text-red-300">
                                                    {{ $row['message'] }}
                                                </span>
                                            @else
                                                <span class="inline-flex rounded bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                                                    {{ __('Will apply') }}
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 dark:border-slate-800 dark:bg-slate-950/40">
                        @if($preview['applicable'])
                            <p class="text-sm text-slate-600 dark:text-slate-300">
                                {{ __('Every row resolved. Applying writes all of them together.') }}
                            </p>
                            <form method="POST" action="{{ route('admin.inventory.bulk-stock.apply') }}">
                                @csrf
                                <input type="hidden" name="token" value="{{ $token }}">
                                <button type="submit"
                                        class="rounded-lg bg-emerald-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                                    {{ __('Apply :count change(s)', ['count' => $preview['totals']['rows']]) }}
                                </button>
                            </form>
                        @else
                            <p class="text-sm font-medium text-red-700 dark:text-red-300">
                                {{ __('Correct the rows marked above, then review again. Nothing has been applied.') }}
                            </p>
                            <span class="cursor-not-allowed rounded-lg border border-dashed border-slate-300 px-5 py-2 text-sm font-semibold text-slate-400 dark:border-slate-700">
                                {{ __('Apply') }}
                            </span>
                        @endif
                    </div>
                </section>
            @endisset

        </div>
    </div>
</x-app-layout>
