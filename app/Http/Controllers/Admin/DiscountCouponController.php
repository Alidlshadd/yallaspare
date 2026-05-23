<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Discount;
use App\Models\Product;
use App\Models\Setting;
use App\Services\CouponService;
use App\Support\SqlSafe;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DiscountCouponController extends Controller
{
    public function __construct(private readonly CouponService $couponService)
    {
    }

    public function createCoupon(): View
    {
        $settings = Setting::allWithDefaults();

        return view('admin.discounts.create-coupon', [
            'settings' => $settings,
        ]);
    }

    public function edit(): View
    {
        $settings = Setting::allWithDefaults();
        [$products, $categories, $brands] = $this->loadDiscountResources();
        $dashboard = $this->buildCouponDashboardData($settings);

        return view('admin.discounts.edit', [
            'settings' => $settings,
            'products' => $products,
            'categories' => $categories,
            'brands' => $brands,
            'dashboard' => $dashboard,
        ]);
    }

    public function rules(Request $request): View
    {
        $settings = Setting::allWithDefaults();
        $this->seedLegacyDiscountRuleFromSettings($settings);
        [, $categories, $brands] = $this->loadDiscountResources(includeProducts: false);
        $editingDiscountId = max(0, (int) old('discount_id', (int) $request->query('discount')));
        $editingDiscount = $editingDiscountId > 0
            ? Discount::query()
                ->with(['products:id,name_en,name_ar,name_ku,sku,brand,category_id,stock_quantity', 'products.category:id,name_en,name_ar,name_ku', 'categories:id,name_en,name_ar,name_ku'])
                ->find($editingDiscountId)
            : null;
        $formState = $this->buildDiscountRuleFormState($editingDiscount);
        $selectedProductIds = collect(is_array(old('discount_product_ids')) ? old('discount_product_ids') : ($formState['selected_products'] ?? []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values();
        $selectedProductsData = $selectedProductIds->isEmpty()
            ? collect()
            : Product::query()
                ->select(['id', 'name_en', 'name_ar', 'name_ku', 'sku', 'brand', 'category_id', 'stock_quantity'])
                ->with(['category:id,name_en,name_ar,name_ku'])
                ->whereIn('id', $selectedProductIds->all())
                ->orderBy('name_en')
                ->get()
                ->map(fn (Product $product) => $this->transformProductPickerRow($product));
        $discountRows = Discount::query()
            ->withCount(['products', 'categories'])
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (Discount $discount) => $this->transformDiscountRuleRow($discount));

        return view('admin.discounts.rules', [
            'settings' => $settings,
            'categories' => $categories,
            'brands' => $brands,
            'discountRows' => $discountRows,
            'editingDiscount' => $editingDiscount,
            'formState' => $formState,
            'selectedProductsData' => $selectedProductsData,
        ]);
    }

    public function searchProducts(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));
        $brand = trim((string) $request->query('brand', ''));
        $stock = trim((string) $request->query('stock', ''));
        $categoryId = (int) $request->query('category_id', 0);
        $perPage = max(12, min(36, (int) $request->query('per_page', 18)));

        $products = Product::query()
            ->select(['id', 'name_en', 'name_ar', 'name_ku', 'sku', 'brand', 'category_id', 'stock_quantity', 'low_stock_threshold'])
            ->with(['category:id,name_en,name_ar,name_ku'])
            ->where('is_active', true)
            ->when($query !== '', function ($builder) use ($query) {
                $builder->where(function ($nested) use ($query) {
                    SqlSafe::whereLike($nested, 'name_en', $query);
                    SqlSafe::orWhereLike($nested, 'name_ar', $query);
                    SqlSafe::orWhereLike($nested, 'name_ku', $query);
                    SqlSafe::orWhereLike($nested, 'sku', $query);
                    SqlSafe::orWhereLike($nested, 'brand', $query);
                });
            })
            ->when($categoryId > 0, fn ($builder) => $builder->where('category_id', $categoryId))
            ->when($brand !== '', fn ($builder) => $builder->where('brand', $brand))
            ->when($stock !== '', function ($builder) use ($stock) {
                if ($stock === 'in_stock') {
                    $builder->where('stock_quantity', '>', 0);
                    return;
                }

                if ($stock === 'out_of_stock') {
                    $builder->where('stock_quantity', '<=', 0);
                    return;
                }

                if ($stock === 'low_stock') {
                    $builder->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                        ->where('stock_quantity', '>', 0);
                }
            })
            ->orderBy('name_en')
            ->paginate($perPage);

        return response()->json([
            'data' => $products->getCollection()->map(fn (Product $product) => $this->transformProductPickerRow($product))->values(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'has_more' => $products->hasMorePages(),
            ],
        ]);
    }

    /**
     * @return array{0: \Illuminate\Support\Collection<int, \App\Models\Product>, 1: \Illuminate\Support\Collection<int, \App\Models\Category>, 2: \Illuminate\Support\Collection<int, string>}
     */
    private function loadDiscountResources(bool $includeProducts = true): array
    {
        $products = $includeProducts
            ? Product::query()
                ->select(['id', 'name_en', 'name_ar', 'name_ku', 'sku', 'brand'])
                ->where('is_active', true)
                ->orderBy('name_en')
                ->get()
            : collect();
        $categories = Category::query()
            ->select(['id', 'name_en', 'name_ar', 'name_ku'])
            ->orderBy('name_en')
            ->get();
        $brands = Product::query()
            ->whereNotNull('brand')
            ->where('brand', '!=', '')
            ->distinct()
            ->orderBy('brand')
            ->pluck('brand')
            ->values();

        return [$products, $categories, $brands];
    }

    /**
     * @return array{id:int,name:string,sku:string,brand:?string,category:?string,stock_quantity:int,stock_state:string}
     */
    private function transformProductPickerRow(Product $product): array
    {
        $stockQuantity = (int) ($product->stock_quantity ?? 0);
        $lowStockThreshold = (int) ($product->low_stock_threshold ?? 0);
        $stockState = $stockQuantity <= 0
            ? 'out_of_stock'
            : ($lowStockThreshold > 0 && $stockQuantity <= $lowStockThreshold ? 'low_stock' : 'in_stock');

        return [
            'id' => (int) $product->id,
            'name' => (string) $product->name,
            'sku' => (string) ($product->sku ?? ''),
            'brand' => filled($product->brand) ? (string) $product->brand : null,
            'category' => (string) optional($product->category)->name,
            'stock_quantity' => $stockQuantity,
            'stock_state' => $stockState,
        ];
    }

    /**
     * Build dashboard data from persisted coupon/coupon_usages records.
     *
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private function buildCouponDashboardData(array $settings): array
    {
        $now = Carbon::now();
        $currencyCode = strtoupper((string) data_get($settings, 'currency_code', 'IQD'));
        $currencySymbol = (string) data_get($settings, 'currency_symbol', $currencyCode);
        $currencyLabel = $currencyCode !== '' ? $currencyCode : $currencySymbol;
        $currencyDecimals = $currencyCode === 'IQD' ? 0 : 2;
        $coupons = [];
        $activeCoupons = 0;
        $totalRedemptions = 0;
        $avgDiscountValue = 0.0;
        $avgDiscountSuffix = '%';

        if (Schema::hasTable('coupons')) {
            $couponQuery = DB::table('coupons')
                ->select([
                    'id',
                    'code',
                    'type',
                    'value',
                    'usage_limit',
                    'used_count',
                    'starts_at',
                    'ends_at',
                    'is_active',
                    'created_at',
                ]);

            if (Schema::hasColumn('coupons', 'deleted_at')) {
                $couponQuery->whereNull('deleted_at');
            }

            $couponRows = $couponQuery
                ->orderByDesc('created_at')
                ->limit(200)
                ->get();

            $coupons = $couponRows->map(function ($row) use ($now) {
                $startsAt = $row->starts_at ? Carbon::parse((string) $row->starts_at) : null;
                $endsAt = $row->ends_at ? Carbon::parse((string) $row->ends_at) : null;

                $status = 'paused';
                if ((bool) $row->is_active) {
                    if ($startsAt && $startsAt->isFuture()) {
                        $status = 'scheduled';
                    } elseif ($endsAt && $endsAt->isPast()) {
                        $status = 'expired';
                    } else {
                        $status = 'active';
                    }
                }

                $discount = (string) $row->value;
                if ((string) $row->type === 'percent') {
                    $discount = number_format((float) $row->value, 2) . '%';
                } elseif ((string) $row->type === 'fixed') {
                    $discount = number_format((float) $row->value, $currencyDecimals) . ' ' . $currencyLabel;
                }

                return [
                    'id' => (int) $row->id,
                    'code' => (string) $row->code,
                    'discount' => $discount,
                    'usageUsed' => (int) ($row->used_count ?? 0),
                    'usageLimit' => (int) ($row->usage_limit ?? 0),
                    'expiry' => $endsAt ? $endsAt->toDateString() : 'No expiry',
                    'status' => $status,
                    'platforms' => ['Web'],
                ];
            })->values()->all();
        }

        if (count($coupons) === 0) {
            $fallbackCode = strtoupper(trim((string) data_get($settings, 'coupon_code', '')));
            if ($fallbackCode !== '') {
                $fallbackType = (string) data_get($settings, 'coupon_type', 'percent');
                $fallbackValue = (float) data_get($settings, 'coupon_value', 0);
                $fallbackStarts = (string) data_get($settings, 'coupon_starts_at', '');
                $fallbackEnds = (string) data_get($settings, 'coupon_ends_at', '');
                $fallbackEnabled = (string) data_get($settings, 'coupon_enabled', '0') === '1';

                $startsAt = $fallbackStarts !== '' ? Carbon::parse($fallbackStarts) : null;
                $endsAt = $fallbackEnds !== '' ? Carbon::parse($fallbackEnds) : null;

                $status = 'paused';
                if ($fallbackEnabled) {
                    if ($startsAt && $startsAt->isFuture()) {
                        $status = 'scheduled';
                    } elseif ($endsAt && $endsAt->isPast()) {
                        $status = 'expired';
                    } else {
                        $status = 'active';
                    }
                }

                $discount = $fallbackType === 'percent'
                    ? number_format($fallbackValue, 2) . '%'
                    : number_format($fallbackValue, $currencyDecimals) . ' ' . $currencyLabel;

                $coupons[] = [
                    'id' => 0,
                    'code' => $fallbackCode,
                    'discount' => $discount,
                    'usageUsed' => 0,
                    'usageLimit' => (int) data_get($settings, 'coupon_usage_limit', 0),
                    'expiry' => $fallbackEnds !== '' ? $fallbackEnds : 'No expiry',
                    'status' => $status,
                    'platforms' => ['Web'],
                ];
            }
        }

        foreach ($coupons as $coupon) {
            if (($coupon['status'] ?? '') === 'active') {
                $activeCoupons++;
            }
            $totalRedemptions += (int) ($coupon['usageUsed'] ?? 0);
        }

        if (count($coupons) > 0) {
            $numericDiscounts = [];
            foreach ($coupons as $coupon) {
                $raw = (string) ($coupon['discount'] ?? '0');
                $numericDiscounts[] = (float) preg_replace('/[^0-9.]/', '', $raw);
            }
            $avgDiscountValue = array_sum($numericDiscounts) / max(1, count($numericDiscounts));
            $percentCount = collect($coupons)->filter(fn ($c) => str_contains((string) ($c['discount'] ?? ''), '%'))->count();
            $avgDiscountSuffix = $percentCount >= (count($coupons) / 2) ? '%' : '';
        }

        $revenueImpact = 0.0;
        if (Schema::hasTable('coupon_usages')) {
            $revenueImpact = (float) DB::table('coupon_usages')->sum('discount_amount');
        }

        $trendPoints = array_fill(0, 12, 0);
        if (Schema::hasTable('coupon_usages')) {
            $startDate = $now->copy()->subDays(11)->startOfDay();
            $rawTrend = DB::table('coupon_usages')
                ->selectRaw('DATE(used_at) as day, COUNT(*) as total')
                ->where('used_at', '>=', $startDate)
                ->groupBy('day')
                ->orderBy('day')
                ->get()
                ->keyBy('day');

            $trend = [];
            for ($i = 11; $i >= 0; $i--) {
                $day = $now->copy()->subDays($i)->toDateString();
                $trend[] = (int) ($rawTrend[$day]->total ?? 0);
            }
            $trendPoints = $trend;
        }

        $usageDistribution = [
            ['label' => '0-25%', 'value' => 0, 'color' => 'bg-cyan-500'],
            ['label' => '26-50%', 'value' => 0, 'color' => 'bg-sky-500'],
            ['label' => '51-75%', 'value' => 0, 'color' => 'bg-indigo-500'],
            ['label' => '76-100%', 'value' => 0, 'color' => 'bg-slate-500'],
        ];

        $limitedCoupons = collect($coupons)->filter(fn ($c) => (int) ($c['usageLimit'] ?? 0) > 0)->values();
        if ($limitedCoupons->count() > 0) {
            $bucketCounts = [0, 0, 0, 0];
            foreach ($limitedCoupons as $coupon) {
                $used = (int) $coupon['usageUsed'];
                $limit = max(1, (int) $coupon['usageLimit']);
                $percent = ($used / $limit) * 100;
                if ($percent <= 25) {
                    $bucketCounts[0]++;
                } elseif ($percent <= 50) {
                    $bucketCounts[1]++;
                } elseif ($percent <= 75) {
                    $bucketCounts[2]++;
                } else {
                    $bucketCounts[3]++;
                }
            }
            foreach ($usageDistribution as $idx => $bucket) {
                $usageDistribution[$idx]['value'] = (int) round(($bucketCounts[$idx] / $limitedCoupons->count()) * 100);
            }
        }

        $platformDistribution = [
            ['label' => 'Web', 'value' => 100, 'color' => 'bg-cyan-500'],
            ['label' => 'Mobile', 'value' => 0, 'color' => 'bg-indigo-500'],
            ['label' => 'Dealer Portal', 'value' => 0, 'color' => 'bg-slate-500'],
        ];

        $avgDiscountLabel = number_format($avgDiscountValue, 2) . $avgDiscountSuffix;
        if ($avgDiscountSuffix === '') {
            $avgDiscountLabel .= ' ' . $currencyLabel;
        }

        return [
            'activeCoupons' => $activeCoupons,
            'totalRedemptions' => $totalRedemptions,
            'revenueImpact' => $revenueImpact,
            'avgDiscountLabel' => $avgDiscountLabel,
            'trendPoints' => $trendPoints,
            'usageDistribution' => $usageDistribution,
            'platformDistribution' => $platformDistribution,
            'coupons' => $coupons,
            'currencyLabel' => $currencyLabel,
            'currencyDecimals' => $currencyDecimals,
        ];
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'discounts_enabled' => ['nullable', 'boolean'],
            'discount_label' => ['nullable', 'string', 'max:120'],
            'discount_type' => ['required', 'in:percent,fixed'],
            'discount_value' => ['required', 'numeric', 'min:0', 'max:100000000'],
            'discount_minimum_subtotal' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
            'discount_usage_limit' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'discount_starts_at' => ['nullable', 'date'],
            'discount_ends_at' => ['nullable', 'date', 'after_or_equal:discount_starts_at'],
            'discount_scope' => ['required', Rule::in(['all', 'products', 'categories', 'brands'])],
            'discount_product_ids' => ['nullable', 'array'],
            'discount_product_ids.*' => ['integer', 'exists:products,id'],
            'discount_category_ids' => ['nullable', 'array'],
            'discount_category_ids.*' => ['integer', 'exists:categories,id'],
            'discount_brands' => ['nullable', 'array'],
            'discount_brands.*' => ['string', 'max:120'],
            'coupon_enabled' => ['nullable', 'boolean'],
            'coupon_code' => ['nullable', 'string', 'max:40'],
            'coupon_type' => ['required', 'in:percent,fixed'],
            'coupon_value' => ['required', 'numeric', 'min:0', 'max:100000000'],
            'coupon_min_order' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
            'coupon_usage_limit' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'coupon_starts_at' => ['nullable', 'date'],
            'coupon_ends_at' => ['nullable', 'date', 'after_or_equal:coupon_starts_at'],
        ]);

        $discountType = (string) $data['discount_type'];
        $couponType = (string) $data['coupon_type'];
        $discountValue = (float) $data['discount_value'];
        $couponValue = (float) $data['coupon_value'];
        $discountScope = (string) $data['discount_scope'];

        if ($discountType === 'percent' && $discountValue > 100) {
            return back()->withInput()->withErrors([
                'discount_value' => __('Percentage discount cannot exceed 100.'),
            ]);
        }

        if ($couponType === 'percent' && $couponValue > 100) {
            return back()->withInput()->withErrors([
                'coupon_value' => __('Coupon percentage cannot exceed 100.'),
            ]);
        }

        $selectedProducts = array_values(array_unique(array_map('intval', $request->input('discount_product_ids', []))));
        $selectedCategories = array_values(array_unique(array_map('intval', $request->input('discount_category_ids', []))));
        $selectedBrands = array_values(array_unique(array_filter(array_map(
            fn ($brand) => trim((string) $brand),
            $request->input('discount_brands', [])
        ))));

        if ($discountScope === 'products' && count($selectedProducts) === 0) {
            return back()->withInput()->withErrors([
                'discount_product_ids' => __('Select at least one product for targeted discount.'),
            ]);
        }

        if ($discountScope === 'categories' && count($selectedCategories) === 0) {
            return back()->withInput()->withErrors([
                'discount_category_ids' => __('Select at least one category for targeted discount.'),
            ]);
        }

        if ($discountScope === 'brands' && count($selectedBrands) === 0) {
            return back()->withInput()->withErrors([
                'discount_brands' => __('Select at least one brand for targeted discount.'),
            ]);
        }

        $couponEnabled = $request->boolean('coupon_enabled');
        $couponCode = strtoupper(trim((string) ($data['coupon_code'] ?? '')));
        $couponCode = preg_replace('/[^A-Z0-9_-]/', '', $couponCode) ?? '';

        if ($couponEnabled && $couponCode === '') {
            return back()->withInput()->withErrors([
                'coupon_code' => __('Coupon code is required when coupon is enabled.'),
            ]);
        }

        Setting::setMany([
            'discounts_enabled' => $request->boolean('discounts_enabled') ? '1' : '0',
            'discount_label' => (string) ($data['discount_label'] ?? ''),
            'discount_type' => $discountType,
            'discount_value' => (string) $discountValue,
            'discount_starts_at' => (string) ($data['discount_starts_at'] ?? ''),
            'discount_ends_at' => (string) ($data['discount_ends_at'] ?? ''),
            'discount_scope' => $discountScope,
            'discount_product_ids' => json_encode($selectedProducts),
            'discount_category_ids' => json_encode($selectedCategories),
            'discount_brands' => json_encode($selectedBrands),
            'coupon_enabled' => $couponEnabled ? '1' : '0',
            'coupon_code' => $couponCode,
            'coupon_type' => $couponType,
            'coupon_value' => (string) $couponValue,
            'coupon_min_order' => (string) ($data['coupon_min_order'] ?? 0),
            'coupon_usage_limit' => (string) ($data['coupon_usage_limit'] ?? 0),
            'coupon_starts_at' => (string) ($data['coupon_starts_at'] ?? ''),
            'coupon_ends_at' => (string) ($data['coupon_ends_at'] ?? ''),
        ]);

        $this->couponService->syncLegacySettingCoupon();

        return redirect()
            ->route('admin.discounts.edit')
            ->with('success', __('Discount and coupon settings updated.'));
    }

    public function updateRules(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'discount_id' => ['nullable', 'integer', 'exists:discounts,id'],
            'discounts_enabled' => ['nullable', 'boolean'],
            'discount_label' => ['nullable', 'string', 'max:120'],
            'discount_type' => ['required', 'in:percent,fixed'],
            'discount_value' => ['required', 'numeric', 'min:0', 'max:100000000'],
            'discount_starts_at' => ['nullable', 'date'],
            'discount_ends_at' => ['nullable', 'date', 'after_or_equal:discount_starts_at'],
            'discount_scope' => ['required', Rule::in(['all', 'products', 'categories', 'brands'])],
            'discount_product_ids' => ['nullable', 'array'],
            'discount_product_ids.*' => ['integer', 'exists:products,id'],
            'discount_category_ids' => ['nullable', 'array'],
            'discount_category_ids.*' => ['integer', 'exists:categories,id'],
            'discount_brands' => ['nullable', 'array'],
            'discount_brands.*' => ['string', 'max:120'],
        ]);

        $discountType = (string) $data['discount_type'];
        $discountValue = (float) $data['discount_value'];
        $discountScope = (string) $data['discount_scope'];

        if ($discountType === 'percent' && $discountValue > 100) {
            return back()->withInput()->withErrors([
                'discount_value' => __('Percentage discount cannot exceed 100.'),
            ]);
        }

        $selectedProducts = array_values(array_unique(array_map('intval', $request->input('discount_product_ids', []))));
        $selectedCategories = array_values(array_unique(array_map('intval', $request->input('discount_category_ids', []))));
        $selectedBrands = array_values(array_unique(array_filter(array_map(
            fn ($brand) => trim((string) $brand),
            $request->input('discount_brands', [])
        ))));

        if ($discountScope === 'products' && count($selectedProducts) === 0) {
            return back()->withInput()->withErrors([
                'discount_product_ids' => __('Select at least one product for targeted discount.'),
            ]);
        }

        if ($discountScope === 'categories' && count($selectedCategories) === 0) {
            return back()->withInput()->withErrors([
                'discount_category_ids' => __('Select at least one category for targeted discount.'),
            ]);
        }

        if ($discountScope === 'brands' && count($selectedBrands) === 0) {
            return back()->withInput()->withErrors([
                'discount_brands' => __('Select at least one brand for targeted discount.'),
            ]);
        }

        $discountId = (int) ($data['discount_id'] ?? 0);
        $discount = $discountId > 0
            ? Discount::query()->findOrFail($discountId)
            : new Discount();

        DB::transaction(function () use ($request, $data, $discount, $discountScope, $discountType, $discountValue, $selectedProducts, $selectedCategories, $selectedBrands): void {
            $discount->fill([
                'name' => trim((string) ($data['discount_label'] ?? '')) !== ''
                    ? trim((string) $data['discount_label'])
                    : 'Discount Rule ' . now()->format('Y-m-d H:i'),
                'scope' => $this->mapFormScopeToDiscountScope($discountScope),
                'type' => $discountType,
                'value' => $discountValue,
                'minimum_subtotal' => isset($data['discount_minimum_subtotal']) && $data['discount_minimum_subtotal'] !== ''
                    ? (float) $data['discount_minimum_subtotal']
                    : null,
                'usage_limit' => isset($data['discount_usage_limit']) && $data['discount_usage_limit'] !== ''
                    ? (int) $data['discount_usage_limit']
                    : null,
                'starts_at' => $data['discount_starts_at'] ?? null,
                'ends_at' => $data['discount_ends_at'] ?? null,
                'is_active' => $request->boolean('discounts_enabled'),
                'brand_names' => $discountScope === 'brands' ? $selectedBrands : [],
            ]);
            $discount->save();

            $discount->products()->sync($discountScope === 'products' ? $selectedProducts : []);
            $discount->categories()->sync($discountScope === 'categories' ? $selectedCategories : []);
        });

        return redirect()
            ->route('admin.discounts.rules')
            ->with('success', $discountId > 0 ? __('Discount rule updated successfully.') : __('Discount rule created successfully.'));
    }

    public function updateRuleStatus(Request $request, Discount $discount): RedirectResponse
    {
        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $discount->forceFill([
            'is_active' => (bool) $data['is_active'],
        ])->save();

        return redirect()
            ->route('admin.discounts.rules')
            ->with('success', (bool) $data['is_active'] ? __('Discount rule activated successfully.') : __('Discount rule deactivated successfully.'));
    }

    public function destroyRule(Discount $discount): RedirectResponse
    {
        DB::transaction(function () use ($discount): void {
            $discount->products()->detach();
            $discount->categories()->detach();
            $discount->delete();
        });

        return redirect()
            ->route('admin.discounts.rules')
            ->with('success', __('Discount rule deleted successfully.'));
    }

    /**
     * @return array{id:?int,is_active:bool,label:string,type:string,value:string,starts_at:string,ends_at:string,scope:string,selected_products:array<int>,selected_categories:array<int>,selected_brands:array<int,string>}
     */
    private function buildDiscountRuleFormState(?Discount $discount): array
    {
        if (!$discount) {
            return [
                'id' => null,
                'is_active' => false,
                'label' => '',
                'type' => 'percent',
                'value' => '0',
                'minimum_subtotal' => '',
                'usage_limit' => '',
                'starts_at' => '',
                'ends_at' => '',
                'scope' => 'all',
                'selected_products' => [],
                'selected_categories' => [],
                'selected_brands' => [],
            ];
        }

        $discount->loadMissing(['products:id', 'categories:id']);

        return [
            'id' => (int) $discount->id,
            'is_active' => (bool) $discount->is_active,
            'label' => (string) $discount->name,
            'type' => (string) $discount->type,
            'value' => (string) $discount->value,
            'minimum_subtotal' => $discount->minimum_subtotal !== null ? (string) $discount->minimum_subtotal : '',
            'usage_limit' => $discount->usage_limit !== null ? (string) $discount->usage_limit : '',
            'starts_at' => $discount->starts_at?->format('Y-m-d\TH:i') ?? '',
            'ends_at' => $discount->ends_at?->format('Y-m-d\TH:i') ?? '',
            'scope' => $this->mapDiscountScopeToFormScope((string) $discount->scope),
            'selected_products' => $discount->products->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            'selected_categories' => $discount->categories->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            'selected_brands' => collect($discount->brand_names ?? [])->map(fn ($brand) => (string) $brand)->values()->all(),
        ];
    }

    /**
     * @return array{id:int,name:string,isActive:bool,statusLabel:string,statusClass:string,scopeLabel:string,valueLabel:string,windowLabel:string,targetLabel:string,targetPreview:array<int,string>,minimumSubtotalLabel:string,usedCount:int,createdAtLabel:string,updatedAtLabel:string,editUrl:string}
     */
    private function transformDiscountRuleRow(Discount $discount): array
    {
        $status = $this->resolveDiscountStatus($discount);
        $formScope = $this->mapDiscountScopeToFormScope((string) $discount->scope);
        $targetCount = match ($formScope) {
            'products' => (int) ($discount->products_count ?? 0),
            'categories' => (int) ($discount->categories_count ?? 0),
            'brands' => count($discount->brand_names ?? []),
            default => 0,
        };
        $targetLabel = match ($formScope) {
            'products' => number_format($targetCount) . ' products',
            'categories' => number_format($targetCount) . ' categories',
            'brands' => number_format($targetCount) . ' brands',
            default => __('Full catalog'),
        };
        $valueLabel = (string) $discount->value;
        if ((string) $discount->type === 'percent') {
            $valueLabel .= '%';
        }
        $targetPreview = match ($formScope) {
            'products' => $discount->products()
                ->select(['products.id', 'name_en', 'name_ar', 'name_ku'])
                ->orderBy('name_en')
                ->limit(5)
                ->get()
                ->map(fn (Product $product) => (string) $product->name)
                ->all(),
            'categories' => $discount->categories()
                ->select(['categories.id', 'name_en', 'name_ar', 'name_ku'])
                ->orderBy('name_en')
                ->limit(5)
                ->get()
                ->map(fn (Category $category) => (string) $category->name)
                ->all(),
            'brands' => collect($discount->brand_names ?? [])->map(fn ($brand) => (string) $brand)->take(5)->values()->all(),
            default => ['Applies to the full product catalog'],
        };

        return [
            'id' => (int) $discount->id,
            'name' => (string) $discount->name,
            'isActive' => (bool) $discount->is_active,
            'statusLabel' => ucfirst($status),
            'statusClass' => match ($status) {
                'active' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/20 dark:text-emerald-300',
                'scheduled' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/60 dark:bg-amber-950/20 dark:text-amber-300',
                'expired' => 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/20 dark:text-rose-300',
                default => 'border-slate-200 bg-slate-100 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300',
            },
            'scopeLabel' => $this->discountScopeLabel($formScope),
            'valueLabel' => $valueLabel,
            'windowLabel' => $this->discountWindowLabel($discount),
            'targetLabel' => $targetLabel,
            'targetPreview' => $targetPreview,
            'minimumSubtotalLabel' => $discount->minimum_subtotal !== null
                ? number_format((float) $discount->minimum_subtotal, 2)
                : __('Not required'),
            'usageLimitLabel' => $discount->usage_limit !== null
                ? number_format((int) $discount->usage_limit)
                : __('Unlimited'),
            'usedCount' => (int) $discount->used_count,
            'createdAtLabel' => $discount->created_at?->format('Y-m-d H:i') ?? __('Unknown'),
            'updatedAtLabel' => $discount->updated_at?->format('Y-m-d H:i') ?? __('Unknown'),
            'editUrl' => route('admin.discounts.rules', ['discount' => $discount->id]) . '#discount-rule-form',
        ];
    }

    private function resolveDiscountStatus(Discount $discount): string
    {
        if (!$discount->is_active) {
            return 'draft';
        }

        if ($discount->starts_at && $discount->starts_at->isFuture()) {
            return 'scheduled';
        }

        if ($discount->ends_at && $discount->ends_at->isPast()) {
            return 'expired';
        }

        return 'active';
    }

    private function discountWindowLabel(Discount $discount): string
    {
        if (!$discount->starts_at && !$discount->ends_at) {
            return __('No schedule window');
        }

        return trim(sprintf(
            '%s - %s',
            $discount->starts_at?->format('Y-m-d H:i') ?? __('Immediate'),
            $discount->ends_at?->format('Y-m-d H:i') ?? __('Open')
        ));
    }

    private function discountScopeLabel(string $scope): string
    {
        return match ($scope) {
            'products' => __('Product Targeting'),
            'categories' => __('Category Targeting'),
            'brands' => __('Brand Targeting'),
            default => __('Storewide'),
        };
    }

    private function mapDiscountScopeToFormScope(string $scope): string
    {
        return match ($scope) {
            'product' => 'products',
            'category' => 'categories',
            'brand' => 'brands',
            default => 'all',
        };
    }

    private function mapFormScopeToDiscountScope(string $scope): string
    {
        return match ($scope) {
            'products' => 'product',
            'categories' => 'category',
            'brands' => 'brand',
            default => 'catalog',
        };
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function seedLegacyDiscountRuleFromSettings(array $settings): void
    {
        if (Discount::query()->exists()) {
            return;
        }

        $label = trim((string) data_get($settings, 'discount_label', ''));
        $value = (float) data_get($settings, 'discount_value', 0);
        $type = (string) data_get($settings, 'discount_type', 'percent');
        $scope = (string) data_get($settings, 'discount_scope', 'all');

        if ($label === '' && $value <= 0) {
            return;
        }

        $selectedProducts = collect(json_decode((string) data_get($settings, 'discount_product_ids', '[]'), true) ?: [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();
        $selectedCategories = collect(json_decode((string) data_get($settings, 'discount_category_ids', '[]'), true) ?: [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();
        $selectedBrands = collect(json_decode((string) data_get($settings, 'discount_brands', '[]'), true) ?: [])
            ->map(fn ($brand) => trim((string) $brand))
            ->filter()
            ->values()
            ->all();

        DB::transaction(function () use ($settings, $label, $value, $type, $scope, $selectedProducts, $selectedCategories, $selectedBrands): void {
            $discount = Discount::query()->create([
                'name' => $label !== '' ? $label : 'Imported Legacy Rule',
                'scope' => $this->mapFormScopeToDiscountScope($scope),
                'type' => in_array($type, ['percent', 'fixed'], true) ? $type : 'percent',
                'value' => $value,
                'starts_at' => data_get($settings, 'discount_starts_at') ?: null,
                'ends_at' => data_get($settings, 'discount_ends_at') ?: null,
                'is_active' => (string) data_get($settings, 'discounts_enabled', '0') === '1',
                'brand_names' => $scope === 'brands' ? $selectedBrands : [],
            ]);

            $discount->products()->sync($scope === 'products' ? $selectedProducts : []);
            $discount->categories()->sync($scope === 'categories' ? $selectedCategories : []);
        });
    }
}
