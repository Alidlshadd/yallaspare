<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-slate-800 dark:text-slate-100">{{ __('Purchase Planning') }}</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Prioritize products to reorder using sales velocity, stock, and customer demand.') }}</p>
            </div>
            <span class="inline-flex w-fit rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300">
                {{ __('Inventory Intelligence') }}
            </span>
        </div>
    </x-slot>

    @php
        $money = fn ($value) => number_format((float) $value, $currency['decimals']) . ' ' . $currency['label'];
        $ppConfig = [
            'currency' => ['label' => $currency['label'], 'decimals' => $currency['decimals']],
            'budget' => (float) $summary['estimated_budget'],
            'labels' => [
                'listName' => __('Order List'),
                'listShort' => __('List'),
                'addToList' => __('Add to purchase list'),
                'alreadyAdded' => __('Already in list — click to add +1'),
                'status_draft' => __('Draft'),
                'status_saved' => __('Saved'),
                'status_ordered' => __('Ordered'),
                'overBudget' => __('Over page budget by'),
                'budgetRemaining' => __('Budget remaining'),
                'manualAdded' => __('Manual item added to list'),
                'savedFlash' => __('List saved (stored in this browser)'),
                'draftFlash' => __('Draft saved'),
                'exportedFlash' => __('CSV exported'),
                'orderedFlash' => __('Marked as ordered'),
                'confirmClear' => __('Remove all items from this list?'),
                'confirmDelete' => __('Delete this purchase list? This cannot be undone.'),
                'itemsWord' => __('items'),
                'csvHeaders' => [__('Product'), __('SKU'), __('Quantity'), __('Unit Cost'), __('Row Total'), __('Notes'), __('Manual')],
            ],
        ];
    @endphp

    <style>
        /* Purchase Planning — Purchase List Builder (pp-) */
        .pp-add {
            width: 32px; height: 32px; border-radius: 9px;
            background: #fbbf24; color: #422006;
            font-weight: 900; font-size: 16px; line-height: 1;
            display: inline-flex; align-items: center; justify-content: center;
            box-shadow: 0 3px 8px -3px rgba(180, 83, 9, 0.5);
            transition: transform .12s ease, background .15s ease;
        }
        .pp-add:hover { transform: scale(1.08); }
        .pp-add.pp-added { background: #059669; color: #fff; font-size: 12px; box-shadow: none; }
        .dark .pp-add.pp-added { background: #10b981; }

        .pp-tab {
            padding: 6px 12px; border-radius: 9px 9px 0 0;
            font-size: 12px; font-weight: 800; color: #94a3b8;
            display: inline-flex; align-items: center; gap: 5px;
        }
        .pp-tab .pp-tab-cnt {
            font-size: 10px; font-weight: 800; padding: 1px 6px; border-radius: 999px;
            background: #eef2f7; color: #94a3b8;
            font-family: ui-monospace, 'JetBrains Mono', Consolas, monospace;
        }
        .dark .pp-tab .pp-tab-cnt { background: rgba(255,255,255,0.06); color: #94a3b8; }
        .pp-tab.pp-tab-on { background: #04042a; color: #fff; }
        .pp-tab.pp-tab-on .pp-tab-cnt { background: rgba(251,191,36,0.2); color: #fbbf24; }
        .pp-tab-new { color: #b45309; }
        .dark .pp-tab-new { color: #fbbf24; }

        .pp-chip-draft { background: rgba(251,191,36,0.16); color: #b45309; }
        .dark .pp-chip-draft { color: #fbbf24; }
        .pp-chip-saved { background: rgba(5,150,105,0.12); color: #059669; }
        .dark .pp-chip-saved { color: #34d399; }
        .pp-chip-ordered { background: rgba(3,105,161,0.12); color: #0369a1; }
        .dark .pp-chip-ordered { color: #38bdf8; }

        .pp-dock-head {
            background: linear-gradient(135deg, #04042a, #10104a);
            position: relative; overflow: hidden;
        }
        .pp-dock-head::after {
            content: ""; position: absolute; inset: 0;
            background-image: repeating-linear-gradient(135deg, rgba(255,255,255,0.05) 0 1px, transparent 1px 14px);
        }
        .pp-dock-head > * { position: relative; z-index: 1; }

        .pp-hero {
            background: linear-gradient(135deg, #04042a, #10104a);
            position: relative; overflow: hidden;
        }
        .pp-hero::after {
            content: ""; position: absolute; inset: 0;
            background-image: repeating-linear-gradient(135deg, rgba(255,255,255,0.05) 0 1px, transparent 1px 14px);
        }
        .pp-hero > * { position: relative; z-index: 1; }

        .pp-num { font-family: ui-monospace, 'JetBrains Mono', Consolas, monospace; font-variant-numeric: tabular-nums; }

        /* Print: only the purchase-order sheet */
        .pp-print { display: none; }
        @media print {
            body * { visibility: hidden; }
            .pp-print, .pp-print * { visibility: visible; }
            .pp-print {
                display: block !important; position: absolute; inset: 0 auto auto 0;
                width: 100%; padding: 24px; background: #fff; color: #0f172a;
            }
        }
    </style>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8" x-data="purchaseBuilder" data-config="{{ json_encode($ppConfig) }}">

            {{-- ============ summary cards ============ --}}
            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400 dark:text-slate-500">{{ __('Out of Stock') }}</p>
                        <i class="fas fa-circle-exclamation text-rose-500"></i>
                    </div>
                    <p class="pp-num mt-3 text-2xl font-black text-rose-600 dark:text-rose-400">{{ number_format($summary['out_of_stock']) }}</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400 dark:text-slate-500">{{ __('Low Stock') }}</p>
                        <i class="fas fa-arrow-trend-down text-amber-500"></i>
                    </div>
                    <p class="pp-num mt-3 text-2xl font-black text-slate-900 dark:text-slate-100">{{ number_format($summary['low_stock']) }}</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400 dark:text-slate-500">{{ __('Waiting Customers') }}</p>
                        <i class="fas fa-bell text-sky-500"></i>
                    </div>
                    <p class="pp-num mt-3 text-2xl font-black text-sky-700 dark:text-sky-300">{{ number_format($summary['waiting_customers']) }}</p>
                </article>
                <article class="pp-hero rounded-2xl border border-transparent p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-white/55">{{ __('Page Budget') }}</p>
                        <i class="fas fa-coins text-amber-400"></i>
                    </div>
                    <p class="pp-num mt-3 text-2xl font-black text-amber-400">{{ $money($summary['estimated_budget']) }}</p>
                </article>
            </section>

            {{-- ============ filters (unchanged fields, compact) ============ --}}
            <form method="GET" action="{{ route('admin.purchase-planning.index') }}" class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-wrap items-center gap-2">
                    <input type="search" name="search" value="{{ $search }}" placeholder="{{ __('Search product, SKU, brand') }}" class="min-w-0 flex-[2_1_220px] rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                    <select name="status" class="min-w-0 flex-[1_1_140px] rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        @foreach(['needs_reorder' => __('Needs reorder'), 'out_of_stock' => __('Out of stock'), 'low_stock' => __('Low stock'), 'all' => __('All products')] as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="days" class="min-w-0 flex-[1_1_130px] rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        @foreach([7, 30, 90] as $option)
                            <option value="{{ $option }}" @selected($days === $option)>{{ __('Sales :days days', ['days' => $option]) }}</option>
                        @endforeach
                    </select>
                    <select name="coverage_days" class="min-w-0 flex-[1_1_130px] rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        @foreach([14, 30, 60, 90] as $option)
                            <option value="{{ $option }}" @selected($coverageDays === $option)>{{ __('Cover :days days', ['days' => $option]) }}</option>
                        @endforeach
                    </select>
                    <a href="{{ route('admin.purchase-planning.index') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">{{ __('Reset') }}</a>
                    <button class="rounded-xl bg-[#04042a] px-4 py-2 text-sm font-semibold text-white hover:bg-[#10104a]">{{ __('Apply') }}</button>
                </div>
            </form>

            {{-- ============ workbench: table + side dock ============ --}}
            <div class="grid items-start gap-4 xl:grid-cols-[minmax(0,1fr)_400px]">

                {{-- ---------- left column ---------- --}}
                <div class="min-w-0 space-y-6">
                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center gap-2 border-b border-slate-200 px-4 py-3 dark:border-slate-800">
                            <i class="fas fa-boxes-stacked text-slate-400"></i>
                            <h3 class="text-sm font-extrabold text-slate-800 dark:text-slate-100">{{ __('Products to reorder') }}</h3>
                            <span class="ms-auto text-xs font-semibold text-slate-400">{{ __(':from–:to of :total', ['from' => $products->firstItem() ?? 0, 'to' => $products->lastItem() ?? 0, 'total' => $products->total()]) }}</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-950/40 dark:text-slate-400">
                                    <tr>
                                        <th class="px-4 py-3 text-left">{{ __('Product') }}</th>
                                        <th class="px-4 py-3 text-right">{{ __('Stock') }}</th>
                                        <th class="px-4 py-3 text-right">{{ __('Sold') }}</th>
                                        <th class="px-4 py-3 text-right">{{ __('Days Left') }}</th>
                                        <th class="px-4 py-3 text-right">{{ __('Waiting') }}</th>
                                        <th class="px-4 py-3 text-right">{{ __('Suggested Qty') }}</th>
                                        <th class="px-4 py-3 text-right">{{ __('Est. Cost') }}</th>
                                        <th class="px-4 py-3 text-right">{{ __('Add') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    @forelse($products as $product)
                                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40">
                                            <td class="px-4 py-3">
                                                <div class="font-semibold text-slate-900 dark:text-slate-100">{{ $product->name }}</div>
                                                <div class="mt-1 text-xs text-slate-500">{{ $product->sku ?? __('N/A') }} @if($product->brand) · {{ $product->brand }} @endif</div>
                                            </td>
                                            <td class="pp-num px-4 py-3 text-right font-semibold {{ (int) $product->stock_quantity <= 0 ? 'text-rose-600' : 'text-slate-700 dark:text-slate-200' }}">{{ number_format((int) $product->stock_quantity) }}</td>
                                            <td class="pp-num px-4 py-3 text-right text-slate-600 dark:text-slate-300">{{ number_format((int) $product->sold_quantity) }}</td>
                                            <td class="pp-num px-4 py-3 text-right text-slate-600 dark:text-slate-300">{{ $product->days_remaining === null ? __('N/A') : number_format((int) $product->days_remaining) }}</td>
                                            <td class="pp-num px-4 py-3 text-right text-sky-700 dark:text-sky-300">{{ number_format((int) $product->waiting_count) }}</td>
                                            <td class="pp-num px-4 py-3 text-right font-bold text-slate-900 dark:text-slate-100">{{ number_format((int) $product->recommended_quantity) }}</td>
                                            <td class="pp-num px-4 py-3 text-right text-slate-600 dark:text-slate-300">{{ $money($product->estimated_purchase_cost) }}</td>
                                            <td class="px-4 py-3 text-right">
                                                <button
                                                    type="button"
                                                    class="pp-add"
                                                    data-pp-add
                                                    data-id="{{ $product->id }}"
                                                    data-name="{{ $product->name }}"
                                                    data-sku="{{ $product->sku ?? '' }}"
                                                    data-qty="{{ max(1, (int) $product->recommended_quantity) }}"
                                                    data-cost="{{ (float) ($product->dealer_price ?? $product->price) }}"
                                                    @click="addFromRow"
                                                    aria-label="{{ __('Add to purchase list') }}"
                                                >+</button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="px-4 py-10 text-center text-sm text-slate-500">{{ __('No products matched the current planning filters.') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="border-t border-slate-200 px-4 py-3 dark:border-slate-800">{{ $products->links() }}</div>
                    </section>

                    {{-- ---------- recent purchase lists ---------- --}}
                    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center gap-2 border-b border-slate-200 px-4 py-3 dark:border-slate-800">
                            <i class="fas fa-folder-open text-slate-400"></i>
                            <h3 class="text-sm font-extrabold text-slate-800 dark:text-slate-100">{{ __('Recent Purchase Lists') }}</h3>
                            <span class="ms-auto text-xs font-semibold text-slate-400">{{ __('saved in this browser') }}</span>
                        </div>
                        <div class="grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-3" x-show="hasRecent">
                            <template x-for="entry in recentEntries" :key="entry.index">
                                <article class="rounded-xl border border-slate-200 bg-slate-50 p-3.5 dark:border-slate-700 dark:bg-slate-950/40">
                                    <div class="flex items-center justify-between gap-2">
                                        <h4 class="truncate text-sm font-extrabold text-slate-800 dark:text-slate-100" x-text="entry.name"></h4>
                                        <span class="rounded-full px-2 py-0.5 text-[10px] font-black uppercase tracking-wide" :class="entry.statusClass" x-text="entry.statusLabel"></span>
                                    </div>
                                    <p class="mt-1 text-xs text-slate-400" x-text="entry.meta"></p>
                                    <p class="pp-num mt-2 text-sm font-extrabold text-amber-700 dark:text-amber-400" x-text="entry.totalLabel"></p>
                                    <button type="button" class="mt-2 w-full rounded-lg border border-slate-200 bg-white py-1.5 text-xs font-bold text-slate-600 hover:border-slate-400 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300" @click="openView(entry)">
                                        {{ __('View items') }}
                                    </button>
                                </article>
                            </template>
                        </div>
                        <p class="px-4 py-8 text-center text-sm text-slate-400" x-show="recentEmpty" x-cloak>
                            {{ __('No purchase lists yet. Add products to a list and save it — it will appear here.') }}
                        </p>
                    </section>
                </div>

                {{-- ---------- right column: side dock ---------- --}}
                <aside class="min-w-0 xl:sticky xl:top-4">
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">

                        {{-- tabs --}}
                        <div class="flex flex-wrap gap-1.5 px-2.5 pt-2.5">
                            <template x-for="(list, index) in lists" :key="index">
                                <button type="button" class="pp-tab" :class="tabClass(index)" @click="selectList(index)">
                                    <span x-text="tabLabel(index)"></span>
                                    <span class="pp-tab-cnt" x-text="tabCount(index)"></span>
                                </button>
                            </template>
                            <button type="button" class="pp-tab pp-tab-new" @click="newList">+ {{ __('New') }}</button>
                        </div>

                        {{-- dock header --}}
                        <div class="pp-dock-head px-4 py-3.5 text-white">
                            <div class="flex items-center gap-2">
                                <input
                                    type="text"
                                    class="min-w-0 flex-1 border-0 border-b border-dashed border-white/25 bg-transparent p-0 pb-0.5 text-[15px] font-extrabold text-white focus:border-amber-400 focus:ring-0"
                                    :value="activeName"
                                    @input="onNameInput"
                                    aria-label="{{ __('List name') }}"
                                >
                                <span class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-wide" :class="activeStatusClass" x-text="activeStatusLabel"></span>
                            </div>
                            <div class="mt-2.5 flex gap-5">
                                <div>
                                    <p class="text-[10px] font-extrabold uppercase tracking-[0.12em] text-white/50">{{ __('Items') }}</p>
                                    <p class="pp-num text-[15px] font-black" x-text="itemsCountLabel"></p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-extrabold uppercase tracking-[0.12em] text-white/50">{{ __('Quantity') }}</p>
                                    <p class="pp-num text-[15px] font-black" x-text="qtyTotalLabel"></p>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[10px] font-extrabold uppercase tracking-[0.12em] text-white/50">{{ __('Est. Cost') }}</p>
                                    <p class="pp-num truncate text-[15px] font-black text-amber-400" x-text="grandTotalLabel"></p>
                                </div>
                            </div>
                        </div>

                        {{-- items --}}
                        <div class="max-h-[380px] overflow-y-auto">
                            <div class="px-4 py-8 text-center text-sm text-slate-400" x-show="activeEmpty">
                                <i class="fas fa-cart-plus mb-2 block text-xl"></i>
                                {{ __('This list is empty. Click + on a product, or add a manual item by code below.') }}
                            </div>
                            <template x-for="item in activeItems" :key="item.key">
                                <div class="border-b border-slate-100 px-4 py-2.5 dark:border-slate-800" :data-key="item.key">
                                    <div class="flex items-start gap-2">
                                        <div class="min-w-0 flex-1">
                                            <p class="text-[13px] font-bold leading-snug text-slate-900 dark:text-slate-100">
                                                <span x-text="item.name"></span>
                                                <span class="ms-1 inline-block rounded border border-dashed border-amber-600 bg-amber-50 px-1.5 align-[1px] text-[9px] font-black uppercase tracking-wide text-amber-700 dark:border-amber-400 dark:bg-amber-400/10 dark:text-amber-300" x-show="item.manual">{{ __('Manual — not in system') }}</span>
                                            </p>
                                            <p class="text-[11px] text-slate-400" x-text="item.sku"></p>
                                        </div>
                                        <button type="button" class="shrink-0 px-1 text-[15px] font-black text-rose-500 opacity-70 hover:opacity-100" @click="removeItem" :data-key="item.key" aria-label="{{ __('Remove') }}">✕</button>
                                    </div>
                                    <div class="mt-2 grid grid-cols-[84px_110px_1fr] items-center gap-1.5">
                                        <div>
                                            <label class="mb-0.5 block text-[9px] font-extrabold uppercase tracking-[0.09em] text-slate-400">{{ __('Qty') }}</label>
                                            <input type="number" min="1" step="1" class="pp-num h-[30px] w-full rounded-lg border-slate-300 bg-slate-50 px-2 py-0 text-right text-[13px] focus:border-amber-400 focus:bg-white focus:ring-2 focus:ring-amber-400/25 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:focus:bg-slate-900"
                                                :value="item.qty" :data-key="item.key" @input="onQtyInput" @change="onQtyChange">
                                        </div>
                                        <div>
                                            <label class="mb-0.5 block text-[9px] font-extrabold uppercase tracking-[0.09em] text-slate-400">{{ __('Unit cost') }}</label>
                                            <input type="number" min="0" step="any" class="pp-num h-[30px] w-full rounded-lg border-slate-300 bg-slate-50 px-2 py-0 text-right text-[13px] focus:border-amber-400 focus:bg-white focus:ring-2 focus:ring-amber-400/25 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:focus:bg-slate-900"
                                                :value="item.cost" :data-key="item.key" @input="onCostInput" @change="onCostChange">
                                        </div>
                                        <div class="text-right">
                                            <p class="text-[9px] font-extrabold uppercase tracking-[0.09em] text-slate-400">{{ __('Row total') }}</p>
                                            <p class="pp-num text-[13px] font-extrabold text-slate-900 dark:text-slate-100" x-text="rowTotalLabel(item)"></p>
                                        </div>
                                    </div>
                                    <input type="text" placeholder="✎ {{ __('note…') }}" class="mt-1.5 h-[26px] w-full border-0 border-b border-dashed border-slate-200 bg-transparent p-0 px-0.5 text-[11.5px] text-slate-500 focus:border-amber-400 focus:ring-0 dark:border-slate-700 dark:text-slate-400"
                                        :value="item.note" :data-key="item.key" @input="onNoteInput">
                                </div>
                            </template>
                        </div>

                        {{-- add by code --}}
                        <button type="button" class="flex w-full items-center justify-between border-t border-slate-100 px-4 py-2.5 text-xs font-extrabold text-amber-700 hover:bg-amber-50/50 dark:border-slate-800 dark:text-amber-400 dark:hover:bg-slate-800/40" @click="toggleAbc">
                            <span><i class="fas fa-barcode me-1.5"></i>{{ __('Add by code (manual item)') }}</span>
                            <span x-text="abcChevron"></span>
                        </button>
                        <div class="space-y-1.5 px-4 pb-3" x-show="abcOpen" x-cloak>
                            <div class="grid grid-cols-2 gap-1.5">
                                <input type="text" x-model="abc.code" placeholder="{{ __('Code / SKU / part no. *') }}" class="h-8 rounded-lg border-slate-300 bg-slate-50 px-2.5 py-0 text-xs focus:border-amber-400 focus:ring-2 focus:ring-amber-400/25 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                <input type="text" x-model="abc.name" placeholder="{{ __('Product name (optional)') }}" class="h-8 rounded-lg border-slate-300 bg-slate-50 px-2.5 py-0 text-xs focus:border-amber-400 focus:ring-2 focus:ring-amber-400/25 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                            </div>
                            <div class="grid grid-cols-2 gap-1.5">
                                <input type="number" min="1" step="1" x-model="abc.qty" placeholder="{{ __('Quantity *') }}" class="pp-num h-8 rounded-lg border-slate-300 bg-slate-50 px-2.5 py-0 text-xs focus:border-amber-400 focus:ring-2 focus:ring-amber-400/25 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                <input type="number" min="0" step="any" x-model="abc.cost" placeholder="{{ __('Unit cost') }}" class="pp-num h-8 rounded-lg border-slate-300 bg-slate-50 px-2.5 py-0 text-xs focus:border-amber-400 focus:ring-2 focus:ring-amber-400/25 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                            </div>
                            <input type="text" x-model="abc.note" placeholder="{{ __('Notes') }}" class="h-8 w-full rounded-lg border-slate-300 bg-slate-50 px-2.5 py-0 text-xs focus:border-amber-400 focus:ring-2 focus:ring-amber-400/25 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                            <p class="text-[10.5px] leading-snug text-slate-400">{{ __('Not in the product database? It is added to this list only, flagged Manual. No product record is created.') }}</p>
                            <button type="button" class="w-full rounded-lg bg-[#04042a] py-2 text-xs font-extrabold text-white hover:bg-[#10104a]" @click="addManual">{{ __('Add to current list') }}</button>
                        </div>

                        {{-- budget warning --}}
                        <p class="mx-4 mb-2 rounded-lg border border-rose-300 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-600 dark:border-rose-800 dark:bg-rose-950/30 dark:text-rose-300" x-show="overBudget" x-cloak>
                            ⚠ <span x-text="budgetWarningLabel"></span>
                        </p>

                        {{-- grand total --}}
                        <div class="border-t-2 border-amber-400 bg-slate-50 px-4 py-2.5 dark:bg-slate-950/40">
                            <div class="flex items-baseline justify-between">
                                <span class="text-[10.5px] font-black uppercase tracking-[0.12em] text-slate-400">{{ __('Grand Total') }}</span>
                                <span class="pp-num text-lg font-black text-amber-700 dark:text-amber-400" x-text="grandTotalLabel"></span>
                            </div>
                            <p class="mt-0.5 text-right text-[11px] font-semibold text-slate-400" x-show="hasBudget" x-text="budgetRemainingLabel"></p>
                        </div>

                        <p class="px-4 pt-2 text-xs font-extrabold text-emerald-600 dark:text-emerald-400" x-show="hasFlash" x-cloak x-text="flashMessage"></p>

                        {{-- actions --}}
                        <div class="flex flex-wrap gap-1.5 px-3.5 py-3">
                            <button type="button" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-extrabold text-white hover:bg-emerald-500" @click="saveList"><i class="fas fa-floppy-disk me-1"></i>{{ __('Save List') }}</button>
                            <button type="button" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-extrabold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" @click="saveDraft">{{ __('Save Draft') }}</button>
                            <button type="button" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-extrabold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" @click="printList"><i class="fas fa-print me-1"></i>{{ __('Print') }}</button>
                            <button type="button" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-extrabold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" @click="exportCsv"><i class="fas fa-file-csv me-1"></i>{{ __('Export CSV') }}</button>
                            <button type="button" class="rounded-lg px-3 py-1.5 text-xs font-extrabold text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/30" @click="clearList">✕ {{ __('Clear') }}</button>
                        </div>
                        <p class="px-4 pb-3 text-[10.5px] text-slate-400">{{ __('Lists are stored in this browser only — they survive reloads and filters, but other admins cannot see them.') }}</p>
                    </div>
                </aside>
            </div>

            {{-- ============ view saved list modal ============ --}}
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-[#04042a]/55 p-4" x-show="viewOpen" x-cloak @click.self="closeView" role="dialog" aria-modal="true">
                <div class="max-h-[82vh] w-full max-w-xl overflow-auto rounded-2xl bg-white shadow-2xl dark:bg-slate-900">
                    <div class="pp-dock-head flex items-center justify-between px-5 py-3.5 text-white">
                        <div class="min-w-0">
                            <h3 class="truncate text-[15px] font-extrabold" x-text="viewTitle"></h3>
                            <p class="text-[11px] text-white/60" x-text="viewMeta"></p>
                        </div>
                        <button type="button" class="px-1 text-lg" @click="closeView" aria-label="{{ __('Close') }}">✕</button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-950/40 dark:text-slate-400">
                                <tr>
                                    <th class="px-4 py-2.5 text-left">{{ __('Item') }}</th>
                                    <th class="px-4 py-2.5 text-right">{{ __('Qty') }}</th>
                                    <th class="px-4 py-2.5 text-right">{{ __('Unit') }}</th>
                                    <th class="px-4 py-2.5 text-right">{{ __('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <template x-for="item in viewItems" :key="item.key">
                                    <tr>
                                        <td class="px-4 py-2.5">
                                            <span class="font-semibold text-slate-800 dark:text-slate-100" x-text="item.name"></span>
                                            <span class="ms-1 inline-block rounded border border-dashed border-amber-600 bg-amber-50 px-1.5 text-[9px] font-black uppercase text-amber-700 dark:border-amber-400 dark:bg-amber-400/10 dark:text-amber-300" x-show="item.manual">{{ __('Manual') }}</span>
                                            <span class="block text-[11px] text-slate-400" x-text="item.sku"></span>
                                        </td>
                                        <td class="pp-num px-4 py-2.5 text-right" x-text="item.qtyLabel"></td>
                                        <td class="pp-num px-4 py-2.5 text-right" x-text="item.unitLabel"></td>
                                        <td class="pp-num px-4 py-2.5 text-right font-extrabold" x-text="item.totalLabel"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div class="flex items-baseline justify-between border-t-2 border-amber-400 px-5 py-3">
                        <span class="text-[10.5px] font-black uppercase tracking-[0.12em] text-slate-400">{{ __('Total') }}</span>
                        <span class="pp-num text-lg font-black text-amber-700 dark:text-amber-400" x-text="viewTotalLabel"></span>
                    </div>
                    <div class="flex flex-wrap items-center gap-1.5 px-5 pb-4">
                        <button type="button" class="rounded-lg border border-sky-300 px-3 py-1.5 text-xs font-extrabold text-sky-700 hover:bg-sky-50 dark:border-sky-800 dark:text-sky-300 dark:hover:bg-sky-950/30" @click="markOrdered">{{ __('Mark as Ordered') }}</button>
                        <button type="button" class="ms-auto rounded-lg px-3 py-1.5 text-xs font-extrabold text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/30" @click="deleteViewedList">{{ __('Delete list') }}</button>
                    </div>
                </div>
            </div>

            {{-- ============ print-only purchase order ============ --}}
            <section class="pp-print">
                <div style="border-bottom: 3px solid #04042a; padding-bottom: 12px; margin-bottom: 16px;">
                    <p style="margin: 0; font-size: 11px; font-weight: 900; letter-spacing: 0.15em; color: #b45309;">YALLA SPARE</p>
                    <h1 style="margin: 2px 0 0; font-size: 22px; font-weight: 900; color: #04042a;">{{ __('Purchase Order') }} — <span x-text="activeName"></span></h1>
                    <p style="margin: 4px 0 0; font-size: 12px; color: #64748b;"><span x-text="printDateLabel"></span> · <span x-text="activeStatusLabel"></span></p>
                </div>
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr>
                            <th style="text-align: left; border-bottom: 2px solid #04042a; padding: 6px 8px;">{{ __('Item') }}</th>
                            <th style="text-align: left; border-bottom: 2px solid #04042a; padding: 6px 8px;">{{ __('SKU') }}</th>
                            <th style="text-align: right; border-bottom: 2px solid #04042a; padding: 6px 8px;">{{ __('Qty') }}</th>
                            <th style="text-align: right; border-bottom: 2px solid #04042a; padding: 6px 8px;">{{ __('Unit Cost') }}</th>
                            <th style="text-align: right; border-bottom: 2px solid #04042a; padding: 6px 8px;">{{ __('Total') }}</th>
                            <th style="text-align: left; border-bottom: 2px solid #04042a; padding: 6px 8px;">{{ __('Notes') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="item in printItems" :key="item.key">
                            <tr>
                                <td style="border-bottom: 1px solid #e2e8f0; padding: 6px 8px;"><span x-text="item.name"></span><span x-show="item.manual"> ({{ __('Manual') }})</span></td>
                                <td style="border-bottom: 1px solid #e2e8f0; padding: 6px 8px;" x-text="item.sku"></td>
                                <td style="border-bottom: 1px solid #e2e8f0; padding: 6px 8px; text-align: right;" x-text="item.qtyLabel"></td>
                                <td style="border-bottom: 1px solid #e2e8f0; padding: 6px 8px; text-align: right;" x-text="item.unitLabel"></td>
                                <td style="border-bottom: 1px solid #e2e8f0; padding: 6px 8px; text-align: right; font-weight: 700;" x-text="item.totalLabel"></td>
                                <td style="border-bottom: 1px solid #e2e8f0; padding: 6px 8px;" x-text="item.note"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <p style="margin-top: 14px; text-align: right; font-size: 16px; font-weight: 900; color: #04042a;">
                    {{ __('Grand Total') }}: <span x-text="grandTotalLabel"></span>
                </p>
            </section>
        </div>
    </div>
</x-app-layout>
