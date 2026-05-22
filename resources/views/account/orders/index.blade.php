@extends('layouts.user')

@section('content')
    @php
        $currencyLabel = (string) ($systemSettings['currency_label'] ?? 'IQD');
        $currencyDecimals = (int) ($systemSettings['currency_decimals'] ?? 0);
    @endphp
    <div class="space-y-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ __('Account / Orders') }}</p>
                    <h2 class="mt-1 text-2xl font-semibold tracking-[-0.02em] text-slate-950 dark:text-white">{{ __('My Orders') }}</h2>
                </div>
                <a href="{{ route('account.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:text-slate-950 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                    {{ __('Back to Account') }}
                </a>
            </div>

            @if (session('status'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 shadow-sm dark:border-emerald-900/50 dark:bg-emerald-900/20">
                    <p class="text-sm font-semibold text-emerald-800 dark:text-emerald-300">{{ session('status') }}</p>
                </div>
            @endif

            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="border-b border-slate-100 px-6 py-6 sm:px-8 dark:border-slate-800">
                            <h3 class="text-xl font-semibold tracking-[-0.02em] text-slate-950 dark:text-white">{{ __('Orders History') }}</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-300">{{ __('Track every purchase from your YallaSpare account with a clean chronological view.') }}</p>
                        </div>

                        @if ($orders->isEmpty())
                            <div class="px-6 py-16 text-center sm:px-8">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-slate-100 text-slate-500">
                                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M7 4h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" />
                                    </svg>
                                </div>
                                <h4 class="mt-5 text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('No orders yet') }}</h4>
                                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ __('Browse the marketplace and place your first order to see it here.') }}</p>
                                <a href="{{ route('shop.index') }}" class="mt-6 inline-flex items-center justify-center rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                                    {{ __('Browse Products') }}
                                </a>
                            </div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                                    <thead class="bg-slate-50 dark:bg-slate-800/70">
                                        <tr>
                                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-300 sm:px-8">{{ __('Order ID') }}</th>
                                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-300">{{ __('Status') }}</th>
                                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-300">{{ __('Total Amount') }}</th>
                                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-300">{{ __('Date') }}</th>
                                            <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-300 sm:px-8">{{ __('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-900">
                                        @foreach ($orders as $order)
                                            @php($statusMeta = \App\Models\Order::statusMeta($order->status))
                                            <tr id="order-{{ $order->id }}" class="transition hover:bg-slate-50/80 dark:hover:bg-slate-800/60">
                                                <td class="px-6 py-4 sm:px-8">
                                                    <div class="font-semibold text-slate-900 dark:text-slate-100">#{{ $order->order_number ?: $order->id }}</div>
                                                    <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">Ref {{ str_pad((string) $order->id, 6, '0', STR_PAD_LEFT) }}</div>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $statusMeta['class'] }}">
                                                        {{ $statusMeta['label'] }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 text-sm font-semibold text-slate-900 dark:text-white">{{ number_format((float) $order->total_amount, $currencyDecimals) }} {{ $currencyLabel }}</td>
                                                <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-300">{{ optional($order->created_at)->format('M d, Y') }}</td>
                                                <td class="px-6 py-4 text-right sm:px-8">
                                                    <div class="flex flex-wrap justify-end gap-2">
                                                        <a href="{{ route('account.orders.show', $order) }}" class="text-sm font-semibold text-slate-700 transition hover:text-slate-900 dark:text-slate-300 dark:hover:text-slate-100">{{ __('View') }}</a>
                                                        <x-invoice-language-links :order="$order" route-name="account.orders.invoice" mode="inline" size="xs" />
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="border-t border-slate-200 px-6 py-4 sm:px-8 dark:border-slate-800">
                                {{ $orders->links() }}
                            </div>
                        @endif
            </section>
    </div>
@endsection
