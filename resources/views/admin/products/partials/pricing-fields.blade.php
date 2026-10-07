{{--
    Price, dealer price and the currency they are typed in.

    A dollar price is stored as dollars; the dinar figure under each field is
    only a preview of what the shop will show at the rate set in Settings, and
    it is worked out here with the same whole-number arithmetic and rounding
    the server uses.

    Expects: $inputBase, $inputError, $currencyLabel, and $product when editing.
--}}
@php
    $pricingProduct = $product ?? null;
    $usdRatePer100 = \App\Support\Pricing\ExchangeRate::perHundred();
    $usdRatePerDollar = \App\Support\Pricing\ExchangeRate::perDollar();
    $savedCurrency = $pricingProduct
        ? ($pricingProduct->isUsdPriced() ? 'USD' : 'IQD')
        : \App\Support\Pricing\ExchangeRate::defaultCurrency();
    $priceCurrency = \App\Support\Pricing\ExchangeRate::normalizeCurrency(old('price_currency', $savedCurrency));

    // Without a rate there is nothing to convert with, so a new product
    // cannot start on dollars even when that is the default.
    if ($usdRatePer100 === null && ! $pricingProduct?->isUsdPriced() && ! old('price_currency')) {
        $priceCurrency = 'IQD';
    }

    $savedIsUsd = (bool) $pricingProduct?->isUsdPriced();
    $priceValue = old('price', $pricingProduct ? ($savedIsUsd ? $pricingProduct->price_usd : $pricingProduct->price) : '');
    $dealerPriceValue = old('dealer_price', $pricingProduct ? ($savedIsUsd ? $pricingProduct->dealer_price_usd : $pricingProduct->dealer_price) : '');
    $costPriceValue = old('cost_price', $pricingProduct ? ($savedIsUsd ? $pricingProduct->cost_price_usd : $pricingProduct->cost_price) : '');
@endphp

<div
    class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4"
    data-pricing-fields
    data-rate-per-100="{{ $usdRatePer100 ?? '' }}"
    data-dinar-label="{{ $currencyLabel }}"
    data-preview-template="{{ __('= :amount IQD at the current rate') }}"
    data-profit-template="{{ __('Net profit per unit: :amount (:percent% of the price)') }}"
    data-dealer-profit-template="{{ __('At the dealer price: :amount') }}"
>
    <div class="md:col-span-2">
        <label for="price_currency" class="block text-sm font-medium text-slate-700">{{ __('Price currency') }}</label>
        <select id="price_currency" name="price_currency" class="{{ $inputBase }} md:max-w-xs @error('price_currency') {{ $inputError }} @enderror" data-price-currency @error('price_currency') aria-invalid="true" @enderror>
            <option value="IQD" @selected($priceCurrency === 'IQD')>{{ __('IQD — Iraqi dinar') }}</option>
            <option value="USD" @selected($priceCurrency === 'USD') @disabled($usdRatePer100 === null && $priceCurrency !== 'USD')>{{ __('USD ($) — US dollar') }}</option>
        </select>
        @if ($usdRatePer100 === null)
            <p class="text-xs text-slate-500 mt-1">
                {{ __('No exchange rate has been set yet. Products can only be priced in USD once it is set.') }}
                <a href="{{ route('admin.exchange-rate.edit') }}" class="font-semibold underline">{{ __('Set the exchange rate') }}</a>
            </p>
        @else
            <p class="text-xs text-slate-500 mt-1">
                <span dir="ltr">{{ __('1 USD = :rate IQD', ['rate' => $usdRatePerDollar]) }}</span>
                · {{ __('A USD price follows the exchange rate. An IQD price stays exactly as entered.') }}
            </p>
            <p class="text-xs text-slate-500 mt-1">{{ __('Switching the currency does not convert the amounts. Enter them in the currency you select.') }}</p>
        @endif
        @error('price_currency')
            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="price" class="block text-sm font-medium text-slate-700">{{ __('Price') }} <span class="text-rose-500">*</span></label>
        <div class="relative">
            <input id="price" aria-label="{{ __('Price') }}" type="number" step="0.01" min="0" name="price" value="{{ $priceValue }}" class="{{ $inputBase }} pr-16 @error('price') {{ $inputError }} @enderror" required data-price-input data-role="price" @error('price') aria-invalid="true" @enderror>
            <span class="absolute inset-y-0 right-3 flex items-center text-xs text-slate-500" data-price-suffix>{{ $priceCurrency === 'USD' ? 'USD' : $currencyLabel }}</span>
        </div>
        <p class="text-xs font-semibold text-slate-700 mt-1 hidden" dir="ltr" data-price-preview></p>
        @error('price')
            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
        @enderror
    </div>
    <div>
        <label for="dealer_price" class="block text-sm font-medium text-slate-700">{{ __('Dealer Price') }}</label>
        <div class="relative">
            <input id="dealer_price" type="number" step="0.01" min="0" name="dealer_price" value="{{ $dealerPriceValue }}" class="{{ $inputBase }} pr-16 @error('dealer_price') {{ $inputError }} @enderror" placeholder="{{ __('Optional') }}" data-price-input data-role="dealer" @error('dealer_price') aria-invalid="true" @enderror>
            <span class="absolute inset-y-0 right-3 flex items-center text-xs text-slate-500" data-price-suffix>{{ $priceCurrency === 'USD' ? 'USD' : $currencyLabel }}</span>
        </div>
        <p class="text-xs font-semibold text-slate-700 mt-1 hidden" dir="ltr" data-price-preview></p>
        <p class="text-xs text-slate-500 mt-1">{{ __('Leave empty to use dealer discount rules.') }}</p>
        @error('dealer_price')
            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="md:col-span-2 border-t border-slate-200 pt-4">
        <div class="md:max-w-[calc(50%-0.5rem)]">
            <label for="cost_price" class="block text-sm font-medium text-slate-700">{{ __('Purchase price (your cost)') }}</label>
            <div class="relative">
                <input id="cost_price" type="number" step="0.01" min="0" name="cost_price" value="{{ $costPriceValue }}" class="{{ $inputBase }} pr-16 @error('cost_price') {{ $inputError }} @enderror" placeholder="{{ __('Optional') }}" data-price-input data-role="cost" @error('cost_price') aria-invalid="true" @enderror>
                <span class="absolute inset-y-0 right-3 flex items-center text-xs text-slate-500" data-price-suffix>{{ $priceCurrency === 'USD' ? 'USD' : $currencyLabel }}</span>
            </div>
            <p class="text-xs font-semibold text-slate-700 mt-1 hidden" dir="ltr" data-price-preview></p>
            @error('cost_price')
                <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
            @enderror
        </div>
        <p class="text-xs text-slate-500 mt-1">{{ __('What you pay for one unit, in the same currency as the price. Only staff see it; customers never do.') }}</p>
        <p class="text-sm font-bold text-slate-900 mt-2 hidden" data-profit-line></p>
        <p class="text-xs font-semibold text-slate-600 mt-1 hidden" data-dealer-profit-line></p>
    </div>
</div>

<script nonce="{{ $cspNonce }}">
    (function () {
        const root = document.querySelector('[data-pricing-fields]');
        if (!root) return;

        const select = root.querySelector('[data-price-currency]');
        const inputs = root.querySelectorAll('[data-price-input]');
        const suffixes = root.querySelectorAll('[data-price-suffix]');

        // Hundredths as whole numbers, so the preview never disagrees with
        // the server by a float's last digit.
        const hundredths = (value) => {
            const match = /^(\d+)(?:\.(\d{1,2}))?$/.exec(String(value).trim());
            return match ? BigInt(match[1]) * 100n + BigInt((match[2] || '').padEnd(2, '0')) : null;
        };

        const rate = hundredths(root.dataset.ratePer100 || '');

        const render = () => {
            const isUsd = select.value === 'USD';

            suffixes.forEach((suffix) => { suffix.textContent = isUsd ? 'USD' : root.dataset.dinarLabel; });

            inputs.forEach((input) => {
                const preview = input.closest('div').parentElement.querySelector('[data-price-preview]');
                const amount = hundredths(input.value);

                if (!isUsd || rate === null || amount === null) {
                    preview.classList.add('hidden');
                    preview.textContent = '';
                    return;
                }

                // dollars × (dinars per 100 dollars) ÷ 100, rounded half-up.
                const dinars = (amount * rate + 500000n) / 1000000n;
                preview.textContent = root.dataset.previewTemplate.replace(':amount', dinars.toLocaleString('en-US'));
                preview.classList.remove('hidden');
            });

            renderProfit(isUsd);
        };

        // Profit in dinars, the currency the shop is run in: for a dollar
        // product both sides are converted first, exactly as they are stored.
        const inDinars = (role, isUsd) => {
            const amount = hundredths(root.querySelector('[data-role="' + role + '"]').value);
            if (amount === null) return null;
            if (!isUsd) return amount;

            return rate === null ? null : ((amount * rate + 500000n) / 1000000n) * 100n;
        };

        const dinarText = (value) => {
            const negative = value < 0n;
            const absolute = negative ? -value : value;
            const cents = absolute % 100n;
            const text = (absolute / 100n).toLocaleString('en-US') + (cents === 0n ? '' : '.' + cents.toString().padStart(2, '0'));

            return (negative ? '-' : '') + text + ' ' + root.dataset.dinarLabel;
        };

        const renderProfit = (isUsd) => {
            const profitLine = root.querySelector('[data-profit-line]');
            const dealerLine = root.querySelector('[data-dealer-profit-line]');
            const cost = inDinars('cost', isUsd);
            const price = inDinars('price', isUsd);
            const dealer = inDinars('dealer', isUsd);

            profitLine.classList.add('hidden');
            dealerLine.classList.add('hidden');

            if (cost === null || price === null) return;

            const profit = price - cost;
            const percent = price > 0n ? Number((profit * 1000n) / price) / 10 : 0;
            profitLine.textContent = root.dataset.profitTemplate.replace(':amount', dinarText(profit)).replace(':percent', percent.toString());
            profitLine.classList.toggle('text-rose-600', profit < 0n);
            profitLine.classList.remove('hidden');

            if (dealer !== null) {
                dealerLine.textContent = root.dataset.dealerProfitTemplate.replace(':amount', dinarText(dealer - cost));
                dealerLine.classList.remove('hidden');
            }
        };

        select.addEventListener('change', render);
        inputs.forEach((input) => input.addEventListener('input', render));
        render();
    })();
</script>
