<x-app-layout>
<x-slot name="header">
    <span>{{ __('Coupon Analytics & Management') }}</span>
</x-slot>

@php
    $couponEnabled = (string) old('coupon_enabled', data_get($settings, 'coupon_enabled', '0')) === '1';
    $couponType = old('coupon_type', (string) data_get($settings, 'coupon_type', 'percent'));
    $couponValue = (float) old('coupon_value', (string) data_get($settings, 'coupon_value', '0'));
    $couponCode = (string) old('coupon_code', (string) data_get($settings, 'coupon_code', ''));
    $couponStartsAt = old('coupon_starts_at', (string) data_get($settings, 'coupon_starts_at', ''));
    $couponEndsAt = old('coupon_ends_at', (string) data_get($settings, 'coupon_ends_at', ''));
    $couponUsageLimit = (int) old('coupon_usage_limit', (string) data_get($settings, 'coupon_usage_limit', '0'));

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

    $activeCoupons = (int) data_get($dashboard, 'activeCoupons', 0);
    $totalRedemptions = (int) data_get($dashboard, 'totalRedemptions', 0);
    $revenueImpact = (float) data_get($dashboard, 'revenueImpact', 0);
    $avgDiscount = (string) data_get($dashboard, 'avgDiscountLabel', '0.00');
    $currencyLabel = (string) data_get($dashboard, 'currencyLabel', (string) data_get($settings, 'currency_code', 'IQD'));
    $currencyDecimals = (int) data_get($dashboard, 'currencyDecimals', (strtoupper((string) data_get($settings, 'currency_code', 'IQD')) === 'IQD' ? 0 : 2));
    $trendPoints = data_get($dashboard, 'trendPoints', array_fill(0, 12, 0));
    $usageDistribution = data_get($dashboard, 'usageDistribution', []);
    $platformDistribution = data_get($dashboard, 'platformDistribution', []);
    $couponRows = data_get($dashboard, 'coupons', []);

    $primaryPlatform = is_array($platformDistribution) && isset($platformDistribution[0]) ? $platformDistribution[0] : [];
    $primaryPlatformLabel = (string) data_get($primaryPlatform, 'label', 'Web');
    $primaryPlatformValue = (int) data_get($primaryPlatform, 'value', 0);
    $primaryUsageBucket = is_array($usageDistribution) && isset($usageDistribution[0]) ? $usageDistribution[0] : [];
    $primaryUsageLabel = (string) data_get($primaryUsageBucket, 'label', 'Steady');
    $primaryUsageValue = (int) data_get($primaryUsageBucket, 'value', 0);
    $heroTrendPoints = array_values(array_slice(array_map('intval', is_array($trendPoints) ? $trendPoints : []), -7));
    $heroTrendPoints = count($heroTrendPoints) < 7 ? array_pad($heroTrendPoints, 7, 0) : $heroTrendPoints;
    $heroTrendMax = max($heroTrendPoints ?: [0]);
    $campaignStateLabel = $couponEnabled ? 'Campaign Live' : 'Draft Mode';
    $campaignStateClass = $couponEnabled
        ? 'border-emerald-300/40 bg-emerald-400/15 text-emerald-50'
        : 'border-white/15 bg-white/10 text-slate-100';
    $campaignWindow = $couponStartsAt || $couponEndsAt
        ? trim(sprintf('%s - %s', $couponStartsAt ?: 'Now', $couponEndsAt ?: 'Open'))
        : 'No schedule defined';
    $activeRowCount = collect($couponRows)->where('status', 'active')->count();
    $scheduledRowCount = collect($couponRows)->where('status', 'scheduled')->count();
    $expiredRowCount = collect($couponRows)->where('status', 'expired')->count();
    $pausedRowCount = collect($couponRows)->where('status', 'paused')->count();
@endphp

<div class="py-10">
    <div class="mx-auto max-w-7xl space-y-7 px-4 sm:px-6 lg:px-8">
        @include('admin.discounts.partials._alerts')

        @include('admin.discounts.partials._coupon_hero')

        @include('admin.discounts.partials._coupon_dashboard_form')
    </div>
</div>

@include('admin.discounts.partials._coupon_scripts')
</x-app-layout>
