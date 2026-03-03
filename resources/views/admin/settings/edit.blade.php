<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-slate-900 dark:text-white">System Settings</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">Configure branding, currency, and inventory defaults.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-600 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300">
                    <i class="fas fa-shield-alt text-emerald-500"></i>
                    Settings are saved securely
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
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">Branding</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Manage site identity and visuals that appear across the storefront.</p>
                        </div>
                        <div class="p-6">
                            <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="space-y-6">
                                @csrf
                                @method('PUT')

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Site Name</label>
                                        <input type="text" name="site_name" value="{{ old('site_name', $settings['site_name'] ?? '') }}" class="w-full rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Site Logo</label>
                                        <input type="file" name="site_logo" accept="image/*" class="w-full rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">PNG/JPG up to 2MB</p>
                                        @if(!empty($settings['site_logo']))
                                            <label class="inline-flex items-center mt-2 text-xs text-slate-600 dark:text-slate-400">
                                                <input type="checkbox" name="remove_logo" value="1" class="rounded border-slate-300 dark:border-slate-700 mr-2">
                                                Remove current logo
                                            </label>
                                        @endif
                                    </div>
                                </div>

                                <div class="pt-6 border-t border-slate-200 dark:border-slate-800">
                                    <div class="flex items-center justify-between mb-4">
                                        <div>
                                            <p class="text-sm font-semibold text-slate-900 dark:text-white">Currency</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">Set the currency format shown to customers.</p>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Currency Code</label>
                                            <input type="text" name="currency_code" value="{{ old('currency_code', $settings['currency_code'] ?? 'IQD') }}" class="w-full rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Example: USD, EUR, IQD</p>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Currency Symbol</label>
                                            <input type="text" name="currency_symbol" value="{{ old('currency_symbol', $settings['currency_symbol'] ?? 'IQD') }}" class="w-full rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Example: $, EUR, IQD</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="pt-6 border-t border-slate-200 dark:border-slate-800">
                                    <div class="flex items-center justify-between mb-4">
                                        <div>
                                            <p class="text-sm font-semibold text-slate-900 dark:text-white">Inventory Defaults</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">Configure alert thresholds used across the dashboard.</p>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Low Stock Threshold</label>
                                            <input type="number" name="low_stock_threshold" min="0" value="{{ old('low_stock_threshold', (int) ($settings['low_stock_threshold'] ?? 5)) }}" class="w-full rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Used across dashboard and products table.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center justify-end gap-3">
                                    <button type="submit" class="px-5 py-2.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold transition dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100">
                                        Save Settings
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-black/30 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wide">Live Preview</h3>
                            <span class="text-xs text-slate-400 dark:text-slate-500">Admin view</span>
                        </div>
                        <div class="space-y-4">
                            <div class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-800/60">
                                @if(!empty($settings['site_logo']))
                                    <img src="{{ asset('storage/' . $settings['site_logo']) }}" alt="Site Logo" class="h-12 w-12 rounded-lg object-cover">
                                @else
                                    <div class="h-12 w-12 rounded-lg bg-slate-200 dark:bg-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-200 font-semibold">YS</div>
                                @endif
                                <div>
                                    <p class="text-sm text-slate-500 dark:text-slate-400">Site Name</p>
                                    <p class="font-semibold text-slate-800 dark:text-slate-100">{{ $settings['site_name'] ?? 'YallaSpare' }}</p>
                                </div>
                            </div>

                            <div class="p-3 rounded-xl border border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-800/60">
                                <p class="text-sm text-slate-500 dark:text-slate-400">Currency</p>
                                <p class="font-semibold text-slate-800 dark:text-slate-100">{{ $settings['currency_symbol'] ?? 'IQD' }} ({{ $settings['currency_code'] ?? 'IQD' }})</p>
                            </div>

                            <div class="p-3 rounded-xl border border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-800/60">
                                <p class="text-sm text-slate-500 dark:text-slate-400">Low Stock Threshold</p>
                                <p class="font-semibold text-slate-800 dark:text-slate-100">{{ (int) ($settings['low_stock_threshold'] ?? 5) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm dark:shadow-black/30">
                        <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wide mb-3">Tips</h3>
                        <ul class="space-y-3 text-sm text-slate-500 dark:text-slate-400">
                            <li class="flex items-start gap-2">
                                <i class="fas fa-circle-check text-emerald-500 mt-0.5"></i>
                                Keep logos square for best results in the sidebar.
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-circle-check text-emerald-500 mt-0.5"></i>
                                Use official ISO currency codes for clean formatting.
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
