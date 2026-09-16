<section id="welcome-offer" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900 sm:p-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ __('welcome.title') }}</h2>
            <p class="mt-1 max-w-3xl text-sm text-slate-600 dark:text-slate-300">{{ __('welcome.admin_intro') }}</p>
        </div>
        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ (string) data_get($settings, 'welcome_offer_enabled', '0') === '1' ? __('Active') : __('Paused') }}</span>
    </div>
    <form action="{{ route('admin.discounts.welcome-offer.update') }}" method="POST" class="mt-5 space-y-5">
        @csrf
        @method('PUT')
        <input type="hidden" name="welcome_offer_enabled" value="0">
        <label class="flex items-center gap-3 text-sm font-semibold text-slate-800 dark:text-slate-200">
            <input type="checkbox" name="welcome_offer_enabled" value="1" @checked((string) old('welcome_offer_enabled', data_get($settings, 'welcome_offer_enabled', '0')) === '1') class="rounded border-slate-300 text-orange-600 focus:ring-orange-500">
            {{ __('welcome.enable') }}
        </label>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <label for="welcome_offer_type" class="block text-sm font-medium text-slate-700 dark:text-slate-200">{{ __('welcome.type') }}</label>
                <select id="welcome_offer_type" name="welcome_offer_type" class="mt-2 w-full rounded-xl border-slate-300 bg-white text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                    @foreach (['percent', 'fixed', 'free_shipping'] as $type)
                        <option value="{{ $type }}" @selected(old('welcome_offer_type', data_get($settings, 'welcome_offer_type', 'percent')) === $type)>{{ __('welcome.type_'.$type) }}</option>
                    @endforeach
                </select>
                @error('welcome_offer_type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            @foreach (['value' => [20, '0.01', 999999999], 'minimum_subtotal' => [0, '0.01', 999999999], 'maximum_discount' => [0, '0.01', 999999999], 'valid_days' => [30, '1', 365]] as $field => [$default, $step, $max])
                <div>
                    <label for="welcome_offer_{{ $field }}" class="block text-sm font-medium text-slate-700 dark:text-slate-200">{{ __('welcome.'.$field) }}</label>
                    <input id="welcome_offer_{{ $field }}" name="welcome_offer_{{ $field }}" type="number" min="0" max="{{ $max }}" step="{{ $step }}" required value="{{ old('welcome_offer_'.$field, data_get($settings, 'welcome_offer_'.$field, $default)) }}" class="mt-2 w-full rounded-xl border-slate-300 bg-white text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('welcome.'.$field.'_help') }}</p>
                    @error('welcome_offer_'.$field)<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            @endforeach
        </div>
        <p class="rounded-xl bg-slate-50 p-3 text-sm leading-6 text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ __('welcome.admin_rules') }}</p>
        <button type="submit" class="rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white dark:bg-white dark:text-slate-900">{{ __('welcome.save') }}</button>
    </form>
</section>
