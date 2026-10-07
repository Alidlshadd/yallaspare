@php
    $cardClass = 'bg-white border border-slate-200/70 rounded-2xl p-5 sm:p-6 bento-shadow';
    $labelClass = 'block text-[11px] font-bold uppercase tracking-widest text-slate-500 mb-1.5';
    $fieldClass = 'h-12 w-full px-3.5 rounded-xl border border-slate-200 bg-slate-50 text-base font-semibold text-slate-900 focus:outline-none focus:border-accent focus:ring-2 focus:ring-accent/30';
    $hintClass = 'mt-1.5 text-xs text-slate-500';
    // Shown without a trailing ".00": the owner types 150000, not 150000.00.
    $rateInput = old('usd_rate_per_100', $ratePer100 !== null ? rtrim(rtrim($ratePer100, '0'), '.') : '');
    $selectedCurrency = old('default_price_currency', $defaultCurrency);
@endphp

<x-app-layout>
    <x-slot name="header">{{ __('Exchange Rate') }}</x-slot>

    <div class="bg-[#f3f4f7] dark:bg-slate-950 min-h-screen">
    <div class="py-6">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

        @include('admin.manual-invoices.partials.hero', [
            'eyebrow' => __('Pricing'),
            'title' => __('Exchange Rate'),
            'subtitle' => $ratePerDollar !== null
                ? __('1 USD = :rate IQD', ['rate' => $ratePerDollar])
                : __('No exchange rate has been set yet. Products can only be priced in USD once it is set.'),
            'actions' => [
                ['href' => route('admin.products.index'), 'label' => __('Products')],
            ],
        ])

        @include('admin.manual-invoices.partials.flash')

        <div class="grid grid-cols-1 lg:grid-cols-[1fr_320px] gap-4">
            <form method="POST" action="{{ route('admin.exchange-rate.update') }}" class="{{ $cardClass }} min-w-0" data-loading-form data-loading-button-text="Saving...">
                @csrf
                @method('PUT')

                <h2 class="text-sm font-bold text-slate-900">{{ __('USD exchange rate') }}</h2>
                <p class="mt-1 mb-5 text-xs text-slate-500">{{ __('You set this rate yourself; it is never fetched automatically. Saving a new rate reprices every USD-priced product at once.') }}</p>

                <div>
                    <label for="usd_rate_per_100" class="{{ $labelClass }}">{{ __('How many IQD is 100 USD?') }}</label>
                    <div class="relative">
                        <input
                            id="usd_rate_per_100"
                            type="text"
                            name="usd_rate_per_100"
                            value="{{ $rateInput }}"
                            inputmode="decimal"
                            autocomplete="off"
                            dir="ltr"
                            placeholder="150000"
                            class="{{ $fieldClass }} pe-16"
                            data-usd-rate-input
                            @error('usd_rate_per_100') aria-invalid="true" @enderror
                        >
                        <span class="pointer-events-none absolute inset-y-0 end-4 flex items-center text-xs font-bold text-slate-500">IQD</span>
                    </div>
                    @error('usd_rate_per_100')
                        <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror

                    <div class="mt-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3" dir="ltr">
                        <p class="text-lg font-bold text-slate-900" data-usd-rate-preview data-template="{{ __('1 USD = :rate IQD') }}">{{ $ratePerDollar !== null ? __('1 USD = :rate IQD', ['rate' => $ratePerDollar]) : '—' }}</p>
                        <p class="mt-0.5 text-xs text-slate-500" data-usd-rate-example data-template="{{ __('Example: a 10 USD product costs :amount IQD') }}"></p>
                    </div>
                </div>

                <div class="mt-6 border-t border-slate-200 pt-5">
                    <label for="default_price_currency" class="{{ $labelClass }}">{{ __('Default price currency for new products') }}</label>
                    <select id="default_price_currency" name="default_price_currency" class="{{ $fieldClass }}">
                        <option value="IQD" @selected($selectedCurrency === 'IQD')>{{ __('IQD — Iraqi dinar') }}</option>
                        <option value="USD" @selected($selectedCurrency === 'USD')>{{ __('USD ($) — US dollar') }}</option>
                    </select>
                    <p class="{{ $hintClass }}">{{ __('Only the starting choice on the new product form. Existing products keep the currency they were priced in.') }}</p>
                    @error('default_price_currency')
                        <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit"
                            class="inline-flex w-full sm:w-auto items-center justify-center gap-2 h-11 px-6 rounded-xl text-sm font-bold text-navy-deep shadow-md transition hover:brightness-105"
                            style="background: linear-gradient(180deg, #ff8a3d, #e65c00);">
                        {{ __('Save exchange rate') }}
                    </button>
                </div>
            </form>

            <aside class="space-y-4">
                <section class="{{ $cardClass }}">
                    <h2 class="text-sm font-bold text-slate-900 mb-3">{{ __('Current rate') }}</h2>
                    <dl class="space-y-2.5 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">{{ __('Last updated') }}</dt>
                            <dd class="font-bold text-slate-900" dir="ltr">{{ $updatedAt?->format('Y-m-d H:i') ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">{{ __('Updated by') }}</dt>
                            <dd class="font-bold text-slate-900">{{ $updatedBy !== '' ? $updatedBy : '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 border-t border-slate-200 pt-2.5">
                            <dt class="text-slate-500">{{ __('Products priced in USD') }}</dt>
                            <dd class="font-bold text-slate-900">{{ number_format($usdProductCount) }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">{{ __('Products priced in IQD') }}</dt>
                            <dd class="font-bold text-slate-900">{{ number_format($iqdProductCount) }}</dd>
                        </div>
                    </dl>
                    <p class="{{ $hintClass }}">{{ __('A USD price follows the exchange rate. An IQD price stays exactly as entered.') }}</p>
                </section>
            </aside>
        </div>

        @if ($examples->isNotEmpty())
            <section class="{{ $cardClass }} mt-4">
                <h2 class="text-sm font-bold text-slate-900 mb-3">{{ __('USD-priced products at the current rate') }}</h2>
                <ul class="divide-y divide-slate-100 text-sm">
                    @foreach ($examples as $example)
                        <li class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 py-2.5">
                            <a href="{{ route('admin.products.edit', $example) }}" class="min-w-0 font-bold text-slate-900 hover:underline">
                                {{ $example->localizedName() }}
                                <span class="ms-1 font-mono text-[11px] font-normal text-slate-500">{{ $example->sku }}</span>
                            </a>
                            <span class="whitespace-nowrap text-slate-700" dir="ltr">
                                ${{ number_format((float) $example->price_usd, 2) }}
                                <span class="mx-1 text-slate-400">→</span>
                                <span class="font-bold text-slate-900">{{ number_format((float) $example->price) }} IQD</span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
    </div>
    </div>

    <script nonce="{{ $cspNonce }}">
        (function () {
            const input = document.querySelector('[data-usd-rate-input]');
            const preview = document.querySelector('[data-usd-rate-preview]');
            const example = document.querySelector('[data-usd-rate-example]');
            if (!input || !preview || !example) return;

            const render = () => {
                const match = /^(\d{1,9})(?:\.(\d{1,2}))?$/.exec(input.value.replace(/[,\s]/g, ''));

                if (!match || (Number(match[1]) === 0 && !Number(match[2] || 0))) {
                    preview.textContent = '—';
                    example.textContent = '';
                    return;
                }

                // Whole-number arithmetic, the same rounding the server uses.
                const hundredths = BigInt(match[1]) * 100n + BigInt((match[2] || '').padEnd(2, '0'));
                const perDollar = (Number(hundredths) / 10000).toString();
                const tenDollars = (1000n * hundredths + 500000n) / 1000000n;

                preview.textContent = preview.dataset.template.replace(':rate', perDollar);
                example.textContent = example.dataset.template.replace(':amount', tenDollars.toLocaleString('en-US'));
            };

            input.addEventListener('input', render);
            render();
        })();
    </script>
</x-app-layout>
