@extends('layouts.user')

@section('title', __('Activity'))
@section('subtitle', __('Recent orders and account movement in one place'))
@section('actions')
    <a
        href="{{ route('user.account.edit') }}"
        class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition duration-200 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#070740] focus-visible:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
    >
        {{ __('Account') }}
        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M7.22 4.97a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 1 1-1.06-1.06L10.94 10 7.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
        </svg>
    </a>
@endsection

@section('content')
    @php
        $currencyLabel = (string) ($systemSettings['currency_label'] ?? 'IQD');
        $currencyDecimals = (int) ($systemSettings['currency_decimals'] ?? 0);
    @endphp
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[18rem_minmax(0,1fr)]">
        <aside class="space-y-6">
            @include('user.partials.account-nav')
        </aside>

        <div class="space-y-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div class="rounded-3xl border border-slate-200/80 bg-white px-4 py-4 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10">
                    <p class="text-xs font-medium uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Total Orders') }}</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">{{ $totalOrders }}</p>
                </div>
                <div class="rounded-3xl border border-slate-200/80 bg-white px-4 py-4 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10">
                    <p class="text-xs font-medium uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Pending') }}</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">{{ $pendingOrders }}</p>
                </div>
                <div class="rounded-3xl border border-slate-200/80 bg-white px-4 py-4 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10">
                    <p class="text-xs font-medium uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Delivered') }}</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">{{ $deliveredOrders }}</p>
                </div>
                <div class="rounded-3xl border border-slate-200/80 bg-white px-4 py-4 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10">
                    <p class="text-xs font-medium uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Spend') }}</p>
                    <p class="mt-2 text-xl font-semibold text-slate-900 dark:text-white">{{ number_format($totalSpend, $currencyDecimals) }} {{ $currencyLabel }}</p>
                </div>
            </div>

            <x-ui.card>
                <x-slot name="header">
                    <div>
                        <h2 class="text-base font-semibold text-app">{{ __('Recent Orders') }}</h2>
                        <p class="mt-1 text-sm text-muted">{{ __('Your latest account activity is driven by order history right now.') }}</p>
                    </div>
                </x-slot>

                <div class="space-y-3">
                    @forelse ($recentOrders as $order)
                        @php($statusMeta = \App\Models\Order::statusMeta($order->status))
                        <div class="flex flex-col gap-3 rounded-app border border-app bg-surface-1 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-sm font-semibold text-app">#{{ $order->order_number ?: $order->id }}</p>
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $statusMeta['class'] }}">
                                        {{ $statusMeta['label'] }}
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-muted">{{ optional($order->created_at)->format('M d, Y') }} • {{ number_format((float) $order->total_amount, $currencyDecimals) }} {{ $currencyLabel }}</p>
                            </div>
                            <a href="{{ route('account.orders.show', $order) }}" class="inline-flex h-10 items-center justify-center rounded-app border border-[var(--border)] bg-[var(--surface-2)] px-4 text-sm font-medium text-[var(--text)] transition duration-150 hover:bg-[var(--surface-1)] focus:outline-none focus:ring-4 ring-focus">
                                {{ __('View in Orders') }}
                            </a>
                        </div>
                    @empty
                        <x-ui.empty :title="__('No recent activity')" :description="__('Place your first order and activity will appear here.')">
                            <x-slot name="action">
                                <a href="{{ route('shop.index') }}" class="inline-flex h-10 items-center justify-center rounded-app border border-[var(--border)] bg-[var(--primary)] px-4 text-sm font-medium text-white transition duration-150 hover:bg-[var(--primary-hover)] focus:outline-none focus:ring-4 ring-focus">
                                    {{ __('Browse Products') }}
                                </a>
                            </x-slot>
                        </x-ui.empty>
                    @endforelse
                </div>
            </x-ui.card>
        </div>
    </div>
@endsection
