<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold text-slate-800 dark:text-slate-100">{{ __('Dead Stock') }}</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Find products holding stock value without recent paid sales.') }}</p>
        </div>
    </x-slot>

    @php
        $money = fn ($value) => number_format((float) $value, $currency['decimals']) . ' ' . $currency['label'];
        $maxValue = max(1.0, (float) $products->getCollection()->max('inventory_value'));
        $yearCutoff = now()->subDays(365);
        $chipUrl = fn (int $days) => route('admin.dead-stock.index', array_filter([
            'idle_days' => $days,
            'search' => $search !== '' ? $search : null,
            'never_sold' => $neverSoldOnly ? 1 : null,
        ], fn ($param) => $param !== null));
        $neverSoldUrl = route('admin.dead-stock.index', array_filter([
            'idle_days' => $idleDays,
            'search' => $search !== '' ? $search : null,
            'never_sold' => $neverSoldOnly ? null : 1,
        ], fn ($param) => $param !== null));
    @endphp

    <style>
        .ds-hero {
            position: relative; overflow: hidden;
            background: linear-gradient(135deg, #04041f, #12124a);
            border-radius: 16px; padding: 18px 20px; color: #fff;
        }
        .ds-hero::after {
            content: ""; position: absolute; inset: 0;
            background-image: repeating-linear-gradient(135deg, rgba(255,255,255,0.05) 0 1px, transparent 1px 14px);
        }
        .ds-hero > * { position: relative; z-index: 1; }
        .ds-hero .ds-lbl { color: rgba(255,255,255,0.55); }
        .ds-hero-big {
            margin-top: 6px; font-size: 28px; font-weight: 700; line-height: 1.1; color: #ff8a3d;
            font-family: ui-monospace, 'JetBrains Mono', Consolas, monospace; font-variant-numeric: tabular-nums;
        }
        .ds-hero-sub { margin-top: 4px; font-size: 12px; color: rgba(255,255,255,0.65); }

        .ds-lbl {
            font-size: 10px; font-weight: 700; letter-spacing: 0.13em; text-transform: uppercase;
            color: var(--text-muted);
        }
        .ds-stat {
            background: var(--surface); border: 1px solid #e3e9f1; border-radius: 16px; padding: 14px 16px;
            box-shadow: 0 1px 2px rgba(7,7,64,0.04), 0 4px 16px rgba(7,7,64,0.06);
        }
        .dark .ds-stat { background: var(--surface); border-color: var(--border); }
        .ds-stat-big {
            margin-top: 4px; font-size: 24px; font-weight: 700; color: var(--text);
            font-family: ui-monospace, 'JetBrains Mono', Consolas, monospace; font-variant-numeric: tabular-nums;
        }
        .dark .ds-stat-big { color: var(--text); }
        .ds-stat-big.rose { color: #be123c; }
        .dark .ds-stat-big.rose { color: #fda4af; }

        /* Idle-days chips — same dialect as Reviews/Vehicle Finder */
        .ds-chip {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 6px 12px; border-radius: 999px;
            font-size: 11.5px; font-weight: 700; line-height: 1;
            border: 1px solid var(--border); background: var(--surface); color: var(--text-secondary);
            text-decoration: none; transition: all .15s ease;
        }
        .ds-chip:hover { background: var(--surface-sunk); border-color: var(--border); color: #04041f; }
        .ds-chip .cnt {
            background: rgba(15,23,42,0.06); padding: 1px 7px; border-radius: 999px;
            font-size: 10.5px; font-weight: 700; color: var(--text-secondary);
            font-family: ui-monospace, 'JetBrains Mono', Consolas, monospace;
        }
        .ds-chip.on {
            background: #04041f; color: #ffb27a; border-color: #04041f;
            box-shadow: 0 6px 14px -8px rgba(4,4,42,0.40);
        }
        .ds-chip.on .cnt { background: rgba(252,211,77,0.18); color: #ffb27a; }
        .dark .ds-chip { background: var(--surface-sunk); border-color: var(--border); color: var(--text-secondary); }
        .dark .ds-chip .cnt { background: rgba(255,255,255,0.06); color: var(--text-secondary); }
        .dark .ds-chip:hover { background: linear-gradient(var(--hover-tint), var(--hover-tint)), var(--surface-sunk); color: var(--text); }
        .dark .ds-chip.on { background: #ff8a3d; color: #04041f; border-color: #ff8a3d; }
        .dark .ds-chip.on .cnt { background: rgba(4,4,42,0.18); color: #04041f; }
        .ds-chip.rose.on { background: #f43f5e; color: #fff; border-color: #f43f5e; box-shadow: 0 6px 14px -8px rgba(244,63,94,0.45); }
        .dark .ds-chip.rose.on { background: #f43f5e; color: var(--text); border-color: #f43f5e; }

        .ds-inp {
            width: 100%; height: 38px; padding: 0 12px; font-size: 13px;
            border: 1px solid var(--border); border-radius: 10px;
            background: var(--surface-sunk); color: var(--text);
        }
        .ds-inp:focus {
            outline: none; border-color: #ff8a3d; background: var(--surface);
            box-shadow: 0 0 0 3px rgb(255 138 61 / 0.25);
        }
        .dark .ds-inp { background: var(--surface-sunk); border-color: var(--border); color: var(--text); }
        .dark .ds-inp:focus { background: var(--surface); }

        .ds-btn {
            display: inline-flex; align-items: center; justify-content: center;
            height: 38px; padding: 0 18px; border-radius: 10px; border: 1px solid #04041f;
            background: #04041f; color: #ffb27a; font-size: 12px; font-weight: 700; cursor: pointer;
            transition: all .15s ease;
        }
        .ds-btn:hover { background: #070740; transform: translateY(-1px); }
        .dark .ds-btn { background: #ff8a3d; color: #04041f; border-color: #ff8a3d; }
        .dark .ds-btn:hover { background: #e65c00; }

        /* Age pills */
        .ds-pill {
            display: inline-block; font-size: 10.5px; font-weight: 700; padding: 3px 9px; border-radius: 999px;
            font-family: ui-monospace, 'JetBrains Mono', Consolas, monospace; font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }
        .ds-pill.idle { background: #fef3c7; color: #b45309; }
        .dark .ds-pill.idle { background: rgb(255 138 61 / 0.14); color: #ffb27a; }
        .ds-pill.old { background: #ffe4e6; color: #be123c; }
        .dark .ds-pill.old { background: rgba(244,63,94,0.14); color: #fda4af; }
        .ds-pill.never { background: #f43f5e; color: #fff; font-family: inherit; letter-spacing: 0.04em; }

        /* Inline proportional value bar */
        .ds-vbar {
            position: relative; height: 24px; min-width: 150px; border-radius: 7px;
            background: #eef2f7; overflow: hidden;
        }
        .dark .ds-vbar { background: var(--surface-sunk); }
        .ds-vbar i {
            position: absolute; inset-block: 0; inset-inline-start: 0; border-radius: 7px;
            background: linear-gradient(90deg, #ff8a3d, #e65c00); opacity: 0.8;
        }
        .ds-vbar b {
            position: absolute; inset: 0; display: flex; align-items: center; justify-content: flex-end;
            padding: 0 9px; font-size: 11px; font-weight: 700; color: var(--text);
            font-family: ui-monospace, 'JetBrains Mono', Consolas, monospace; font-variant-numeric: tabular-nums;
        }
        .dark .ds-vbar b { color: var(--text); }

        .ds-edit {
            display: inline-block; border: 1px solid var(--border); border-radius: 8px; padding: 5px 12px;
            font-size: 11px; font-weight: 700; color: var(--text-secondary); text-decoration: none;
            transition: all .15s ease;
        }
        .ds-edit:hover { background: var(--surface-sunk); border-color: var(--border); color: #04041f; }
        .dark .ds-edit { border-color: var(--border); color: var(--text-secondary); }
        .dark .ds-edit:hover { background: linear-gradient(var(--hover-tint), var(--hover-tint)), var(--surface-sunk); color: var(--text); }
    </style>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-[1.4fr_1fr_1fr_1fr]">
                <article class="ds-hero">
                    <p class="ds-lbl">{{ __('Value On Page') }}</p>
                    <p class="ds-hero-big">{{ $money($summary['value_on_page']) }}</p>
                    <p class="ds-hero-sub">{{ __('No sales :days days', ['days' => $idleDays]) }}</p>
                </article>
                <article class="ds-stat">
                    <p class="ds-lbl">{{ __('Products') }}</p>
                    <p class="ds-stat-big">{{ number_format($summary['products']) }}</p>
                </article>
                <article class="ds-stat">
                    <p class="ds-lbl">{{ __('Units On Page') }}</p>
                    <p class="ds-stat-big">{{ number_format($summary['units']) }}</p>
                </article>
                <article class="ds-stat">
                    <p class="ds-lbl">{{ __('Never Sold On Page') }}</p>
                    <p class="ds-stat-big rose">{{ number_format($summary['never_sold_on_page']) }}</p>
                </article>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <form method="GET" action="{{ route('admin.dead-stock.index') }}" class="flex flex-col gap-3 sm:flex-row">
                    <input type="hidden" name="idle_days" value="{{ $idleDays }}">
                    @if($neverSoldOnly)
                        <input type="hidden" name="never_sold" value="1">
                    @endif
                    <input type="search" name="search" value="{{ $search }}" placeholder="{{ __('Search product, SKU, brand') }}" class="ds-inp sm:flex-1">
                    <button class="ds-btn">{{ __('Filter') }}</button>
                </form>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach($idleBuckets as $option => $count)
                        <a href="{{ $chipUrl($option) }}" class="ds-chip {{ $idleDays === $option ? 'on' : '' }}">
                            {{ __('No sales :days days', ['days' => $option]) }}
                            <span class="cnt">{{ number_format($count) }}</span>
                        </a>
                    @endforeach
                    <a href="{{ $neverSoldUrl }}" class="ds-chip rose {{ $neverSoldOnly ? 'on' : '' }}">{{ __('Never sold only') }}</a>
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-950/40 dark:text-slate-400">
                            <tr>
                                <th class="px-4 py-3 text-left">{{ __('Product') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Stock') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Last Sale') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Lifetime Sold') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Inventory Value') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($products as $product)
                                @php
                                    $lastSold = $product->last_sold_at ? \Carbon\Carbon::parse($product->last_sold_at) : null;
                                    $valuePct = max(3, (int) round($product->inventory_value / $maxValue * 100));
                                @endphp
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-slate-900 dark:text-slate-100">{{ $product->name }}</div>
                                        <div class="text-xs text-slate-500">{{ $product->sku ?? __('N/A') }} @if($product->brand) · {{ $product->brand }} @endif</div>
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold tabular-nums">{{ number_format((int) $product->stock_quantity) }}</td>
                                    <td class="px-4 py-3 text-right">
                                        @if(! $lastSold)
                                            <span class="ds-pill never">{{ __('Never sold') }}</span>
                                        @else
                                            <span class="ds-pill {{ $lastSold->lt($yearCutoff) ? 'old' : 'idle' }}">{{ $lastSold->diffForHumans() }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right text-slate-600 tabular-nums dark:text-slate-300">{{ number_format((int) $product->lifetime_sold_quantity) }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="ds-vbar"><i style="width: {{ $valuePct }}%"></i><b>{{ $money($product->inventory_value) }}</b></div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('admin.products.edit', $product) }}" class="ds-edit">{{ __('Edit') }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">{{ __('No dead stock matched the filters.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-200 px-4 py-3 dark:border-slate-800">{{ $products->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
