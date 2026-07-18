@extends('layouts.user')

@section('content')
    <div class="space-y-5 pb-16">
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
                            <svg class="h-4 w-4 rtl:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M7.22 4.97a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 1 1-1.06-1.06L10.94 10 7.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    </div>
                </div>
            </section>
        @else
            @include('shop.partials.checkout-steps', ['current' => 1])

            @php $totalSavings = 0.0; @endphp

            <section class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1fr)_20rem] xl:items-start">
                <div class="rounded-2xl border border-slate-200/80 bg-white px-4 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10 sm:rounded-3xl sm:px-6">
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
                            $totalSavings += $unitDiscountAmount * $item->quantity;
                        @endphp

                        <article
                            class="cart-row-in flex flex-wrap items-center gap-3 border-b border-slate-100 py-4 last:border-b-0 dark:border-slate-800 sm:gap-4"
                            style="animation-delay: {{ min($loop->index * 70, 420) }}ms"
                        >
                            <a href="{{ $product ? route('shop.show', $product) : '#' }}" class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-slate-50 transition duration-200 hover:border-primary/30 dark:border-slate-700 dark:bg-slate-950 sm:h-[4.5rem] sm:w-[4.5rem]">
                                <img
                                    src="{{ $product?->image ? asset('storage/' . ltrim((string) $product->image, '/')) : '/images/placeholder-product.png' }}"
                                    alt="{{ $product?->name ?? __('Product image') }}"
                                    class="h-full w-full object-contain"
                                >
                            </a>

                            <div class="min-w-0 flex-1 basis-52">
                                <h2 class="truncate text-sm font-semibold tracking-[-0.01em] text-slate-950 dark:text-white sm:text-[0.95rem]">
                                    {{ $product?->name ?? __('Deleted Product') }}
                                </h2>
                                <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                    <span class="inline-flex rounded-full border border-slate-200/80 bg-slate-100 px-2 py-0.5 text-[9.5px] font-semibold uppercase tracking-[0.1em] text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400">
                                        {{ $product?->sku ?: __('No SKU') }}
                                    </span>
                                    <span class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ number_format($unitPrice, 2) }} {{ $currencySymbol }}
                                        @if ($unitHasDiscount)
                                            <s class="text-slate-400 dark:text-slate-600">{{ number_format($baseUnitPrice, 2) }}</s>
                                        @endif
                                    </span>
                                    @if ($unitHasDiscount)
                                        <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[9.5px] font-bold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                                            -{{ $unitDiscountPercent }}%
                                        </span>
                                    @endif
                                </div>
                                @if ($product)
                                    <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">{{ __('Available') }}: {{ $maxQuantity }}</p>
                                @endif
                            </div>

                            <form action="{{ route('cart.update', $item) }}" method="POST" class="shrink-0">
                                @csrf
                                @method('PATCH')
                                <label for="quantity-{{ $item->id }}" class="sr-only">{{ __('Quantity') }}</label>
                                <div class="inline-flex h-10 items-stretch overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm shadow-slate-900/5 dark:border-slate-700 dark:bg-slate-950 dark:shadow-black/10">
                                    <button
                                        type="button"
                                        data-quantity-step="down"
                                        data-quantity-target="quantity-{{ $item->id }}"
                                        class="inline-flex w-9 items-center justify-center text-slate-500 transition duration-150 hover:bg-slate-50 hover:text-primary active:scale-90 focus:outline-none focus-visible:z-10 focus-visible:ring-2 focus-visible:ring-primary dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white"
                                        aria-label="{{ __('Decrease quantity') }}"
                                    >
                                        &minus;
                                    </button>
                                    <input
                                        id="quantity-{{ $item->id }}"
                                        name="quantity"
                                        type="number"
                                        min="1"
                                        max="{{ $maxQuantity }}"
                                        value="{{ $item->quantity }}"
                                        data-submit-on-change
                                        class="block w-12 border-0 bg-white px-1 text-center text-sm font-semibold text-slate-900 outline-none [appearance:textfield] focus:ring-0 dark:bg-slate-950 dark:text-white [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                                    >
                                    <button
                                        type="button"
                                        data-quantity-step="up"
                                        data-quantity-target="quantity-{{ $item->id }}"
                                        @disabled($item->quantity >= $maxQuantity)
                                        class="inline-flex w-9 items-center justify-center text-slate-500 transition duration-150 hover:bg-slate-50 hover:text-primary active:scale-90 focus:outline-none focus-visible:z-10 focus-visible:ring-2 focus-visible:ring-primary disabled:cursor-not-allowed disabled:text-slate-300 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white dark:disabled:text-slate-600"
                                        aria-label="{{ __('Increase quantity') }}"
                                    >
                                        +
                                    </button>
                                </div>
                            </form>

                            <div class="w-28 shrink-0 text-end">
                                <p class="break-all text-[0.95rem] font-bold tracking-[-0.02em] text-primary dark:text-white">
                                    {{ number_format($lineTotal, 2) }}
                                    <span class="text-[10px] font-semibold uppercase tracking-[0.08em] text-amber-600 dark:text-amber-300">{{ $currencySymbol }}</span>
                                </p>
                            </div>

                            <form action="{{ route('cart.remove', $item) }}" method="POST" class="shrink-0">
                                @csrf
                                @method('DELETE')
                                <button
                                    type="submit"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-rose-200 bg-rose-50 text-rose-600 transition duration-200 hover:-translate-y-0.5 hover:bg-rose-100 hover:shadow-md hover:shadow-rose-900/10 active:translate-y-0 active:scale-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-300 dark:border-rose-900/40 dark:bg-rose-900/20 dark:text-rose-300"
                                    aria-label="{{ __('Remove') }}"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M9.5 7V5a1 1 0 0 1 1-1h3a1 1 0 0 1 1 1v2m-8.5 0 .9 12.1a1 1 0 0 0 1 .9h6.2a1 1 0 0 0 1-.9L16.5 7M10 11v5m4-5v5" />
                                    </svg>
                                </button>
                            </form>
                        </article>
                    @endforeach
                </div>

                <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10 sm:rounded-3xl xl:sticky xl:top-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Order Summary') }}</p>

                    <dl class="mt-3">
                        <div class="flex items-center justify-between border-b border-dashed border-slate-200/80 py-2.5 dark:border-slate-800">
                            <dt class="text-sm text-slate-600 dark:text-slate-300">{{ __('Items') }}</dt>
                            <dd class="text-sm font-semibold text-slate-950 dark:text-white">{{ $items->sum('quantity') }}</dd>
                        </div>
                        <div class="flex items-center justify-between border-b border-dashed border-slate-200/80 py-2.5 dark:border-slate-800">
                            <dt class="text-sm text-slate-600 dark:text-slate-300">{{ __('Product Lines') }}</dt>
                            <dd class="text-sm font-semibold text-slate-950 dark:text-white">{{ $items->count() }}</dd>
                        </div>
                        @if ($totalSavings > 0)
                            <div class="flex items-center justify-between border-b border-dashed border-slate-200/80 py-2.5 dark:border-slate-800">
                                <dt class="text-sm text-slate-600 dark:text-slate-300">{{ __('You save') }}</dt>
                                <dd class="text-sm font-bold text-emerald-600 dark:text-emerald-300">-{{ number_format($totalSavings, 2) }} {{ $currencySymbol }}</dd>
                            </div>
                        @endif
                        <div class="flex items-end justify-between pt-3">
                            <dt class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ __('Subtotal') }}</dt>
                            <dd class="break-all text-xl font-bold tracking-[-0.02em] text-primary dark:text-white">
                                {{ number_format($subtotal, 2) }}
                                <span class="text-[11px] font-semibold uppercase tracking-[0.08em] text-amber-600 dark:text-amber-300">{{ $currencySymbol }}</span>
                            </dd>
                        </div>
                    </dl>

                    <p class="mt-4 border-t border-slate-100 pt-3 text-xs leading-5 text-slate-400 dark:border-slate-800 dark:text-slate-500">
                        {{ __('Address, notes and coupon are on the next step.') }}
                    </p>
                </section>
            </section>

            <div class="sticky bottom-3 z-30 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-primary px-4 py-3 text-white shadow-xl shadow-primary/25 dark:shadow-black/40 sm:px-6">
                <p class="text-xs font-medium text-white/70 sm:text-sm">
                    {{ __('Step :current of :total', ['current' => 1, 'total' => 3]) }} · {{ $items->sum('quantity') }} {{ __('Items') }}
                </p>
                <div class="flex items-center gap-4">
                    <p class="break-all text-lg font-bold tracking-[-0.02em]">
                        {{ number_format($subtotal, 2) }}
                        <span class="text-[11px] font-semibold uppercase tracking-[0.08em] text-amber-300">{{ $currencySymbol }}</span>
                    </p>
                    <a
                        href="{{ route('checkout.delivery') }}"
                        class="group inline-flex items-center gap-2 rounded-xl bg-amber-400 px-5 py-2.5 text-sm font-bold text-slate-950 transition duration-200 hover:-translate-y-0.5 hover:bg-amber-300 hover:shadow-lg hover:shadow-black/20 active:translate-y-0 active:scale-[0.98] focus:outline-none focus-visible:ring-2 focus-visible:ring-white/60"
                    >
                        {{ __('Continue to Delivery') }}
                        <svg class="h-4 w-4 transition-transform duration-200 group-hover:translate-x-0.5 rtl:rotate-180 rtl:group-hover:-translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 12h16m-6-6 6 6-6 6" />
                        </svg>
                    </a>
                </div>
            </div>
        @endif
    </div>
@endsection
