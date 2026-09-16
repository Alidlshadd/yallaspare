@php
    $welcomeService = app(\App\Services\WelcomeOfferService::class);
    $welcomeOffer = isset($welcomeSummary) ? ($welcomeSummary['offer'] ?? null)
        : (auth()->check() ? $welcomeService->available(auth()->user()) : $welcomeService->campaign());
@endphp
@if ($welcomeOffer)
    <aside class="my-4 rounded-2xl border border-orange-200 bg-orange-50 p-4 text-sm text-slate-800 dark:border-orange-900 dark:bg-slate-800 dark:text-slate-100" data-welcome-offer>
        <p class="font-bold">{{ $welcomeService->label($welcomeOffer) }}</p>
        <p class="mt-1 leading-6">{{ __('welcome.customer_rules') }}</p>
        @if ((float) $welcomeOffer['minimum_subtotal'] > 0)
            <p class="mt-1">{{ __('welcome.minimum', ['amount' => number_format($welcomeOffer['minimum_subtotal']), 'currency' => \App\Models\Setting::getValue('currency_code', 'IQD')]) }}</p>
        @endif
        @if ((float) $welcomeOffer['maximum_discount'] > 0 && $welcomeOffer['type'] !== 'free_shipping')
            <p class="mt-1">{{ __('welcome.maximum', ['amount' => number_format($welcomeOffer['maximum_discount']), 'currency' => \App\Models\Setting::getValue('currency_code', 'IQD')]) }}</p>
        @endif
        @if (! empty($welcomeOffer['expires_at']))
            <p class="mt-1">{{ __('welcome.expires', ['date' => \Illuminate\Support\Carbon::parse($welcomeOffer['expires_at'])->format('Y-m-d H:i')]) }}</p>
        @elseif ($welcomeOffer['valid_days'] > 0)
            <p class="mt-1">{{ __('welcome.days', ['days' => $welcomeOffer['valid_days']]) }}</p>
        @endif
        @if (isset($welcomeSummary))
            <p class="mt-2 font-semibold">{{ $welcomeSummary['valid'] ? __('welcome.applied') : __('welcome.not_applied') }}</p>
        @endif
    </aside>
@endif
