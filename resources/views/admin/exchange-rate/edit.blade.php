@php
    $cardClass = 'pm-panel pm-secondary';
    $labelClass = 'pm-label';
    $hintClass = 'pm-hint';
    $rateInput = old('usd_rate_per_100', $ratePer100 !== null ? rtrim(rtrim($ratePer100, '0'), '.') : '');
    $selectedCurrency = old('default_price_currency', $defaultCurrency);
@endphp
<x-app-layout>
    <x-slot name="header">{{ __('Exchange Rate & Prices') }}</x-slot>
    @vite('resources/js/admin-pricing.js')
    <div class="pricing-workspace" data-pricing-workspace data-rate="{{ $ratePer100 ?? '' }}" data-default-currency="{{ $defaultCurrency }}" data-messages="{{ json_encode(__('pricing')) }}">
        <header class="pm-heading">
            <div><p class="pm-kicker"><span></span>{{ __('pricing.workspace') }}</p><h1>{{ __('Exchange Rate & Price Management') }}</h1><p>{{ __('pricing.intro') }}</p></div>
            <a href="{{ route('admin.products.index') }}" class="pm-button pm-button-quiet"><x-ph-icon name="cube" :size="18" />{{ __('Products') }} <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M6 18 18 6M6 6h12v12"/></svg></a>
        </header>
        <div class="pm-stats">
            <article class="pm-stat pm-stat-featured"><div class="pm-stat-label">{{ __('Current rate') }}<x-ph-icon name="currency-circle-dollar" :size="23" /></div><strong dir="ltr"><span data-saved-rate>{{ $ratePer100 !== null ? number_format((float) $ratePer100, (float) $ratePer100 == (int) $ratePer100 ? 0 : 2) : '—' }}</span><small>IQD</small></strong><p>{{ __('pricing.rate_hint') }}</p></article>
            <article class="pm-stat"><div class="pm-stat-label">{{ __('Last updated') }}<x-ph-icon name="clock-counter-clockwise" :size="22" /></div><strong class="pm-stat-date" dir="ltr" data-rate-date>{{ $updatedAt?->format('d M Y · H:i') ?? '—' }}</strong><p><span>{{ __('Updated by') }}:</span> <span data-rate-author>{{ $updatedBy ?: '—' }}</span></p></article>
            <article class="pm-stat"><div class="pm-stat-label">{{ __('Products priced in USD') }}<span class="pm-currency pm-currency-usd">USD</span></div><strong>{{ number_format($usdProductCount) }}</strong><p>{{ __('pricing.usd_hint') }}</p></article>
            <article class="pm-stat"><div class="pm-stat-label">{{ __('Products priced in IQD') }}<span class="pm-currency">IQD</span></div><strong>{{ number_format($iqdProductCount) }}</strong><p>{{ __('pricing.iqd_hint') }}</p></article>
        </div>
        <nav class="pm-nav" aria-label="{{ __('Exchange Rate & Prices') }}">
            <a href="#rate-editor"><x-ph-icon name="coins" :size="18" />{{ __('Exchange rate') }}</a>
            @if ($canManagePrices)
                <a href="#prices"><x-ph-icon name="grid-four" :size="18" />{{ __('Product prices') }}</a>
                <a href="#convert">{{ __('pricing.conversion_title') }}</a><a href="#history">{{ __('pricing.history_title') }}</a>
            @endif
        </nav>
        @include('admin.manual-invoices.partials.flash')
        <div class="pm-notice" role="status" aria-live="polite" data-page-message hidden></div>
        <section class="pm-panel pm-rate-panel" id="rate-editor" aria-labelledby="rate-heading">
            <form method="POST" action="{{ route('admin.exchange-rate.update') }}" data-rate-form data-loading-skip="true">
                @csrf @method('PUT')
                <div class="pm-panel-heading"><div><p class="pm-kicker">{{ __('USD exchange rate') }}</p><h2 id="rate-heading">{{ __('pricing.rate_editor') }}</h2></div><span class="pm-state" data-rate-state>{{ __('pricing.saved_rate') }}</span></div>
                <div class="pm-rate-grid">
                    <div class="pm-rate-fields">
                        <div><label class="pm-label" for="usd_rate_per_100">{{ __('How many IQD is 100 USD?') }}</label><div class="pm-input-unit pm-rate-input"><input id="usd_rate_per_100" name="usd_rate_per_100" value="{{ $rateInput }}" type="text" inputmode="decimal" dir="ltr" autocomplete="off" placeholder="170000" aria-describedby="rate-help rate-error" data-usd-rate-input @error('usd_rate_per_100') aria-invalid="true" @enderror><span>IQD</span></div></div>
                        <div><label class="pm-label" for="default_price_currency">{{ __('Default price currency for new products') }}</label><select id="default_price_currency" name="default_price_currency" class="pm-input"><option value="IQD" @selected($selectedCurrency === 'IQD')>{{ __('IQD — Iraqi dinar') }}</option><option value="USD" @selected($selectedCurrency === 'USD')>{{ __('USD ($) — US dollar') }}</option></select><p class="pm-hint">{{ __('Only the starting choice on the new product form. Existing products keep the currency they were priced in.') }}</p></div>
                    </div>
                    <aside class="pm-rate-preview" aria-label="{{ __('pricing.live_preview') }}">
                        <div class="pm-preview-caption"><span>{{ __('pricing.live_preview') }}</span><span class="pm-live-dot" aria-hidden="true"></span></div>
                        <div class="pm-rate-equation" dir="ltr"><span>100 <small>USD</small></span><span class="pm-equals">=</span><strong data-rate-hundred>—</strong><small>IQD</small></div>
                        <div class="pm-rate-equation pm-rate-equation-small" dir="ltr"><span>1 <small>USD</small></span><span class="pm-equals">=</span><strong data-rate-dollar>—</strong><small>IQD</small></div>
                        <p>{{ __('pricing.rate_note') }}</p>
                    </aside>
                </div>
                <div class="pm-rate-footer"><p id="rate-help">{{ __('You set this rate yourself; it is never fetched automatically. Saving a new rate changes what every USD-priced product sells for in IQD. Their USD prices stay as they are.') }}</p><button class="pm-button pm-button-primary" type="submit"><span data-button-label>{{ __('Save exchange rate') }}</span></button></div>
                <p class="pm-form-message" id="rate-error" role="status" data-form-message>@error('usd_rate_per_100'){{ $message }}@enderror</p>
            </form>
        </section>
        @if ($canManagePrices) @include('admin.exchange-rate.partials.prices') @endif
    </div>
</x-app-layout>