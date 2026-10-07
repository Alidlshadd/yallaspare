@if ($bulk)
<section class="pm-panel pm-bulk-preview" id="bulk-preview" aria-labelledby="bulk-preview-heading">
    <div class="pm-panel-heading"><div><p class="pm-kicker">{{ __('pricing.step_preview') }}</p><h2 id="bulk-preview-heading">{{ __('Confirm the price change') }}</h2><p class="pm-hint">{{ __('pricing.review_hint') }}</p></div><span class="pm-preview-status">{{ __('Nothing has been saved yet.') }}</span></div>
    <div class="pm-bulk-summary">
        <div><span>{{ __('Change by') }}</span><strong><bdi>{{ (str_starts_with($bulk['value'], '-') ? '' : '+').(str_contains($bulk['value'], '.') ? rtrim(rtrim($bulk['value'], '0'), '.') : $bulk['value']) }}{{ $bulk['mode'] === 'percent' ? '%' : ' '.strtoupper($bulk['mode']) }}</bdi></strong><small>{{ $modeLabels[$bulk['mode']] }}</small></div>
        <div><span>{{ __('pricing.bulk_scope') }}</span><strong>{{ number_format($bulk['count']) }}</strong><small>{{ __('It will be applied to :count products.', ['count' => number_format($bulk['count'])]) }}</small></div>
        <p>{{ $bulk['selected'] ? __('These are the products you ticked.') : __('These are all the products matching the filters.') }}<br>{{ $bulk['mode'] === 'percent' ? __('A percentage also moves the dealer price, so its proportion stays the same.') : __('A fixed amount moves the selling price only; dealer prices stay as they are.') }}</p>
    </div>
    <div class="pm-preview-scroll" tabindex="0" role="region" aria-label="{{ __('Confirm the price change') }}">
        <table class="pm-preview-table"><thead><tr><th scope="col">{{ __('Product') }}</th><th scope="col">{{ __('Old price') }}</th><th scope="col">{{ __('New price') }}</th><th scope="col">{{ __('pricing.difference') }}</th><th scope="col">{{ __('pricing.percent') }}</th></tr></thead><tbody>
            @foreach ($bulk['rows'] as $row)
                @php
                    $difference = $row['new_iqd'] - $row['old_iqd'];
                    $percent = $row['old_iqd'] > 0 ? $difference / $row['old_iqd'] * 100 : null;
                    $direction = $difference > 0 ? 'up' : ($difference < 0 ? 'down' : 'same');
                @endphp
                <tr>
                    <td><strong class="pm-product-name">{{ $row['product']->localizedName() }}</strong><bdi class="pm-sku">{{ $row['product']->sku }}</bdi></td>
                    <td><bdi>{{ $dinar($row['old_iqd']) }} IQD</bdi>@if ($row['old_usd'] !== null)<small dir="ltr">${{ $usd($row['old_usd']) }}</small>@endif</td>
                    <td>@if ($row['problem'] !== null)<span class="pm-error">{{ $row['problem'] }} {{ __('Skipped.') }}</span>@else<strong dir="ltr">{{ $dinar($row['new_iqd']) }} IQD</strong>@if ($row['new_usd'] !== null)<small dir="ltr">${{ $usd($row['new_usd']) }}</small>@endif @endif</td>
                    <td>@if ($row['problem'] === null)<span class="pm-delta pm-delta-{{ $direction }}"><bdi>{{ $difference > 0 ? '+' : '' }}{{ $dinar($difference) }} IQD</bdi><small>{{ $difference > 0 ? '↑ '.__('pricing.increase') : ($difference < 0 ? '↓ '.__('pricing.decrease') : '− '.__('pricing.unchanged')) }}</small></span>@else — @endif</td>
                    <td><bdi class="pm-delta pm-delta-{{ $direction }}">{{ $row['problem'] === null && $percent !== null ? ($percent > 0 ? '+' : '').number_format($percent, 2).'%' : '—' }}</bdi></td>
                </tr>
            @endforeach
        </tbody></table>
    </div>
    @if ($bulk['count'] > $bulk['rows']->count())<p class="pm-preview-note">{{ __('Showing the first :shown of :total products. The change is applied to every one of them, by the same rule.', ['shown' => number_format($bulk['rows']->count()), 'total' => number_format($bulk['count'])]) }}</p>@endif
    <form method="POST" action="{{ route('admin.exchange-rate.bulk.apply') }}" class="pm-apply-bar" data-operation-form data-async-operation data-loading-skip="true">
        @csrf <input type="hidden" name="token" value="{{ $bulk['token'] }}">
        <div><p class="pm-kicker">{{ __('pricing.step_apply') }}</p><p>{{ __('Apply to :count products', ['count' => number_format($bulk['count'])]) }}</p><p class="pm-form-message" data-operation-message role="status" tabindex="-1"></p></div>
        <div><a href="{{ route('admin.exchange-rate.edit', $pageQuery) }}#prices" class="pm-button pm-button-quiet">{{ __('Cancel') }}</a><button type="submit" class="pm-button pm-button-primary">{{ __('pricing.apply') }}</button></div>
    </form>
</section>
@endif
