@php
    $settingsLogoUrl = \App\Support\Branding::logoUrlFromValue((string) ($settings['site_logo'] ?? ''));
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-slate-900 dark:text-white">{{ __('System Settings') }}</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Configure branding, currency, and inventory defaults.') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-600 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300">
                    <i class="fas fa-shield-alt text-emerald-500"></i>
                    {{ __('Settings are saved securely') }}
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-900/30 dark:text-emerald-200">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-900/30 dark:text-red-200">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <div class="xl:col-span-2 space-y-6">
                    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-black/30">
                        <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-800">
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Branding') }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Manage site identity and visuals that appear across the storefront.') }}</p>
                        </div>
                        <div class="p-6">
                            <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="space-y-6">
                                @csrf
                                @method('PUT')

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Site Name') }}</label>
                                        <input type="text" name="site_name" value="{{ old('site_name', $settings['site_name'] ?? '') }}" class="w-full rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Site Logo') }}</label>
                                        <input type="file" name="site_logo" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp" class="w-full rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ __('Transparent PNG or WEBP recommended. If the uploaded logo has a white outer background, the system will try to save it as a transparent PNG. Max 8MB.') }}</p>
                                        @error('site_logo')
                                            <p class="mt-1 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                                        @enderror
                                        @if(!empty($settings['site_logo']))
                                            <label class="inline-flex items-center mt-2 text-xs text-slate-600 dark:text-slate-400">
                                                <input type="checkbox" name="remove_logo" value="1" class="rounded border-slate-300 dark:border-slate-700 mr-2">
                                                {{ __('Remove current logo') }}
                                            </label>
                                        @endif
                                    </div>
                                </div>

                                <div class="pt-6 border-t border-slate-200 dark:border-slate-800">
                                    <div class="flex items-center justify-between mb-4">
                                        <div>
                                            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Currency') }}</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Set the currency format shown to customers.') }}</p>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Currency Code') }}</label>
                                            <input type="text" name="currency_code" value="{{ old('currency_code', $settings['currency_code'] ?? 'IQD') }}" class="w-full rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ __('Example: USD, EUR, IQD') }}</p>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Currency Symbol') }}</label>
                                            <input type="text" name="currency_symbol" value="{{ old('currency_symbol', $settings['currency_symbol'] ?? 'IQD') }}" class="w-full rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ __('Example: IQD, EUR') }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="pt-6 border-t border-slate-200 dark:border-slate-800">
                                    <div class="flex items-center justify-between mb-4">
                                        <div>
                                            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Operations Defaults') }}</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Configure shipping and inventory defaults used across checkout, invoices, and dashboard.') }}</p>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Shipping Fee') }}</label>
                                            <input type="number" name="shipping_fee" min="0" step="0.01" value="{{ old('shipping_fee', (float) ($settings['shipping_fee'] ?? 5000)) }}" class="w-full rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ __('Used for checkout, order totals, and invoices.') }}</p>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Low Stock Threshold') }}</label>
                                            <input type="number" name="low_stock_threshold" min="0" value="{{ old('low_stock_threshold', (int) ($settings['low_stock_threshold'] ?? 5)) }}" class="w-full rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ __('Used across dashboard and products table.') }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="pt-6 border-t border-slate-200 dark:border-slate-800">
                                    <div class="mb-4">
                                        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Notification Providers') }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Webhook URLs receive recipient, message, and context JSON. Leave empty to keep log transport.') }}</p>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('SMS Webhook URL') }}</label>
                                            <input type="url" name="sms_provider_webhook_url" value="{{ old('sms_provider_webhook_url', $settings['sms_provider_webhook_url'] ?? '') }}" class="w-full rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('WhatsApp Webhook URL') }}</label>
                                            <input type="url" name="whatsapp_provider_webhook_url" value="{{ old('whatsapp_provider_webhook_url', $settings['whatsapp_provider_webhook_url'] ?? '') }}" class="w-full rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                        </div>
                                    </div>
                                </div>

                                <div class="pt-6 border-t border-slate-200 dark:border-slate-800">
                                    <div class="mb-4">
                                        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('English Notification Templates') }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">
                                            {{ __('Available placeholders:') }}
                                            <code>@{{order_number}}</code>,
                                            <code>@{{status}}</code>,
                                            <code>@{{total}}</code>,
                                            <code>@{{from}}</code>,
                                            <code>@{{to}}</code>,
                                            <code>@{{customer_name}}</code>.
                                        </p>
                                    </div>
                                    <div class="grid grid-cols-1 gap-4">
                                        <input type="text" name="notification_order_placed_en_subject" value="{{ old('notification_order_placed_en_subject', $settings['notification_order_placed_en_subject'] ?? 'Order Confirmation') }}" class="w-full rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100" placeholder="{{ __('Order placed subject') }}">
                                        <textarea name="notification_order_placed_en_body" rows="3" class="w-full rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100" placeholder="{{ __('Order placed body') }}">{{ old('notification_order_placed_en_body', $settings['notification_order_placed_en_body'] ?? '') }}</textarea>
                                        <input type="text" name="notification_order_status_updated_en_subject" value="{{ old('notification_order_status_updated_en_subject', $settings['notification_order_status_updated_en_subject'] ?? 'Order Status Updated') }}" class="w-full rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100" placeholder="{{ __('Status update subject') }}">
                                        <textarea name="notification_order_status_updated_en_body" rows="3" class="w-full rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100" placeholder="{{ __('Status update body') }}">{{ old('notification_order_status_updated_en_body', $settings['notification_order_status_updated_en_body'] ?? '') }}</textarea>
                                    </div>
                                </div>

                                <div class="pt-6 border-t border-slate-200 dark:border-slate-800">
                                    <div class="mb-4">
                                        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Arabic & Kurdish Notification Templates') }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('These override the English fallback for users whose locale preference is Arabic or Kurdish.') }}</p>
                                    </div>
                                    <div class="grid grid-cols-1 gap-4">
                                        @foreach(['ar' => 'Arabic', 'ku' => 'Kurdish'] as $localeCode => $localeLabel)
                                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                                                <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __($localeLabel) }}</p>
                                                <div class="grid gap-3">
                                                    <input type="text" name="notification_order_placed_{{ $localeCode }}_subject" value="{{ old('notification_order_placed_' . $localeCode . '_subject', $settings['notification_order_placed_' . $localeCode . '_subject'] ?? '') }}" class="w-full rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100" placeholder="{{ __('Order placed subject') }}">
                                                    <textarea name="notification_order_placed_{{ $localeCode }}_body" rows="2" class="w-full rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100" placeholder="{{ __('Order placed body') }}">{{ old('notification_order_placed_' . $localeCode . '_body', $settings['notification_order_placed_' . $localeCode . '_body'] ?? '') }}</textarea>
                                                    <input type="text" name="notification_order_status_updated_{{ $localeCode }}_subject" value="{{ old('notification_order_status_updated_' . $localeCode . '_subject', $settings['notification_order_status_updated_' . $localeCode . '_subject'] ?? '') }}" class="w-full rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100" placeholder="{{ __('Status update subject') }}">
                                                    <textarea name="notification_order_status_updated_{{ $localeCode }}_body" rows="2" class="w-full rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100" placeholder="{{ __('Status update body') }}">{{ old('notification_order_status_updated_' . $localeCode . '_body', $settings['notification_order_status_updated_' . $localeCode . '_body'] ?? '') }}</textarea>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="flex items-center justify-end gap-3">
                                    <button type="submit" class="px-5 py-2.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold transition dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100">
                                        {{ __('Save Settings') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-black/30 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wide">{{ __('Live Preview') }}</h3>
                            <span class="text-xs text-slate-400 dark:text-slate-500">{{ __('Admin view') }}</span>
                        </div>
                        <div class="space-y-4">
                            <div class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-800/60">
                                <x-brand-mark
                                    :logo-url="$settingsLogoUrl"
                                    :brand="$settings['site_name'] ?? 'YallaSpare'"
                                    wrapper-class="h-12 w-12 rounded-lg overflow-hidden"
                                    img-class="h-full w-full object-contain"
                                    fallback-class="inline-flex h-full w-full items-center justify-center rounded-lg bg-slate-200 dark:bg-slate-800"
                                    fallback-text-class="font-semibold text-slate-600 dark:text-slate-200"
                                />
                                <div>
                                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Site Name') }}</p>
                                    <p class="font-semibold text-slate-800 dark:text-slate-100">{{ $settings['site_name'] ?? 'YallaSpare' }}</p>
                                </div>
                            </div>

                            <div class="p-3 rounded-xl border border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-800/60">
                                <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Currency') }}</p>
                                <p class="font-semibold text-slate-800 dark:text-slate-100">{{ $settings['currency_symbol'] ?? 'IQD' }} ({{ $settings['currency_code'] ?? 'IQD' }})</p>
                            </div>

                            <div class="p-3 rounded-xl border border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-800/60">
                                <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Low Stock Threshold') }}</p>
                                <p class="font-semibold text-slate-800 dark:text-slate-100">{{ (int) ($settings['low_stock_threshold'] ?? 5) }}</p>
                            </div>

                            <div class="p-3 rounded-xl border border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-800/60">
                                <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Shipping Fee') }}</p>
                                <p class="font-semibold text-slate-800 dark:text-slate-100">{{ number_format((float) ($settings['shipping_fee'] ?? 5000), 0) }} {{ $settings['currency_code'] ?? 'IQD' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm dark:shadow-black/30">
                        <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wide mb-3">{{ __('Tips') }}</h3>
                        <ul class="space-y-3 text-sm text-slate-500 dark:text-slate-400">
                            <li class="flex items-start gap-2">
                                <i class="fas fa-circle-check text-emerald-500 mt-0.5"></i>
                                {{ __('Keep logos square for best results in the sidebar.') }}
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-circle-check text-emerald-500 mt-0.5"></i>
                                {{ __('Use official ISO currency codes for clean formatting.') }}
                            </li>
                        </ul>
                    </div>

                    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm dark:shadow-black/30">
                        <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wide mb-3">{{ __('Production Readiness') }}</h3>
                        <div class="space-y-3">
                            @foreach(($productionChecks ?? []) as $check)
                                <div class="flex items-start justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-950">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $check['label'] }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Current:') }} {{ $check['value'] ?: '-' }} · {{ __('Expected:') }} {{ $check['expected'] }}</p>
                                    </div>
                                    <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $check['ok'] ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                        {{ $check['ok'] ? __('OK') : __('Action') }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
