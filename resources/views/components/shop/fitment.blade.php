@props(['board'])

@php
    /** @var \App\Support\Vehicle\FitmentBoard $board */
    $whatsappNumber = preg_replace('/\D+/', '', (string) config('services.otpiq.whatsapp.contact_number', ''));
    $whatsappUrl = $whatsappNumber === '' || ! config('services.otpiq.whatsapp.user_visible', false)
        ? null
        : 'https://wa.me/'.$whatsappNumber
            .'?text='.rawurlencode(__('Hello, I would like to check whether this part fits my car. My chassis number is: '));
@endphp

{{--
    Direction is never set here. The page already carries it on <html> from the
    active locale, and a component that decided for itself would be right in one
    language and wrong in the other two. What this file does instead is isolate
    the runs that must not be reordered — a year range, a displacement — so they
    read the same way whichever direction they sit in.
--}}
<section data-product-compatibility class="fitment-board rounded-2xl border border-app bg-surface-2 p-5">
    <header class="flex items-start gap-3">
        <span class="fitment-board-mark" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="h-[18px] w-[18px]">
                <path d="M3 13.5 4.7 8.4A2.6 2.6 0 0 1 7.2 6.6h9.6a2.6 2.6 0 0 1 2.5 1.8L21 13.5" />
                <path d="M3 13.5h18v3.6a1 1 0 0 1-1 1h-1.6a1 1 0 0 1-1-1v-.9H6.6v.9a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1z" />
            </svg>
        </span>
        <div class="min-w-0">
            <h2 class="fitment-board-title">{{ __('Vehicle Compatibility') }}</h2>
            <p class="fitment-board-lede">{{ __('This part is compatible with the vehicle configurations listed below.') }}</p>
            {{-- Each count is its own isolated run, so a leading numeral cannot
                 drift down the line and land beside the wrong words. --}}
            <p class="fitment-board-count">
                <bdi>{{ trans_choice(':count vehicle family|:count vehicle families', $board->familyCount(), ['count' => $board->familyCount()]) }}</bdi>
                <span class="fitment-dot" aria-hidden="true">·</span>
                <bdi>{{ trans_choice(':count compatible configuration|:count compatible configurations', $board->configurationCount, ['count' => $board->configurationCount]) }}</bdi>
            </p>
        </div>
    </header>

    <div class="fitment-families">
        @foreach($board->families as $family)
            <article class="fitment-family">
                <h3 class="fitment-family-name"><bdi>{{ $family['name'] }}</bdi></h3>

                <ul class="fitment-grid" role="list">
                    @foreach($family['configurations'] as $configuration)
                        <li class="fitment-card @unless($configuration['complete']) is-partial @endunless">
                            <h4 class="fitment-card-name"><bdi>{{ $configuration['variant'] }}</bdi></h4>
                            <p class="fitment-card-years"><bdi>{{ $configuration['years'] }}</bdi></p>

                            {{-- The engines this part was recorded against for
                                 this car. Each is one option on the same
                                 vehicle, so they sit inside the card rather
                                 than each getting a card of their own. --}}
                            @if($configuration['engines']->isNotEmpty())
                                <ul class="fitment-engines" role="list">
                                    @foreach($configuration['engines'] as $engine)
                                        <li class="fitment-chips">
                                            @if($engine['displacement'] !== '')
                                                {{-- A displacement reads left to right in every language. --}}
                                                <span class="fitment-chip strong" dir="ltr">{{ $engine['displacement'] }}L</span>
                                            @endif
                                            @if($engine['aspiration'] !== '')
                                                <span class="fitment-chip">{{ $engine['aspiration'] }}</span>
                                            @endif
                                            @if($engine['fuel'] !== '')
                                                <span class="fitment-chip">{{ $engine['fuel'] }}</span>
                                            @endif
                                            @if($engine['displacement'] === '' && $engine['fuel'] === '')
                                                {{-- Free text with no structured columns behind it: shown
                                                     exactly as recorded rather than re-read for parts. --}}
                                                <span class="fitment-chip"><bdi>{{ $engine['label'] }}</bdi></span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <div class="fitment-chips">
                                    <span class="fitment-chip muted">{{ __('Engine not recorded') }}</span>
                                </div>
                            @endif

                            @if($configuration['notes'] !== '')
                                <p class="fitment-card-note"><bdi>{{ $configuration['notes'] }}</bdi></p>
                            @endif

                            @if($configuration['complete'])
                                <p class="fitment-card-status is-confirmed">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3 shrink-0" aria-hidden="true">
                                        <path d="m5 12.5 4.5 4.5L19 7.5" />
                                    </svg>
                                    {{ __('Confirmed fit for this part') }}
                                </p>
                            @else
                                {{-- Not a fit that has been ruled out — a record that does not
                                     say which car it means. Marking it confirmed would be a
                                     promise the data cannot keep, so it points at the chassis
                                     check below instead. --}}
                                <p class="fitment-card-status is-partial">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3 shrink-0" aria-hidden="true">
                                        <circle cx="12" cy="12" r="8.5" />
                                        <path d="M12 8v5M12 16.2v.01" />
                                    </svg>
                                    {{ __('Compatibility details incomplete') }}
                                </p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </article>
        @endforeach
    </div>

    <div class="fitment-chassis">
        <div class="min-w-0">
            <p class="fitment-chassis-title">{{ __('Not sure which one you have?') }}</p>
            <p class="fitment-chassis-copy">{{ __('Send us your chassis number and we will confirm the fit for you.') }}</p>
        </div>
        @if($whatsappUrl)
            <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="fitment-chassis-action">
                <svg viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4 shrink-0" aria-hidden="true">
                    <path d="M12 3.2A8.8 8.8 0 0 0 4.5 16l-1.2 4.8 5-1.2A8.8 8.8 0 1 0 12 3.2Zm0 15.8a7 7 0 0 1-3.5-.9l-.3-.2-2.9.7.7-2.8-.2-.3A7 7 0 1 1 12 19Zm3.9-5.2c-.2-.1-1.2-.6-1.4-.7-.2-.1-.3-.1-.5.1l-.4.5c-.1.2-.3.2-.4.1a5.8 5.8 0 0 1-1.7-1.1 6.6 6.6 0 0 1-1.2-1.5c-.1-.2 0-.3.1-.4l.3-.4.2-.3c.1-.1 0-.3 0-.4l-.6-1.4c-.2-.4-.3-.4-.5-.4h-.4a.8.8 0 0 0-.6.3c-.2.2-.8.8-.8 1.9s.8 2.3.9 2.4c.1.2 1.5 2.3 3.6 3.1.5.2 1 .4 1.3.5.5.2 1 .1 1.4.1.4-.1 1.2-.5 1.4-.9.2-.4.2-.8.1-.9 0-.1-.2-.2-.4-.3Z"/>
                </svg>
                {{ __('Check with chassis number') }}
            </a>
        @endif
    </div>
</section>

@once
    @push('head')
        <style>
            /* Colour carries meaning here and nothing else: the accent marks a
               fit that is really confirmed, and a card that cannot say that much
               is left plain so it is not mistaken for one. */
            .fitment-board {
                --fit-navy: #070740;
                --fit-accent: #ff6a00;
                --fit-soft: #e6e8ec;
            }

            .fitment-board-mark {
                display: grid;
                place-items: center;
                height: 2.25rem;
                width: 2.25rem;
                flex-shrink: 0;
                border-radius: 0.7rem;
                background: var(--fit-navy);
                color: #fff;
            }

            .fitment-board-title {
                font-size: 17px;
                font-weight: 650;
                letter-spacing: -0.02em;
                color: var(--fit-navy);
            }

            .fitment-board-lede {
                margin-top: 0.15rem;
                font-size: 12.5px;
                line-height: 1.5;
                color: #5a5a78;
            }

            .fitment-board-count {
                margin-top: 0.35rem;
                font-size: 11.5px;
                font-weight: 600;
                color: #6b6b85;
            }

            .fitment-dot { margin: 0 0.15rem; }

            .fitment-families { margin-top: 1rem; }

            .fitment-family + .fitment-family {
                margin-top: 0.75rem;
                padding-top: 0.75rem;
                border-top: 1px solid var(--fit-soft);
            }

            .fitment-family-name {
                font-size: 11.5px;
                font-weight: 700;
                letter-spacing: 0.1em;
                text-transform: uppercase;
                color: var(--fit-navy);
            }

            /* One column on a phone, two from tablet up. Three made every card
               too narrow for a model name to sit on one line. */
            .fitment-grid {
                display: grid;
                gap: 0.5rem;
                margin-top: 0.5rem;
                grid-template-columns: 1fr;
            }

            @media (min-width: 640px) {
                .fitment-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            }

            .fitment-card {
                padding: 0.625rem 0.75rem;
                border: 1px solid var(--fit-soft);
                border-radius: 0.75rem;
                background: #fff;
                transition: border-color 180ms ease;
            }

            .fitment-card:hover { border-color: rgba(7, 7, 64, 0.22); }

            .fitment-card-name {
                font-size: 14.5px;
                font-weight: 700;
                letter-spacing: -0.01em;
                line-height: 1.2;
                color: var(--fit-navy);
                overflow-wrap: anywhere;
            }

            .fitment-card-years {
                margin-top: 0.1rem;
                font-size: 12.5px;
                font-weight: 600;
                font-variant-numeric: tabular-nums;
                color: #55557a;
            }

            .fitment-engines {
                display: flex;
                flex-direction: column;
                gap: 0.25rem;
                margin-top: 0.4rem;
            }

            .fitment-engines .fitment-chips { margin-top: 0; }

            .fitment-chips {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.3rem;
                margin-top: 0.4rem;
            }

            .fitment-chip {
                display: inline-flex;
                align-items: center;
                border-radius: 999px;
                border: 1px solid var(--fit-soft);
                background: #f7f8fa;
                padding: 0.1rem 0.45rem;
                font-size: 11.5px;
                font-weight: 600;
                color: #3c3c5c;
            }

            .fitment-chip.strong {
                border-color: rgba(7, 7, 64, 0.16);
                background: rgba(7, 7, 64, 0.055);
                color: var(--fit-navy);
                font-variant-numeric: tabular-nums;
            }

            .fitment-chip.muted {
                border-style: dashed;
                background: transparent;
                color: #6b6b85;
                font-weight: 500;
            }

            .fitment-card-note {
                margin-top: 0.35rem;
                font-size: 11.5px;
                line-height: 1.4;
                color: #5a5a78;
            }

            .fitment-card-status {
                display: inline-flex;
                align-items: center;
                gap: 0.3rem;
                margin-top: 0.4rem;
                font-size: 11px;
                font-weight: 600;
            }

            .fitment-card-status.is-confirmed { color: var(--fit-accent); }

            .fitment-card-status.is-partial {
                color: #6b6b85;
                font-weight: 500;
            }

            .fitment-card.is-partial { background: #fbfbfd; }

            .fitment-chassis {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.625rem;
                margin-top: 1rem;
                padding: 0.75rem 0.875rem;
                border-radius: 0.75rem;
                background: var(--fit-navy);
                color: #fff;
            }

            .fitment-chassis-title {
                font-size: 13px;
                font-weight: 700;
            }

            .fitment-chassis-copy {
                margin-top: 0.1rem;
                font-size: 12px;
                line-height: 1.4;
                color: rgba(255, 255, 255, 0.72);
            }

            .fitment-chassis-action {
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                border-radius: 0.6rem;
                background: var(--fit-accent);
                padding: 0.4rem 0.8rem;
                font-size: 12px;
                font-weight: 700;
                color: #fff;
                white-space: nowrap;
                transition: filter 160ms ease;
            }

            .fitment-chassis-action:hover { filter: brightness(1.08); }

            .fitment-chassis-action:focus-visible {
                outline: 2px solid #fff;
                outline-offset: 2px;
            }

            /* Dark mode keeps the same reading order; only the ground changes. */
            .dark .fitment-board { --fit-soft: rgba(148, 163, 184, 0.22); }
            .dark .fitment-board-mark { background: var(--fit-accent); color: #070740; }
            .dark .fitment-board-title,
            .dark .fitment-family-name { color: #eef0f8; }
            .dark .fitment-board-lede,
            .dark .fitment-board-count { color: #a8aec4; }
            .dark .fitment-card { background: rgba(15, 23, 42, 0.55); }
            .dark .fitment-card.is-partial { background: rgba(15, 23, 42, 0.32); }
            .dark .fitment-card:hover { border-color: rgba(255, 106, 0, 0.35); }
            .dark .fitment-card-name { color: #f1f3f9; }
            .dark .fitment-card-years { color: #b7bdd0; }
            .dark .fitment-chip { background: rgba(148, 163, 184, 0.1); color: #d5d9e6; }
            .dark .fitment-chip.strong { background: rgba(255, 106, 0, 0.12); border-color: rgba(255, 106, 0, 0.3); color: #ffb27a; }
            .dark .fitment-chip.muted,
            .dark .fitment-card-status.is-partial { color: #99a0b8; }
            .dark .fitment-card-note { color: #a8aec4; }
            .dark .fitment-chassis { background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(148, 163, 184, 0.18); }

            @media (prefers-reduced-motion: reduce) {
                .fitment-card,
                .fitment-chassis-action { transition: none; }
            }
        </style>
    @endpush
@endonce
