@extends('layouts.user')

@section('content')
    <div class="mx-auto w-full max-w-3xl space-y-6 py-4">
        @if (session('success'))
            <x-ui.alert variant="success" :title="__('Order Confirmed')">
                {{ session('success') }}
            </x-ui.alert>
        @endif

        <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10 sm:p-8">
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ __('Checkout Complete') }}</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-[-0.03em] text-slate-950 dark:text-white">{{ __('Thank you for your order') }}</h1>
            <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">
                {{ __('Your order') }} <span class="font-semibold text-slate-900 dark:text-white">#{{ $order->order_number ?: $order->id }}</span> {{ __('has been received.') }}
            </p>
        </section>

        <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10 sm:p-8">
            <h2 class="text-xl font-semibold text-slate-950 dark:text-white">{{ __('Payment Summary') }}</h2>

            <div class="mt-5 rounded-2xl border border-slate-200/80 bg-slate-50 px-4 py-2 dark:border-slate-800 dark:bg-slate-950">
                <div class="flex items-center justify-between border-b border-slate-200/80 py-3 dark:border-slate-800">
                    <span class="text-sm font-medium text-slate-600 dark:text-slate-300">{{ __('Subtotal') }}</span>
                    <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ number_format($subtotal, 0) }} {{ $currencySymbol }}</span>
                </div>
                <div class="flex items-center justify-between border-b border-slate-200/80 py-3 dark:border-slate-800">
                    <span class="text-sm font-medium text-slate-600 dark:text-slate-300">{{ __('Shipping Fee') }}</span>
                    <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ number_format($shippingFee, 0) }} {{ $currencySymbol }}</span>
                </div>
                @if(($discountAmount ?? 0) > 0)
                    <div class="flex items-center justify-between border-b border-slate-200/80 py-3 dark:border-slate-800">
                        <span class="text-sm font-medium text-slate-600 dark:text-slate-300">{{ __('Discount') }}</span>
                        <span class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">-{{ number_format($discountAmount, 0) }} {{ $currencySymbol }}</span>
                    </div>
                @endif
                <div class="flex items-center justify-between py-3">
                    <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Total') }}</span>
                    <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ number_format($grandTotal, 0) }} {{ $currencySymbol }}</span>
                </div>
            </div>

            <div class="mt-6 flex flex-wrap items-center gap-3">
                @if ($order->user_id && auth()->check())
                    <a
                        href="{{ route('account.orders.show', $order) }}"
                        class="inline-flex items-center justify-center rounded-2xl bg-primary px-4 py-2.5 text-sm font-semibold text-white transition duration-200 hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                    >
                        {{ __('View Order') }}
                    </a>
                @endif
                <a
                    href="{{ route('shop.index') }}"
                    class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition duration-200 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                >
                    {{ __('Continue Shopping') }}
                </a>
            </div>
        </section>
    </div>
@endsection
