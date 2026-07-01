<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold text-slate-800 dark:text-slate-100">{{ __('Dead Stock') }}</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Find products holding stock value without recent paid sales.') }}</p>
        </div>
    </x-slot>

    @php $money = fn ($value) => number_format((float) $value, $currency['decimals']) . ' ' . $currency['label']; @endphp

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="grid gap-4 md:grid-cols-4">
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">{{ __('Products') }}</p>
                    <p class="mt-2 text-3xl font-black text-slate-900 dark:text-slate-100">{{ number_format($summary['products']) }}</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">{{ __('Units On Page') }}</p>
                    <p class="mt-2 text-3xl font-black text-slate-900 dark:text-slate-100">{{ number_format($summary['units']) }}</p>
                </article>
                <article class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-amber-800 shadow-sm dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-200">
                    <p class="text-xs font-bold uppercase tracking-[0.14em] opacity-75">{{ __('Value On Page') }}</p>
                    <p class="mt-2 text-2xl font-black">{{ $money($summary['value_on_page']) }}</p>
                </article>
                <article class="rounded-2xl border border-rose-200 bg-rose-50 p-5 text-rose-800 shadow-sm dark:border-rose-900/50 dark:bg-rose-950/20 dark:text-rose-200">
                    <p class="text-xs font-bold uppercase tracking-[0.14em] opacity-75">{{ __('Never Sold On Page') }}</p>
                    <p class="mt-2 text-3xl font-black">{{ number_format($summary['never_sold_on_page']) }}</p>
                </article>
            </section>

            <form method="GET" action="{{ route('admin.dead-stock.index') }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="grid gap-3 md:grid-cols-4">
                    <input type="search" name="search" value="{{ $search }}" placeholder="{{ __('Search product, SKU, brand') }}" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 md:col-span-2">
                    <select name="idle_days" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        @foreach([30, 60, 90, 180, 365] as $option)
                            <option value="{{ $option }}" @selected($idleDays === $option)>{{ __('No sales :days days', ['days' => $option]) }}</option>
                        @endforeach
                    </select>
                    <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">{{ __('Filter') }}</button>
                </div>
            </form>

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
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-slate-900 dark:text-slate-100">{{ $product->name }}</div>
                                        <div class="text-xs text-slate-500">{{ $product->sku ?? __('N/A') }} @if($product->brand) · {{ $product->brand }} @endif</div>
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold">{{ number_format((int) $product->stock_quantity) }}</td>
                                    <td class="px-4 py-3 text-right text-slate-500">{{ $product->last_sold_at ? \Carbon\Carbon::parse($product->last_sold_at)->diffForHumans() : __('Never') }}</td>
                                    <td class="px-4 py-3 text-right text-slate-600 dark:text-slate-300">{{ number_format((int) $product->lifetime_sold_quantity) }}</td>
                                    <td class="px-4 py-3 text-right font-bold text-amber-700 dark:text-amber-300">{{ $money($product->inventory_value) }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('admin.products.edit', $product) }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">{{ __('Edit') }}</a>
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
