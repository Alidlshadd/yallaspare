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
        $cards = [
            ['label' => __('Out of Stock'), 'value' => number_format($summary['out_of_stock']), 'icon' => 'fa-circle-exclamation', 'class' => 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-900/50 dark:bg-rose-950/20 dark:text-rose-200'],
            ['label' => __('Low Stock'), 'value' => number_format($summary['low_stock']), 'icon' => 'fa-arrow-trend-down', 'class' => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-200'],
            ['label' => __('Waiting Customers'), 'value' => number_format($summary['waiting_customers']), 'icon' => 'fa-bell', 'class' => 'border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-900/50 dark:bg-sky-950/20 dark:text-sky-200'],
            ['label' => __('Page Budget'), 'value' => $money($summary['estimated_budget']), 'icon' => 'fa-coins', 'class' => 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/20 dark:text-emerald-200'],
        ];
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @foreach($cards as $card)
                    <article class="rounded-2xl border p-5 shadow-sm {{ $card['class'] }}">
                        <div class="flex items-start justify-between gap-3">
                            <p class="text-xs font-bold uppercase tracking-[0.14em] opacity-75">{{ $card['label'] }}</p>
                            <i class="fas {{ $card['icon'] }}"></i>
                        </div>
                        <p class="mt-3 text-2xl font-black">{{ $card['value'] }}</p>
                    </article>
                @endforeach
            </section>

            <form method="GET" action="{{ route('admin.purchase-planning.index') }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="grid gap-3 md:grid-cols-5">
                    <input type="search" name="search" value="{{ $search }}" placeholder="{{ __('Search product, SKU, brand') }}" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 md:col-span-2">
                    <select name="status" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        @foreach(['needs_reorder' => __('Needs reorder'), 'out_of_stock' => __('Out of stock'), 'low_stock' => __('Low stock'), 'all' => __('All products')] as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="days" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        @foreach([7, 30, 90] as $option)
                            <option value="{{ $option }}" @selected($days === $option)>{{ __('Sales :days days', ['days' => $option]) }}</option>
                        @endforeach
                    </select>
                    <select name="coverage_days" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        @foreach([14, 30, 60, 90] as $option)
                            <option value="{{ $option }}" @selected($coverageDays === $option)>{{ __('Cover :days days', ['days' => $option]) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mt-3 flex justify-end gap-2">
                    <a href="{{ route('admin.purchase-planning.index') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">{{ __('Reset') }}</a>
                    <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">{{ __('Apply') }}</button>
                </div>
            </form>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
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
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($products as $product)
                                <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40">
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-slate-900 dark:text-slate-100">{{ $product->name }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ $product->sku ?? __('N/A') }} @if($product->brand) · {{ $product->brand }} @endif</div>
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold {{ (int) $product->stock_quantity <= 0 ? 'text-rose-600' : 'text-slate-700 dark:text-slate-200' }}">{{ number_format((int) $product->stock_quantity) }}</td>
                                    <td class="px-4 py-3 text-right text-slate-600 dark:text-slate-300">{{ number_format((int) $product->sold_quantity) }}</td>
                                    <td class="px-4 py-3 text-right text-slate-600 dark:text-slate-300">{{ $product->days_remaining === null ? __('N/A') : number_format((int) $product->days_remaining) }}</td>
                                    <td class="px-4 py-3 text-right text-sky-700 dark:text-sky-300">{{ number_format((int) $product->waiting_count) }}</td>
                                    <td class="px-4 py-3 text-right font-bold text-slate-900 dark:text-slate-100">{{ number_format((int) $product->recommended_quantity) }}</td>
                                    <td class="px-4 py-3 text-right text-slate-600 dark:text-slate-300">{{ $money($product->estimated_purchase_cost) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500">{{ __('No products matched the current planning filters.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-200 px-4 py-3 dark:border-slate-800">{{ $products->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
