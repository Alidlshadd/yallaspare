@php
    $usd = fn ($value) => \App\Support\Pricing\ExchangeRate::formatUsd($value);
    $plainUsd = fn ($value) => $value === null ? '' : str_replace(',', '', $usd($value));
    $dinar = fn ($value) => number_format((float) $value);
    $smallField = 'pm-input';
    $ghostButton = 'pm-button pm-button-quiet';
    $solidButton = 'pm-button pm-button-primary';
    $conversionRateInput = rtrim(rtrim($conversionRate, '0'), '.');
    $conversionPerDollar = \App\Support\Pricing\ExchangeRate::perDollar($conversionRate);
    $ratesDiffer = $ratePer100 !== null && ! \App\Support\Pricing\ExchangeRate::sameRate($ratePer100, $conversionRate);
    $pageQuery = array_filter($filters, fn ($value) => $value !== '');
    $modeLabels = ['percent' => __('Percentage (%)'), 'usd' => __('Fixed amount in USD'), 'iqd' => __('Fixed amount in IQD')];
@endphp
@include('admin.exchange-rate.partials.bulk-preview')
<section class="pm-panel pm-products" id="prices" data-price-table aria-labelledby="price-heading">
    <div class="pm-panel-heading"><div><p class="pm-kicker">{{ __('Product prices') }}</p><h2 id="price-heading">{{ __('Edit product prices') }}</h2><p class="pm-hint">{{ __('pricing.table_intro') }}</p></div><span class="pm-result-count">{{ __('pricing.results', ['from' => $products->firstItem() ?? 0, 'to' => $products->lastItem() ?? 0, 'total' => number_format($products->total())]) }}</span></div>
    <form method="GET" action="{{ route('admin.exchange-rate.edit') }}#prices" class="pm-filters">
        <div class="pm-search-field"><label class="pm-label" for="price_search">{{ __('Search') }}</label><div class="pm-search"><x-ph-icon name="magnifying-glass" :size="19" /><input id="price_search" type="search" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('Name, SKU or part code') }}" class="pm-input"></div></div>
        <div><label class="pm-label" for="price_currency_filter">{{ __('Price currency') }}</label><select id="price_currency_filter" name="currency" class="pm-input"><option value="">{{ __('All') }}</option><option value="USD" @selected($filters['currency'] === 'USD')>USD</option><option value="IQD" @selected($filters['currency'] === 'IQD')>IQD</option></select></div>
        <div><label class="pm-label" for="price_category_filter">{{ __('Category') }}</label><select id="price_category_filter" name="category_id" class="pm-input"><option value="">{{ __('All') }}</option>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected($filters['category_id'] === (string) $category->id)>{{ $category->name }}</option>@endforeach</select></div>
        <div class="pm-filter-actions"><button type="submit" class="pm-button pm-button-navy">{{ __('Filter') }}</button>@if ($pageQuery !== [])<a href="{{ route('admin.exchange-rate.edit') }}#prices" class="pm-text-button">{{ __('Clear') }}</a>@endif</div>
    </form>
    <form id="bulkForm" method="POST" action="{{ route('admin.exchange-rate.bulk.preview') }}" class="pm-bulk-tools" data-operation-form data-async-operation data-loading-skip="true">
        @csrf
        @foreach ($pageQuery as $name => $value)<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endforeach
        <div class="pm-bulk-heading"><div><x-ph-icon name="stack" :size="20" /><h3>{{ __('pricing.bulk_title') }}</h3></div><p>{{ __('pricing.bulk_intro') }}</p></div>
        <div class="pm-bulk-fields">
            <div><label class="pm-label" for="bulk_mode">{{ __('Change by') }}</label><select id="bulk_mode" name="bulk_mode" class="pm-input">@foreach ($modeLabels as $mode => $label)<option value="{{ $mode }}" @selected(old('bulk_mode', $bulk['mode'] ?? 'percent') === $mode)>{{ $label }}</option>@endforeach</select></div>
            <div><label class="pm-label" for="bulk_value">{{ __('Amount') }}</label><input id="bulk_value" name="bulk_value" value="{{ old('bulk_value', $bulk['value'] ?? '') }}" placeholder="+10 / -5" type="text" inputmode="decimal" dir="ltr" class="pm-input" required></div>
            <fieldset><legend class="pm-label">{{ __('pricing.bulk_scope') }}</legend><div class="pm-scope"><label><input type="radio" name="bulk_scope" value="selected" @checked(old('bulk_scope', ($bulk && ! $bulk['selected']) ? 'filtered' : 'selected') === 'selected')>{{ __('pricing.selected_scope') }}</label><label><input type="radio" name="bulk_scope" value="filtered" @checked(old('bulk_scope', ($bulk && ! $bulk['selected']) ? 'filtered' : 'selected') === 'filtered')>{{ __('All :count filtered products', ['count' => number_format($products->total())]) }}</label></div></fieldset>
            <button type="submit" class="pm-button pm-button-navy" data-bulk-preview><x-ph-icon name="magnifying-glass" :size="17" />{{ __('pricing.preview') }}</button>
        </div>
        <p class="pm-hint">{{ __('Raise or lower prices by a percentage or by a fixed amount. You are shown every old and new price before anything is saved. Use a minus sign for a decrease.') }}</p>
        @foreach (['bulk_value', 'bulk_mode', 'bulk_scope', 'ids'] as $field) @error($field)<p class="pm-error" role="alert">{{ $message }}</p>@enderror @endforeach
        <p class="pm-form-message" data-operation-message role="status" tabindex="-1"></p>
    </form>
    <div class="pm-selection-bar">
        <label class="pm-mobile-select"><input type="checkbox" data-check-all="bulk">{{ __('pricing.select_page') }}</label>
        <div><span class="pm-selection-count" data-selection-count aria-live="polite">{{ __('pricing.selected', ['count' => 0]) }}</span><button type="button" class="pm-text-button" data-clear-selection hidden>{{ __('pricing.clear_selection') }}</button><span class="pm-muted" data-selection-hint>{{ __('pricing.select_hint') }}</span></div>
        <span class="pm-keyboard-hint">{{ __('pricing.keyboard') }}</span>
    </div>
    <div class="pm-edit-scroll" tabindex="0" role="region" aria-label="{{ __('Edit product prices') }}">
        <table class="pm-edit-table">
            <thead><tr><th class="pm-check-cell"><input type="checkbox" data-check-all="bulk" aria-label="{{ __('pricing.select_page') }}"></th><th scope="col">{{ __('Product') }}</th><th scope="col">{{ __('Priced in') }}</th><th scope="col">{{ __('USD price') }}</th><th scope="col" class="pm-number-heading">{{ __('IQD price now') }}</th><th scope="col">{{ __('New IQD price') }}</th><th scope="col" class="pm-number-heading">{{ __('Save') }}</th></tr></thead>
            @forelse ($products as $product)
                @php
                    $isUsd = $product->isUsdPriced();
                    $formId = 'price-form-'.$product->id;
                    $restoring = (string) old('editing_product_id') === (string) $product->id;
                @endphp
                <tbody data-price-row data-id="{{ $product->id }}" data-currency="{{ $isUsd ? 'USD' : 'IQD' }}" data-saved-usd="{{ $plainUsd($product->price_usd) }}" data-saved-iqd="{{ $product->price }}">
                    <tr class="pm-product-row">
                        <td class="pm-check-cell"><input type="checkbox" name="ids[]" value="{{ $product->id }}" form="bulkForm" data-check="bulk" @checked(in_array((string) $product->id, array_map('strval', (array) old('ids', $bulk['ids'] ?? [])), true)) aria-label="{{ __('Select :product', ['product' => $product->localizedName()]) }}"></td>
                        <td class="pm-product-cell"><div class="pm-product-identity"><span class="pm-product-icon" aria-hidden="true"><x-ph-icon name="cube" :size="21" /></span><div><a href="{{ route('admin.products.edit', $product) }}" class="pm-product-name">{{ $product->localizedName() }}</a><div class="pm-product-meta"><bdi>{{ $product->sku ?: $product->part_number }}</bdi><span class="pm-row-state" data-row-state>{{ __('pricing.ready') }}</span></div></div></div></td>
                        <td class="pm-basis-cell" data-label="{{ __('Priced in') }}"><span class="pm-currency {{ $isUsd ? 'pm-currency-usd' : '' }}">{{ $isUsd ? 'USD' : 'IQD' }}</span></td>
                        <td class="pm-usd-cell" data-label="{{ __('USD price') }}">
                            @if ($isUsd)<div class="pm-input-unit"><input type="text" name="usd_price" form="{{ $formId }}" value="{{ $restoring ? old('usd_price') : $plainUsd($product->price_usd) }}" inputmode="decimal" autocomplete="off" dir="ltr" aria-label="{{ __('USD price of :product', ['product' => $product->localizedName()]) }}" data-usd-input><span>$</span></div><p class="pm-cell-hint" data-usd-hint dir="ltr"></p>@else<span class="pm-muted">{{ __('Not in USD') }}</span>@endif
                        </td>
                        <td class="pm-current-cell" data-label="{{ __('IQD price now') }}"><bdi data-current-iqd>{{ $dinar($product->price) }} IQD</bdi></td>
                        <td class="pm-target-cell" data-label="{{ __('New IQD price') }}"><div class="pm-input-unit"><input type="text" name="target_iqd" form="{{ $formId }}" value="{{ $restoring ? old('target_iqd') : '' }}" placeholder="{{ $dinar($product->price) }}" inputmode="numeric" autocomplete="off" dir="ltr" aria-label="{{ __('New IQD price of :product', ['product' => $product->localizedName()]) }}" data-iqd-input><span>IQD</span></div><p class="pm-cell-hint" data-iqd-hint dir="ltr"></p></td>
                        <td class="pm-save-cell"><form id="{{ $formId }}" method="POST" action="{{ route('admin.exchange-rate.products.update', $product) }}" data-row-form data-loading-skip="true">
                            @csrf @method('PUT')
                            <input type="hidden" name="editing_product_id" value="{{ $product->id }}">
                            <input type="hidden" name="basis" value="{{ $restoring ? old('basis') : '' }}" data-basis>
                            <button type="submit" class="pm-button pm-row-save"><span data-button-label>{{ __('Save') }}</span></button><button type="button" class="pm-text-button pm-row-reset" data-row-reset hidden>{{ __('pricing.reset') }}</button>
                        </form></td>
                    </tr>
                    <tr class="pm-change-row" data-change-row hidden><td colspan="7">
                        <div class="pm-row-review">
                            <dl class="pm-delta-grid">
                                <div><dt>{{ __('pricing.old_price') }}</dt><dd data-old-value dir="ltr"></dd></div><div><dt>{{ __('pricing.new_price') }}</dt><dd data-new-value dir="ltr"></dd></div><div><dt>{{ __('pricing.difference') }}</dt><dd data-difference dir="ltr"></dd></div><div><dt>{{ __('pricing.percent') }}</dt><dd data-percent dir="ltr"></dd></div>
                            </dl>
                            <span class="pm-direction" data-direction></span>
                        </div>
                        <p class="pm-form-message" data-row-message role="status">@if ($restoring){{ $errors->first('usd_price') ?: $errors->first('target_iqd') }}@endif</p>
                    </td></tr>
                </tbody>
            @empty
                <tbody><tr><td colspan="7"><div class="pm-empty"><x-ph-icon name="magnifying-glass" :size="32" /><h3>{{ __('No products match these filters.') }}</h3><p>{{ __('pricing.empty_hint') }}</p><a href="{{ route('admin.exchange-rate.edit') }}#prices" class="pm-button pm-button-quiet">{{ __('Clear') }}</a></div></td></tr></tbody>
            @endforelse
        </table>
    </div>
    <div class="pm-table-footer"><p>{{ __('pricing.preview_note') }}</p>{{ $products->fragment('prices')->links() }}</div>
    <div class="pm-dirty-bar" data-dirty-bar hidden><span data-dirty-count role="status"></span><div><button type="button" class="pm-text-button" data-reset-all>{{ __('pricing.reset_all') }}</button><button type="button" class="pm-button pm-button-primary" data-save-all>{{ __('pricing.save_all') }}</button></div></div>
</section>
<p class="pm-section-note">{{ __('The exchange rate above changes what every USD price sells for in IQD. The tools below change the price of the products themselves. Orders already placed and finalized invoices are never changed by either.') }}</p>
@include('admin.exchange-rate.partials.conversion')
@include('admin.exchange-rate.partials.history')
