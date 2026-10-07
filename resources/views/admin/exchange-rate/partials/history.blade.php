<details class="pm-panel pm-disclosure" id="history"><summary><span><x-ph-icon name="clock-counter-clockwise" :size="22" />{{ __("Recent price changes") }}</span><span aria-hidden="true">+</span></summary><div class="pm-disclosure-body"><p class="pm-notice" data-history-notice hidden>{{ __("pricing.history_changed") }} <a href="{{ request()->fullUrl() }}#history">{{ __("pricing.reload_history") }}</a></p>
    <h3 class="text-sm font-bold text-slate-900">{{ __('Recent price changes') }}</h3>
    <p class="mt-1 text-xs text-slate-500">{{ __('Every price set here is recorded with the old price, the new price and the rate in force. Changing the exchange rate is not listed: it does not change what any product is priced at.') }}</p>

    <div class="mt-4 overflow-x-auto">
        <table class="price-table min-w-full text-sm">
            <thead class="text-[10.5px] font-bold uppercase tracking-widest text-slate-500">
                <tr>
                    <th scope="col" class="py-2 pe-3 text-start">{{ __('When') }}</th>
                    <th scope="col" class="py-2 pe-3 text-start">{{ __('Product') }}</th>
                    <th scope="col" class="py-2 pe-3 text-start">{{ __('Change') }}</th>
                    <th scope="col" class="py-2 pe-3 text-end">{{ __('Old price') }}</th>
                    <th scope="col" class="py-2 pe-3 text-end">{{ __('New price') }}</th>
                    <th scope="col" class="py-2 text-start">{{ __('By') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($history as $change)
                    <tr>
                        <td class="py-2 pe-3 text-xs text-slate-600 whitespace-nowrap" dir="ltr" data-label="{{ __('When') }}">{{ $change->created_at?->format('Y-m-d H:i') }}</td>
                        <td class="py-2 pe-3" data-wide>
                            <span class="font-bold text-slate-900">{{ $change->product_name }}</span>
                            <span class="ms-1 font-mono text-[11px] text-slate-500">{{ $change->product_sku }}</span>
                        </td>
                        <td class="py-2 pe-3 text-xs text-slate-700" data-label="{{ __('Change') }}">
                            {{ match ($change->action) {
                                'conversion' => __('Converted to USD'),
                                'bulk' => __('Bulk change'),
                                default => __('Edited'),
                            } }}
                            @if ($change->action === 'conversion' && $change->conversion_rate_per_100 !== null)
                                <span class="block text-[11px] text-slate-500" dir="ltr">{{ __('1 USD = :rate IQD', ['rate' => \App\Support\Pricing\ExchangeRate::perDollar((string) $change->conversion_rate_per_100)]) }}</span>
                            @elseif ($change->note)
                                <span class="block text-[11px] text-slate-500" dir="ltr">{{ $change->note }}</span>
                            @endif
                        </td>
                        <td class="py-2 pe-3 text-end text-slate-700 whitespace-nowrap" dir="ltr" data-label="{{ __('Old price') }}">
                            {{ $dinar($change->old_price_iqd) }} IQD
                            @if ($change->old_price_usd !== null)<span class="block text-[11px] text-slate-500">${{ $usd($change->old_price_usd) }}</span>@endif
                        </td>
                        <td class="py-2 pe-3 text-end font-bold text-slate-900 whitespace-nowrap" dir="ltr" data-label="{{ __('New price') }}">
                            {{ $dinar($change->new_price_iqd) }} IQD
                            @if ($change->new_price_usd !== null)<span class="block text-[11px] font-normal text-slate-500">${{ $usd($change->new_price_usd) }}</span>@endif
                        </td>
                        <td class="py-2 text-xs text-slate-600" data-label="{{ __('By') }}">{{ $change->user?->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-6 text-center text-sm text-slate-500" data-wide>{{ __('No price has been changed here yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div></details>