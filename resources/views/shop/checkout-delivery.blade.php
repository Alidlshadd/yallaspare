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

        @include('shop.partials.checkout-steps', ['current' => 2])

        <form action="{{ route('checkout.review') }}" method="POST" class="space-y-5">
            @csrf

            <section class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1fr)_20rem] xl:items-start">
                <div class="space-y-4">
                    <section class="cart-row-in rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10 sm:rounded-3xl sm:p-6">
                        <div class="flex items-center gap-2.5">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-primary/5 text-primary dark:bg-slate-800 dark:text-slate-200">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7-5.1-7-11a7 7 0 1 1 14 0c0 5.9-7 11-7 11Z" />
                                    <circle cx="12" cy="10" r="2.5" />
                                </svg>
                            </span>
                            <h2 class="text-lg font-semibold tracking-[-0.02em] text-slate-950 dark:text-white">{{ __('Saved Delivery Address') }}</h2>
                        </div>

                        @if ($addresses->isEmpty())
                            <div class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-4 dark:border-slate-700 dark:bg-slate-950">
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('No saved address found') }}</p>
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ __('Add an address in your account before placing an order.') }}</p>
                                <div class="mt-4 flex justify-end">
                                    <a
                                        href="{{ route('user.account.addresses') }}"
                                        class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition duration-200 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                                    >
                                        {{ __('Manage Addresses') }}
                                    </a>
                                </div>
                            </div>
                        @else
                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                @foreach ($addresses as $address)
                                    <label class="group flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200/80 bg-slate-50 px-4 py-4 transition duration-200 hover:-translate-y-0.5 hover:border-primary/30 hover:shadow-md hover:shadow-slate-900/5 has-[:checked]:border-primary has-[:checked]:bg-primary/[0.04] has-[:checked]:shadow-md has-[:checked]:shadow-primary/10 dark:border-slate-800 dark:bg-slate-950 dark:hover:border-primary/40 dark:has-[:checked]:border-slate-400 dark:has-[:checked]:bg-slate-800/60">
                                        <input
                                            type="radio"
                                            name="address_id"
                                            value="{{ $address->id }}"
                                            @checked((int) old('address_id', $defaultAddress?->id) === (int) $address->id)
                                            class="mt-1 h-4 w-4 border-slate-300 text-primary transition focus:ring-primary/30 dark:border-slate-700 dark:bg-slate-900"
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
                                <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
                            @enderror

                            <div class="mt-4 flex flex-wrap items-center gap-2">
                                <a
                                    href="{{ route('account.addresses.create') }}"
                                    class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition duration-200 hover:-translate-y-0.5 hover:bg-slate-50 hover:shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 active:translate-y-0 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                                >
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                                    </svg>
                                    {{ __('Add Another') }}
                                </a>
                                <a
                                    href="{{ route('user.account.addresses') }}"
                                    class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition duration-200 hover:-translate-y-0.5 hover:bg-slate-50 hover:shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 active:translate-y-0 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                                >
                                    {{ __('Manage Addresses') }}
                                </a>
                            </div>
                        @endif
                    </section>

                    <section class="cart-row-in rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10 sm:rounded-3xl sm:p-6" style="animation-delay: 90ms">
                        <div class="flex items-center gap-2.5">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-primary/5 text-primary dark:bg-slate-800 dark:text-slate-200">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 4.5 19.5 7.5 8 19H5v-3L16.5 4.5Z" />
                                </svg>
                            </span>
                            <h2 class="text-lg font-semibold tracking-[-0.02em] text-slate-950 dark:text-white">{{ __('Notes (optional)') }}</h2>
                        </div>
                        <textarea
                            id="notes"
                            name="notes"
                            rows="3"
                            class="mt-4 block w-full rounded-2xl border border-slate-200/80 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition duration-200 focus:border-primary/20 focus:bg-white focus:ring-4 focus:ring-primary/10 dark:border-slate-800 dark:bg-slate-950 dark:text-white dark:focus:bg-slate-900"
                        >{{ old('notes', $defaultDeliveryNote) }}</textarea>
                    </section>

                    <section class="cart-row-in rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10 sm:rounded-3xl sm:p-6" style="animation-delay: 180ms">
                        <div class="flex items-center gap-2.5">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-primary/5 text-primary dark:bg-slate-800 dark:text-slate-200">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 9V7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v2a2.5 2.5 0 0 0 0 6v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2a2.5 2.5 0 0 0 0-6Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 6.5v2m0 7v2" stroke-dasharray="0.5 3" />
                                </svg>
                            </span>
                            <h2 class="text-lg font-semibold tracking-[-0.02em] text-slate-950 dark:text-white">{{ __('Coupon Code') }}</h2>
                        </div>
                        <div class="mt-4 flex gap-2">
                            <input
                                id="coupon_code"
                                name="coupon_code"
                                value="{{ old('coupon_code', session('checkout.coupon_code', '')) }}"
                                class="min-w-0 flex-1 rounded-2xl border border-slate-200/80 bg-slate-50 px-4 py-3 text-sm uppercase text-slate-900 outline-none transition duration-200 focus:border-primary/20 focus:bg-white focus:ring-4 focus:ring-primary/10 dark:border-slate-800 dark:bg-slate-950 dark:text-white dark:focus:bg-slate-900"
                                placeholder="{{ __('SAVE10') }}"
                            >
                            <button
                                type="submit"
                                name="coupon_action"
                                value="apply"
                                class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition duration-200 hover:-translate-y-0.5 hover:bg-slate-50 hover:shadow-md hover:shadow-slate-900/10 active:translate-y-0 active:scale-[0.98] dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                            >
                                {{ __('Apply') }}
                            </button>
                        </div>
                        @error('coupon_code')
                            <p class="mt-2 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                        @enderror
                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ __('Coupon discounts are confirmed on the order review screen before payment.') }}</p>
                    </section>
                </div>

                <section class="cart-row-in rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10 sm:rounded-3xl xl:sticky xl:top-4" style="animation-delay: 140ms">
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
                        <div class="flex items-end justify-between pt-3">
                            <dt class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ __('Subtotal') }}</dt>
                            <dd class="break-all text-xl font-bold tracking-[-0.02em] text-primary dark:text-white">
                                {{ number_format($subtotal, 2) }}
                                <span class="text-[11px] font-semibold uppercase tracking-[0.08em] text-amber-600 dark:text-amber-300">{{ $currencySymbol }}</span>
                            </dd>
                        </div>
                    </dl>

                    @php
                        $contactMethodLabels = [
                            'phone' => __('Phone'),
                            'email' => __('Email'),
                            'whatsapp' => __('WhatsApp'),
                        ];
                        $contactMethodLabel = $contactMethodLabels[$defaultContactMethod] ?? __(ucfirst((string) $defaultContactMethod));
                    @endphp
                    <div class="mt-4 space-y-1.5 border-t border-slate-100 pt-3 text-xs leading-5 text-slate-500 dark:border-slate-800 dark:text-slate-400">
                        <p>{{ __('Preferred contact: :method', ['method' => $contactMethodLabel]) }}</p>
                        <p>{{ $expressCheckout ? __('Express checkout is enabled. Your default address can be used automatically when available.') : __('Standard checkout is active. You can still review and edit details before ordering.') }}</p>
                    </div>
                </section>
            </section>

            <div class="sticky bottom-3 z-30 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-primary px-4 py-3 text-white shadow-xl shadow-primary/25 dark:shadow-black/40 sm:px-6">
                <div class="flex items-center gap-3">
                    <a
                        href="{{ route('cart.index') }}"
                        class="group inline-flex items-center gap-1.5 rounded-xl border border-white/25 px-3.5 py-2 text-sm font-semibold text-white transition duration-200 hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/60"
                    >
                        <svg class="h-4 w-4 transition-transform duration-200 group-hover:-translate-x-0.5 rtl:rotate-180 rtl:group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4m6-6-6 6 6 6" />
                        </svg>
                        {{ __('Back to Cart') }}
                    </a>
                    <p class="hidden text-xs font-medium text-white/70 sm:block">{{ __('Step :current of :total', ['current' => 2, 'total' => 3]) }}</p>
                </div>
                <div class="flex items-center gap-4">
                    <p class="break-all text-lg font-bold tracking-[-0.02em]">
                        {{ number_format($subtotal, 2) }}
                        <span class="text-[11px] font-semibold uppercase tracking-[0.08em] text-amber-300">{{ $currencySymbol }}</span>
                    </p>
                    <button
                        type="submit"
                        class="group inline-flex items-center gap-2 rounded-xl bg-amber-400 px-5 py-2.5 text-sm font-bold text-slate-950 transition duration-200 hover:-translate-y-0.5 hover:bg-amber-300 hover:shadow-lg hover:shadow-black/20 active:translate-y-0 active:scale-[0.98] focus:outline-none focus-visible:ring-2 focus-visible:ring-white/60"
                    >
                        {{ __('Continue to Review') }}
                        <svg class="h-4 w-4 transition-transform duration-200 group-hover:translate-x-0.5 rtl:rotate-180 rtl:group-hover:-translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 12h16m-6-6 6 6-6 6" />
                        </svg>
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
