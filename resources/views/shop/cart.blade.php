@extends('layouts.store')

@section('title', ($systemSettings['site_name'] ?? 'YallaSpare') . ' | Cart')

@section('content')
    <section class="store-rise mb-6 flex items-end justify-between gap-4">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.14em] text-emerald-700">Checkout</p>
            <h1 class="store-title mt-2 text-3xl font-bold text-slate-900 sm:text-4xl">Your Cart</h1>
        </div>
        <a href="{{ route('shop.index') }}" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-500 hover:text-slate-900">
            Continue shopping
        </a>
    </section>

    @if ($items->isEmpty())
        <section class="store-rise rounded-3xl border border-slate-200 bg-white p-10 text-center shadow-sm">
            <h2 class="store-title text-2xl font-bold text-slate-900">Your cart is empty</h2>
            <p class="mt-2 text-sm text-slate-600">Add products from the shop page to place an order.</p>
            <a href="{{ route('shop.index') }}" class="mt-5 inline-flex rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                Browse products
            </a>
        </section>
    @else
        <section class="grid gap-6 lg:grid-cols-[1fr_340px]">
            <div class="space-y-4">
                @foreach ($items as $item)
                    @php
                        $product = $item->product;
                        $unitPrice = $product ? $product->priceFor(auth()->user()) : 0;
                        $lineTotal = $unitPrice * $item->quantity;
                    @endphp
                    <article class="store-rise rounded-3xl border border-slate-200 bg-white p-5 shadow-sm" style="animation-delay: {{ 120 + (($loop->index % 5) * 70) }}ms;">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ $product?->brand ?: 'Generic' }}</p>
                                <h3 class="store-title mt-1 text-xl font-bold text-slate-900">{{ $product?->name_en ?? 'Deleted Product' }}</h3>
                                <p class="mt-1 text-sm text-slate-500">{{ $product?->sku ?: 'No SKU' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-slate-500">Line total</p>
                                <p class="store-title text-2xl font-bold text-emerald-700">
                                    {{ number_format($lineTotal, 2) }} {{ $currencySymbol }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <form action="{{ route('cart.update', $item) }}" method="POST" class="flex items-center gap-2">
                                @csrf
                                @method('PATCH')
                                <label for="quantity-{{ $item->id }}" class="text-sm font-semibold text-slate-600">Qty</label>
                                <input
                                    id="quantity-{{ $item->id }}"
                                    name="quantity"
                                    type="number"
                                    min="1"
                                    max="99"
                                    value="{{ $item->quantity }}"
                                    class="w-20 rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-800 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100"
                                >
                                <button type="submit" class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-500 hover:text-slate-900">
                                    Update
                                </button>
                            </form>

                            <form action="{{ route('cart.remove', $item) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-700 transition hover:border-rose-300 hover:bg-rose-100">
                                    Remove
                                </button>
                            </form>
                        </div>
                    </article>
                @endforeach
            </div>

            <aside class="store-rise rounded-3xl border border-slate-200 bg-white p-5 shadow-sm" style="animation-delay: 140ms;">
                <h2 class="store-title text-2xl font-bold text-slate-900">Delivery Details</h2>
                <p class="mt-1 text-sm text-slate-600">Fill in your information to complete the order.</p>

                <div class="mt-4 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-emerald-700">Order subtotal</p>
                    <p class="store-title mt-1 text-3xl font-bold text-emerald-800">
                        {{ number_format($subtotal, 2) }} {{ $currencySymbol }}
                    </p>
                </div>

                <form action="{{ route('checkout.store') }}" method="POST" class="mt-5 space-y-3">
                    @csrf

                    <div>
                        <label for="delivery_address" class="mb-1 block text-sm font-semibold text-slate-700">Address</label>
                        <input id="delivery_address" name="delivery_address" value="{{ old('delivery_address') }}" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                        @error('delivery_address')
                            <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="delivery_city" class="mb-1 block text-sm font-semibold text-slate-700">City</label>
                        <input id="delivery_city" name="delivery_city" value="{{ old('delivery_city') }}" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                        @error('delivery_city')
                            <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="delivery_phone" class="mb-1 block text-sm font-semibold text-slate-700">Phone</label>
                        <input id="delivery_phone" name="delivery_phone" value="{{ old('delivery_phone') }}" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                        @error('delivery_phone')
                            <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="payment_method" class="mb-1 block text-sm font-semibold text-slate-700">Payment Method</label>
                        <select id="payment_method" name="payment_method" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                            <option value="cash_on_delivery" @selected(old('payment_method') === 'cash_on_delivery')>Cash on delivery</option>
                            <option value="bank_transfer" @selected(old('payment_method') === 'bank_transfer')>Bank transfer</option>
                        </select>
                    </div>

                    <div>
                        <label for="notes" class="mb-1 block text-sm font-semibold text-slate-700">Notes (optional)</label>
                        <textarea id="notes" name="notes" rows="3" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">{{ old('notes') }}</textarea>
                    </div>

                    <button type="submit" class="mt-2 w-full rounded-xl bg-[var(--store-accent)] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[var(--store-accent-dark)]">
                        Place Order
                    </button>
                </form>
            </aside>
        </section>
    @endif
@endsection
