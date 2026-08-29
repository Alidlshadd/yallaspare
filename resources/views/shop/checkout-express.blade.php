@extends('layouts.user')

@section('title', __('Delivery Details'))

@section('checkout_back', route('cart.index'))
@section('checkout_back_label', __('Back to Cart'))

@section('content')
    <div class="space-y-5 pb-16">
        @if (session('error'))
            <x-ui.alert variant="danger" :title="__('Please review')">
                {{ session('error') }}
            </x-ui.alert>
        @endif

        @include('shop.partials.checkout-steps', [
            'current' => 2,
            'steps' => [
                1 => ['label' => __('Cart'), 'icon' => 'cart'],
                2 => ['label' => __('Delivery'), 'icon' => 'truck'],
                3 => ['label' => __('Confirm'), 'icon' => 'check'],
            ],
        ])

        <form action="{{ route('checkout.express.store') }}" method="POST" class="space-y-5">
            @csrf

            <section class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1fr)_20rem] xl:items-start">
                <div class="space-y-4">
                    <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-900/5 dark:bg-slate-900 dark:shadow-black/10 sm:rounded-3xl sm:p-6">
                        <div class="flex items-center gap-2.5">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-primary/5 text-primary dark:bg-slate-800 dark:text-slate-200">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 19a4 4 0 0 0-8 0M12 11a3.2 3.2 0 1 0 0-6.4 3.2 3.2 0 0 0 0 6.4Z" />
                                </svg>
                            </span>
                            <h2 class="text-lg font-semibold tracking-[-0.02em] text-slate-950">{{ __('Who is this order for?') }}</h2>
                        </div>

                        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-400">
                            {{ __('No sign-up needed. We send a 6-digit code to your phone to confirm the order, and your account is created along the way.') }}
                        </p>

                        <div class="mt-5 space-y-5">
                            <div>
                                <label for="name" class="block text-sm font-medium text-slate-700">{{ __('Full Name') }}</label>
                                <input
                                    id="name"
                                    name="name"
                                    type="text"
                                    value="{{ old('name') }}"
                                    autocomplete="name"
                                    required
                                    class="mt-2 block w-full rounded-xl border bg-white px-4 py-3 text-sm text-slate-900 placeholder-muted focus:border-transparent focus:outline-none focus-visible:ring-2 focus-visible:ring-accent {{ $errors->has('name') ? 'border-rose-300' : 'border-slate-200' }}"
                                >
                                @error('name')
                                    <p class="mt-2 text-sm text-rose-600 dark:text-rose-300">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="phone" class="block text-sm font-medium text-slate-700">{{ __('Phone') }}</label>
                                {{-- The dial code stays LTR even in Arabic and Kurdish: a phone
                                     number reversed around its "+" is unreadable in any locale. --}}
                                <div class="mt-2 grid grid-cols-[7.5rem_minmax(0,1fr)] gap-2" dir="ltr">
                                    <label class="sr-only" for="country_code">{{ __('Country code') }}</label>
                                    <select
                                        id="country_code"
                                        name="country_code"
                                        required
                                        class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 focus:border-transparent focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                                    >
                                        <option value="+964" @selected(old('country_code', '+964') === '+964')>🇮🇶 +964</option>
                                    </select>
                                    <input
                                        id="phone"
                                        name="phone"
                                        type="tel"
                                        inputmode="tel"
                                        autocomplete="tel-national"
                                        value="{{ old('phone') }}"
                                        placeholder="0770 000 0000"
                                        required
                                        class="block w-full rounded-xl border bg-white px-4 py-3 text-sm text-slate-900 placeholder-muted focus:border-transparent focus:outline-none focus-visible:ring-2 focus-visible:ring-accent {{ $errors->has('phone') ? 'border-rose-300' : 'border-slate-200' }}"
                                    >
                                </div>
                                <p class="mt-2 text-xs text-slate-500">{{ __('The confirmation code and delivery calls both go to this number.') }}</p>
                                @error('country_code')
                                    <p class="mt-2 text-sm text-rose-600 dark:text-rose-300">{{ $message }}</p>
                                @enderror
                                @error('phone')
                                    <p class="mt-2 text-sm text-rose-600 dark:text-rose-300">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-900/5 dark:bg-slate-900 dark:shadow-black/10 sm:rounded-3xl sm:p-6">
                        <div class="flex items-center gap-2.5">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-primary/5 text-primary dark:bg-slate-800 dark:text-slate-200">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7-5.1-7-11a7 7 0 1 1 14 0c0 5.9-7 11-7 11Z" />
                                    <circle cx="12" cy="10" r="2.5" />
                                </svg>
                            </span>
                            <h2 class="text-lg font-semibold tracking-[-0.02em] text-slate-950">{{ __('Where should it go?') }}</h2>
                        </div>

                        <div class="mt-5 space-y-5">
                            <div class="grid gap-5 sm:grid-cols-2">
                                <div>
                                    <label for="governorate_id" class="block text-sm font-medium text-slate-700">{{ __('Governorate') }}</label>
                                    <select
                                        id="governorate_id"
                                        name="governorate_id"
                                        required
                                        class="mt-2 block w-full rounded-xl border bg-white px-4 py-3 text-sm text-slate-900 focus:border-transparent focus:outline-none focus-visible:ring-2 focus-visible:ring-accent {{ $errors->has('governorate_id') ? 'border-rose-300' : 'border-slate-200' }}"
                                    >
                                        <option value="">{{ __('Select a governorate') }}</option>
                                        @foreach ($governorates as $governorate)
                                            <option value="{{ $governorate->id }}" @selected((int) old('governorate_id') === (int) $governorate->id)>
                                                {{ $governorate->localizedName() }} &mdash; {{ $governorate->shipping_fee > 0 ? number_format($governorate->shipping_fee).' '.$currencySymbol : __('Free delivery') }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="mt-2 text-xs text-slate-500">{{ __('Delivery time and shipping fee are set by governorate.') }}</p>
                                    @error('governorate_id')
                                        <p class="mt-2 text-sm text-rose-600 dark:text-rose-300">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="city" class="block text-sm font-medium text-slate-700">{{ __('City') }}</label>
                                    <input
                                        id="city"
                                        name="city"
                                        type="text"
                                        value="{{ old('city') }}"
                                        placeholder="{{ __('Baghdad') }}"
                                        required
                                        class="mt-2 block w-full rounded-xl border bg-white px-4 py-3 text-sm text-slate-900 placeholder-muted focus:border-transparent focus:outline-none focus-visible:ring-2 focus-visible:ring-accent {{ $errors->has('city') ? 'border-rose-300' : 'border-slate-200' }}"
                                    >
                                    @error('city')
                                        <p class="mt-2 text-sm text-rose-600 dark:text-rose-300">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div>
                                <label for="address_line1" class="block text-sm font-medium text-slate-700">{{ __('Address Line 1') }}</label>
                                <input
                                    id="address_line1"
                                    name="address_line1"
                                    type="text"
                                    value="{{ old('address_line1') }}"
                                    placeholder="{{ __('Street, district, building') }}"
                                    required
                                    class="mt-2 block w-full rounded-xl border bg-white px-4 py-3 text-sm text-slate-900 placeholder-muted focus:border-transparent focus:outline-none focus-visible:ring-2 focus-visible:ring-accent {{ $errors->has('address_line1') ? 'border-rose-300' : 'border-slate-200' }}"
                                >
                                @error('address_line1')
                                    <p class="mt-2 text-sm text-rose-600 dark:text-rose-300">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="address_line2" class="block text-sm font-medium text-slate-700">{{ __('Address Line 2') }}</label>
                                <input
                                    id="address_line2"
                                    name="address_line2"
                                    type="text"
                                    value="{{ old('address_line2') }}"
                                    placeholder="{{ __('Apartment, floor, landmark') }}"
                                    class="mt-2 block w-full rounded-xl border bg-white px-4 py-3 text-sm text-slate-900 placeholder-muted focus:border-transparent focus:outline-none focus-visible:ring-2 focus-visible:ring-accent {{ $errors->has('address_line2') ? 'border-rose-300' : 'border-slate-200' }}"
                                >
                                @error('address_line2')
                                    <p class="mt-2 text-sm text-rose-600 dark:text-rose-300">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="notes" class="block text-sm font-medium text-slate-700">{{ __('Delivery Note') }}</label>
                                <textarea
                                    id="notes"
                                    name="notes"
                                    rows="3"
                                    placeholder="{{ __('Anything the driver should know') }}"
                                    class="mt-2 block w-full rounded-xl border bg-white px-4 py-3 text-sm text-slate-900 placeholder-muted focus:border-transparent focus:outline-none focus-visible:ring-2 focus-visible:ring-accent {{ $errors->has('notes') ? 'border-rose-300' : 'border-slate-200' }}"
                                >{{ old('notes') }}</textarea>
                                @error('notes')
                                    <p class="mt-2 text-sm text-rose-600 dark:text-rose-300">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </section>
                </div>

                <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-900/5 dark:bg-slate-900 dark:shadow-black/10 sm:rounded-3xl xl:sticky xl:top-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('Order Summary') }}</p>

                    <dl class="mt-3">
                        <div class="flex items-center justify-between border-b border-dashed border-slate-200/80 py-2.5">
                            <dt class="text-sm text-slate-600">{{ __('Items') }}</dt>
                            <dd class="text-sm font-semibold text-slate-950">{{ $items->sum('quantity') }}</dd>
                        </div>
                        <div class="flex items-center justify-between border-b border-dashed border-slate-200/80 py-2.5">
                            <dt class="text-sm text-slate-600">{{ __('Product Lines') }}</dt>
                            <dd class="text-sm font-semibold text-slate-950">{{ $items->count() }}</dd>
                        </div>
                        <div class="flex items-end justify-between pt-3">
                            <dt class="text-sm font-semibold text-slate-700">{{ __('Subtotal') }}</dt>
                            <dd class="break-all text-xl font-bold tracking-[-0.02em] text-primary dark:text-white">
                                {{ number_format($subtotal, 2) }}
                                <span class="text-[11px] font-semibold uppercase tracking-[0.08em] text-muted">{{ $currencySymbol }}</span>
                            </dd>
                        </div>
                    </dl>

                    <p class="mt-4 border-t border-slate-100 pt-3 text-xs leading-5 text-muted dark:text-slate-500">
                        {{ __('Shipping is added once you pick a governorate. Payment is cash on delivery.') }}
                    </p>

                    <p class="mt-3 text-xs leading-5 text-muted dark:text-slate-500">
                        {{ __('Already have an account?') }}
                        <a href="{{ route('login') }}" class="font-semibold text-primary underline underline-offset-4 dark:text-white">{{ __('Sign in') }}</a>
                    </p>
                </section>
            </section>

            <div class="sticky bottom-3 z-30 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-primary px-4 py-3 text-white shadow-xl shadow-primary/25 dark:shadow-black/40 sm:px-6">
                <p class="text-xs font-medium text-white/70 sm:text-sm">
                    {{ __('Step :current of :total', ['current' => 2, 'total' => 3]) }} · {{ $items->sum('quantity') }} {{ __('Items') }}
                </p>
                <button
                    type="submit"
                    class="font-display group inline-flex items-center gap-2 rounded-xl bg-accent px-5 py-2.5 text-sm font-bold text-navy transition duration-200 hover:-translate-y-0.5 hover:bg-accent-hover hover:shadow-lg hover:shadow-black/20 active:translate-y-0 active:scale-[0.98] focus:outline-none focus-visible:ring-2 focus-visible:ring-accent/60"
                >
                    {{ __('Send confirmation code') }}
                    <svg class="h-4 w-4 transition-transform duration-200 group-hover:translate-x-0.5 rtl:rotate-180 rtl:group-hover:-translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 12h16m-6-6 6 6-6 6" />
                    </svg>
                </button>
            </div>
        </form>
    </div>
@endsection
