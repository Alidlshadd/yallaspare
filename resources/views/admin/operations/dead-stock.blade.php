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
            background: linear-gradient(135deg, #04042a, #10104a);
            border-radius: 16px; padding: 18px 20px; color: #fff;
        }
        .ds-hero::after {
            content: ""; position: absolute; inset: 0;
            background-image: repeating-linear-gradient(135deg, rgba(255,255,255,0.05) 0 1px, transparent 1px 14px);
        }
        .ds-hero > * { position: relative; z-index: 1; }
        .ds-hero .ds-lbl { color: rgba(255,255,255,0.55); }
        .ds-hero-big {
            margin-top: 6px; font-size: 28px; font-weight: 900; line-height: 1.1; color: #fbbf24;
            font-family: ui-monospace, 'JetBrains Mono', Consolas, monospace; font-variant-numeric: tabular-nums;
        }
        .ds-hero-sub { margin-top: 4px; font-size: 12px; color: rgba(255,255,255,0.65); }

        .ds-lbl {
            font-size: 10px; font-weight: 800; letter-spacing: 0.13em; text-transform: uppercase;
            color: #94a3b8;
        }
        .ds-stat {
            background: #fff; border: 1px solid #e3e9f1; border-radius: 16px; padding: 14px 16px;
            box-shadow: 0 1px 2px rgba(7,7,64,0.04), 0 4px 16px rgba(7,7,64,0.06);
        }
        .dark .ds-stat { background: #0f172a; border-color: #1e293b; }
        .ds-stat-big {
            margin-top: 4px; font-size: 24px; font-weight: 900; color: #0f172a;
            font-family: ui-monospace, 'JetBrains Mono', Consolas, monospace; font-variant-numeric: tabular-nums;
        }
        .dark .ds-stat-big { color: #f1f5f9; }
        .ds-stat-big.rose { color: #be123c; }
        .dark .ds-stat-big.rose { color: #fda4af; }

        /* Idle-days chips — same dialect as Reviews/Vehicle Finder */
        .ds-chip {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 6px 12px; border-radius: 999px;
            font-size: 11.5px; font-weight: 700; line-height: 1;
            border: 1px solid #e2e8f0; background: #fff; color: #475569;
            text-decoration: none; transition: all .15s ease;
        }
        .ds-chip:hover { background: #f8fafc; border-color: #cbd5e1; color: #04042a; }
        .ds-chip .cnt {
            background: rgba(15,23,42,0.06); padding: 1px 7px; border-radius: 999px;
            font-size: 10.5px; font-weight: 800; color: #475569;
            font-family: ui-monospace, 'JetBrains Mono', Consolas, monospace;
        }
        .ds-chip.on {
            background: #04042a; color: #fcd34d; border-color: #04042a;
            box-shadow: 0 6px 14px -8px rgba(4,4,42,0.40);
        }
        .ds-chip.on .cnt { background: rgba(252,211,77,0.18); color: #fcd34d; }
        .dark .ds-chip { background: #1e293b; border-color: #334155; color: #cbd5e1; }
        .dark .ds-chip .cnt { background: rgba(255,255,255,0.06); color: #cbd5e1; }
        .dark .ds-chip:hover { background: #334155; color: #fff; }
        .dark .ds-chip.on { background: #fbbf24; color: #04042a; border-color: #fbbf24; }
        .dark .ds-chip.on .cnt { background: rgba(4,4,42,0.18); color: #04042a; }
        .ds-chip.rose.on { background: #f43f5e; color: #fff; border-color: #f43f5e; box-shadow: 0 6px 14px -8px rgba(244,63,94,0.45); }
        .dark .ds-chip.rose.on { background: #f43f5e; color: #fff; border-color: #f43f5e; }

        .ds-inp {
            width: 100%; height: 38px; padding: 0 12px; font-size: 13px;
            border: 1px solid #e2e8f0; border-radius: 10px;
            background: #f8fafc; color: #0f172a;
        }
        .ds-inp:focus {
            outline: none; border-color: #fbbf24; background: #fff;
            box-shadow: 0 0 0 3px rgba(251,191,36,0.25);
        }
        .dark .ds-inp { background: #1e293b; border-color: #334155; color: #f1f5f9; }
        .dark .ds-inp:focus { background: #0f172a; }

        .ds-btn {
            display: inline-flex; align-items: center; justify-content: center;
            height: 38px; padding: 0 18px; border-radius: 10px; border: 1px solid #04042a;
            background: #04042a; color: #fcd34d; font-size: 12px; font-weight: 800; cursor: pointer;
            transition: all .15s ease;
        }
        .ds-btn:hover { background: #07073a; transform: translateY(-1px); }
        .dark .ds-btn { background: #fbbf24; color: #04042a; border-color: #fbbf24; }
        .dark .ds-btn:hover { background: #f59e0b; }

        /* Age pills */
        .ds-pill {
            display: inline-block; font-size: 10.5px; font-weight: 800; padding: 3px 9px; border-radius: 999px;
            font-family: ui-monospace, 'JetBrains Mono', Consolas, monospace; font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }
        .ds-pill.idle { background: #fef3c7; color: #b45309; }
        .dark .ds-pill.idle { background: rgba(251,191,36,0.14); color: #fcd34d; }
        .ds-pill.old { background: #ffe4e6; color: #be123c; }
        .dark .ds-pill.old { background: rgba(244,63,94,0.14); color: #fda4af; }
        .ds-pill.never { background: #f43f5e; color: #fff; font-family: inherit; letter-spacing: 0.04em; }

        /* Inline proportional value bar */
        .ds-vbar {
            position: relative; height: 24px; min-width: 150px; border-radius: 7px;
            background: #eef2f7; overflow: hidden;
        }
        .dark .ds-vbar { background: #1e293b; }
        .ds-vbar i {
            position: absolute; inset-block: 0; inset-inline-start: 0; border-radius: 7px;
            background: linear-gradient(90deg, #fbbf24, #f59e0b); opacity: 0.8;
        }
        .ds-vbar b {
            position: absolute; inset: 0; display: flex; align-items: center; justify-content: flex-end;
            padding: 0 9px; font-size: 11px; font-weight: 800; color: #0f172a;
            font-family: ui-monospace, 'JetBrains Mono', Consolas, monospace; font-variant-numeric: tabular-nums;
        }
        .dark .ds-vbar b { color: #f1f5f9; }

        .ds-edit {
            display: inline-block; border: 1px solid #e2e8f0; border-radius: 8px; padding: 5px 12px;
            font-size: 11px; font-weight: 700; color: #475569; text-decoration: none;
            transition: all .15s ease;
        }
        .ds-edit:hover { background: #f8fafc; border-color: #cbd5e1; color: #04042a; }
        .dark .ds-edit { border-color: #334155; color: #cbd5e1; }
        .dark .ds-edit:hover { background: #334155; color: #fff; }
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
