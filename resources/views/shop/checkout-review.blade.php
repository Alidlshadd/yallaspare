@extends('layouts.user')

@section('content')
    <div class="mx-auto w-full max-w-5xl space-y-6 py-4">
        @if (session('success'))
            <x-ui.alert variant="success" :title="__('Success')">
                {{ session('success') }}
            </x-ui.alert>
        @endif

        @if (session('error'))
            <x-ui.alert variant="danger" :title="__('Please review')">
                {{ session('error') }}
            </x-ui.alert>
        @endif

        @include('shop.partials.checkout-steps', ['current' => 3])

        <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm shadow-slate-900/5 dark:bg-slate-900 dark:shadow-black/10 sm:p-8">
            <p class="text-sm font-medium text-slate-500">{{ __('Final Check') }}</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-[-0.03em] text-slate-950">{{ __('Review your order details') }}</h1>
            <p class="mt-3 text-sm leading-6 text-slate-600">
                {{ __('Please confirm address, phone, products, and totals. If everything is correct, place the order from the button below.') }}
            </p>
        </section>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
            <section class="space-y-4">
                <article class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-900/5 dark:bg-slate-900 dark:shadow-black/10">
                    <h2 class="text-lg font-semibold text-slate-900">{{ __('Delivery') }}</h2>
                    <div class="mt-3 rounded-2xl border border-slate-200/80 bg-slate-50 px-4 py-4 text-sm leading-6 text-slate-700">
                        <p class="font-semibold text-slate-900">{{ $address->label ?: __('Saved Address') }}</p>
                        <p class="mt-1">{{ $address->address_line1 }}</p>
                        @if ($address->address_line2)
                            <p>{{ $address->address_line2 }}</p>
                        @endif
                        <p>{{ $address->city }}, {{ $address->country }}</p>
                        <p class="mt-1">{{ __('Phone:') }} {{ $address->phone }}</p>
                    </div>
                    @if (trim($notes) !== '')
                        <div class="mt-3 rounded-2xl border border-slate-200/80 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('Notes') }}</p>
                            <p class="mt-1">{{ $notes }}</p>
                        </div>
                    @endif
                </article>

                <article class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-900/5 dark:bg-slate-900 dark:shadow-black/10">
                    <h2 class="text-lg font-semibold text-slate-900">{{ __('Products') }}</h2>
                    <div class="mt-4 space-y-3">
                        @foreach ($items as $item)
                            @php
                                $product = $item->product;
                                $pricing = $product ? $product->pricingFor(auth()->user()) : null;
                                $unitPrice = $pricing ? (float) $pricing['price'] : 0;
                                $baseUnitPrice = $pricing ? (float) $pricing['base_price'] : $unitPrice;
                                $unitHasDiscount = $pricing ? (bool) $pricing['has_discount'] : false;
                                $unitDiscountPercent = $pricing ? (int) $pricing['discount_percent'] : 0;
                            @endphp
                            <div class="flex items-center justify-between rounded-2xl border border-slate-200/80 bg-slate-50 px-4 py-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $product?->name ?? __('Deleted Product') }}</p>
                                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                        <span>{{ __('Qty:') }} {{ $item->quantity }}</span>
                                        @if($unitHasDiscount)
                                            <span class="font-semibold text-muted line-through dark:text-slate-500">{{ number_format($baseUnitPrice, 0) }}</span>
                                            <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">-{{ $unitDiscountPercent }}%</span>
                                        @endif
                                    </div>
                                </div>
                                <p class="text-sm font-semibold text-slate-900">
                                    {{ number_format($unitPrice * $item->quantity, 0) }} {{ $currencySymbol }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </article>
            </section>

            <aside class="space-y-4">
                <section class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-900/5 dark:bg-slate-900 dark:shadow-black/10">
                    <h2 class="text-lg font-semibold text-slate-900">{{ __('Payment Summary') }}</h2>
                    <div class="mt-4 rounded-2xl border border-slate-200/80 bg-slate-50 px-4 py-2">
                        <div class="flex items-center justify-between border-b border-slate-200/80 py-3">
                            <span class="text-sm text-slate-600">{{ __('Subtotal') }}</span>
                            <span class="text-sm font-semibold text-slate-900">{{ number_format($subtotal, 0) }} {{ $currencySymbol }}</span>
                        </div>
                        <div class="flex items-center justify-between border-b border-slate-200/80 py-3">
                            <span class="text-sm text-slate-600">{{ __('Shipping Fee') }}</span>
                            <span class="text-sm font-semibold text-slate-900">{{ number_format($shippingFee, 0) }} {{ $currencySymbol }}</span>
                        </div>
                        @if (($discountAmount ?? 0) > 0)
                            <div class="flex items-center justify-between border-b border-slate-200/80 py-3">
                                <span class="text-sm text-slate-600">{{ __('Discount') }}</span>
                                <span class="text-sm font-semibold text-accent-ink dark:text-accent">-{{ number_format($discountAmount, 0) }} {{ $currencySymbol }}</span>
                            </div>
                        @endif
                        <div class="flex items-center justify-between py-3">
                            <span class="text-sm font-semibold text-slate-900">{{ __('Total') }}</span>
                            <span class="text-sm font-semibold text-slate-900">{{ number_format($grandTotal, 0) }} {{ $currencySymbol }}</span>
                        </div>
                    </div>

                    <form action="{{ route('checkout.review') }}" method="POST" class="mt-4 rounded-2xl border border-slate-200/80 bg-white p-3 dark:bg-slate-950">
                        @csrf
                        <input type="hidden" name="address_id" value="{{ $address->id }}">
                        <input type="hidden" name="notes" value="{{ $notes }}">
                        <label class="block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('Coupon Code') }}</label>
                        <div class="mt-2 flex gap-2">
                            <input
                                type="text"
                                name="coupon_code"
                                value="{{ old('coupon_code', data_get($couponSummary ?? [], 'code', '')) }}"
                                class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm uppercase text-slate-900 outline-none focus:border-primary/20 focus:ring-4 focus:ring-accent/10"
                                placeholder="{{ __('SAVE10') }}"
                            >
                            <button type="submit" name="coupon_action" value="apply" class="rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white dark:bg-white dark:text-slate-900">
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
                    </form>

                    <form
                        action="{{ route('checkout.store') }}"
                        method="POST"
                        class="mt-4 space-y-3"
                        data-loading-form
                        data-loading-kind="checkout"
                        data-loading-message="Placing your order securely..."
                        data-loading-button-text="Placing order..."
                    >
                        @csrf
                        <input type="hidden" name="address_id" value="{{ $address->id }}">
                        <input type="hidden" name="notes" value="{{ $notes }}">
                        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 dark:border-slate-800 dark:bg-slate-950">
                            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('Payment Method') }}</p>
                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                @foreach($paymentMethods as $method)
                                    @php
                                        $isDisabled = ! ($method['enabled'] ?? false);
                                        $isCod = ($method['key'] ?? null) === 'cash_on_delivery';
                                    @endphp
                                    <label class="relative flex min-h-28 items-start gap-3 overflow-hidden rounded-2xl border p-4 text-sm transition {{ $isDisabled ? 'cursor-not-allowed border-amber-200 bg-gradient-to-br from-amber-50 via-white to-orange-50/70 shadow-sm dark:border-amber-500/25 dark:from-amber-500/10 dark:via-slate-900 dark:to-orange-500/5' : 'cursor-pointer border-primary/35 bg-gradient-to-br from-primary/5 via-white to-accent/10 shadow-sm ring-1 ring-primary/10 hover:border-primary/60 hover:shadow-md dark:border-info/30 dark:from-info/10 dark:via-slate-900 dark:to-info/5' }}">
                                        <input
                                            type="radio"
                                            name="payment_method"
                                            value="{{ $method['key'] }}"
                                            @checked(old('payment_method', 'cash_on_delivery') === $method['key'])
                                            @disabled($isDisabled)
                                            class="mt-1 h-4 w-4 shrink-0 border-slate-300 text-primary focus:ring-accent/30 disabled:border-amber-300 disabled:bg-amber-50 dark:border-slate-700 dark:bg-slate-900 dark:disabled:border-amber-500/40 dark:disabled:bg-slate-800"
                                        >
                                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $isDisabled ? 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300' : 'bg-primary/10 text-primary dark:bg-info/10 dark:text-info' }}">
                                            <i class="fas {{ $isCod ? 'fa-money-bill-wave' : 'fa-credit-card' }}" aria-hidden="true"></i>
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="flex flex-wrap items-center gap-2">
                                                <span class="font-bold text-slate-900 dark:text-white">{{ __($method['label']) }}</span>
                                                @if($method['coming_soon'] ?? false)
                                                    <span class="rounded-full border border-amber-200 bg-amber-100/80 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/15 dark:text-amber-200">{{ __('Coming Soon') }}</span>
                                                @endif
                                            </span>
                                            <span class="mt-1.5 block text-xs leading-5 {{ $isDisabled ? 'text-amber-800/80 dark:text-amber-200/75' : 'text-slate-500 dark:text-slate-400' }}">{{ __($method['description']) }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            @error('payment_method')
                                <p class="mt-2 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center rounded-2xl bg-primary px-4 py-3 text-sm font-semibold text-white transition duration-200 hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2"
                        >
                            {{ __('Confirm & Place Order') }}
                        </button>
                        <a
                            href="{{ route('cart.index') }}"
                            class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition duration-200 hover:bg-slate-50 dark:bg-slate-900 dark:hover:bg-slate-800"
                        >
                            {{ __('Back to Cart') }}
                        </a>
                    </form>
                </section>
            </aside>
        </div>
    </div>
@endsection
