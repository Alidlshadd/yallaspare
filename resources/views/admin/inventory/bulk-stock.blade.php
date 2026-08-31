<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-[11px] font-extrabold uppercase tracking-[0.18em] text-accent">{{ __('Inventory operation') }}</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ __('Bulk Stock Adjustment') }}</h2>
                <p class="mt-1 max-w-3xl text-sm text-slate-500 dark:text-slate-400">{{ __('Raise or lower the stock of many products in one go. Nothing is applied until you have seen the review.') }}</p>
            </div>
            <a href="{{ route('admin.inventory.index') }}"
               class="inline-flex h-10 w-fit items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 shadow-sm transition hover:border-slate-300 hover:text-primary dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                <i class="fas fa-clock-rotate-left text-xs text-accent" aria-hidden="true"></i>
                {{ __('Movement history') }}
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-[92rem] space-y-6 px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/20 dark:text-emerald-300">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-bold text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/20 dark:text-rose-300">{{ session('error') }}</div>
            @endif

            @if($errors->any())
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-medium text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/20 dark:text-rose-300">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="overflow-hidden rounded-3xl bg-[#070740] text-white shadow-[0_24px_70px_-34px_rgba(7,7,64,0.65)]">
                <div class="grid gap-6 px-6 py-7 lg:grid-cols-[1.3fr_1fr] lg:px-8">
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-white/10 bg-white/10 text-[#FF8A3D]">
                            <i class="fas fa-layer-group text-lg" aria-hidden="true"></i>
                        </div>
                        <div>
                            <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-white/55">{{ __('Controlled stock update') }}</p>
                            <h3 class="mt-1 text-xl font-bold">{{ __('Review first. Apply once.') }}</h3>
                            <p class="mt-2 max-w-2xl text-sm leading-6 text-white/65">{{ __('Every code is matched to the catalogue and every projected stock level is checked before anything changes.') }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 divide-x divide-white/10 rounded-2xl border border-white/10 bg-white/[0.06] rtl:divide-x-reverse">
                        <div class="px-3 py-4 text-center">
                            <i class="fas fa-magnifying-glass text-sm text-[#FF8A3D]" aria-hidden="true"></i>
                            <p class="mt-2 text-[10px] font-bold uppercase tracking-wider text-white/55">{{ __('Resolve') }}</p>
                        </div>
                        <div class="px-3 py-4 text-center">
                            <i class="fas fa-shield-halved text-sm text-[#FF8A3D]" aria-hidden="true"></i>
                            <p class="mt-2 text-[10px] font-bold uppercase tracking-wider text-white/55">{{ __('Validate') }}</p>
                        </div>
                        <div class="px-3 py-4 text-center">
                            <i class="fas fa-file-shield text-sm text-[#FF8A3D]" aria-hidden="true"></i>
                            <p class="mt-2 text-[10px] font-bold uppercase tracking-wider text-white/55">{{ __('Audit') }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <form method="POST" action="{{ route('admin.inventory.bulk-stock.preview') }}" enctype="multipart/form-data"
                  class="space-y-6" x-data="bulkStockRows"
                  data-config="{{ json_encode(['rows' => $inputRows], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) }}">
                @csrf

                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                    <div class="flex items-start gap-3">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#070740] text-xs font-extrabold text-white">1</span>
                        <div class="min-w-0 flex-1">
                            <label for="reason" class="block text-sm font-bold text-slate-900 dark:text-white">{{ __('Reason') }} <span class="text-rose-500">*</span></label>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ __('Recorded against every line, so the history can answer why these numbers changed.') }}</p>
                        </div>
                    </div>
                    <input id="reason" name="reason" type="text" required maxlength="200" value="{{ old('reason', $reason) }}"
                           placeholder="{{ __('Stock count, damaged goods, supplier delivery…') }}"
                           class="mt-4 w-full rounded-xl border-slate-300 bg-white px-4 py-3 text-slate-900 shadow-sm focus:border-accent focus:ring-2 focus:ring-accent/25 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                </section>

                <div class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(19rem,0.8fr)]">
                    <section class="min-w-0 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200 px-5 py-5 dark:border-slate-800 sm:px-6">
                            <div class="flex items-start gap-3">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#070740] text-xs font-extrabold text-white">2</span>
                                <div>
                                    <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ __('Manual stock entries') }}</h3>
                                    <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">{{ __('Enter the code, choose + or −, then enter the quantity. SKU, OEM and part numbers are accepted.') }}</p>
                                </div>
                            </div>
                            <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-bold text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                <span x-text="filledCountText">0</span> {{ __('rows ready') }}
                            </span>
                        </div>

                        <div class="hidden grid-cols-[3rem_minmax(12rem,1fr)_10rem_11rem_3rem] gap-3 border-b border-slate-100 bg-slate-50/80 px-5 py-3 text-[10px] font-extrabold uppercase tracking-[0.14em] text-slate-500 dark:border-slate-800 dark:bg-slate-950/40 sm:grid sm:px-6">
                            <span>#</span>
                            <span>01 · {{ __('Code') }}</span>
                            <span>02 · {{ __('Direction') }}</span>
                            <span>03 · {{ __('Quantity') }}</span>
                            <span></span>
                        </div>

                        <div class="divide-y divide-slate-100 dark:divide-slate-800">
                            <template x-for="(row, index) in rows" :key="row.key">
                                <div class="grid gap-3 px-5 py-4 sm:grid-cols-[3rem_minmax(12rem,1fr)_10rem_11rem_3rem] sm:items-center sm:px-6">
                                    <span class="hidden font-mono text-xs font-bold text-slate-400 sm:block" x-text="rowNumber(index)"></span>
                                    <label class="block">
                                        <span class="mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-slate-500 sm:hidden">{{ __('Code') }}</span>
                                        <input type="text" x-model="row.code" :name="codeName(index)" data-bulk-code autocomplete="off"
                                               placeholder="{{ __('SKU / OEM / part number') }}"
                                               class="w-full rounded-xl border-slate-300 bg-white font-mono text-sm text-slate-900 shadow-sm focus:border-accent focus:ring-2 focus:ring-accent/25 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                                    </label>
                                    <label class="block">
                                        <span class="mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-slate-500 sm:hidden">{{ __('Direction') }}</span>
                                        <select x-model="row.operation" :name="operationName(index)"
                                                class="w-full rounded-xl border-slate-300 bg-white text-sm font-extrabold text-slate-800 shadow-sm focus:border-accent focus:ring-2 focus:ring-accent/25 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                                            <option value="in">+ {{ __('Stock In') }}</option>
                                            <option value="out">− {{ __('Stock Out') }}</option>
                                        </select>
                                    </label>
                                    <label class="block">
                                        <span class="mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-slate-500 sm:hidden">{{ __('Quantity') }}</span>
                                        <input type="number" min="1" step="1" inputmode="numeric" x-model="row.quantity" :name="quantityName(index)" placeholder="0"
                                               class="w-full rounded-xl border-slate-300 bg-white text-end font-mono text-sm font-bold tabular-nums text-slate-900 shadow-sm focus:border-accent focus:ring-2 focus:ring-accent/25 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                                    </label>
                                    <button type="button" @click="removeRow" :data-row-key="row.key"
                                            class="inline-flex h-10 w-full items-center justify-center rounded-xl text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/30 sm:w-10" aria-label="{{ __('Remove') }}">
                                        <i class="fas fa-trash-can text-xs" aria-hidden="true"></i><span class="ms-2 text-xs font-bold sm:hidden">{{ __('Remove') }}</span>
                                    </button>
                                </div>
                            </template>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 bg-slate-50/70 px-5 py-4 dark:border-slate-800 dark:bg-slate-950/30 sm:px-6">
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Empty rows are ignored. You can add up to :max products.', ['max' => number_format($maxRows)]) }}</p>
                            <button type="button" @click="addRow"
                                    class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-extrabold text-slate-700 shadow-sm transition hover:border-accent hover:text-primary dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                <i class="fas fa-plus text-accent" aria-hidden="true"></i>{{ __('Add row') }}
                            </button>
                        </div>
                    </section>

                    <aside class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-accent">{{ __('Alternative') }}</p>
                                <h3 class="mt-1 text-base font-bold text-slate-900 dark:text-white">{{ __('Import a file') }}</h3>
                            </div>
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300"><i class="fas fa-file-arrow-up" aria-hidden="true"></i></span>
                        </div>
                        <label for="file" class="mt-5 flex cursor-pointer flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center transition hover:border-accent hover:bg-orange-50/40 dark:border-slate-700 dark:bg-slate-950/50 dark:hover:border-accent">
                            <i class="fas fa-cloud-arrow-up text-2xl text-accent" aria-hidden="true"></i>
                            <span class="mt-3 text-sm font-bold text-slate-800 dark:text-white">{{ __('Choose CSV or Excel file') }}</span>
                            <span class="mt-1 text-xs text-slate-500">{{ __('Maximum file size: 5 MB') }}</span>
                            <input id="file" name="file" type="file" accept=".csv,.txt,.xlsx,.xls" class="mt-4 block w-full text-xs text-slate-500 file:me-3 file:rounded-lg file:border-0 file:bg-[#070740] file:px-3 file:py-2 file:text-xs file:font-bold file:text-white">
                        </label>
                        <div class="mt-5 space-y-3 rounded-2xl border border-slate-200 p-4 text-xs text-slate-500 dark:border-slate-700 dark:text-slate-400">
                            <p class="font-bold text-slate-700 dark:text-slate-200">{{ __('Required columns') }}</p>
                            <div class="flex flex-wrap gap-2"><code class="rounded-lg bg-slate-100 px-2 py-1 font-mono dark:bg-slate-800">sku</code><code class="rounded-lg bg-slate-100 px-2 py-1 font-mono dark:bg-slate-800">quantity_change</code></div>
                            <p>{{ __('A selected file takes precedence over manual rows.') }}</p>
                            <p>{{ __('Files written for the older importer, with :columns, still work.', ['columns' => 'product_sku, type, quantity']) }}</p>
                        </div>
                    </aside>
                </div>

                <div class="flex flex-col gap-3 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div class="flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/30 dark:text-emerald-400"><i class="fas fa-lock" aria-hidden="true"></i></span>
                        <span>{{ __('Reviewing does not change stock. You will confirm the validated result in the next step.') }}</span>
                    </div>
                    <button type="submit" class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-[#FF6A00] px-6 text-sm font-extrabold text-white shadow-[0_10px_24px_-12px_rgba(255,106,0,0.9)] transition hover:bg-[#E85F00] focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2">
                        {{ __('Review changes') }}<i class="fas fa-arrow-right text-xs rtl:rotate-180" aria-hidden="true"></i>
                    </button>
                </div>
            </form>

            @if(!empty($parseErrors))
                <section class="rounded-3xl border border-rose-200 bg-rose-50 p-5 dark:border-rose-900/60 dark:bg-rose-950/20">
                    <h3 class="text-sm font-bold text-rose-800 dark:text-rose-300">{{ __(':count line(s) could not be read', ['count' => count($parseErrors)]) }}</h3>
                    <ul class="mt-3 space-y-2 text-sm text-rose-700 dark:text-rose-300">
                        @foreach($parseErrors as $error)
                            <li class="flex gap-2"><span class="font-mono text-xs">{{ __('Line :line', ['line' => $error['line']]) }}</span><span>{{ $error['message'] }}</span></li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @isset($preview)
                <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="border-b border-slate-200 px-5 py-5 dark:border-slate-800 sm:px-6">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-accent">{{ __('Validation result') }}</p>
                                <h3 class="mt-1 text-lg font-bold text-slate-900 dark:text-white">{{ __('Review') }} · {{ __(':count row(s)', ['count' => $preview['totals']['rows']]) }}</h3>
                            </div>
                            <div class="grid grid-cols-3 gap-2 text-center">
                                <div class="rounded-xl bg-slate-50 px-4 py-2 dark:bg-slate-800"><p class="font-mono text-sm font-extrabold text-emerald-600">+{{ number_format($preview['totals']['added']) }}</p><p class="text-[9px] font-bold uppercase tracking-wider text-slate-400">{{ __('Stock In') }}</p></div>
                                <div class="rounded-xl bg-slate-50 px-4 py-2 dark:bg-slate-800"><p class="font-mono text-sm font-extrabold text-amber-600">−{{ number_format($preview['totals']['removed']) }}</p><p class="text-[9px] font-bold uppercase tracking-wider text-slate-400">{{ __('Stock Out') }}</p></div>
                                <div class="rounded-xl bg-slate-50 px-4 py-2 dark:bg-slate-800"><p class="font-mono text-sm font-extrabold {{ $preview['totals']['blocked'] > 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ $preview['totals']['blocked'] }}</p><p class="text-[9px] font-bold uppercase tracking-wider text-slate-400">{{ __('Blocked') }}</p></div>
                            </div>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[850px] text-sm">
                            <thead class="bg-slate-50 text-[10px] uppercase tracking-[0.12em] text-slate-500 dark:bg-slate-950/40">
                                <tr>
                                    <th class="px-5 py-3 text-start font-extrabold">{{ __('Code') }}</th><th class="px-5 py-3 text-start font-extrabold">{{ __('Product') }}</th><th class="px-5 py-3 text-end font-extrabold">{{ __('Current') }}</th><th class="px-5 py-3 text-end font-extrabold">{{ __('Change') }}</th><th class="px-5 py-3 text-end font-extrabold">{{ __('New') }}</th><th class="px-5 py-3 text-start font-extrabold">{{ __('Status') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach($preview['rows'] as $row)
                                    @php($blocked = $row['status'] !== \App\Services\Inventory\BulkStockAdjustment::STATUS_OK)
                                    <tr class="{{ $blocked ? 'bg-rose-50/60 dark:bg-rose-950/10' : '' }}">
                                        <td class="whitespace-nowrap px-5 py-4 font-mono font-bold text-slate-800 dark:text-slate-100">{{ $row['sku'] }} @if(count($row['lines']) > 1)<span class="ms-1 rounded-md bg-slate-100 px-1.5 py-0.5 text-[9px] font-bold text-slate-500 dark:bg-slate-800">{{ __(':count lines merged', ['count' => count($row['lines'])]) }}</span>@endif</td>
                                        <td class="px-5 py-4 font-medium text-slate-700 dark:text-slate-300">{{ $row['product_name'] ?? '—' }}</td>
                                        <td class="whitespace-nowrap px-5 py-4 text-end font-mono tabular-nums text-slate-500">{{ $row['stock_before'] ?? '—' }}</td>
                                        <td class="whitespace-nowrap px-5 py-4 text-end font-mono font-extrabold tabular-nums {{ $row['change'] > 0 ? 'text-emerald-600' : 'text-amber-600' }}">{{ $row['change'] > 0 ? '+' : '−' }}{{ abs($row['change']) }}</td>
                                        <td class="whitespace-nowrap px-5 py-4 text-end font-mono font-extrabold tabular-nums text-slate-900 dark:text-white">{{ $row['stock_after'] ?? '—' }}</td>
                                        <td class="px-5 py-4">@if($blocked)<span class="inline-flex rounded-lg bg-rose-100 px-2.5 py-1 text-xs font-bold text-rose-700 dark:bg-rose-950/40 dark:text-rose-300">{{ $row['message'] }}</span>@else<span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300"><i class="fas fa-check text-[9px]" aria-hidden="true"></i>{{ __('Will apply') }}</span>@endif</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50/70 px-5 py-5 dark:border-slate-800 dark:bg-slate-950/30 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        @if($preview['applicable'])
                            <p class="text-sm font-medium text-slate-600 dark:text-slate-300">{{ __('Every row resolved. Applying writes all of them together.') }}</p>
                            <form method="POST" action="{{ route('admin.inventory.bulk-stock.apply') }}">@csrf<input type="hidden" name="token" value="{{ $token }}"><button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-[#FF6A00] px-6 text-sm font-extrabold text-white transition hover:bg-[#E85F00]"><i class="fas fa-check" aria-hidden="true"></i>{{ __('Apply :count change(s)', ['count' => $preview['totals']['rows']]) }}</button></form>
                        @else
                            <p class="text-sm font-bold text-rose-700 dark:text-rose-300">{{ __('Correct the rows marked above, then review again. Nothing has been applied.') }}</p>
                            <span class="cursor-not-allowed rounded-xl border border-dashed border-slate-300 px-6 py-2.5 text-sm font-bold text-slate-400 dark:border-slate-700">{{ __('Apply') }}</span>
                        @endif
                    </div>
                </section>
            @endisset
        </div>
    </div>
</x-app-layout>
