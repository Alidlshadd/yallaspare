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

    $liveCount = $discountRows->where('status', 'live')->count();
    $totalRedemptions = (int) $discountRows->sum('usedCount');
    $overlapNames = $discountRows
        ->filter(fn ($row) => $row['status'] === 'live' && $row['scope'] === 'all')
        ->pluck('name');

    $tagChip = fn (string $ruleStatus) => match ($ruleStatus) {
        'live' => ['label' => __('Live'), 'class' => 'dr-st-live'],
        'scheduled' => ['label' => __('Scheduled'), 'class' => 'dr-st-scheduled'],
        'expired' => ['label' => __('Expired'), 'class' => 'dr-st-expired'],
        default => ['label' => __('Draft'), 'class' => 'dr-st-draft'],
    };

    $drConfig = [
        'searchUrl' => route('admin.discounts.products.search'),
        'initialScope' => $discountScope,
        'categoryOptions' => collect($categories)->map(fn ($category) => ['id' => (int) $category->id, 'name' => (string) $category->name])->values(),
        'brandOptions' => collect($brands)->map(fn ($brand) => (string) $brand)->values(),
        'initialSelectedIds' => array_values($selectedProducts),
        'initialSelectedProducts' => ($selectedProductsData ?? collect())->values(),
        'basics' => [
            'label' => $discountLabel,
            'type' => $discountType === 'fixed' ? 'fixed' : 'percent',
            'value' => $discountValue,
            'startsAt' => $discountStartsAt,
            'endsAt' => $discountEndsAt,
        ],
        'rows' => $discountRows->map(fn ($row) => [
            'name' => $row['name'],
            'status' => $row['statusLabel'],
            'value' => $row['valueLabel'],
            'scope' => $row['scopeLabel'] . ' — ' . $row['targetLabel'],
            'window' => $row['windowLabel'],
            'used' => $row['usedCount'],
        ])->values(),
        'labels' => [
            'csvHeaders' => [__('Rule'), __('Status'), __('Value'), __('Scope'), __('Window'), __('Used')],
            'exportedFlash' => __('CSV exported'),
            'duplicatedFlash' => __('Rule copied into the builder — save it as a new rule'),
            'copySuffix' => __('(copy)'),
            'noLabel' => __('No label set'),
            'immediate' => __('Immediate'),
            'openEnded' => __('Open-ended'),
            'scopeAll' => __('Storewide'),
            'scopeProducts' => __('Product Targeting'),
            'scopeCategories' => __('Category Targeting'),
            'scopeBrands' => __('Brand Targeting'),
            'productsSelected' => __('products selected'),
            'categoriesWord' => __('categories'),
            'brandsWord' => __('brands'),
            'fullCatalog' => __('Applies across the full catalog'),
            'selected' => __('Selected'),
            'selectProduct' => __('Select product'),
            'noSku' => __('No SKU'),
            'uncategorized' => __('Uncategorized'),
            'outOfStock' => __('Out of stock'),
            'lowStock' => __('Low stock'),
            'inStock' => __('In stock'),
        ],
        'ui' => [
            'scopeOn' => 'border-amber-400 bg-amber-50/70 shadow-sm dark:border-amber-500/60 dark:bg-amber-950/20',
            'scopeOff' => 'border-slate-200 bg-white hover:border-amber-300 hover:bg-amber-50/30 dark:border-slate-700 dark:bg-slate-950 dark:hover:border-amber-900/50',
            'productOn' => 'border-amber-400 bg-amber-50/60 dark:border-amber-500/60 dark:bg-amber-950/20',
            'productOff' => 'border-slate-200 bg-white hover:border-amber-300 hover:bg-amber-50/40 dark:border-slate-700 dark:bg-slate-950 dark:hover:border-amber-900/40',
            'tickOn' => 'border-amber-500 bg-amber-400 text-[#422006]',
            'tickOff' => 'border-slate-300 bg-white text-slate-400 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-500',
            'stockOut' => 'bg-rose-100 text-rose-700 dark:bg-rose-950/30 dark:text-rose-300',
            'stockLow' => 'bg-amber-100 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300',
            'stockIn' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300',
        ],
    ];
@endphp

<style>
    /* Discount Rules — Price Tag Wall (dr-) */
    .dr-hero {
        background: linear-gradient(135deg, #04042a, #10104a);
        position: relative; overflow: hidden;
    }
    .dr-hero::after {
        content: ""; position: absolute; inset: 0;
        background-image: repeating-linear-gradient(135deg, rgba(255,255,255,0.05) 0 1px, transparent 1px 14px);
    }
    .dr-hero > * { position: relative; z-index: 1; }
    .dr-num { font-family: ui-monospace, 'JetBrains Mono', Consolas, monospace; font-variant-numeric: tabular-nums; }

    .dr-pegboard {
        background-color: #e8edf4;
        background-image: radial-gradient(circle at 11px 11px, rgba(4,4,42,0.13) 1.6px, transparent 2px);
        background-size: 26px 26px;
    }
    .dark .dr-pegboard {
        background-color: #0c1226;
        background-image: radial-gradient(circle at 11px 11px, rgba(255,255,255,0.08) 1.6px, transparent 2px);
    }

    .dr-tag {
        position: relative; background: #fff; border: 1.5px solid #e2e8f0;
        border-radius: 8px 18px 18px 8px; padding: 14px 14px 12px 27px;
        box-shadow: 0 6px 16px -6px rgba(4,4,42,0.28);
    }
    .dark .dr-tag { background: #0f172a; border-color: #334155; }
    .dr-tag::before {
        content: ""; position: absolute; left: 9px; top: 23px;
        width: 9px; height: 9px; border-radius: 999px; background: #e8edf4;
        box-shadow: 0 0 0 2.5px #94a3b8;
    }
    .dark .dr-tag::before { background: #0c1226; box-shadow: 0 0 0 2.5px #475569; }
    .dr-tag.dr-live { border-color: rgba(5,150,105,0.55); }
    .dark .dr-tag.dr-live { border-color: rgba(52,211,153,0.5); }
    .dr-tag.dr-draft { border-style: dashed; }
    .dr-tag.dr-draft .dr-tag-val { color: #94a3b8; }
    .dr-tag.dr-expired { opacity: 0.62; }
    .dr-tag-val { font-size: 26px; font-weight: 900; color: #b45309; letter-spacing: -0.01em; }
    .dark .dr-tag-val { color: #fbbf24; }

    .dr-st-live { background: rgba(5,150,105,0.13); color: #059669; }
    .dark .dr-st-live { color: #34d399; }
    .dr-st-draft { background: rgba(100,116,139,0.15); color: #64748b; }
    .dark .dr-st-draft { color: #94a3b8; }
    .dr-st-scheduled { background: rgba(251,191,36,0.2); color: #b45309; }
    .dark .dr-st-scheduled { color: #fbbf24; }
    .dr-st-expired { background: rgba(225,29,72,0.12); color: #e11d48; }
    .dark .dr-st-expired { color: #fb7185; }

    .dr-countdown {
        display: inline-block; margin-top: 6px; font-size: 10.5px; font-weight: 800;
        color: #b45309; background: rgba(251,191,36,0.14); border-radius: 999px; padding: 2px 9px;
    }
    .dark .dr-countdown { color: #fbbf24; }

    .dr-tag-new {
        border: 2px dashed #94a3b8; border-radius: 8px 18px 18px 8px; background: transparent;
        display: grid; place-items: center; min-height: 150px; color: #64748b;
        transition: border-color .15s, color .15s; text-decoration: none;
    }
    .dr-tag-new:hover { border-color: #b45309; color: #b45309; }
    .dark .dr-tag-new { border-color: #475569; color: #94a3b8; }
    .dark .dr-tag-new:hover { border-color: #fbbf24; color: #fbbf24; }
</style>

<div class="py-8">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8" x-data="discountRules" data-config="{{ json_encode($drConfig) }}">
        @include('admin.discounts.partials._alerts')

        {{-- ============ navy header ============ --}}
        <section class="dr-hero rounded-3xl p-6 text-white shadow-sm sm:p-7">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-[11px] font-black uppercase tracking-[0.16em] text-amber-400">{{ __('Discount Rules') }}</p>
                    <h1 class="mt-1.5 text-2xl font-black tracking-tight sm:text-3xl">
                        <span class="dr-num">{{ number_format($discountRows->count()) }}</span> {{ __('rules') }}
                        <span class="text-white/40">·</span>
                        <span class="dr-num text-emerald-300">{{ number_format($liveCount) }}</span> {{ __('live') }}
                        <span class="text-white/40">·</span>
                        <span class="dr-num text-amber-400">{{ number_format($totalRedemptions) }}</span>
                        <span class="text-base font-bold text-white/60">{{ __('redemptions') }}</span>
                    </h1>
                    <p class="mt-1.5 max-w-2xl text-sm text-white/60">{{ __('Every rule is a price tag on the wall — flip it live, copy it, or press a new one below.') }}</p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <a href="{{ route('admin.discounts.edit') }}" class="rounded-xl border border-white/15 bg-white/5 px-4 py-2 text-sm font-bold text-white/80 hover:bg-white/10">{{ __('Coupon Management') }}</a>
                    <a href="{{ route('admin.discounts.rules') }}#discount-rule-form" class="rounded-xl bg-amber-400 px-4 py-2 text-sm font-extrabold text-[#422006] hover:bg-amber-300">+ {{ __('New Rule') }}</a>
                </div>
            </div>
        </section>

        {{-- ============ toolbar ============ --}}
        <div class="flex flex-wrap items-center gap-2 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <input type="search" placeholder="{{ __('Search rule name or scope…') }}" class="min-w-0 flex-[2_1_200px] rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" @input="onWallSearch">
            <select class="min-w-0 flex-[1_1_130px] rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" @change="onWallStatus">
                <option value="all">{{ __('All statuses') }}</option>
                <option value="live">{{ __('Live') }}</option>
                <option value="scheduled">{{ __('Scheduled') }}</option>
                <option value="draft">{{ __('Draft') }}</option>
                <option value="expired">{{ __('Expired') }}</option>
            </select>
            <select class="min-w-0 flex-[1_1_150px] rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" @change="onWallSort">
                <option value="default">{{ __('Sort: Status') }}</option>
                <option value="value">{{ __('Sort: Highest discount') }}</option>
                <option value="used">{{ __('Sort: Most used') }}</option>
                <option value="name">{{ __('Sort: Name A–Z') }}</option>
            </select>
            <button type="button" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" @click="exportRules"><i class="fas fa-file-csv me-1"></i>{{ __('Export') }}</button>
            <p class="w-full text-xs font-extrabold text-emerald-600 dark:text-emerald-400 sm:ms-auto sm:w-auto" x-show="hasFlash" x-cloak x-text="flashMessage"></p>
        </div>

        {{-- ============ overlap warning ============ --}}
        @if ($overlapNames->count() > 1)
            <div class="flex items-center gap-2 rounded-2xl border border-rose-300 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-600 dark:border-rose-800 dark:bg-rose-950/30 dark:text-rose-300">
                ⚠ {{ __(':count live storewide rules are competing (:names) — customers get only one; check which should win.', ['count' => $overlapNames->count(), 'names' => $overlapNames->implode(', ')]) }}
            </div>
        @endif

        {{-- ============ tag wall ============ --}}
        <section class="dr-pegboard rounded-3xl border border-slate-200 p-5 dark:border-slate-800">
            @if ($discountRows->isEmpty())
                <div class="rounded-2xl border-2 border-dashed border-slate-300 bg-white/60 px-6 py-14 text-center dark:border-slate-700 dark:bg-slate-900/60">
                    <i class="fas fa-tag mb-3 block text-2xl text-slate-300 dark:text-slate-600"></i>
                    <p class="text-sm font-bold text-slate-600 dark:text-slate-300">{{ __('No saved discount rules yet') }}</p>
                    <p class="mt-1 text-xs text-slate-400">{{ __('Press your first tag in the builder below — it will hang here.') }}</p>
                    <a href="#discount-rule-form" class="mt-4 inline-flex rounded-xl bg-[#04042a] px-4 py-2 text-xs font-bold text-white hover:bg-[#10104a]">{{ __('Create Discount Rule') }}</a>
                </div>
            @else
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3" x-ref="wall">
                    @foreach ($discountRows as $row)
                        @php
                            $chip = $tagChip((string) $row['status']);
                            $ruleJson = json_encode([
                                'name' => $row['name'],
                                'type' => $row['type'],
                                'value' => $row['valueRaw'],
                                'startsAt' => $row['startsAt'],
                                'endsAt' => $row['endsAt'],
                            ]);
                        @endphp
                        <article
                            class="dr-tag dr-{{ $row['status'] }}"
                            x-data="toggle"
                            data-tag
                            data-name="{{ strtolower($row['name']) }}"
                            data-scope-label="{{ strtolower($row['scopeLabel'] . ' ' . $row['targetLabel']) }}"
                            data-status="{{ $row['status'] }}"
                            data-value="{{ $row['type'] === 'percent' ? $row['valueRaw'] : $row['valueRaw'] / 1000 }}"
                            data-used="{{ $row['usedCount'] }}"
                            data-rule="{{ $ruleJson }}"
                        >
                            <span class="absolute end-3 top-2.5 rounded-full px-2 py-0.5 text-[10px] font-black uppercase tracking-wide {{ $chip['class'] }}">{{ $chip['label'] }}</span>
                            <p class="dr-tag-val dr-num">−{{ $row['valueLabel'] }}</p>
                            <h3 class="mt-0.5 text-sm font-extrabold text-slate-900 dark:text-slate-100">{{ $row['name'] }}</h3>
                            <p class="mt-0.5 text-[11px] text-slate-400">
                                {{ $row['scopeLabel'] }} · {{ $row['targetLabel'] }} · {{ $row['windowLabel'] }}@if($row['usedCount'] > 0) · {{ __('used :n×', ['n' => number_format($row['usedCount'])]) }}@endif
                            </p>
                            @if ($row['countdown'] !== '')
                                <span class="dr-countdown">⏱ {{ $row['countdown'] }}</span>
                            @endif

                            <div class="mt-2.5 flex flex-wrap items-center gap-1.5">
                                <a href="{{ $row['editUrl'] }}" class="rounded-lg bg-[#04042a] px-2.5 py-1.5 text-[10.5px] font-extrabold text-white hover:bg-[#10104a]">{{ __('Edit') }}</a>
                                <form action="{{ route('admin.discounts.update-rule-status', $row['id']) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="is_active" value="{{ $row['isActive'] ? 0 : 1 }}">
                                    <button type="submit" class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-[10.5px] font-extrabold text-slate-600 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800">
                                        {{ $row['isActive'] ? __('Pause') : __('Activate') }}
                                    </button>
                                </form>
                                <button type="button" class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-[10.5px] font-extrabold text-slate-600 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800" @click="duplicateRule">{{ __('Duplicate') }}</button>
                                <button type="button" class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-[10.5px] font-extrabold text-slate-600 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800" @click="toggle">
                                    <span x-show="closed">{{ __('Details') }}</span>
                                    <span x-show="open" x-cloak>{{ __('Hide') }}</span>
                                </button>
                                <form action="{{ route('admin.discounts.destroy-rule', $row['id']) }}" method="POST" data-danger-confirm data-danger-title="{{ __('Delete Discount Rule') }}" data-danger-description="{{ __('This will permanently delete the selected discount rule.') }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg px-2 py-1.5 text-[10.5px] font-extrabold text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/30">✕</button>
                                </form>
                            </div>

                            <div class="mt-2.5 border-t border-dashed border-slate-200 pt-2 text-[11px] text-slate-500 dark:border-slate-700 dark:text-slate-400" x-show="open" x-cloak>
                                <p>{{ __('Min subtotal') }}: {{ $row['minimumSubtotalLabel'] }} · {{ __('Usage limit') }}: {{ $row['usageLimitLabel'] }}</p>
                                <p class="mt-0.5">{{ __('Created') }} {{ $row['createdAtLabel'] }} · {{ __('Updated') }} {{ $row['updatedAtLabel'] }}</p>
                                <div class="mt-1.5 flex flex-wrap gap-1">
                                    @foreach ($row['targetPreview'] as $target)
                                        <span class="rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-[10px] text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400">{{ $target }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </article>
                    @endforeach

                    <a href="{{ route('admin.discounts.rules') }}#discount-rule-form" class="dr-tag-new" data-tag-new>
                        <span class="text-center">
                            <span class="block text-3xl font-black">+</span>
                            <span class="mt-1 block text-xs font-extrabold">{{ __('Print a new tag') }}</span>
                        </span>
                    </a>
                </div>
                <p class="rounded-2xl border-2 border-dashed border-slate-300 bg-white/60 px-6 py-10 text-center text-sm text-slate-400 dark:border-slate-700 dark:bg-slate-900/60" x-show="wallEmpty" x-cloak>
                    {{ __('No rules matched the search or status filter.') }}
                </p>
            @endif
        </section>

        {{-- ============ builder: the tag press ============ --}}
        <form
            id="discount-rule-form"
            action="{{ route('admin.discounts.update-rules') }}"
            method="POST"
            class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900"
        >
            @csrf
            @method('PUT')
            <input type="hidden" name="discount_id" value="{{ $discountId }}">

            <div class="dr-hero flex flex-wrap items-center justify-between gap-3 px-5 py-4 text-white">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.15em] text-amber-400">{{ __('Tag Press') }}</p>
                    <h2 class="mt-0.5 text-lg font-black">{{ $builderTitle }}</h2>
                </div>
                <label class="inline-flex items-center gap-2.5 rounded-full border border-white/15 bg-white/5 px-4 py-2 text-sm font-bold">
                    <input type="hidden" name="discounts_enabled" value="0">
                    <input type="checkbox" name="discounts_enabled" value="1" class="h-4 w-4 rounded border-white/30 bg-transparent text-amber-400 focus:ring-amber-300" @checked($discountsEnabled)>
                    {{ __('Enable Rule') }}
                </label>
            </div>

            <div class="grid gap-5 p-5 xl:grid-cols-[minmax(0,1.65fr)_minmax(280px,1fr)]">
                <div class="min-w-0 space-y-5">
                    {{-- basics --}}
                    <div class="grid gap-3 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label for="discount_label" class="mb-1 block text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ __('Display Label') }}</label>
                            <input id="discount_label" type="text" name="discount_label" x-model="basics.label" placeholder="{{ __('Summer Campaign, Brand Event, Clearance...') }}" class="w-full rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-400/25 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        </div>
                        <div>
                            <label for="discount_type" class="mb-1 block text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ __('Discount Type') }}</label>
                            <select id="discount_type" name="discount_type" x-model="basics.type" class="w-full rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-400/25 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                <option value="percent">{{ __('Percent') }}</option>
                                <option value="fixed">{{ __('Fixed') }}</option>
                            </select>
                        </div>
                        <div>
                            <label for="discount_value" class="mb-1 block text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ __('Discount Value') }}</label>
                            <input id="discount_value" type="number" step="0.01" min="0" max="100000000" name="discount_value" x-model="basics.value" class="dr-num w-full rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-400/25 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        </div>
                        <div>
                            <label for="discount_minimum_subtotal" class="mb-1 block text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ __('Minimum Subtotal') }}</label>
                            <input id="discount_minimum_subtotal" type="number" step="0.01" min="0" max="100000000" name="discount_minimum_subtotal" value="{{ $discountMinimumSubtotal }}" placeholder="{{ __('Optional') }}" class="dr-num w-full rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-400/25 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        </div>
                        <div>
                            <label for="discount_usage_limit" class="mb-1 block text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ __('Usage Limit') }}</label>
                            <input id="discount_usage_limit" type="number" min="0" max="1000000" name="discount_usage_limit" value="{{ $discountUsageLimit }}" placeholder="{{ __('Unlimited') }}" class="dr-num w-full rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-400/25 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        </div>
                        <div>
                            <label for="discount_starts_at" class="mb-1 block text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ __('Starts At') }}</label>
                            <input id="discount_starts_at" type="datetime-local" name="discount_starts_at" x-model="basics.startsAt" class="w-full rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-400/25 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        </div>
                        <div>
                            <label for="discount_ends_at" class="mb-1 block text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ __('Ends At') }}</label>
                            <input id="discount_ends_at" type="datetime-local" name="discount_ends_at" x-model="basics.endsAt" class="w-full rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-400/25 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        </div>
                    </div>

                    {{-- scope --}}
                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <p class="text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ __('Discount Scope') }}</p>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-slate-500 dark:bg-slate-800 dark:text-slate-400" x-text="scopeBadgeLabel"></span>
                        </div>
                        <div class="grid gap-2.5 sm:grid-cols-2 xl:grid-cols-4">
                            <label class="cursor-pointer rounded-xl border-[1.5px] p-3 transition" :class="scopeAllCardClass">
                                <input type="radio" name="discount_scope" value="all" x-model="scope" class="sr-only">
                                <span class="mb-1.5 flex h-8 w-8 items-center justify-center rounded-lg bg-[#04042a] text-amber-400"><i class="fas fa-globe text-xs"></i></span>
                                <span class="block text-sm font-extrabold text-slate-900 dark:text-slate-100">{{ __('All Products') }}</span>
                                <span class="block text-[11px] text-slate-400">{{ __('Full catalog') }}</span>
                            </label>
                            <label class="cursor-pointer rounded-xl border-[1.5px] p-3 transition" :class="scopeProductsCardClass">
                                <input type="radio" name="discount_scope" value="products" x-model="scope" class="sr-only">
                                <span class="mb-1.5 flex h-8 w-8 items-center justify-center rounded-lg bg-[#04042a] text-amber-400"><i class="fas fa-box text-xs"></i></span>
                                <span class="block text-sm font-extrabold text-slate-900 dark:text-slate-100">{{ __('Specific Products') }}</span>
                                <span class="block text-[11px] text-slate-400">{{ __('Pick exact items') }}</span>
                            </label>
                            <label class="cursor-pointer rounded-xl border-[1.5px] p-3 transition" :class="scopeCategoriesCardClass">
                                <input type="radio" name="discount_scope" value="categories" x-model="scope" class="sr-only">
                                <span class="mb-1.5 flex h-8 w-8 items-center justify-center rounded-lg bg-[#04042a] text-amber-400"><i class="fas fa-layer-group text-xs"></i></span>
                                <span class="block text-sm font-extrabold text-slate-900 dark:text-slate-100">{{ __('Specific Categories') }}</span>
                                <span class="block text-[11px] text-slate-400">{{ __('Product families') }}</span>
                            </label>
                            <label class="cursor-pointer rounded-xl border-[1.5px] p-3 transition" :class="scopeBrandsCardClass">
                                <input type="radio" name="discount_scope" value="brands" x-model="scope" class="sr-only">
                                <span class="mb-1.5 flex h-8 w-8 items-center justify-center rounded-lg bg-[#04042a] text-amber-400"><i class="fas fa-tags text-xs"></i></span>
                                <span class="block text-sm font-extrabold text-slate-900 dark:text-slate-100">{{ __('Specific Brands') }}</span>
                                <span class="block text-[11px] text-slate-400">{{ __('Brand-led rules') }}</span>
                            </label>
                        </div>

                        <template x-for="productId in selectedProductIds" :key="productId">
                            <input type="hidden" name="discount_product_ids[]" :value="productId">
                        </template>

                        <p class="mt-3 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-xs text-slate-500 dark:border-slate-700 dark:bg-slate-950/40 dark:text-slate-400" x-show="scopeIsAll" x-cloak>
                            {{ __('This discount will apply across the full catalog. No additional targeting is required.') }}
                        </p>

                        {{-- products picker --}}
                        <div class="mt-3 rounded-2xl border border-slate-200 bg-slate-50/70 p-4 dark:border-slate-700 dark:bg-slate-950/40" x-show="scopeIsProducts" x-cloak>
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm font-extrabold text-slate-800 dark:text-slate-100">{{ __('Search and select products') }}</p>
                                <span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-bold text-slate-500 shadow-sm dark:bg-slate-900 dark:text-slate-400"><span x-text="meta.total"></span> {{ __('results') }} · <span x-text="selectedCountLabel"></span> {{ __('selected') }}</span>
                            </div>
                            <div class="mt-3 grid gap-2 md:grid-cols-4">
                                <input type="text" x-model="filters.query" @input.debounce.300ms="refreshProducts" placeholder="{{ __('Search name, SKU, or brand') }}" class="rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-400/25 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 md:col-span-4">
                                <select x-model="filters.categoryId" @change="refreshProducts" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                    <option value="">{{ __('All categories') }}</option>
                                    <template x-for="category in categoryOptions" :key="category.id">
                                        <option :value="category.id" x-text="category.name"></option>
                                    </template>
                                </select>
                                <select x-model="filters.brand" @change="refreshProducts" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                    <option value="">{{ __('All brands') }}</option>
                                    <template x-for="brand in brandOptions" :key="brand">
                                        <option :value="brand" x-text="brand"></option>
                                    </template>
                                </select>
                                <select x-model="filters.stock" @change="refreshProducts" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                    <option value="">{{ __('All stock') }}</option>
                                    <option value="in_stock">{{ __('In stock') }}</option>
                                    <option value="low_stock">{{ __('Low stock') }}</option>
                                    <option value="out_of_stock">{{ __('Out of stock') }}</option>
                                </select>
                                <button type="button" @click="resetFilters" class="rounded-xl border border-slate-200 bg-white px-3 text-xs font-bold text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800">{{ __('Reset Filters') }}</button>
                            </div>

                            <div class="mt-3 min-h-[10rem]">
                                <p class="py-10 text-center text-sm text-slate-400" x-show="loading" x-cloak>{{ __('Loading products...') }}</p>
                                <p class="rounded-xl border border-dashed border-slate-300 bg-white px-4 py-10 text-center text-sm text-slate-400 dark:border-slate-700 dark:bg-slate-900" x-show="productsEmpty" x-cloak>{{ __('No products matched the current search and filters.') }}</p>
                                <div class="grid gap-2.5 md:grid-cols-2 xl:grid-cols-3" x-show="productsVisible" x-cloak>
                                    <template x-for="product in products" :key="product.id">
                                        <button type="button" @click="toggleProduct(product)" class="rounded-xl border-[1.5px] p-3 text-left transition" :class="productCardClass(product)">
                                            <div class="flex items-start justify-between gap-2">
                                                <span class="line-clamp-2 text-[13px] font-bold leading-snug text-slate-900 dark:text-slate-100" x-text="product.name"></span>
                                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg border text-[11px] font-black" :class="productTickClass(product)">
                                                    <span x-show="productIsSelected(product)" x-cloak>✓</span>
                                                    <span x-show="productNotSelected(product)">+</span>
                                                </span>
                                            </div>
                                            <div class="mt-1.5 flex flex-wrap gap-1 text-[10px] text-slate-400">
                                                <span class="rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 dark:border-slate-700 dark:bg-slate-800" x-text="productSku(product)"></span>
                                                <span class="rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 dark:border-slate-700 dark:bg-slate-800" x-show="product.brand" x-text="product.brand"></span>
                                                <span class="rounded-full px-2 py-0.5 font-bold" :class="stockToneClass(product)" x-text="stockLabel(product)"></span>
                                            </div>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                                <p class="text-xs text-slate-400">{{ __('Page') }} <span x-text="meta.currentPage"></span> / <span x-text="meta.lastPage"></span></p>
                                <div class="flex gap-2">
                                    <button type="button" x-show="meta.hasMore" x-cloak @click="loadMoreProducts" class="rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800">{{ __('Load More Products') }}</button>
                                    <button type="button" @click="clearSelectedProducts" :disabled="selectionEmpty" class="rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800">{{ __('Clear All') }}</button>
                                </div>
                            </div>

                            <div class="mt-3 space-y-2" x-show="hasSelection" x-cloak>
                                <p class="text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ __('Selected Products') }}</p>
                                <div class="grid max-h-64 gap-2 overflow-y-auto md:grid-cols-2">
                                    <template x-for="product in selectedProductList" :key="product.id">
                                        <div class="flex items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 dark:border-slate-700 dark:bg-slate-900">
                                            <div class="min-w-0">
                                                <p class="truncate text-[12.5px] font-bold text-slate-800 dark:text-slate-100" x-text="product.name"></p>
                                                <p class="text-[10px] text-slate-400" x-text="productSku(product)"></p>
                                            </div>
                                            <button type="button" @click="removeProduct(product)" class="shrink-0 rounded-lg px-2 py-1 text-xs font-black text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/30">✕</button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        {{-- categories --}}
                        <div class="mt-3 rounded-2xl border border-slate-200 bg-slate-50/70 p-4 dark:border-slate-700 dark:bg-slate-950/40" x-show="scopeIsCategories" x-cloak>
                            <p class="text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ __('Categories') }}</p>
                            <div class="mt-2 grid max-h-72 gap-1.5 overflow-y-auto md:grid-cols-2" @change="syncCategorySelection">
                                @foreach ($categories as $category)
                                    <label class="flex items-center gap-2.5 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-amber-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-amber-900/50">
                                        <input type="checkbox" name="discount_category_ids[]" value="{{ $category->id }}" class="h-4 w-4 rounded border-slate-300 text-amber-500 focus:ring-amber-400" @checked(in_array((int) $category->id, $selectedCategories, true))>
                                        <span class="truncate">{{ $category->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- brands --}}
                        <div class="mt-3 rounded-2xl border border-slate-200 bg-slate-50/70 p-4 dark:border-slate-700 dark:bg-slate-950/40" x-show="scopeIsBrands" x-cloak>
                            <p class="text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ __('Brands') }}</p>
                            <div class="mt-2 grid max-h-72 gap-1.5 overflow-y-auto md:grid-cols-2" @change="syncBrandSelection">
                                @foreach ($brands as $brand)
                                    <label class="flex items-center gap-2.5 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-amber-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-amber-900/50">
                                        <input type="checkbox" name="discount_brands[]" value="{{ $brand }}" class="h-4 w-4 rounded border-slate-300 text-amber-500 focus:ring-amber-400" @checked(in_array((string) $brand, $selectedBrands, true))>
                                        <span class="truncate">{{ $brand }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                {{-- sticky summary rail --}}
                <aside class="min-w-0 xl:sticky xl:top-4 xl:self-start">
                    <div class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700">
                        <div class="flex items-center justify-between bg-slate-50 px-4 py-2.5 dark:bg-slate-950/40">
                            <span class="text-[10px] font-black uppercase tracking-[0.13em] text-slate-400">{{ __('Live Summary') }}</span>
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-black uppercase tracking-wide {{ $discountsEnabled ? 'dr-st-live' : 'dr-st-draft' }}">{{ $discountsEnabled ? __('Live') : __('Draft') }}</span>
                        </div>
                        <div class="p-4">
                            <p class="dr-num text-3xl font-black text-amber-700 dark:text-amber-400" x-text="summaryValueLabel"></p>
                            <p class="mt-1 text-sm font-bold text-slate-700 dark:text-slate-200" x-text="summaryLabelText"></p>
                            <p class="mt-0.5 text-xs text-slate-400" x-text="scopeUtilizationLabel"></p>
                            <p class="dr-num mt-0.5 text-xs text-slate-400" x-text="summaryWindowLabel"></p>

                            <div class="mt-4 border-t border-dashed border-slate-200 pt-3 dark:border-slate-700">
                                <label class="text-[10px] font-black uppercase tracking-[0.13em] text-slate-400">{{ __('Price Simulator') }}</label>
                                <input type="number" min="0" placeholder="{{ __('Sample price, e.g. 25000') }}" class="dr-num mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-400/25 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" @input="onSimInput">
                                <p class="dr-num mt-2 text-sm font-bold text-emerald-600 dark:text-emerald-400" x-text="simOutLabel"></p>
                            </div>

                            <button type="submit" class="mt-4 w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-extrabold text-white hover:bg-emerald-500">{{ $builderActionLabel }}</button>
                            @if ($isEditingDiscount)
                                <a href="{{ route('admin.discounts.rules') }}#discount-rule-form" class="mt-2 block rounded-xl border border-slate-200 px-4 py-2.5 text-center text-sm font-bold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">{{ __('Create New Rule') }}</a>
                            @endif
                        </div>
                    </div>
                </aside>
            </div>
        </form>
    </div>
</div>
</x-app-layout>
