@php
    $welcomeCurrency = (string) data_get($settings, 'currency_code', 'IQD');
    $welcomeState = function (bool $useOld) use ($settings): array {
        $read = fn ($key, $default) => $useOld ? old('welcome_offer_'.$key, data_get($settings, 'welcome_offer_'.$key, $default)) : data_get($settings, 'welcome_offer_'.$key, $default);
        $type = (string) $read('type', 'percent');
        return ['enabled' => (string) $read('enabled', '0') === '1', 'type' => $type,
            'value' => $type === 'free_shipping' ? 0 : (float) $read('value', 20),
            'minimum' => (float) $read('minimum_subtotal', 0), 'maximum' => (float) $read('maximum_discount', 0), 'days' => (int) $read('valid_days', 30)];
    };
    $welcomeFields = $welcomeState(true);
    $welcomeConfig = ['fields' => $welcomeFields, 'saved' => $welcomeState(false), 'currency' => $welcomeCurrency,
        'labels' => ['percent' => __('welcome.preview_percent'), 'fixed' => __('welcome.preview_fixed'), 'free_shipping' => __('welcome.preview_shipping'),
            'days' => __('welcome.preview_days'), 'noExpiry' => __('welcome.no_expiry'), 'minimum' => __('welcome.preview_minimum'), 'noMinimum' => __('welcome.no_minimum'),
            'active' => __('Active'), 'paused' => __('Paused'), 'unsaved' => __('welcome.unsaved'), 'saved' => __('welcome.up_to_date'),
            'pauseNote' => __('welcome.pause_note'), 'belowMinimum' => __('welcome.below_minimum'), 'automatic' => __('welcome.automatic')]];
@endphp

<div id="welcome-offer" x-data="welcomeOfferEditor" data-config="{{ json_encode($welcomeConfig) }}">
    <header class="wo-heading">
        <div>
            <a href="{{ route('admin.discounts.edit') }}" class="wo-back"><span aria-hidden="true">&lsaquo;</span> {{ __('Coupon Management') }}</a>
            <p class="wo-eyebrow">{{ __('welcome.growth') }}</p>
            <h1>{{ __('welcome.nav') }}</h1>
            <p class="wo-description">{{ __('welcome.page_intro') }}</p>
        </div>
        <div class="wo-header-mark" aria-hidden="true"><x-ph-icon name="star" :size="34" /></div>
    </header>

    <div class="wo-stats">
        @foreach (['issued' => ['users-three', __('welcome.issued')], 'redeemed' => ['ticket', __('welcome.redeemed')], 'savings' => ['coins', __('welcome.savings')]] as $stat => [$icon, $label])
            <article class="wo-stat">
                <span class="wo-stat-icon"><x-ph-icon :name="$icon" :size="22" /></span>
                <div><p>{{ $label }}</p><strong>{{ number_format((float) ($welcomeStats[$stat] ?? 0), 0) }}@if($stat === 'savings') <small>{{ $welcomeCurrency }}</small>@endif</strong></div>
            </article>
        @endforeach
    </div>
    <p class="wo-stats-note">{{ __('welcome.stats_note') }}</p>

    <form action="{{ route('admin.discounts.welcome-offer.update') }}" method="POST" class="wo-editor">
        @csrf
        @method('PUT')
        <div class="wo-workspace">
            <div class="wo-main">
                <section class="wo-panel wo-activation">
                    <div class="wo-activation-copy"><span class="wo-icon"><x-ph-icon name="megaphone-simple" :size="24" /></span><div><h2>{{ __('welcome.campaign_status') }}</h2><p>{{ __('welcome.status_help') }}</p></div></div>
                    <input type="hidden" name="welcome_offer_enabled" value="0">
                    <label class="wo-toggle">
                        <span x-text="statusLabel">{{ $welcomeFields['enabled'] ? __('Active') : __('Paused') }}</span>
                        <input type="checkbox" name="welcome_offer_enabled" value="1" x-model="enabled" @checked($welcomeFields['enabled']) aria-label="{{ __('welcome.enable') }}">
                        <span class="wo-switch" aria-hidden="true"></span>
                    </label>
                </section>

                <section class="wo-panel">
                    <div class="wo-section-title"><span>01</span><div><h2>{{ __('welcome.choose_reward') }}</h2><p>{{ __('welcome.choose_help') }}</p></div></div>
                    <fieldset class="wo-choices">
                        <legend class="sr-only">{{ __('welcome.type') }}</legend>
                        @foreach (['percent' => 'percent', 'fixed' => 'coins', 'free_shipping' => 'truck'] as $type => $icon)
                            <label class="wo-choice">
                                <input type="radio" name="welcome_offer_type" value="{{ $type }}" x-model="type" @change="changeType" @checked($welcomeFields['type'] === $type)>
                                <span class="wo-choice-icon"><x-ph-icon :name="$icon" :size="26" /></span>
                                <strong>{{ __('welcome.type_'.$type) }}</strong>
                                <span class="wo-choice-help">{{ __('welcome.choice_'.$type) }}</span>
                                <span class="wo-choice-check" aria-hidden="true">✓</span>
                            </label>
                        @endforeach
                    </fieldset>
                    @error('welcome_offer_type')<p class="wo-error">{{ $message }}</p>@enderror

                    <div class="wo-value-area" x-show="showDiscount">
                        <div>
                            <label for="welcome_offer_value" class="wo-label">{{ __('welcome.value') }}</label>
                            <div class="wo-value-input"><input id="welcome_offer_value" name="welcome_offer_value" type="number" min="0.01" :max="valueMax" step="0.01" :required="showDiscount" :disabled="isShipping" x-model="value" value="{{ $welcomeFields['value'] }}" aria-describedby="wo-value-help"><span x-text="valueUnit">{{ $welcomeFields['type'] === 'percent' ? '%' : $welcomeCurrency }}</span></div>
                            <p id="wo-value-help" class="wo-help">{{ __('welcome.value_hint') }}</p>
                            @error('welcome_offer_value')<p class="wo-error">{{ $message }}</p>@enderror
                        </div>
                        <div x-show="isPercent" class="wo-presets"><span>{{ __('welcome.quick_select') }}</span><div>@foreach ([10, 15, 20, 25] as $preset)<button type="button" data-value="{{ $preset }}" @click="usePreset">{{ $preset }}%</button>@endforeach</div></div>
                    </div>
                    <input type="hidden" name="welcome_offer_value" value="0" :disabled="showDiscount" @disabled($welcomeFields['type'] !== 'free_shipping')>
                    <div class="wo-shipping-note" x-show="isShipping" x-cloak><x-ph-icon name="truck" :size="26" /><div><strong>{{ __('welcome.shipping_title') }}</strong><p>{{ __('welcome.shipping_help') }}</p></div></div>
                </section>

                <section class="wo-panel">
                    <div class="wo-section-title"><span>02</span><div><h2>{{ __('welcome.set_conditions') }}</h2><p>{{ __('welcome.conditions_help') }}</p></div></div>
                    <div class="wo-fields">
                        @foreach (['minimum_subtotal' => ['minimum', 0, $welcomeCurrency], 'maximum_discount' => ['maximum', 0, $welcomeCurrency], 'valid_days' => ['days', 30, __('welcome.days_unit')]] as $field => [$model, $default, $unit])
                            <div @if($field === 'maximum_discount') x-show="showDiscount" @endif>
                                <label for="welcome_offer_{{ $field }}" class="wo-label">{{ __('welcome.'.$field) }}</label>
                                <div class="wo-input-wrap"><input id="welcome_offer_{{ $field }}" name="welcome_offer_{{ $field }}" type="number" min="0" max="{{ $field === 'valid_days' ? 365 : 999999999 }}" step="{{ $field === 'valid_days' ? 1 : '0.01' }}" required x-model="{{ $model }}" value="{{ $welcomeFields[$model] }}" aria-describedby="wo-help-{{ $field }}"><span>{{ $unit }}</span></div>
                                <p id="wo-help-{{ $field }}" class="wo-help">{{ __('welcome.'.$field.'_help') }}</p>
                                @error('welcome_offer_'.$field)<p class="wo-error">{{ $message }}</p>@enderror
                            </div>
                        @endforeach
                    </div>
                </section>

                <details class="wo-panel wo-rules">
                    <summary><x-ph-icon name="list-checks" :size="21" /><span>{{ __('welcome.rules_title') }}</span><span class="wo-plus" aria-hidden="true">+</span></summary>
                    <div class="wo-rule-list">@foreach (['rule_new', 'rule_once', 'rule_coupon', 'rule_changes'] as $rule)<p><span aria-hidden="true">✓</span>{{ __('welcome.'.$rule) }}</p>@endforeach</div>
                </details>
            </div>

            <aside class="wo-preview" aria-label="{{ __('welcome.preview') }}">
                <div class="wo-preview-label"><span class="wo-live-dot" aria-hidden="true"></span>{{ __('welcome.preview') }}<span>{{ __('welcome.live') }}</span></div>
                <div class="wo-ticket">
                    <div class="wo-ticket-top"><span>YALLASPARE</span><x-ph-icon name="star" :size="24" /></div>
                    <p class="wo-ticket-kicker">{{ __('welcome.first_order') }}</p>
                    <h2 x-text="offerHeadline">{{ __('welcome.preview_percent', ['value' => $welcomeFields['value']]) }}</h2>
                    <p class="wo-ticket-subtitle">{{ __('welcome.ticket_subtitle') }}</p>
                    <div class="wo-ticket-divider"></div>
                    <div class="wo-ticket-terms"><span x-text="minimumLabel"></span><span x-text="durationLabel"></span></div>
                    <span class="wo-auto-badge">{{ __('welcome.no_code') }}</span>
                </div>

                <section class="wo-receipt">
                    <h3>{{ __('welcome.try_order') }}</h3><p class="wo-help">{{ __('welcome.example_help') }}</p>
                    <div class="wo-example-fields">
                        <div><label for="wo-example-subtotal">{{ __('Subtotal') }}</label><input id="wo-example-subtotal" type="number" min="0" step="1000" max="999999999" x-model="exampleSubtotal" value="50000"></div>
                        <div><label for="wo-example-shipping">{{ __('Shipping') }}</label><input id="wo-example-shipping" type="number" min="0" step="1000" max="999999999" x-model="exampleShipping" value="5000"></div>
                    </div>
                    <dl aria-live="polite" aria-atomic="true">
                        <div><dt>{{ __('Subtotal') }}</dt><dd x-text="subtotalLabel"></dd></div>
                        <div><dt>{{ __('Shipping') }}</dt><dd x-text="shippingLabel"></dd></div>
                        <div class="wo-saving"><dt>{{ __('welcome.reward') }}</dt><dd x-text="discountLabel"></dd></div>
                        <div class="wo-total"><dt>{{ __('Total') }}</dt><dd x-text="totalLabel"></dd></div>
                    </dl>
                    <p class="wo-preview-note" x-text="previewMessage"></p>
                </section>
            </aside>
        </div>
        <footer class="wo-savebar">
            <div><span class="wo-save-dot" :class="{ 'wo-is-dirty': dirty }" aria-hidden="true"></span><span x-text="saveState" role="status">{{ __('welcome.up_to_date') }}</span></div>
            <button type="submit" class="wo-save"><x-ph-icon name="list-checks" :size="19" />{{ __('welcome.save') }}</button>
        </footer>
    </form>
</div>
