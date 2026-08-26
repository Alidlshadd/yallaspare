@php
    $enabledPaymentMethodKeys = collect($paymentMethods)
        ->filter(fn (array $method): bool => (bool) ($method['enabled'] ?? false))
        ->pluck('key');
    $selectedPaymentMethod = old('payment_method', 'cash_on_delivery');

    if (! $enabledPaymentMethodKeys->contains($selectedPaymentMethod)) {
        $selectedPaymentMethod = 'cash_on_delivery';
    }
@endphp

<div class="rounded-2xl border border-slate-200/80 bg-white p-3.5 dark:border-slate-800 dark:bg-slate-950">
    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ __('Payment Method') }}</p>
    <div class="mt-2.5 grid gap-3 sm:grid-cols-2">
        @foreach($paymentMethods as $method)
            @php
                $isDisabled = ! ($method['enabled'] ?? false);
                $isCod = ($method['key'] ?? null) === 'cash_on_delivery';
                $isSelected = ! $isDisabled && $selectedPaymentMethod === ($method['key'] ?? null);
                $description = $isCod
                    ? 'Pay when your order arrives'
                    : (($method['coming_soon'] ?? false) ? 'Fast & secure online payment' : ($method['description'] ?? ''));
            @endphp

            <label
                data-payment-method-card="{{ $method['key'] }}"
                class="relative flex min-h-24 min-w-0 items-center gap-3 overflow-hidden rounded-2xl border px-3.5 py-3 text-sm {{ $isDisabled
                    ? 'cursor-not-allowed border-slate-200 bg-slate-50/80 dark:border-slate-700 dark:bg-slate-900/60'
                    : 'cursor-pointer border-[#070740]/45 bg-[#070740]/[0.025] shadow-[0_1px_3px_rgba(7,7,64,0.08)] transition duration-200 hover:-translate-y-0.5 hover:border-[#070740]/70 hover:shadow-[0_3px_8px_rgba(7,7,64,0.10)] focus-within:border-[#070740] focus-within:ring-2 focus-within:ring-[#FF6A00]/25 motion-reduce:transform-none motion-reduce:transition-none dark:border-slate-500 dark:bg-slate-900/80 dark:hover:border-slate-300 dark:focus-within:border-white' }}"
            >
                @if($isSelected)
                    <span aria-hidden="true" class="absolute inset-y-3 start-0 w-0.5 rounded-full bg-[#FF6A00]"></span>
                @endif

                <input
                    type="radio"
                    name="payment_method"
                    value="{{ $method['key'] }}"
                    @checked($isSelected)
                    @disabled($isDisabled)
                    class="sr-only"
                >

                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $isDisabled
                    ? 'border border-slate-200 bg-white text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300'
                    : 'bg-[#070740]/[0.07] text-[#070740] dark:bg-white/10 dark:text-white' }}">
                    <i class="fas {{ $isCod ? 'fa-truck' : 'fa-credit-card' }} text-sm" aria-hidden="true"></i>
                </span>

                <span class="min-w-0 flex-1">
                    <span class="flex min-w-0 flex-wrap items-center justify-between gap-x-2 gap-y-1">
                        <span class="min-w-0 font-semibold leading-5 text-slate-950 dark:text-white">{{ __($method['label']) }}</span>

                        @if($method['coming_soon'] ?? false)
                            <span class="shrink-0 whitespace-nowrap rounded-full border border-[#FF6A00]/20 bg-[#FF6A00]/[0.08] px-2 py-0.5 text-[9px] font-bold uppercase leading-4 tracking-[0.08em] text-[#C85100] dark:border-orange-400/25 dark:bg-orange-400/10 dark:text-orange-300">
                                {{ __('Coming Soon') }}
                            </span>
                        @elseif($isSelected)
                            <span aria-hidden="true" class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#070740] text-white dark:bg-white dark:text-[#070740]">
                                <i class="fas fa-check text-[10px]" aria-hidden="true"></i>
                            </span>
                        @endif
                    </span>

                    <span class="mt-1 block text-xs leading-4 text-slate-500 dark:text-slate-400">{{ __($description) }}</span>
                </span>
            </label>
        @endforeach
    </div>

    @error('payment_method')
        <p class="mt-2 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
    @enderror
</div>
