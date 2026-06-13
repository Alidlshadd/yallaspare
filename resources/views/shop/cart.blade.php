@extends('layouts.user')

@section('content')
    <div class="space-y-6 pb-16">
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

        @if ($items->isEmpty())
            <section class="rounded-3xl border border-slate-200/80 bg-white p-8 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10 sm:p-10">
                <div class="rounded-3xl border border-slate-200/80 bg-slate-50 px-6 py-10 text-center dark:border-slate-800 dark:bg-slate-950">
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ __('Cart') }}</p>
                    <h2 class="mt-2 text-2xl font-semibold tracking-[-0.03em] text-slate-950 dark:text-white">{{ __('Your cart is empty') }}</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">
                        {{ __('Add products from the catalog to start building your order.') }}
                    </p>

                    <div class="mt-6 flex items-center justify-center">
                        <a
                            href="{{ route('shop.index') }}"
                            class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition duration-200 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                        >
                            {{ __('Browse Products') }}
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M7.22 4.97a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 1 1-1.06-1.06L10.94 10 7.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    </div>
                </div>
            </section>
        @else
            <section class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
                <div class="space-y-4">
                    @foreach ($items as $item)
                        @php
                            $product = $item->product;
                            $pricing = $product ? $product->pricingFor(auth()->user()) : null;
                            $unitPrice = $pricing ? (float) $pricing['price'] : 0;
                            $baseUnitPrice = $pricing ? (float) $pricing['base_price'] : $unitPrice;
                            $unitHasDiscount = $pricing ? (bool) $pricing['has_discount'] : false;
                            $unitDiscountAmount = $pricing ? (float) $pricing['discount_amount'] : 0;
                            $unitDiscountPercent = $pricing ? (int) $pricing['discount_percent'] : 0;
                            $lineTotal = $unitPrice * $item->quantity;
                            $maxQuantity = $product ? min(99, max(1, (int) $product->stock_quantity)) : 1;
                        @endphp

                        <article class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm shadow-slate-900/5 transition duration-200 hover:-translate-y-0.5 hover:border-primary/20 hover:shadow-md hover:shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10 dark:hover:border-primary/30 dark:hover:shadow-black/20 sm:rounded-3xl sm:p-6">
                            <div class="flex min-w-0 flex-col gap-4 min-[480px]:flex-row min-[480px]:items-start sm:gap-6">
                                <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-950 sm:h-24 sm:w-24">
                                    <img
                                        src="{{ $product?->image ? asset('storage/' . ltrim((string) $product->image, '/')) : '/images/placeholder-product.png' }}"
                                        alt="{{ $product?->name ?? __('Product image') }}"
                                        class="h-full w-full object-contain"
                                    >
                                </div>

                                <div class="min-w-0 flex-1 space-y-4">
                                    <div class="flex flex-col gap-5">
                                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="inline-flex rounded-full border border-slate-200/80 bg-slate-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                                        {{ $product?->brand ?: __('Generic') }}
                                                    </span>
                                                    <span class="inline-flex rounded-full border border-slate-200/80 bg-slate-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                                        {{ $product?->sku ?: __('No SKU') }}
                                                    </span>
                                                </div>

                                                <h2 class="mt-3 text-xl font-semibold tracking-[-0.02em] text-slate-950 dark:text-white">
                                                    {{ $product?->name ?? __('Deleted Product') }}
                                                </h2>
                                            </div>

                                            <div class="w-full rounded-2xl border border-slate-200/80 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950 lg:w-auto">
                                                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Line Total') }}</p>
                                                <p class="mt-1 break-all text-lg font-semibold tracking-[-0.02em] text-slate-950 dark:text-white">
                                                    {{ number_format($lineTotal, 2) }} {{ $currencySymbol }}
                                                </p>
                                            </div>
                                        </div>

                                        <div class="rounded-3xl border border-slate-200/80 bg-slate-50 px-4 py-4 dark:border-slate-800 dark:bg-slate-950">
                                            <div class="grid grid-cols-1 gap-4 md:grid-cols-3 md:gap-0">
                                                <div class="md:px-2">
                                                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Unit Price') }}</p>
                                                    <div class="mt-1 flex flex-wrap items-center gap-2">
                                                        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ number_format($unitPrice, 2) }} {{ $currencySymbol }}</p>
                                                        @if($unitHasDiscount)
                                                            <p class="text-xs font-semibold text-slate-400 line-through dark:text-slate-500">{{ number_format($baseUnitPrice, 2) }}</p>
                                                            <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                                                                -{{ $unitDiscountPercent }}%
                                                            </span>
                                                        @endif
                                                    </div>
                                                    @if($unitHasDiscount)
                                                        <p class="mt-1 text-xs font-medium text-emerald-700 dark:text-emerald-300">{{ __('You save') }} {{ number_format($unitDiscountAmount * $item->quantity, 2) }} {{ $currencySymbol }}</p>
                                                    @endif
                                                </div>
                                                <div class="md:border-x md:border-slate-200/80 md:px-4 dark:md:border-slate-800">
                                                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Quantity') }}</p>
                                                    <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">{{ $item->quantity }}</p>
                                                    @if($product)
                                                        <p class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('Available') }}: {{ $maxQuantity }}</p>
                                                    @endif
                                                </div>
                                                <div class="md:px-2 md:text-right">
                                                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Status') }}</p>
                                                    <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">{{ $product ? __('Ready') : __('Unavailable') }}</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex flex-col gap-4 border-t border-slate-200/80 pt-5 dark:border-slate-800 lg:flex-row lg:items-end lg:justify-between">
                                            <form action="{{ route('cart.update', $item) }}" method="POST" class="w-full max-w-sm space-y-2">
                                                @csrf
                                                @method('PATCH')

                                                <label for="quantity-{{ $item->id }}" class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Quantity') }}</label>
                                                <div class="flex items-center">
                                                    <div class="inline-flex h-12 items-stretch overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-950 dark:shadow-black/10">
                                                        <button
                                                            type="button"
                                                            onclick="const input = document.getElementById('quantity-{{ $item->id }}'); input.stepDown(); input.form.requestSubmit();"
                                                            class="inline-flex h-12 w-11 items-center justify-center border-r border-slate-200/80 bg-primary text-base font-semibold text-white transition duration-200 hover:bg-primary-hover focus:outline-none focus-visible:z-10 focus-visible:ring-2 focus-visible:ring-primary dark:border-slate-800"
                                                            aria-label="{{ __('Decrease quantity') }}"
                                                        >
                                                            -
                                                        </button>
                                                        <input
                                                            id="quantity-{{ $item->id }}"
                                                            name="quantity"
                                                            type="number"
                                                            min="1"
                                                            max="{{ $maxQuantity }}"
                                                            value="{{ $item->quantity }}"
                                                            onchange="this.form.requestSubmit()"
                                                            class="block h-12 w-16 border-0 bg-white px-2 text-center text-sm font-semibold text-slate-900 outline-none [appearance:textfield] focus:ring-0 dark:bg-slate-950 dark:text-white [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                                                        >
                                                        <button
                                                            type="button"
                                                            onclick="const input = document.getElementById('quantity-{{ $item->id }}'); input.stepUp(); input.form.requestSubmit();"
                                                            @disabled($item->quantity >= $maxQuantity)
                                                            class="inline-flex h-12 w-11 items-center justify-center border-l border-slate-200/80 bg-primary text-base font-semibold text-white transition duration-200 hover:bg-primary-hover focus:outline-none focus-visible:z-10 focus-visible:ring-2 focus-visible:ring-primary disabled:cursor-not-allowed disabled:bg-slate-300 disabled:text-slate-500 dark:border-slate-800 dark:disabled:bg-slate-800 dark:disabled:text-slate-500"
                                                            aria-label="{{ __('Increase quantity') }}"
                                                        >
                                                            +
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>

                                            <form action="{{ route('cart.remove', $item) }}" method="POST" class="flex justify-start lg:justify-end">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="inline-flex items-center justify-center rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-sm font-medium text-rose-700 transition duration-200 hover:bg-rose-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-300 focus-visible:ring-offset-2 dark:border-rose-900/40 dark:bg-rose-900/20 dark:text-rose-300"
                                                >
                                                    {{ __('Remove') }}
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="space-y-6">
                    <section class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10 sm:p-6">
                        <div>
                            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ __('Summary') }}</p>
                            <h2 class="mt-2 text-2xl font-semibold tracking-[-0.03em] text-slate-950 dark:text-white">{{ __('Order Summary') }}</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ __('A clean breakdown before checkout.') }}</p>
                        </div>

                        <div class="mt-5 rounded-3xl border border-slate-200/80 bg-slate-50 px-4 py-2 dark:border-slate-800 dark:bg-slate-950">
                            <div class="flex items-center justify-between border-b border-slate-200/80 py-3 dark:border-slate-800">
                                <span class="text-sm font-medium text-slate-600 dark:text-slate-300">{{ __('Items') }}</span>
                                <span class="text-sm font-semibold text-slate-950 dark:text-white">{{ $items->sum('quantity') }}</span>
                            </div>
                            <div class="flex items-center justify-between border-b border-slate-200/80 py-3 dark:border-slate-800">
                                <span class="text-sm font-medium text-slate-600 dark:text-slate-300">{{ __('Product Lines') }}</span>
                                <span class="text-sm font-semibold text-slate-950 dark:text-white">{{ $items->count() }}</span>
                            </div>
                            <div class="flex items-center justify-between py-3">
                                <span class="text-sm font-medium text-slate-600 dark:text-slate-300">{{ __('Subtotal') }}</span>
                                <span class="text-sm font-semibold text-slate-950 dark:text-white">{{ number_format($subtotal, 2) }} {{ $currencySymbol }}</span>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10 sm:p-6">
                        <div>
                            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ __('Address Book') }}</p>
                            <h2 class="mt-2 text-2xl font-semibold tracking-[-0.03em] text-slate-950 dark:text-white">{{ __('Saved Delivery Address') }}</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ __('Choose a saved address, or add a new one if the customer wants delivery somewhere else.') }}</p>
                        </div>

                        <form action="{{ route('checkout.review') }}" method="POST" class="mt-5 space-y-4">
                            @csrf

                            @if ($addresses->isEmpty())
                                <div class="rounded-2xl border border-slate-200/80 bg-slate-50 px-4 py-4 dark:border-slate-800 dark:bg-slate-950">
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('No saved address found') }}</p>
                                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ __('Add an address in your account before placing an order.') }}</p>
                                    <div class="mt-4 flex justify-end">
                                        <a
                                            href="{{ route('user.account.addresses') }}"
                                            class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition duration-200 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                                        >
                                            {{ __('Manage Addresses') }}
                                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M7.22 4.97a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 1 1-1.06-1.06L10.94 10 7.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            @else
                                <div class="space-y-3">
                                    @foreach ($addresses as $address)
                                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200/80 bg-slate-50 px-4 py-4 transition duration-200 hover:border-primary/20 dark:border-slate-800 dark:bg-slate-950 dark:hover:border-primary/30">
                                            <input
                                                type="radio"
                                                name="address_id"
                                                value="{{ $address->id }}"
                                                @checked((int) old('address_id', $defaultAddress?->id) === (int) $address->id)
                                                class="mt-1 h-4 w-4 border-slate-300 text-primary focus:ring-primary/30 dark:border-slate-700 dark:bg-slate-900"
                                            >
                                            <span class="min-w-0 flex-1">
                                                <span class="flex flex-wrap items-center gap-2">
                                                    <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ $address->label ?: __('Saved Address') }}</span>
                                                    @if ($address->is_default)
                                                        <span class="inline-flex rounded-full border border-slate-200/80 bg-white px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">{{ __('Default') }}</span>
                                                    @endif
                                                </span>
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
                                    <p class="text-xs font-medium text-rose-600">{{ $message }}</p>
                                @enderror

                                <div class="rounded-2xl border border-slate-200/80 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950">
                                    <p class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Need a different address?') }}</p>
                                    <div class="mt-3 flex flex-wrap items-center justify-end gap-3">
                                        <a
                                            href="{{ route('account.addresses.create') }}"
                                            class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition duration-200 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                                        >
                                            {{ __('Add Another') }}
                                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path d="M10 4.25a.75.75 0 0 1 .75.75v4.25H15a.75.75 0 0 1 0 1.5h-4.25V15a.75.75 0 0 1-1.5 0v-4.25H5a.75.75 0 0 1 0-1.5h4.25V5a.75.75 0 0 1 .75-.75Z" />
                                            </svg>
                                        </a>
                                        <a
                                            href="{{ route('user.account.addresses') }}"
                                            class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition duration-200 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                                        >
                                            {{ __('Manage Addresses') }}
                                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M7.22 4.97a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 1 1-1.06-1.06L10.94 10 7.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                            </svg>
                                        </a>
                                    </div>
                                </div>

                            @endif

                            @php
                                $contactMethodLabels = [
                                    'phone' => __('Phone'),
                                    'email' => __('Email'),
                                    'whatsapp' => __('WhatsApp'),
                                ];
                                $contactMethodLabel = $contactMethodLabels[$defaultContactMethod] ?? __(ucfirst((string) $defaultContactMethod));
                            @endphp
                            <div class="rounded-2xl border border-slate-200/80 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950">
                                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Checkout Defaults') }}</p>
                                <p class="mt-1 text-sm font-medium text-slate-900 dark:text-white">{{ __('Preferred contact: :method', ['method' => $contactMethodLabel]) }}</p>
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                                    {{ $expressCheckout ? __('Express checkout is enabled. Your default address can be used automatically when available.') : __('Standard checkout is active. You can still review and edit details before ordering.') }}
                                </p>
                            </div>

                            <div>
                                <label for="notes" class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Notes (optional)') }}</label>
                                <textarea
                                    id="notes"
                                    name="notes"
                                    rows="4"
                                    class="block w-full rounded-2xl border border-slate-200/80 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition duration-200 focus:border-primary/20 focus:ring-4 focus:ring-primary/10 dark:border-slate-800 dark:bg-slate-950 dark:text-white"
                                >{{ old('notes', $defaultDeliveryNote) }}</textarea>
                            </div>

                            @if ($addresses->isNotEmpty())
                                <div class="rounded-2xl border border-slate-200/80 bg-slate-50 px-4 py-4 dark:border-slate-800 dark:bg-slate-950">
                                    <label for="coupon_code" class="block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Coupon Code') }}</label>
                                    <div class="mt-2 flex gap-2">
                                        <input
                                            id="coupon_code"
                                            name="coupon_code"
                                            value="{{ old('coupon_code', session('checkout.coupon_code', '')) }}"
                                            class="min-w-0 flex-1 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm uppercase text-slate-900 outline-none transition duration-200 focus:border-primary/20 focus:ring-4 focus:ring-primary/10 dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                                            placeholder="{{ __('SAVE10') }}"
                                        >
                                        <button
                                            type="submit"
                                            name="coupon_action"
                                            value="apply"
                                            class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition duration-200 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                                        >
                                            {{ __('Apply') }}
                                        </button>
                                    </div>
                                    @error('coupon_code')
                                        <p class="mt-2 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                                    @enderror
                                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ __('Coupon discounts are confirmed on the order review screen before payment.') }}</p>
                                </div>
                            @endif

                            @if ($addresses->isNotEmpty())
                                <button
                                    type="submit"
                                    class="inline-flex w-full items-center justify-center rounded-2xl bg-primary px-4 py-3 text-sm font-semibold text-white transition duration-200 hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                                >
                                    {{ $expressCheckout ? __('Review Order Faster') : __('Review Order') }}
                                </button>
                            @endif
                        </form>
                    </section>
                </div>
            </section>
        @endif
    </div>
@endsection
