@php
    $settingsLogoUrl = \App\Support\Branding::logoUrlFromValue((string) ($settings['site_logo'] ?? ''));
    $storefrontHeroVideo = trim((string) ($settings['storefront_hero_video'] ?? ''));
    $storefrontHeroVideoUrl = $storefrontHeroVideo !== '' && \Illuminate\Support\Facades\Storage::disk('public')->exists($storefrontHeroVideo)
        ? asset('storage/' . $storefrontHeroVideo)
        : null;

    $checks = $productionChecks ?? [];
    $checksPassed = collect($checks)->where('ok', true)->count();
    $checksAction = count($checks) - $checksPassed;

    $inputClasses = 'w-full rounded-lg border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/30 dark:border-slate-700';
    $labelClasses = 'mb-1.5 block text-[11px] font-bold uppercase tracking-widest text-slate-500';
    $hintClasses = 'mt-1 text-xs text-slate-500';
    $whatsappAdminVisible = (bool) config('services.otpiq.whatsapp.admin_visible', true);

    $sections = [
        'branding' => __('Branding'),
        'hero' => __('Storefront Hero'),
        'currency' => __('Currency'),
        'operations' => __('Operations Defaults'),
        'providers' => __('Notification Providers'),
        'templates-en' => __('English Notification Templates'),
        'templates-locale' => __('Arabic & Kurdish Notification Templates'),
    ];

    $sectionFields = [
        'branding' => ['site_name', 'site_logo'],
        'hero' => ['storefront_hero_video'],
        'currency' => ['currency_code', 'currency_symbol'],
        'operations' => ['shipping_fee', 'low_stock_threshold'],
        'providers' => $whatsappAdminVisible
            ? ['sms_provider_webhook_url', 'whatsapp_provider_webhook_url']
            : ['sms_provider_webhook_url'],
        'templates-en' => [
            'notification_order_placed_en_subject', 'notification_order_placed_en_body',
            'notification_order_status_updated_en_subject', 'notification_order_status_updated_en_body',
        ],
        'templates-locale' => [
            'notification_order_placed_ar_subject', 'notification_order_placed_ar_body',
            'notification_order_status_updated_ar_subject', 'notification_order_status_updated_ar_body',
            'notification_order_placed_ku_subject', 'notification_order_placed_ku_body',
            'notification_order_status_updated_ku_subject', 'notification_order_status_updated_ku_body',
        ],
    ];

    $activeSection = 'branding';
    foreach ($sectionFields as $sectionKey => $fields) {
        if ($errors->hasAny($fields)) {
            $activeSection = $sectionKey;
            break;
        }
    }
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">{{ __('System Settings') }}</h2>
                <p class="text-sm text-slate-500 mt-1">{{ __('Configure branding, currency, and inventory defaults.') }}</p>
            </div>
            <span class="inline-flex items-center gap-2 rounded-full border border-emerald-300 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 dark:border-emerald-400/40">
                <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                {{ __('Settings are saved securely') }}
            </span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            @if(session('success'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/20 dark:text-red-300">
                    {{ $errors->first() }}
                </div>
            @endif

            <div
                x-data="settingsConsole"
                data-initial-section="{{ $activeSection }}"
                class="rounded-xl border border-slate-200 bg-white shadow-sm"
            >
                {{-- Header row: title + compact stat pills --}}
                <div class="flex flex-col gap-4 border-b border-slate-200 px-5 py-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-widest text-slate-500">{{ __('System Settings') }}</p>
                        <p class="mt-1 max-w-lg text-xs text-slate-500">{{ __('Configure branding, currency, and inventory defaults.') }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <div class="min-w-[92px] rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-muted dark:text-slate-500">{{ __('Checks passed') }}</p>
                            <p class="mt-0.5 text-base font-bold tabular-nums text-emerald-600">{{ $checksPassed }}<span class="text-xs font-bold text-muted dark:text-slate-500"> / {{ count($checks) }}</span></p>
                        </div>
                        <div class="min-w-[92px] rounded-lg border {{ $checksAction > 0 ? 'border-rose-300/70 dark:border-rose-400/35' : 'border-slate-200 dark:border-slate-700' }} bg-slate-50 px-3 py-1.5">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-muted dark:text-slate-500">{{ __('Action needed') }}</p>
                            <p class="mt-0.5 text-base font-bold tabular-nums {{ $checksAction > 0 ? 'text-rose-600 dark:text-rose-300' : 'text-slate-900 dark:text-white' }}">{{ $checksAction }}</p>
                        </div>
                        <div class="min-w-[92px] rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-muted dark:text-slate-500">{{ __('Currency') }}</p>
                            <p class="mt-0.5 text-base font-bold text-slate-900">{{ $settings['currency_symbol'] ?? 'IQD' }}</p>
                        </div>
                        <div class="min-w-[92px] rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-muted dark:text-slate-500">{{ __('Shipping Fee') }}</p>
                            <p class="mt-0.5 text-base font-bold tabular-nums text-slate-900">{{ number_format((float) ($settings['shipping_fee'] ?? 5000), 0) }}</p>
                        </div>
                    </div>
                </div>

                {{-- Section tabs --}}
                <div class="flex gap-1 overflow-x-auto border-b border-slate-200 px-5" role="tablist">
                    @foreach($sections as $key => $label)
                        <button
                            type="button"
                            role="tab"
                            @click="setActive('{{ $key }}')"
                            :class="tabClass('{{ $key }}')"
                            class="whitespace-nowrap border-b-2 px-3 py-3 text-sm font-bold transition"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                <div class="flex">
                    {{-- Form canvas --}}
                    <form
                        method="POST"
                        action="{{ route('admin.settings.update') }}"
                        enctype="multipart/form-data"
                        class="min-w-0 flex-1 px-5 py-6"
                        data-admin-settings-form
                        data-loading-form
                        data-loading-button-text="Saving..."
                    >
                        @csrf
                        @method('PUT')

                        <div class="max-w-2xl" x-show="isActive('branding')" x-cloak>
                            <p class="text-lg font-bold text-slate-800">{{ __('Branding') }}</p>
                            <p class="mt-1 mb-5 text-xs text-slate-500">{{ __('Manage site identity and visuals that appear across the storefront.') }}</p>

                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label for="site_name" class="{{ $labelClasses }}">{{ __('Site Name') }}</label>
                                    <input id="site_name" type="text" name="site_name" value="{{ old('site_name', $settings['site_name'] ?? '') }}" class="{{ $inputClasses }}" required>
                                </div>

                                <div>
                                    <label for="site_logo" class="{{ $labelClasses }}">{{ __('Site Logo') }}</label>
                                    <input id="site_logo" type="file" name="site_logo" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp" class="{{ $inputClasses }}">
                                    <p class="{{ $hintClasses }}">{{ __('Transparent PNG or WEBP recommended. If the uploaded logo has a white outer background, the system will try to save it as a transparent PNG. Max 8MB.') }}</p>
                                    @error('site_logo')
                                        <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                    @enderror
                                    @if(!empty($settings['site_logo']))
                                        <label for="storefront_hero_video" class="mt-2 inline-flex items-center gap-2 text-xs text-slate-600">
                                            <input type="checkbox" name="remove_logo" value="1" class="rounded border-slate-300 text-muted focus:ring-accent dark:border-slate-700 dark:text-slate-500">
                                            {{ __('Remove current logo') }}
                                        </label>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="max-w-2xl" x-show="isActive('hero')" x-cloak>
                            <p class="text-lg font-bold text-slate-800">{{ __('Storefront Hero') }}</p>
                            <p class="mt-1 mb-5 text-xs text-slate-500">{{ __('Upload the video shown on the customer home page hero.') }}</p>

                            <label class="{{ $labelClasses }}">{{ __('Hero Video') }}</label>
                            <input id="storefront_hero_video" type="file" name="storefront_hero_video" accept=".mp4,video/mp4" class="{{ $inputClasses }}" data-hero-video-input>
                            <p class="{{ $hintClasses }}">{{ __('MP4 video only. Max 50MB.') }}</p>
                            <p class="mt-1 hidden text-xs font-semibold text-rose-600" data-hero-video-client-error></p>
                            @if($storefrontHeroVideoUrl)
                                <video class="mt-3 h-32 w-full rounded-lg object-cover" muted controls>
                                    <source src="{{ $storefrontHeroVideoUrl }}" type="video/mp4">
                                </video>
                                <label for="currency_code" class="mt-2 inline-flex items-center gap-2 text-xs text-slate-600">
                                    <input type="checkbox" name="remove_storefront_hero_video" value="1" class="rounded border-slate-300 text-muted focus:ring-accent dark:border-slate-700 dark:text-slate-500">
                                    {{ __('Remove current hero video') }}
                                </label>
                            @endif
                            @error('storefront_hero_video')
                                <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="max-w-2xl" x-show="isActive('currency')" x-cloak>
                            <p class="text-lg font-bold text-slate-800">{{ __('Currency') }}</p>
                            <p class="mt-1 mb-5 text-xs text-slate-500">{{ __('Set the currency format shown to customers.') }}</p>

                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label class="{{ $labelClasses }}">{{ __('Currency Code') }}</label>
                                    <input id="currency_code" type="text" name="currency_code" value="{{ old('currency_code', $settings['currency_code'] ?? 'IQD') }}" class="{{ $inputClasses }}" required>
                                    <p class="{{ $hintClasses }}">{{ __('Example: USD, EUR, IQD') }}</p>
                                </div>

                                <div>
                                    <label for="currency_symbol" class="{{ $labelClasses }}">{{ __('Currency Symbol') }}</label>
                                    <input id="currency_symbol" type="text" name="currency_symbol" value="{{ old('currency_symbol', $settings['currency_symbol'] ?? 'IQD') }}" class="{{ $inputClasses }}" required>
                                    <p class="{{ $hintClasses }}">{{ __('Example: IQD, EUR') }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="max-w-2xl" x-show="isActive('operations')" x-cloak>
                            <p class="text-lg font-bold text-slate-800">{{ __('Operations Defaults') }}</p>
                            <p class="mt-1 mb-5 text-xs text-slate-500">{{ __('Configure shipping and inventory defaults used across checkout, invoices, and dashboard.') }}</p>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label for="shipping_fee" class="{{ $labelClasses }}">{{ __('Shipping Fee') }}</label>
                                    <input id="shipping_fee" type="number" name="shipping_fee" min="0" step="0.01" value="{{ old('shipping_fee', (float) ($settings['shipping_fee'] ?? 5000)) }}" class="{{ $inputClasses }}" required>
                                    <p class="{{ $hintClasses }}">{{ __('Used for checkout, order totals, and invoices.') }}</p>
                                </div>

                                <div>
                                    <label for="low_stock_threshold" class="{{ $labelClasses }}">{{ __('Low Stock Threshold') }}</label>
                                    <input id="low_stock_threshold" type="number" name="low_stock_threshold" min="0" value="{{ old('low_stock_threshold', (int) ($settings['low_stock_threshold'] ?? 5)) }}" class="{{ $inputClasses }}" required>
                                    <p class="{{ $hintClasses }}">{{ __('Used across dashboard and products table.') }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="max-w-2xl" x-show="isActive('providers')" x-cloak>
                            <p class="text-lg font-bold text-slate-800">{{ __('Notification Providers') }}</p>
                            <p class="mt-1 mb-5 text-xs text-slate-500">{{ __('Webhook URLs receive recipient, message, and context JSON. Leave empty to keep log transport.') }}</p>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label for="sms_provider_webhook_url" class="{{ $labelClasses }}">{{ __('SMS Webhook URL') }}</label>
                                    <input id="sms_provider_webhook_url" type="url" name="sms_provider_webhook_url" value="{{ old('sms_provider_webhook_url', $settings['sms_provider_webhook_url'] ?? '') }}" class="{{ $inputClasses }}">
                                </div>
                                @if ($whatsappAdminVisible)
                                    <div>
                                        <label for="whatsapp_provider_webhook_url" class="{{ $labelClasses }}">{{ __('WhatsApp Webhook URL') }}</label>
                                        <input id="whatsapp_provider_webhook_url" type="url" name="whatsapp_provider_webhook_url" value="{{ old('whatsapp_provider_webhook_url', $settings['whatsapp_provider_webhook_url'] ?? '') }}" class="{{ $inputClasses }}">
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="max-w-2xl" x-show="isActive('templates-en')" x-cloak>
                            <p class="text-lg font-bold text-slate-800">{{ __('English Notification Templates') }}</p>
                            <p class="mt-1 mb-5 text-xs text-slate-500">
                                {{ __('Available placeholders:') }}
                                <code>@{{order_number}}</code>,
                                <code>@{{status}}</code>,
                                <code>@{{total}}</code>,
                                <code>@{{from}}</code>,
                                <code>@{{to}}</code>,
                                <code>@{{customer_name}}</code>.
                            </p>

                            <div class="grid grid-cols-1 gap-3">
                                <input type="text" name="notification_order_placed_en_subject" value="{{ old('notification_order_placed_en_subject', $settings['notification_order_placed_en_subject'] ?? 'Order Confirmation') }}" class="{{ $inputClasses }}" placeholder="{{ __('Order placed subject') }}">
                                <textarea name="notification_order_placed_en_body" rows="3" class="{{ $inputClasses }}" placeholder="{{ __('Order placed body') }}">{{ old('notification_order_placed_en_body', $settings['notification_order_placed_en_body'] ?? '') }}</textarea>
                                <input type="text" name="notification_order_status_updated_en_subject" value="{{ old('notification_order_status_updated_en_subject', $settings['notification_order_status_updated_en_subject'] ?? 'Order Status Updated') }}" class="{{ $inputClasses }}" placeholder="{{ __('Status update subject') }}">
                                <textarea name="notification_order_status_updated_en_body" rows="3" class="{{ $inputClasses }}" placeholder="{{ __('Status update body') }}">{{ old('notification_order_status_updated_en_body', $settings['notification_order_status_updated_en_body'] ?? '') }}</textarea>
                            </div>
                        </div>

                        <div class="max-w-2xl" x-show="isActive('templates-locale')" x-cloak>
                            <p class="text-lg font-bold text-slate-800">{{ __('Arabic & Kurdish Notification Templates') }}</p>
                            <p class="mt-1 mb-5 text-xs text-slate-500">{{ __('These override the English fallback for users whose locale preference is Arabic or Kurdish.') }}</p>

                            <div class="grid grid-cols-1 gap-4">
                                @foreach(['ar' => 'Arabic', 'ku' => 'Kurdish'] as $localeCode => $localeLabel)
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                        <p class="mb-3 text-[11px] font-bold uppercase tracking-widest text-slate-500">{{ __($localeLabel) }}</p>
                                        <div class="grid gap-3">
                                            <input type="text" name="notification_order_placed_{{ $localeCode }}_subject" value="{{ old('notification_order_placed_' . $localeCode . '_subject', $settings['notification_order_placed_' . $localeCode . '_subject'] ?? '') }}" class="{{ $inputClasses }}" placeholder="{{ __('Order placed subject') }}">
                                            <textarea name="notification_order_placed_{{ $localeCode }}_body" rows="2" class="{{ $inputClasses }}" placeholder="{{ __('Order placed body') }}">{{ old('notification_order_placed_' . $localeCode . '_body', $settings['notification_order_placed_' . $localeCode . '_body'] ?? '') }}</textarea>
                                            <input type="text" name="notification_order_status_updated_{{ $localeCode }}_subject" value="{{ old('notification_order_status_updated_' . $localeCode . '_subject', $settings['notification_order_status_updated_' . $localeCode . '_subject'] ?? '') }}" class="{{ $inputClasses }}" placeholder="{{ __('Status update subject') }}">
                                            <textarea name="notification_order_status_updated_{{ $localeCode }}_body" rows="2" class="{{ $inputClasses }}" placeholder="{{ __('Status update body') }}">{{ old('notification_order_status_updated_' . $localeCode . '_body', $settings['notification_order_status_updated_' . $localeCode . '_body'] ?? '') }}</textarea>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-8 flex items-center justify-end gap-3 border-t border-slate-200 pt-5">
                            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-accent px-5 py-2.5 text-sm font-bold text-slate-900 shadow-sm transition hover:bg-accent disabled:cursor-not-allowed disabled:opacity-70" data-settings-submit>
                                <span data-settings-submit-label>{{ __('Save Settings') }}</span>
                                <span class="hidden" data-settings-submit-loading>{{ __('Uploading...') }}</span>
                            </button>
                        </div>
                    </form>

                    {{-- Readiness dock --}}
                    <div class="hidden w-14 shrink-0 flex-col items-center gap-3 border-s border-slate-200 py-5 lg:flex">
                        @foreach($checks as $check)
                            <div class="group relative">
                                <span class="block h-2.5 w-2.5 rounded-full {{ $check['ok'] ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                <div class="pointer-events-none absolute end-full top-1/2 z-20 me-2 w-56 -translate-y-1/2 rounded-lg border border-slate-200 bg-white p-3 text-start text-xs opacity-0 shadow-xl transition group-hover:opacity-100">
                                    <p class="font-bold text-slate-800">{{ $check['label'] }}</p>
                                    <p class="mt-1 text-slate-500">{{ __('Current:') }} {{ $check['value'] ?: '-' }} &middot; {{ __('Expected:') }} {{ $check['expected'] }}</p>
                                </div>
                            </div>
                        @endforeach

                        <div class="my-1 h-px w-6 bg-slate-200"></div>

                        <div x-data="toggle" class="relative">
                            <button
                                type="button"
                                @click="toggle"
                                :aria-expanded="ariaExpanded"
                                class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-50 dark:hover:bg-slate-800"
                                title="{{ __('Live Preview') }}"
                            >
                                <i class="fas fa-eye text-xs" aria-hidden="true"></i>
                            </button>
                            <div
                                x-show="open"
                                x-cloak
                                x-transition.opacity.duration.150ms
                                class="absolute end-full top-0 z-20 me-3 w-64 rounded-xl border border-slate-200 bg-white p-4 shadow-xl"
                            >
                                <h3 class="mb-2 text-[11px] font-bold uppercase tracking-widest text-slate-500">{{ __('Live Preview') }}</h3>
                                <div class="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-2.5">
                                    <x-brand-mark
                                        :logo-url="$settingsLogoUrl"
                                        :brand="$settings['site_name'] ?? 'YallaSpare'"
                                        wrapper-class="h-9 w-9 rounded-lg overflow-hidden"
                                        img-class="h-full w-full object-contain"
                                        fallback-class="inline-flex h-full w-full items-center justify-center rounded-lg bg-slate-200"
                                        fallback-text-class="font-semibold text-slate-600 text-xs"
                                    />
                                    <div class="min-w-0">
                                        <p class="text-[10px] text-slate-500">{{ __('Site Name') }}</p>
                                        <p class="truncate text-sm font-semibold text-slate-800">{{ $settings['site_name'] ?? 'YallaSpare' }}</p>
                                    </div>
                                </div>
                                <p class="mt-2 text-xs text-slate-500">{{ $settings['currency_symbol'] ?? 'IQD' }} ({{ $settings['currency_code'] ?? 'IQD' }}) &middot; {{ number_format((float) ($settings['shipping_fee'] ?? 5000), 0) }} {{ $settings['currency_code'] ?? 'IQD' }}</p>

                                <h3 class="mb-2 mt-4 text-[11px] font-bold uppercase tracking-widest text-slate-500">{{ __('Tips') }}</h3>
                                <ul class="space-y-1.5 text-xs text-slate-600">
                                    <li class="flex gap-2"><span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-slate-400 dark:bg-slate-500"></span>{{ __('Keep logos square for best results in the sidebar.') }}</li>
                                    <li class="flex gap-2"><span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-slate-400 dark:bg-slate-500"></span>{{ __('Use official ISO currency codes for clean formatting.') }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@push('scripts')
    <script nonce="{{ $cspNonce }}">
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('[data-admin-settings-form]');
            const input = document.querySelector('[data-hero-video-input]');
            const clientError = document.querySelector('[data-hero-video-client-error]');
            const submit = document.querySelector('[data-settings-submit]');
            const submitLabel = document.querySelector('[data-settings-submit-label]');
            const submitLoading = document.querySelector('[data-settings-submit-loading]');
            const maxBytes = 50 * 1024 * 1024;

            if (!form || !input || !clientError || !submit) {
                return;
            }

            const showClientError = (message) => {
                clientError.textContent = message;
                clientError.classList.toggle('hidden', message === '');
            };

            const validateHeroVideo = () => {
                const file = input.files && input.files.length > 0 ? input.files[0] : null;
                if (!file) {
                    showClientError('');
                    return true;
                }

                const name = file.name.toLowerCase();
                if (!name.endsWith('.mp4')) {
                    showClientError(@json(__('Hero video upload failed. Please upload an MP4 video under 50MB. If it still fails, encode it as H.264/AAC MP4.')));
                    return false;
                }

                if (file.size > maxBytes) {
                    showClientError(@json(__('Hero video must be 50MB or smaller.')));
                    return false;
                }

                showClientError('');
                return true;
            };

            input.addEventListener('change', validateHeroVideo);

            form.addEventListener('submit', (event) => {
                if (!validateHeroVideo()) {
                    event.preventDefault();
                    input.focus();
                    return;
                }

                submit.disabled = true;
                submitLabel?.classList.add('hidden');
                submitLoading?.classList.remove('hidden');
            });
        });
    </script>
@endpush
</x-app-layout>
