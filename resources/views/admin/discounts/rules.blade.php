<x-app-layout>
<x-slot name="header">
    <span>{{ filled(old('discount_id', data_get($formState ?? [], 'id'))) ? __('Edit Discount Rule') : __('Create Discount Rule') }}</span>
</x-slot>

@php
    $discountId = old('discount_id', data_get($formState ?? [], 'id'));
    $discountsEnabled = (bool) old('discounts_enabled', data_get($formState ?? [], 'is_active', false));
    $discountLabel = (string) old('discount_label', data_get($formState ?? [], 'label', ''));
    $discountType = (string) old('discount_type', data_get($formState ?? [], 'type', 'percent'));
    $discountValue = (string) old('discount_value', data_get($formState ?? [], 'value', '0'));
    $discountMinimumSubtotal = (string) old('discount_minimum_subtotal', data_get($formState ?? [], 'minimum_subtotal', ''));
    $discountUsageLimit = (string) old('discount_usage_limit', data_get($formState ?? [], 'usage_limit', ''));
    $discountStartsAt = (string) old('discount_starts_at', data_get($formState ?? [], 'starts_at', ''));
    $discountEndsAt = (string) old('discount_ends_at', data_get($formState ?? [], 'ends_at', ''));
    $discountScope = (string) old('discount_scope', data_get($formState ?? [], 'scope', 'all'));
    $selectedProducts = collect(is_array(old('discount_product_ids')) ? old('discount_product_ids') : (data_get($formState ?? [], 'selected_products', []) ?: []))
        ->map(fn ($id) => (int) $id)
        ->all();
    $selectedCategories = collect(is_array(old('discount_category_ids')) ? old('discount_category_ids') : (data_get($formState ?? [], 'selected_categories', []) ?: []))
        ->map(fn ($id) => (int) $id)
        ->all();
    $selectedBrands = collect(is_array(old('discount_brands')) ? old('discount_brands') : (data_get($formState ?? [], 'selected_brands', []) ?: []))
        ->map(fn ($brand) => (string) $brand)
        ->all();
    $isEditingDiscount = filled($discountId);
    $builderTitle = $isEditingDiscount ? __('Edit Discount Rule') : __('Create Discount Rule');
    $builderActionLabel = $isEditingDiscount ? __('Save Changes') : __('Create Discount Rule');
    $builderSupportLabel = $isEditingDiscount ? __('Update the selected rule and keep the targeting in sync.') : __('Create a new discount rule and add it to your saved rule list.');

    $scopeLabel = match ($discountScope) {
        'products' => __('Product Targeting'),
        'categories' => __('Category Targeting'),
        'brands' => __('Brand Targeting'),
        default => __('Storewide'),
    };
    $activationLabel = $discountsEnabled ? __('Rule Live') : ($isEditingDiscount ? __('Draft Rule') : __('New Draft'));
    $activationClass = $discountsEnabled
        ? 'border-emerald-300/40 bg-emerald-400/15 text-emerald-50'
        : 'border-white/15 bg-white/10 text-slate-100';
    $selectedCount = match ($discountScope) {
        'products' => count($selectedProducts),
        'categories' => count($selectedCategories),
        'brands' => count($selectedBrands),
        default => 0,
    };
    $discountValueLabel = $discountType === 'percent'
        ? number_format((float) $discountValue, 2) . '%'
        : number_format((float) $discountValue, 2);
    $discountLimitLabel = trim($discountMinimumSubtotal) !== ''
        ? __('Minimum subtotal:') . ' ' . number_format((float) $discountMinimumSubtotal, 2)
        : __('No minimum subtotal');
    if (trim($discountUsageLimit) !== '') {
        $discountLimitLabel .= ' / ' . __('Usage limit:') . ' ' . number_format((int) $discountUsageLimit);
    }
    $scheduleWindowLabel = $discountStartsAt !== '' || $discountEndsAt !== ''
        ? trim(sprintf('%s - %s', $discountStartsAt !== '' ? $discountStartsAt : 'Immediate', $discountEndsAt !== '' ? $discountEndsAt : 'Open'))
        : 'No schedule window';
    $scopeUtilizationLabel = match ($discountScope) {
        'products' => number_format(count($selectedProducts)) . ' products selected',
        'categories' => number_format(count($selectedCategories)) . ' of ' . number_format(collect($categories)->count()) . ' categories',
        'brands' => number_format(count($selectedBrands)) . ' of ' . number_format(collect($brands)->count()) . ' brands',
        default => 'Applies across the full catalog',
    };
@endphp

<div class="py-10">
    <div class="mx-auto max-w-7xl space-y-7 px-4 sm:px-6 lg:px-8">
        @include('admin.discounts.partials._alerts')

        @include('admin.discounts.partials._discount_rules_hero')

        @include('admin.discounts.partials._discount_rules_directory')

        @include('admin.discounts.partials._discount_rule_form')
    </div>
</div>

@include('admin.discounts.partials._discount_rule_scripts')
</x-app-layout>
