@php
    $enabledPaymentMethodKeys = collect($paymentMethods)
        ->filter(fn (array $method): bool => (bool) ($method['enabled'] ?? false))
        ->pluck('key');
    $selectedPaymentMethod = old('payment_method', 'cash_on_delivery');

    if (! $enabledPaymentMethodKeys->contains($selectedPaymentMethod)) {
        $selectedPaymentMethod = 'cash_on_delivery';
    }
@endphp

{{--
    One card per row, always stacked. The sidebar this renders into on
    checkout-review is 22rem wide, so a viewport-based two-column grid split it
    into ~150px columns and broke "Cash on Delivery" across three lines.
--}}
<fieldset class="m-0 min-w-0 border-0 p-0">
    <legend class="p-0 text-[11px] font-bold uppercase tracking-[0.14em] text-[var(--text-muted)]">
        {{ __('Payment Method') }}
    </legend>

    <div class="mt-3 grid gap-3">
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
                @if($isDisabled) aria-disabled="true" @endif
                class="group relative flex min-h-[92px] min-w-0 items-center gap-3.5 overflow-hidden rounded-2xl border px-4 py-4 transition duration-200 motion-reduce:transition-none {{ $isDisabled
                    ? 'cursor-not-allowed border-dashed border-[var(--border)] bg-[var(--surface-sunk)]'
                    : 'cursor-pointer border-[var(--border)] bg-[var(--surface)] hover:border-[rgb(var(--text-muted-rgb)/0.5)] hover:shadow-[0_6px_18px_-8px_rgb(var(--primary-solid-rgb)/0.3)] has-[:checked]:border-accent/45 has-[:checked]:bg-accent/[0.035] dark:has-[:checked]:bg-accent/[0.07] has-[:focus-visible]:outline has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-offset-2 has-[:focus-visible]:outline-accent' }}"
            >
                <input
                    type="radio"
                    name="payment_method"
                    value="{{ $method['key'] }}"
                    @checked($isSelected)
                    @disabled($isDisabled)
                    class="sr-only"
                >

                @unless($isDisabled)
                    <span
                        aria-hidden="true"
                        class="absolute inset-y-0 start-0 w-[3px] bg-accent opacity-0 transition-opacity duration-200 group-has-[:checked]:opacity-100 motion-reduce:transition-none"
                    ></span>
                @endunless

                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl transition duration-200 motion-reduce:transition-none {{ $isDisabled
                    ? 'border border-[var(--border)] text-[var(--text-muted)]'
                    : 'bg-[var(--surface-sunk)] text-primary group-has-[:checked]:bg-accent/10 group-has-[:checked]:text-accent-ink' }}">
                    {{-- Inline SVG, like the rest of the storefront: layouts/user does not load Font Awesome. --}}
                    @if($isCod)
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M3 7a1 1 0 0 1 1-1h9a1 1 0 0 1 1 1v9H3z" />
                            <path d="M14 10h3.2a1 1 0 0 1 .8.4l2 2.6a1 1 0 0 1 .2.6V16h-6z" />
                            <circle cx="7" cy="18" r="2" />
                            <circle cx="17" cy="18" r="2" />
                        </svg>
                    @else
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="2.5" y="5" width="19" height="14" rx="2.5" />
                            <path d="M2.5 10h19" />
                            <path d="M6.5 15h3" />
                        </svg>
                    @endif
                </span>

                <span class="min-w-0 flex-1">
                    <span class="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1">
                        <span class="min-w-0 text-[15px] font-semibold leading-tight text-[var(--text)]">
                            {{ __($method['label']) }}
                        </span>

                        @if($method['coming_soon'] ?? false)
                            <span class="shrink-0 whitespace-nowrap rounded-full border border-[var(--border)] bg-[var(--surface)] px-2.5 py-0.5 text-[10px] font-bold uppercase leading-4 tracking-[0.09em] text-[var(--text-muted)]">
                                {{ __('Coming Soon') }}
                            </span>
                        @endif
                    </span>

                    <span class="mt-1 block text-[13px] leading-snug text-[var(--text-secondary)]">
                        {{ __($description) }}
                    </span>

                    {{-- --text-muted only clears 3.9:1 on the sunk surface, so the disabled card steps up a level. --}}
                    <span class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-[11.5px] font-semibold {{ $isDisabled ? 'text-[var(--text-secondary)]' : 'text-[var(--text-muted)]' }}">
                        <span class="inline-flex items-center gap-1.5">
                            <span aria-hidden="true" class="h-1.5 w-1.5 shrink-0 rounded-full bg-current"></span>
                            {{ $isDisabled ? __('Not available yet') : __('Available now') }}
                        </span>

                        @unless($isDisabled)
                            {{-- Text cue so the selected state never rests on colour alone. --}}
                            <span class="hidden text-[var(--text-secondary)] group-has-[:checked]:inline">
                                &middot; {{ __('Selected') }}
                            </span>
                        @endunless
                    </span>
                </span>

                @unless($isDisabled)
                    <span
                        aria-hidden="true"
                        class="inline-flex h-[22px] w-[22px] shrink-0 items-center justify-center rounded-full border-[1.5px] border-[var(--border)] text-transparent transition duration-200 group-has-[:checked]:border-accent group-has-[:checked]:bg-accent group-has-[:checked]:text-navy-deep motion-reduce:transition-none"
                    >
                        <svg class="h-2.5 w-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M4.5 12.5 9.5 17.5 19.5 7" />
                        </svg>
                    </span>
                @endunless
            </label>
        @endforeach
    </div>

    @error('payment_method')
        <p class="mt-2 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
    @enderror
</fieldset>
