<x-app-layout>
<x-slot name="header">
    <span>{{ __('Coupon Analytics & Management') }}</span>
</x-slot>

@php
    $couponEnabled = (string) old('coupon_enabled', data_get($settings, 'coupon_enabled', '0')) === '1';
    $couponType = old('coupon_type', (string) data_get($settings, 'coupon_type', 'percent'));
    $couponCode = (string) old('coupon_code', (string) data_get($settings, 'coupon_code', ''));
    $couponStartsAt = old('coupon_starts_at', (string) data_get($settings, 'coupon_starts_at', ''));
    $couponEndsAt = old('coupon_ends_at', (string) data_get($settings, 'coupon_ends_at', ''));

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
    $currencyLabel = (string) data_get($dashboard, 'currencyLabel', 'IQD');
    $currencyDecimals = (int) data_get($dashboard, 'currencyDecimals', 0);
    $trendPoints = array_values(array_map('intval', (array) data_get($dashboard, 'trendPoints', array_fill(0, 12, 0))));
    $trendMax = max(1, max($trendPoints ?: [0]));
    $usageDistribution = (array) data_get($dashboard, 'usageDistribution', []);
    $couponRows = (array) data_get($dashboard, 'coupons', []);

    $activeRowCount = collect($couponRows)->where('status', 'active')->count();
    $scheduledRowCount = collect($couponRows)->where('status', 'scheduled')->count();
    $expiredRowCount = collect($couponRows)->where('status', 'expired')->count();
    $pausedRowCount = collect($couponRows)->where('status', 'paused')->count();

    $chip = fn (string $couponStatus) => match ($couponStatus) {
        'active' => ['label' => __('Active'), 'class' => 'cp-chip-active'],
        'scheduled' => ['label' => __('Scheduled'), 'class' => 'cp-chip-scheduled'],
        'expired' => ['label' => __('Expired'), 'class' => 'cp-chip-expired'],
        default => ['label' => __('Paused'), 'class' => 'cp-chip-paused'],
    };

    $usageLabel = fn (array $c) => number_format($c['usageUsed']) . ' / ' . ($c['usageLimit'] > 0 ? number_format($c['usageLimit']) : '∞');
    $usagePercent = fn (array $c) => $c['usageLimit'] > 0 ? max(0, min(100, (int) round(($c['usageUsed'] / $c['usageLimit']) * 100))) : 0;

    $cpConfig = [
        'rows' => collect($couponRows)->map(fn (array $c) => [
            'code' => $c['code'],
            'discount' => $c['discount'],
            'usage' => $usageLabel($c),
            'expiry' => $c['expiry'],
            'status' => $c['status'],
        ])->values(),
        'settingsOpen' => $errors->any(),
        'code' => $couponCode,
        'type' => $couponType,
        'enabled' => $couponEnabled,
        'labels' => [
            'live' => __('Campaign Live'),
            'draft' => __('Draft Mode'),
            'csvHeaders' => [__('Code'), __('Discount'), __('Usage'), __('Expiry'), __('Status')],
        ],
    ];
@endphp

<style>
    /* Coupon console (cp-) */
    .cp-hero {
        background: linear-gradient(135deg, #04042a, #10104a);
        position: relative; overflow: hidden;
    }
    .cp-hero::after {
        content: ""; position: absolute; inset: 0;
        background-image: repeating-linear-gradient(135deg, rgba(255,255,255,0.05) 0 1px, transparent 1px 14px);
    }
    .cp-hero > * { position: relative; z-index: 1; }

    .cp-num { font-family: ui-monospace, 'JetBrains Mono', Consolas, monospace; font-variant-numeric: tabular-nums; }

    .cp-chip-active { background: rgba(5,150,105,0.12); color: #059669; }
    .dark .cp-chip-active { color: #34d399; }
    .cp-chip-scheduled { background: rgba(251,191,36,0.18); color: #b45309; }
    .dark .cp-chip-scheduled { color: #fbbf24; }
    .cp-chip-expired { background: rgba(225,29,72,0.12); color: #e11d48; }
    .dark .cp-chip-expired { color: #fb7185; }
    .cp-chip-paused { background: rgba(100,116,139,0.14); color: #64748b; }
    .dark .cp-chip-paused { color: #94a3b8; }

    .cp-spark { display: flex; align-items: flex-end; gap: 4px; height: 120px; }
    .cp-spark > div { flex: 1; border-radius: 4px 4px 0 0; background: linear-gradient(to top, #04042a, #10104a); min-height: 4px; }
    .dark .cp-spark > div { background: linear-gradient(to top, #334155, #475569); }
    .cp-spark > div.hot { background: #fbbf24; }
</style>

<div class="py-8">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8" x-data="couponConsole" data-config="{{ json_encode($cpConfig) }}">
        @include('admin.discounts.partials._alerts')

        {{-- ============ navy command header ============ --}}
        <section class="cp-hero rounded-3xl p-6 text-white shadow-sm sm:p-7">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-[11px] font-black uppercase tracking-[0.16em] text-amber-400">{{ __('Coupon Operations') }}</p>
                    <h1 class="mt-1.5 text-2xl font-black tracking-tight sm:text-3xl">
                        <span class="cp-num">{{ number_format(count($couponRows)) }}</span> {{ __('campaigns') }}
                        <span class="text-white/40">·</span>
                        <span class="cp-num">{{ number_format($totalRedemptions) }}</span> {{ __('redemptions') }}
                        <span class="text-white/40">·</span>
                        <span class="cp-num text-amber-400">{{ number_format($revenueImpact, $currencyDecimals) }} {{ $currencyLabel }}</span>
                        <span class="text-base font-bold text-white/60">{{ __('impact') }}</span>
                    </h1>
                    <p class="mt-1.5 max-w-2xl text-sm text-white/60">{{ __('Manage coupon campaigns, watch redemption momentum, and control the site coupon from one console.') }}</p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <span class="rounded-full px-3 py-1 text-xs font-black uppercase tracking-wide" :class="settingsStateClass" x-text="settingsStateLabel"></span>
                    <a href="{{ route('admin.discounts.coupons.create') }}" class="rounded-xl bg-amber-400 px-4 py-2 text-sm font-extrabold text-[#422006] hover:bg-amber-300">+ {{ __('New Coupon') }}</a>
                </div>
            </div>
            <div class="mt-5 grid grid-cols-2 gap-x-8 gap-y-4 sm:grid-cols-3 lg:flex lg:flex-wrap lg:gap-10">
                <div>
                    <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-white/45">{{ __('Active') }}</p>
                    <p class="cp-num mt-0.5 text-xl font-black text-emerald-300">{{ number_format($activeRowCount) }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-white/45">{{ __('Scheduled') }}</p>
                    <p class="cp-num mt-0.5 text-xl font-black text-amber-400">{{ number_format($scheduledRowCount) }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-white/45">{{ __('Expired') }}</p>
                    <p class="cp-num mt-0.5 text-xl font-black text-rose-300">{{ number_format($expiredRowCount) }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-white/45">{{ __('Paused') }}</p>
                    <p class="cp-num mt-0.5 text-xl font-black text-slate-300">{{ number_format($pausedRowCount) }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-white/45">{{ __('Average Discount') }}</p>
                    <p class="cp-num mt-0.5 text-xl font-black">{{ $avgDiscount }}</p>
                </div>
            </div>
        </section>

        {{-- ============ analytics row ============ --}}
        <section class="grid gap-4 xl:grid-cols-[1.5fr_1fr]">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[10px] font-extrabold uppercase tracking-[0.13em] text-slate-400">{{ __('Redemptions') }}</p>
                        <h2 class="mt-0.5 text-sm font-extrabold text-slate-800 dark:text-slate-100">{{ __('Last 12 days') }}</h2>
                    </div>
                    <span class="cp-num rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ number_format($totalRedemptions) }} {{ __('total') }}</span>
                </div>
                <div class="cp-spark mt-4">
                    @foreach ($trendPoints as $index => $point)
                        <div class="{{ $index === count($trendPoints) - 1 ? 'hot' : '' }}" style="height: {{ max(4, (int) round(($point / $trendMax) * 100)) }}%" title="{{ $point }}"></div>
                    @endforeach
                </div>
                <div class="mt-2 flex justify-between text-[10px] font-bold text-slate-400">
                    <span>{{ now()->subDays(11)->format('d M') }}</span>
                    <span>{{ __('today') }}</span>
                </div>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <p class="text-[10px] font-extrabold uppercase tracking-[0.13em] text-slate-400">{{ __('Usage depth') }}</p>
                <h2 class="mt-0.5 text-sm font-extrabold text-slate-800 dark:text-slate-100">{{ __('How exhausted are limited coupons?') }}</h2>
                <div class="mt-4 space-y-3.5">
                    @foreach ($usageDistribution as $bucket)
                        <div>
                            <div class="mb-1 flex items-center justify-between text-xs font-bold text-slate-500 dark:text-slate-400">
                                <span>{{ $bucket['label'] }}</span>
                                <span class="cp-num">{{ (int) $bucket['value'] }}%</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                <div class="h-full rounded-full bg-amber-400" style="width: {{ (int) $bucket['value'] }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </article>
        </section>

        {{-- ============ coupons table ============ --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 px-4 py-3 dark:border-slate-800">
                <i class="fas fa-ticket text-slate-400"></i>
                <h3 class="text-sm font-extrabold text-slate-800 dark:text-slate-100">{{ __('Coupon Campaigns') }}</h3>
                <div class="ms-auto flex flex-wrap items-center gap-2">
                    <input type="search" placeholder="{{ __('Search code…') }}" class="h-9 w-44 rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" @input="onSearchInput">
                    <select class="h-9 rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" @change="onStatusChange">
                        <option value="all">{{ __('All Status') }}</option>
                        <option value="active">{{ __('Active') }}</option>
                        <option value="scheduled">{{ __('Scheduled') }}</option>
                        <option value="expired">{{ __('Expired') }}</option>
                        <option value="paused">{{ __('Paused') }}</option>
                    </select>
                    <button type="button" class="h-9 rounded-xl border border-slate-200 px-3.5 text-sm font-bold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" @click="exportCsv"><i class="fas fa-file-csv me-1"></i>{{ __('Export') }}</button>
                </div>
            </div>
            @if (count($couponRows) === 0)
                <div class="px-4 py-14 text-center">
                    <i class="fas fa-ticket mb-3 block text-2xl text-slate-300 dark:text-slate-600"></i>
                    <p class="text-sm font-bold text-slate-600 dark:text-slate-300">{{ __('No coupons yet') }}</p>
                    <p class="mt-1 text-xs text-slate-400">{{ __('Create your first coupon campaign to start tracking redemptions here.') }}</p>
                    <a href="{{ route('admin.discounts.coupons.create') }}" class="mt-4 inline-flex rounded-xl bg-[#04042a] px-4 py-2 text-xs font-bold text-white hover:bg-[#10104a]">{{ __('Create Coupon') }}</a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-950/40 dark:text-slate-400">
                            <tr>
                                <th class="px-4 py-3 text-left">{{ __('Coupon Code') }}</th>
                                <th class="px-4 py-3 text-left">{{ __('Discount') }}</th>
                                <th class="px-4 py-3 text-left">{{ __('Usage') }}</th>
                                <th class="px-4 py-3 text-left">{{ __('Expiry') }}</th>
                                <th class="px-4 py-3 text-left">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($couponRows as $couponRow)
                                @php
                                    $rowChip = $chip((string) $couponRow['status']);
                                    $rowJson = $couponRow['id'] > 0 ? json_encode([
                                        'code' => $couponRow['code'],
                                        'type' => $couponRow['type'],
                                        'value' => $couponRow['valueRaw'],
                                        'usageLimit' => $couponRow['usageLimit'],
                                        'startsAt' => $couponRow['startsAt'],
                                        'endsAt' => $couponRow['endsAt'],
                                        'usage' => $usageLabel($couponRow),
                                        'updateUrl' => route('admin.discounts.coupons.update', $couponRow['id']),
                                    ]) : null;
                                @endphp
                                <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40"
                                    data-coupon-row
                                    data-code="{{ strtolower($couponRow['code']) }}"
                                    data-status="{{ $couponRow['status'] }}"
                                    @if($rowJson) data-coupon="{{ $rowJson }}" @endif>
                                    <td class="cp-num px-4 py-3 font-black tracking-wide text-slate-900 dark:text-slate-100">{{ $couponRow['code'] }}</td>
                                    <td class="cp-num px-4 py-3 text-slate-700 dark:text-slate-200">{{ $couponRow['discount'] }}</td>
                                    <td class="px-4 py-3">
                                        <span class="cp-num text-xs text-slate-500 dark:text-slate-400">{{ $usageLabel($couponRow) }}</span>
                                        <div class="mt-1 h-1.5 w-28 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                            <div class="h-full rounded-full {{ $usagePercent($couponRow) >= 90 ? 'bg-rose-500' : 'bg-amber-400' }}" style="width: {{ $usagePercent($couponRow) }}%"></div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-slate-500 dark:text-slate-400">{{ $couponRow['expiry'] }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-wide {{ $rowChip['class'] }}">{{ $rowChip['label'] }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($couponRow['id'] > 0)
                                            <div class="flex items-center justify-end gap-1.5">
                                                <button type="button" class="rounded-lg border border-slate-200 px-3 py-1.5 text-[11px] font-bold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" @click="openEdit">{{ __('Edit') }}</button>
                                                <form method="POST" action="{{ route('admin.discounts.coupons.toggle', $couponRow['id']) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="rounded-lg bg-[#04042a] px-3 py-1.5 text-[11px] font-bold text-white hover:bg-[#10104a]">
                                                        {{ $couponRow['status'] === 'active' || $couponRow['status'] === 'scheduled' ? __('Pause') : __('Activate') }}
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('admin.discounts.coupons.destroy', $couponRow['id']) }}" data-confirm="{{ __('Delete coupon :code? This cannot be undone.', ['code' => $couponRow['code']]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="rounded-lg px-3 py-1.5 text-[11px] font-bold text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/30">{{ __('Delete') }}</button>
                                                </form>
                                            </div>
                                        @else
                                            <div class="flex justify-end">
                                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-slate-400 dark:bg-slate-800" title="{{ __('This row comes from the site coupon settings below.') }}">{{ __('Settings coupon') }}</span>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            <tr x-show="filterEmpty" x-cloak>
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-400">{{ __('No coupons matched the search or status filter.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        {{-- ============ site coupon settings (collapsible, existing form) ============ --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <button type="button" class="flex w-full items-center gap-2 px-4 py-3.5 text-left" @click="toggleSettings">
                <i class="fas fa-sliders text-slate-400"></i>
                <span class="text-sm font-extrabold text-slate-800 dark:text-slate-100">{{ __('Site Coupon Settings') }}</span>
                <span class="cp-num ms-2 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-black text-slate-500 dark:bg-slate-800 dark:text-slate-400">{{ $couponCode !== '' ? $couponCode : __('No code') }}</span>
                <span class="ms-auto text-slate-400" x-text="settingsChevron">▾</span>
            </button>
            <div class="border-t border-slate-200 dark:border-slate-800" x-show="settingsOpen" x-cloak>
                <form method="POST" action="{{ route('admin.discounts.update') }}" class="p-4 sm:p-5">
                    @csrf
                    @method('PUT')

                    {{-- preserve discount-rule settings on save (unchanged passthrough) --}}
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

                    <label class="inline-flex items-center gap-2.5 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 dark:border-slate-700 dark:text-slate-200">
                        <input type="checkbox" name="coupon_enabled" value="1" x-model="couponEnabled" class="h-4 w-4 rounded border-slate-300 text-amber-500 focus:ring-amber-400 dark:border-slate-600 dark:bg-slate-900">
                        {{ __('Campaign Active') }}
                        <span class="rounded-full px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wide" :class="settingsStateClass" x-text="settingsStateLabel"></span>
                    </label>

                    <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3" :class="settingsDimClass">
                        <div>
                            <label for="coupon_code" class="mb-1 block text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ __('Coupon Code') }}</label>
                            <div class="flex gap-2">
                                <input type="text" name="coupon_code" id="coupon_code" :value="settingsCode" @input="onSettingsCodeInput" placeholder="SAVE10" class="cp-num w-full rounded-xl border-slate-300 text-sm uppercase focus:border-amber-400 focus:ring-2 focus:ring-amber-400/25 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                <button type="button" class="shrink-0 rounded-xl border border-slate-200 px-3 text-xs font-bold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" @click="generateCode">{{ __('Generate') }}</button>
                            </div>
                            @error('coupon_code')
                                <span class="mt-1 block text-xs font-medium text-rose-600">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label for="coupon_type" class="mb-1 block text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ __('Type') }}</label>
                            <select name="coupon_type" id="coupon_type" x-model="settingsType" class="w-full rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-400/25 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                <option value="percent" @selected($couponType === 'percent')>{{ __('Percent (%)') }}</option>
                                <option value="fixed" @selected($couponType === 'fixed')>{{ __('Fixed Amount') }}</option>
                            </select>
                        </div>
                        <div>
                            <label for="coupon_value" class="mb-1 block text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ __('Value') }}</label>
                            <input type="number" step="0.01" min="0" :max="settingsValueMax" name="coupon_value" id="coupon_value" value="{{ old('coupon_value', (string) data_get($settings, 'coupon_value', '0')) }}" class="cp-num w-full rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-400/25 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                            <span class="mt-1 block text-[11px] text-slate-400">{{ __('Percent type supports max 100.') }}</span>
                            @error('coupon_value')
                                <span class="mt-1 block text-xs font-medium text-rose-600">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label for="coupon_min_order" class="mb-1 block text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ __('Minimum Order') }}</label>
                            <input type="number" step="0.01" min="0" name="coupon_min_order" id="coupon_min_order" value="{{ old('coupon_min_order', (string) data_get($settings, 'coupon_min_order', '0')) }}" class="cp-num w-full rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-400/25 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        </div>
                        <div>
                            <label for="coupon_usage_limit" class="mb-1 block text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ __('Usage Limit') }}</label>
                            <input type="number" min="0" name="coupon_usage_limit" id="coupon_usage_limit" value="{{ old('coupon_usage_limit', (string) data_get($settings, 'coupon_usage_limit', '0')) }}" class="cp-num w-full rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-400/25 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        </div>
                        <div class="grid grid-cols-[1fr_1fr_auto] items-end gap-2">
                            <div>
                                <label for="coupon_starts_at" class="mb-1 block text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ __('Starts At') }}</label>
                                <input type="date" name="coupon_starts_at" id="coupon_starts_at" x-ref="couponStarts" value="{{ $couponStartsAt }}" class="w-full rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-400/25 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                            </div>
                            <div>
                                <label for="coupon_ends_at" class="mb-1 block text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ __('Expiry') }}</label>
                                <input type="date" name="coupon_ends_at" id="coupon_ends_at" x-ref="couponEnds" value="{{ $couponEndsAt }}" class="w-full rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-400/25 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                @error('coupon_ends_at')
                                    <span class="mt-1 block text-xs font-medium text-rose-600">{{ $message }}</span>
                                @enderror
                            </div>
                            <button type="button" class="h-[38px] rounded-xl border border-slate-200 px-3 text-xs font-bold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" @click="clearDates">{{ __('Clear') }}</button>
                        </div>
                    </div>

                    <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4 dark:border-slate-800">
                        <p class="text-xs text-slate-400">{{ __('Saving publishes the site coupon configuration. Discount rule settings are preserved.') }}</p>
                        <button type="submit" class="rounded-xl bg-[#04042a] px-5 py-2.5 text-sm font-extrabold text-white hover:bg-[#10104a]">{{ __('Save Coupon Configuration') }}</button>
                    </div>
                </form>
            </div>
        </section>

        {{-- ============ edit coupon drawer ============ --}}
        <div class="fixed inset-0 z-50 bg-[#04042a]/55" x-show="editOpen" x-cloak @click.self="closeEdit" role="dialog" aria-modal="true">
            <aside class="absolute end-0 top-0 flex h-full w-full max-w-md flex-col bg-white shadow-2xl dark:bg-slate-900">
                <div class="cp-hero flex items-start justify-between gap-3 px-5 py-4 text-white">
                    <div class="min-w-0">
                        <p class="text-[10px] font-black uppercase tracking-[0.14em] text-amber-400">{{ __('Edit Coupon') }}</p>
                        <h3 class="cp-num mt-0.5 truncate text-lg font-black tracking-wide" x-text="edit.code"></h3>
                        <p class="cp-num text-[11px] text-white/60"><span x-text="editUsageLabel"></span> {{ __('used') }}</p>
                    </div>
                    <button type="button" class="shrink-0 px-1 text-lg" @click="closeEdit" aria-label="{{ __('Close') }}">✕</button>
                </div>

                <form method="POST" :action="editUpdateUrl" class="flex min-h-0 flex-1 flex-col">
                    @csrf
                    @method('PATCH')
                    <div class="min-h-0 flex-1 space-y-4 overflow-y-auto p-5">
                        <div>
                            <label class="mb-1 block text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ __('Coupon Code') }}</label>
                            <input type="text" name="code" required :value="edit.code" @input="onEditCodeInput" class="cp-num w-full rounded-xl border-slate-300 text-sm uppercase focus:border-amber-400 focus:ring-2 focus:ring-amber-400/25 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="mb-1 block text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ __('Type') }}</label>
                                <select name="type" x-model="edit.type" class="w-full rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-400/25 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                    <option value="percent">{{ __('Percent (%)') }}</option>
                                    <option value="fixed">{{ __('Fixed Amount') }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ __('Value') }}</label>
                                <input type="number" name="value" required step="0.01" min="0" :max="editValueMax" x-model="edit.value" class="cp-num w-full rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-400/25 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ __('Usage Limit') }} <span class="font-normal text-slate-400">({{ __('0 = unlimited') }})</span></label>
                            <input type="number" name="usage_limit" min="0" x-model="edit.usageLimit" class="cp-num w-full rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-400/25 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="mb-1 block text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ __('Starts At') }}</label>
                                <input type="date" name="starts_at" x-model="edit.startsAt" class="w-full rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-400/25 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                            </div>
                            <div>
                                <label class="mb-1 block text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ __('Expiry') }}</label>
                                <input type="date" name="ends_at" x-model="edit.endsAt" class="w-full rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-400/25 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                            </div>
                        </div>
                        <p class="rounded-xl bg-slate-50 px-3.5 py-2.5 text-[11px] leading-relaxed text-slate-500 dark:bg-slate-950/40 dark:text-slate-400">{{ __('Changes are saved to the coupons table and logged in the activity log. Status follows the active switch and the schedule automatically.') }}</p>
                    </div>
                    <div class="flex items-center gap-2 border-t border-slate-200 px-5 py-3.5 dark:border-slate-800">
                        <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-extrabold text-white hover:bg-emerald-500">{{ __('Save Changes') }}</button>
                        <button type="button" class="ms-auto rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" @click="closeEdit">{{ __('Cancel') }}</button>
                    </div>
                </form>
            </aside>
        </div>
    </div>
</div>
</x-app-layout>
