<x-app-layout>
<x-slot name="header">
    <span>{{ __('Create Coupon') }}</span>
</x-slot>

@php
    $couponEnabled = (string) old('coupon_enabled', data_get($settings, 'coupon_enabled', '0')) === '1';
    $couponType = old('coupon_type', (string) data_get($settings, 'coupon_type', 'percent'));
    $couponCode = (string) old('coupon_code', (string) data_get($settings, 'coupon_code', ''));
    $couponStartsAt = (string) old('coupon_starts_at', (string) data_get($settings, 'coupon_starts_at', ''));
    $couponEndsAt = (string) old('coupon_ends_at', (string) data_get($settings, 'coupon_ends_at', ''));

    $discountsEnabled = (string) old('discounts_enabled', data_get($settings, 'discounts_enabled', '0'));
    $discountLabel = (string) old('discount_label', data_get($settings, 'discount_label', ''));
    $discountType = (string) old('discount_type', data_get($settings, 'discount_type', 'percent'));
    $discountValue = (string) old('discount_value', data_get($settings, 'discount_value', '0'));
    $discountStartsAt = (string) old('discount_starts_at', data_get($settings, 'discount_starts_at', ''));
    $discountEndsAt = (string) old('discount_ends_at', data_get($settings, 'discount_ends_at', ''));
    $discountScope = (string) old('discount_scope', data_get($settings, 'discount_scope', 'all'));
    $selectedProducts = is_array(old('discount_product_ids')) ? old('discount_product_ids') : (json_decode((string) data_get($settings, 'discount_product_ids', '[]'), true) ?: []);
    $selectedCategories = is_array(old('discount_category_ids')) ? old('discount_category_ids') : (json_decode((string) data_get($settings, 'discount_category_ids', '[]'), true) ?: []);
    $selectedBrands = is_array(old('discount_brands')) ? old('discount_brands') : (json_decode((string) data_get($settings, 'discount_brands', '[]'), true) ?: []);
@endphp

<div class="py-10">
    <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200/80 bg-rose-50/90 px-4 py-3 text-sm text-rose-700 shadow-sm dark:border-rose-900/70 dark:bg-rose-900/30 dark:text-rose-200">
                <ul class="list-disc ps-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-[0_18px_34px_rgba(15,23,42,0.08)] dark:border-slate-800 dark:bg-slate-900 sm:p-8">
            <div class="mb-6 flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Promotion') }}</p>
                    <h1 class="mt-1 text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ __('Create Coupon') }}</h1>
                </div>
                <a href="{{ route('admin.discounts.edit') }}" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">{{ __('Back') }}</a>
            </div>

            <form method="POST" action="{{ route('admin.discounts.update') }}" class="space-y-5">
                @csrf
                @method('PUT')

                <input type="hidden" name="discounts_enabled" value="{{ $discountsEnabled }}">
                <input type="hidden" name="discount_label" value="{{ $discountLabel }}">
                <input type="hidden" name="discount_type" value="{{ $discountType }}">
                <input type="hidden" name="discount_value" value="{{ $discountValue }}">
                <input type="hidden" name="discount_starts_at" value="{{ $discountStartsAt }}">
                <input type="hidden" name="discount_ends_at" value="{{ $discountEndsAt }}">
                <input type="hidden" name="discount_scope" value="{{ $discountScope }}">
                @foreach ($selectedProducts as $pid)
                    <input type="hidden" name="discount_product_ids[]" value="{{ (int) $pid }}">
                @endforeach
                @foreach ($selectedCategories as $cid)
                    <input type="hidden" name="discount_category_ids[]" value="{{ (int) $cid }}">
                @endforeach
                @foreach ($selectedBrands as $brand)
                    <input type="hidden" name="discount_brands[]" value="{{ (string) $brand }}">
                @endforeach

                <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4 dark:border-slate-800 dark:bg-slate-950/60">
                    <label class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                        <input type="checkbox" name="coupon_enabled" value="1" class="h-4 w-4 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500 dark:border-slate-600 dark:bg-slate-900" @checked($couponEnabled)>
                        {{ __('Campaign Active') }}
                    </label>
                </div>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <label class="block">
                        <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ __('Coupon Code') }}</span>
                        <div class="flex gap-2">
                            <input type="text" name="coupon_code" id="coupon_code" value="{{ old('coupon_code', $couponCode) }}" placeholder="{{ __('SAVE10') }}" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm uppercase text-slate-700 outline-none transition focus:border-cyan-400 focus:ring-2 focus:ring-cyan-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:focus:border-cyan-500 dark:focus:ring-cyan-900/40">
                            <button type="button" id="coupon-generate-btn" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">{{ __('Generate') }}</button>
                        </div>
                        @error('coupon_code')
                            <span class="mt-1 block text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</span>
                        @enderror
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ __('Type') }}</span>
                        <select name="coupon_type" id="coupon_type" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-cyan-400 focus:ring-2 focus:ring-cyan-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:focus:border-cyan-500 dark:focus:ring-cyan-900/40">
                            <option value="percent" @selected($couponType === 'percent')>{{ __('Percent (%)') }}</option>
                            <option value="fixed" @selected($couponType === 'fixed')>{{ __('Fixed Amount') }}</option>
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ __('Value') }}</span>
                        <input type="number" step="0.01" min="0" name="coupon_value" id="coupon_value" value="{{ old('coupon_value', (string) data_get($settings, 'coupon_value', '0')) }}" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-cyan-400 focus:ring-2 focus:ring-cyan-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:focus:border-cyan-500 dark:focus:ring-cyan-900/40">
                        <span id="coupon-value-help" class="mt-1 block text-xs text-slate-500 dark:text-slate-400">{{ __('Percent type supports max 100.') }}</span>
                        @error('coupon_value')
                            <span class="mt-1 block text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</span>
                        @enderror
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ __('Minimum Order') }}</span>
                        <input type="number" step="0.01" min="0" name="coupon_min_order" id="coupon_min_order" value="{{ old('coupon_min_order', (string) data_get($settings, 'coupon_min_order', '0')) }}" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-cyan-400 focus:ring-2 focus:ring-cyan-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:focus:border-cyan-500 dark:focus:ring-cyan-900/40">
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ __('Usage Limit') }}</span>
                        <input type="number" min="0" name="coupon_usage_limit" id="coupon_usage_limit" value="{{ old('coupon_usage_limit', (string) data_get($settings, 'coupon_usage_limit', '0')) }}" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-cyan-400 focus:ring-2 focus:ring-cyan-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:focus:border-cyan-500 dark:focus:ring-cyan-900/40">
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ __('Starts At') }}</span>
                        <input type="date" name="coupon_starts_at" id="coupon_starts_at" value="{{ $couponStartsAt }}" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-cyan-400 focus:ring-2 focus:ring-cyan-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:focus:border-cyan-500 dark:focus:ring-cyan-900/40">
                    </label>

                    <label class="block lg:col-span-2">
                        <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ __('Ends At') }}</span>
                        <div class="flex gap-2">
                            <input type="date" name="coupon_ends_at" id="coupon_ends_at" value="{{ $couponEndsAt }}" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-cyan-400 focus:ring-2 focus:ring-cyan-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:focus:border-cyan-500 dark:focus:ring-cyan-900/40">
                            <button type="button" id="coupon-clear-dates" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">{{ __('Clear') }}</button>
                        </div>
                        @error('coupon_ends_at')
                            <span class="mt-1 block text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</span>
                        @enderror
                    </label>
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('admin.discounts.edit') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">{{ __('Cancel') }}</a>
                    <button type="submit" class="rounded-xl bg-gradient-to-r from-slate-900 to-cyan-800 px-6 py-2.5 text-sm font-semibold text-white shadow-[0_14px_28px_rgba(15,23,42,0.35)] transition hover:from-slate-800 hover:to-cyan-700">{{ __('Save Coupon') }}</button>
                </div>
            </form>
        </section>
    </div>
</div>

@push('scripts')
<script>
    (() => {
        const couponTypeInput = document.getElementById('coupon_type');
        const couponValueInput = document.getElementById('coupon_value');
        const couponValueHelp = document.getElementById('coupon-value-help');
        const couponCodeInput = document.getElementById('coupon_code');
        const couponGenerateBtn = document.getElementById('coupon-generate-btn');
        const couponStartsInput = document.getElementById('coupon_starts_at');
        const couponEndsInput = document.getElementById('coupon_ends_at');
        const couponClearDatesBtn = document.getElementById('coupon-clear-dates');

        const randomCouponCode = () => `SAVE-${Math.random().toString(36).slice(2, 8).toUpperCase()}`;
        const sanitizeCouponCode = (value) => value.toUpperCase().replace(/[^A-Z0-9_-]/g, '').slice(0, 40);

        const syncCouponTypeLimits = () => {
            if (!couponTypeInput || !couponValueInput) return;
            if (couponTypeInput.value === 'percent') {
                couponValueInput.max = '100';
                if (couponValueHelp) couponValueHelp.textContent = 'Percent type supports max 100.';
                return;
            }
            couponValueInput.removeAttribute('max');
            if (couponValueHelp) couponValueHelp.textContent = 'Fixed amount has no percentage cap.';
        };

        couponTypeInput?.addEventListener('change', syncCouponTypeLimits);
        couponCodeInput?.addEventListener('input', () => {
            couponCodeInput.value = sanitizeCouponCode(couponCodeInput.value || '');
        });

        couponGenerateBtn?.addEventListener('click', () => {
            if (!couponCodeInput) return;
            couponCodeInput.value = randomCouponCode();
        });

        couponStartsInput?.addEventListener('change', () => {
            if (couponEndsInput && couponStartsInput.value) {
                couponEndsInput.min = couponStartsInput.value;
            } else if (couponEndsInput) {
                couponEndsInput.removeAttribute('min');
            }
        });

        couponClearDatesBtn?.addEventListener('click', () => {
            if (couponStartsInput) couponStartsInput.value = '';
            if (couponEndsInput) {
                couponEndsInput.value = '';
                couponEndsInput.removeAttribute('min');
            }
        });

        syncCouponTypeLimits();
    })();
</script>
@endpush
</x-app-layout>
