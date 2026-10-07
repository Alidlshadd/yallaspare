<details class="pm-panel pm-disclosure" id="convert" @if ($conversion) open @endif><summary><span><x-ph-icon name="currency-circle-dollar" :size="22" />{{ __("Convert existing IQD products to USD") }}</span><span aria-hidden="true">+</span></summary><div class="pm-disclosure-body">
    <h3 class="text-sm font-bold text-slate-900">{{ __('Convert existing IQD products to USD') }}</h3>
    <p class="mt-1 text-xs text-slate-500">{{ __('A one-time step for products priced in IQD: each IQD price is divided by the conversion rate and kept as its USD price. From then on the product sells at that USD price times the current exchange rate. Products already in USD are never converted again, and purchase prices and stock are not touched.') }}</p>

    @if ($ratePer100 === null)
        <p class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs font-semibold text-amber-900">{{ __('Set the exchange rate first.') }}</p>
    @elseif ($convertibleCount === 0)
        <p class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-900">{{ __('There are no IQD-priced products left to convert.') }}</p>
    @else
        <form method="GET" action="{{ route('admin.exchange-rate.edit') }}#convert" class="mt-4 flex flex-wrap items-end gap-3">
            <input type="hidden" name="convert" value="preview">
            <div class="w-full sm:w-64">
                <label for="conversion_rate" class="{{ $labelClass }}">{{ __('Conversion rate: 100 USD = … IQD') }}</label>
                <input id="conversion_rate" type="text" name="conversion_rate" value="{{ $conversionRateInput }}" inputmode="decimal" autocomplete="off" dir="ltr" class="{{ $smallField }}">
            </div>
            <button type="submit" class="{{ $ghostButton }}">{{ __('Preview conversion') }}</button>
            <p class="w-full text-xs text-slate-500">
                {{ __(':count products are priced in IQD.', ['count' => number_format($convertibleCount)]) }}
                <span dir="ltr">{{ __('Conversion: 1 USD = :rate IQD.', ['rate' => $conversionPerDollar]) }}</span>
                <span dir="ltr">{{ __('Selling: 1 USD = :rate IQD.', ['rate' => $ratePerDollar]) }}</span>
            </p>
        </form>

        @error('ids')
            <p class="mt-3 text-xs font-semibold text-rose-600">{{ $message }}</p>
        @enderror

        @if ($conversion)
            @if ($ratesDiffer)
                <p class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs font-semibold text-amber-900">{{ __('The conversion rate and the current exchange rate are different, so the IQD price of converted products will change. The new IQD price is shown for each product below.') }}</p>
            @endif

            <form method="POST" action="{{ route('admin.exchange-rate.convert') }}" class="mt-4" data-operation-form data-loading-form>
                @csrf
                <input type="hidden" name="token" value="{{ $conversion['token'] }}">

                <div class="overflow-x-auto" tabindex="0" role="region" aria-label="{{ __('Preview conversion') }}">
                    <table class="price-table min-w-full text-sm">
                        <thead class="text-[10.5px] font-bold uppercase tracking-widest text-slate-500">
                            <tr>
                                <th scope="col" class="py-2 pe-3 text-start w-8"><input type="checkbox" data-check-all="convert" aria-label="{{ __('Select all') }}" checked></th>
                                <th scope="col" class="py-2 pe-3 text-start">{{ __('Product') }}</th>
                                <th scope="col" class="py-2 pe-3 text-end">{{ __('Old IQD price') }}</th>
                                <th scope="col" class="py-2 pe-3 text-end">{{ __('USD price') }}</th>
                                <th scope="col" class="py-2 text-end">{{ __('New IQD price') }}</th>
                                <th scope="col">{{ __('pricing.difference') }}</th>
                                <th scope="col">{{ __('pricing.percent') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($conversion['rows'] as $row)
                                <tr>
                                    <td class="py-2 pe-3" data-wide>
                                        <label class="inline-flex items-center gap-2">
                                            <input type="checkbox" name="ids[]" value="{{ $row['product']->id }}" data-check="convert" @checked(! old("token") || in_array((string) $row["product"]->id, array_map("strval", (array) old("ids", [])), true)) aria-label="{{ __('Select :product', ['product' => $row['product']->localizedName()]) }}">
                                        </label>
                                    </td>
                                    <td class="py-2 pe-3">
                                        <span class="font-bold text-slate-900">{{ $row['product']->localizedName() }}</span>
                                        <span class="ms-1 font-mono text-[11px] text-slate-500">{{ $row['product']->sku }}</span>
                                    </td>
                                    <td class="py-2 pe-3 text-end text-slate-700 whitespace-nowrap" dir="ltr" data-label="{{ __('Old IQD price') }}">{{ $dinar($row['old_iqd']) }} IQD</td>
                                    <td class="py-2 pe-3 text-end font-bold text-slate-900 whitespace-nowrap" dir="ltr" data-label="{{ __('USD price') }}">${{ $usd($row['usd']) }}</td>
                                    <td class="py-2 text-end whitespace-nowrap {{ $row['new_iqd'] !== $row['old_iqd'] ? 'font-bold text-amber-700' : 'text-slate-700' }}" dir="ltr" data-label="{{ __('New IQD price') }}">{{ $dinar($row['new_iqd']) }} IQD</td>
                                    @php
                                        $difference = $row['new_iqd'] - $row['old_iqd'];
                                        $direction = $difference > 0 ? 'up' : ($difference < 0 ? 'down' : 'same');
                                    @endphp
                                    <td class="pm-delta pm-delta-{{ $direction }}"><bdi>{{ $difference > 0 ? '+' : '' }}{{ $dinar($difference) }} IQD</bdi><small class="block">{{ $difference > 0 ? '↑ '.__('pricing.increase') : ($difference < 0 ? '↓ '.__('pricing.decrease') : '− '.__('pricing.unchanged')) }}</small></td>
                                    <td class="pm-delta pm-delta-{{ $direction }}"><bdi>{{ $row['old_iqd'] > 0 ? ($difference > 0 ? '+' : '').number_format($difference / $row['old_iqd'] * 100, 2).'%' : '—' }}</bdi></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($convertibleCount > $conversion['rows']->count())
                    <p class="mt-2 text-xs text-slate-500">{{ __('Showing the first :shown of :total products. "Convert all" converts every one of them, by the same rule.', ['shown' => number_format($conversion['rows']->count()), 'total' => number_format($convertibleCount)]) }}</p>
                @endif

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <button type="submit" name="scope" value="selected" class="{{ $ghostButton }}">{{ __('Convert selected') }}</button>
                    <button type="submit" name="scope" value="all" class="{{ $solidButton }}"
                            data-danger-confirm
                            data-danger-title="{{ __('Convert all to USD') }}"
                            data-danger-action="{{ __('Convert all to USD') }}"
                            data-danger-description="{{ __(':count IQD-priced products will be converted to USD at 1 USD = :rate IQD.', ['count' => number_format($convertibleCount), 'rate' => $conversionPerDollar]) }}">
                        {{ __('Convert all :count products', ['count' => number_format($convertibleCount)]) }}
                    </button>
                    <a href="{{ route('admin.exchange-rate.edit') }}#convert" class="text-xs font-bold text-slate-500 hover:underline">{{ __('Cancel') }}</a>
                </div>
            </form>
        @endif
    @endif
</div></details>
