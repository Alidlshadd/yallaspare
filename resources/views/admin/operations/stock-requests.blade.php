<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold text-slate-800 dark:text-slate-100">{{ __('Stock Requests') }}</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Review products customers are waiting for and mark restock notifications as handled.') }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/20 dark:text-emerald-300">{{ session('success') }}</div>
            @endif

            <section class="grid gap-4 md:grid-cols-3">
                <article class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-200">
                    <p class="text-xs font-bold uppercase tracking-[0.14em] opacity-75">{{ __('Pending Requests') }}</p>
                    <p class="mt-2 text-3xl font-black">{{ number_format($summary['pending']) }}</p>
                </article>
                <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/20 dark:text-emerald-200">
                    <p class="text-xs font-bold uppercase tracking-[0.14em] opacity-75">{{ __('Notified') }}</p>
                    <p class="mt-2 text-3xl font-black">{{ number_format($summary['notified']) }}</p>
                </article>
                <article class="rounded-2xl border border-sky-200 bg-sky-50 p-5 text-sky-800 dark:border-sky-900/50 dark:bg-sky-950/20 dark:text-sky-200">
                    <p class="text-xs font-bold uppercase tracking-[0.14em] opacity-75">{{ __('Requested Products') }}</p>
                    <p class="mt-2 text-3xl font-black">{{ number_format($summary['products']) }}</p>
                </article>
            </section>

            <form method="GET" action="{{ route('admin.stock-requests.index') }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="grid gap-3 md:grid-cols-4">
                    <input type="search" name="search" value="{{ $search }}" placeholder="{{ __('Search product, SKU, brand') }}" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 md:col-span-2">
                    <select name="status" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        @foreach(['pending' => __('Pending'), 'notified' => __('Notified'), 'all' => __('All')] as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">{{ __('Filter') }}</button>
                </div>
            </form>

            @unless($hasTable)
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900">{{ __('Back-in-stock subscriptions table is not available yet.') }}</div>
            @else
                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-950/40 dark:text-slate-400">
                                <tr>
                                    <th class="px-4 py-3 text-left">{{ __('Product') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('Stock') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('Requests') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('Pending') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('Latest') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @forelse($products as $product)
                                    <tr>
                                        <td class="px-4 py-3">
                                            <div class="font-semibold text-slate-900 dark:text-slate-100">{{ $product->name }}</div>
                                            <div class="text-xs text-slate-500">{{ $product->sku ?? __('N/A') }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-right">{{ number_format((int) $product->stock_quantity) }}</td>
                                        <td class="px-4 py-3 text-right">{{ number_format((int) $product->request_count) }}</td>
                                        <td class="px-4 py-3 text-right font-bold text-amber-700 dark:text-amber-300">{{ number_format((int) $product->pending_count) }}</td>
                                        <td class="px-4 py-3 text-right text-slate-500">{{ optional($product->latest_requested_at ? \Carbon\Carbon::parse($product->latest_requested_at) : null)->diffForHumans() }}</td>
                                        <td class="px-4 py-3 text-right">
                                            @if((int) $product->pending_count > 0)
                                                <form method="POST" action="{{ route('admin.stock-requests.notify', $product) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">{{ __('Mark notified') }}</button>
                                                </form>
                                            @else
                                                <span class="text-xs text-slate-400">{{ __('Done') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">{{ __('No stock requests matched the filters.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-slate-200 px-4 py-3 dark:border-slate-800">{{ $products->links() }}</div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h3 class="text-sm font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Recent Requests') }}</h3>
                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                        @forelse($requests as $requestRow)
                            <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                                <div class="font-semibold text-slate-900 dark:text-slate-100">{{ $requestRow->product?->name ?? __('Deleted product') }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $requestRow->user?->name }} · {{ $requestRow->user?->email }}</div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">{{ __('No recent requests.') }}</p>
                        @endforelse
                    </div>
                </section>
            @endunless
        </div>
    </div>
</x-app-layout>
