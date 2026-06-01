@extends('layouts.user')

@section('content')
    <div class="mx-auto w-full max-w-4xl space-y-6 py-4">
        @if (session('error'))
            <x-ui.alert variant="danger" :title="__('Please review')">
                {{ session('error') }}
            </x-ui.alert>
        @endif

        <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10 sm:p-8">
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ __('Buy Now') }}</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-[-0.03em] text-slate-950 dark:text-white">{{ __('Final order check') }}</h1>
            <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">
                {{ __('Confirm product, address, phone, and totals. If everything is correct, place the order.') }}
            </p>
        </section>

        <form action="{{ route('checkout.buy-now', $product) }}" method="POST" class="space-y-6">
            @csrf
            <input type="hidden" name="quantity" value="{{ $quantity }}">

            <section class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Product') }}</h2>
                <div class="mt-4 flex items-center justify-between rounded-2xl border border-slate-200/80 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950">
                    <div>
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $product->name }}</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Quantity:') }} {{ $quantity }}</p>
                    </div>
                    <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ number_format($subtotal, 0) }} {{ $currencySymbol }}</p>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Delivery Address') }}</h2>
                <div class="mt-4 space-y-3">
                    @foreach ($addresses as $address)
                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200/80 bg-slate-50 px-4 py-4 transition duration-200 hover:border-primary/20 dark:border-slate-800 dark:bg-slate-950 dark:hover:border-primary/30">
                            <input
                                type="radio"
                                name="address_id"
                                value="{{ $address->id }}"
                                @checked((int) old('address_id', $selectedAddressId ?? $defaultAddress?->id) === (int) $address->id)
                                class="mt-1 h-4 w-4 border-slate-300 text-primary focus:ring-primary/30 dark:border-slate-700 dark:bg-slate-900"
                            >
                            <span class="min-w-0 flex-1">
                                <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ $address->label ?: __('Saved Address') }}</span>
                                <span class="mt-2 block text-sm leading-6 text-slate-600 dark:text-slate-300">
                                    {{ $address->address_line1 }}
                                    @if ($address->address_line2)
                                        <br>{{ $address->address_line2 }}
                                    @endif
                                    <br>{{ $address->city }}, {{ $address->country }}
                                    <br>{{ $address->phone }}
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('address_id')
                    <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
                @enderror
            </section>

            <section class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Notes') }}</h2>
                <textarea
                    id="notes"
                    name="notes"
                    rows="4"
                    class="mt-3 block w-full rounded-2xl border border-slate-200/80 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition duration-200 focus:border-primary/20 focus:ring-4 focus:ring-primary/10 dark:border-slate-800 dark:bg-slate-950 dark:text-white"
                >{{ old('notes', $defaultDeliveryNote) }}</textarea>
            </section>

            <section class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Payment Summary') }}</h2>
                <div class="mt-4 rounded-2xl border border-slate-200/80 bg-slate-50 px-4 py-2 dark:border-slate-800 dark:bg-slate-950">
                    <div class="flex items-center justify-between border-b border-slate-200/80 py-3 dark:border-slate-800">
                        <span class="text-sm text-slate-600 dark:text-slate-300">{{ __('Subtotal') }}</span>
                        <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ number_format($subtotal, 0) }} {{ $currencySymbol }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-slate-200/80 py-3 dark:border-slate-800">
                        <span class="text-sm text-slate-600 dark:text-slate-300">{{ __('Shipping Fee') }}</span>
                        <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ number_format($shippingFee, 0) }} {{ $currencySymbol }}</span>
                    </div>
                    @if (($discountAmount ?? 0) > 0)
                        <div class="flex items-center justify-between border-b border-slate-200/80 py-3 dark:border-slate-800">
                            <span class="text-sm text-slate-600 dark:text-slate-300">{{ __('Discount') }}</span>
                            <span class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">-{{ number_format($discountAmount, 0) }} {{ $currencySymbol }}</span>
                        </div>
                    @endif
                    <div class="flex items-center justify-between py-3">
                        <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Total') }}</span>
                        <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ number_format($grandTotal, 0) }} {{ $currencySymbol }}</span>
                    </div>
                </div>

                <div class="mt-4 rounded-2xl border border-slate-200/80 bg-white p-3 dark:border-slate-800 dark:bg-slate-950">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ __('Payment Method') }}</p>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                        @foreach($paymentMethods as $method)
                            @php($isDisabled = ! ($method['enabled'] ?? false))
                            <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm transition dark:border-slate-800 dark:bg-slate-900 {{ $isDisabled ? 'cursor-not-allowed opacity-60' : 'cursor-pointer hover:border-primary/30' }}">
                                <input
                                    type="radio"
                                    name="payment_method"
                                    value="{{ $method['key'] }}"
                                    @checked(old('payment_method', 'cash_on_delivery') === $method['key'])
                                    @disabled($isDisabled)
                                    class="h-4 w-4 border-slate-300 text-primary focus:ring-primary/30 dark:border-slate-700 dark:bg-slate-900"
                                >
                                <span class="flex-1 font-medium text-slate-900 dark:text-white">{{ __($method['label']) }}</span>
                                @if($method['coming_soon'] ?? false)
                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">{{ __('Coming soon') }}</span>
                                @elseif($method['online'])
                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ __('Online') }}</span>
                                @endif
                            </label>
                        @endforeach
                    </div>
                    @error('payment_method')
                        <p class="mt-2 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-4 rounded-2xl border border-slate-200/80 bg-white p-3 dark:border-slate-800 dark:bg-slate-950">
                    <label for="coupon_code" class="block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ __('Coupon Code') }}</label>
                    <div class="mt-2 flex gap-2">
                        <input
                            id="coupon_code"
                            type="text"
                            name="coupon_code"
                            value="{{ old('coupon_code', data_get($couponSummary ?? [], 'code', '')) }}"
                            class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm uppercase text-slate-900 outline-none focus:border-primary/20 focus:ring-4 focus:ring-primary/10 dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                            placeholder="{{ __('SAVE10') }}"
                        >
                        <button type="submit" name="coupon_action" value="apply" class="rounded-xl bg-slate-900 px-3 py-2 text-xs font-semibold text-white dark:bg-white dark:text-slate-900">
                            {{ __('Apply') }}
                        </button>
                    </div>
                    @error('coupon_code')
                        <p class="mt-2 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                    @enderror
                    @if (data_get($couponSummary ?? [], 'valid'))
                        <div class="mt-2 flex items-center justify-between rounded-xl bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
                            <span>{{ __('Applied:') }} {{ data_get($couponSummary, 'code') }}</span>
                            <button type="submit" name="coupon_action" value="remove" class="text-emerald-800 underline dark:text-emerald-200">{{ __('Remove') }}</button>
                        </div>
                    @endif
                </div>

                <div class="mt-5 flex flex-wrap gap-3">
                    <button
                        type="submit"
                        formaction="{{ route('checkout.buy-now.place', $product) }}"
                        class="inline-flex items-center justify-center rounded-2xl bg-primary px-5 py-3 text-sm font-semibold text-white transition duration-200 hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                    >
                        {{ __('Confirm & Place Order') }}
                    </button>
                    <a
                        href="{{ route('shop.show', $product) }}"
                        class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition duration-200 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                    >
                        {{ __('Back') }}
                    </a>
                </div>
            </section>
        </form>
    </div>
@endsection
