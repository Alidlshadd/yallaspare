<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVehicleFitment;
use App\Models\Setting;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\Wishlist;
use App\Services\Analytics\ClientAnalytics;
use App\Services\Analytics\SearchTracker;
use App\Support\DbSchema;
use App\Support\LocalizedText;
use App\Support\SqlSafe;
use App\Support\VehicleFilterCache;
use App\Support\VehicleLocalization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function home(): View
    {
        $authUser = auth()->user();
        $customerUser = $authUser && ! $authUser->isAdminPanelUser() ? $authUser : null;
        $locale = app()->getLocale();
        $nameField = match (true) {
            str_starts_with($locale, 'ar') => 'name_ar',
            str_starts_with($locale, 'ku') => 'name_ku',
            default => 'name_en',
        };

        $categories = collect();
        $featuredProducts = collect();
        $bestSellers = collect();
        $newArrivals = collect();
        $brands = collect();
        $vehicleFilters = $this->vehicleFilterOptions();
        $brandOptions = $vehicleFilters['brandOptions'];
        $modelOptions = $vehicleFilters['modelOptions'];
        $engineOptions = $vehicleFilters['engineOptions'];
        $wishlistedProductIds = [];

        if (DbSchema::hasTable('categories')) {
            $categoryColumns = ['id', 'slug', 'name_en', 'name_ar', 'name_ku', 'description'];
            $hasCategoryImage = DbSchema::hasColumn('categories', 'image');

            if ($hasCategoryImage) {
                $categoryColumns[] = 'image';
            }

            $categories = Category::query()
                ->select($categoryColumns)
                ->orderBy('name_en')
                ->take(10)
                ->get()
                ->map(function (Category $category) use ($nameField, $hasCategoryImage) {
                    $imagePath = $hasCategoryImage ? trim((string) $category->image) : '';

                    return [
                        'id' => $category->id,
                        'slug' => $category->slug,
                        'name' => LocalizedText::first($category->{$nameField}, $category->name_en, $category->name_ar, $category->name_ku),
                        'description' => $category->localized_description,
                        'image' => $imagePath !== '' ? asset('storage/'.ltrim($imagePath, '/')) : null,
                    ];
                });
        }

        if (DbSchema::hasTable('products')) {
            $featuredProductsQuery = Product::query()
                ->with('category:id,slug,name_en,name_ar,name_ku')
                ->where('is_active', true);

            if (DbSchema::hasTable('wishlists')) {
                $featuredProductsQuery
                    ->withCount('wishlists')
                    ->orderByDesc('wishlists_count')
                    ->orderByDesc('id');
            } else {
                $featuredProductsQuery->latest('id');
            }

            $featuredProducts = $featuredProductsQuery
                ->take(8)
                ->get()
                ->map(fn (Product $product) => $this->mapHomeProduct($product, $nameField, $customerUser));

            $bestSellers = $this->bestSellingProducts($nameField, $customerUser);
            $newArrivals = Product::query()
                ->with('category:id,slug,name_en,name_ar,name_ku')
                ->where('is_active', true)
                ->latest('created_at')
                ->take(10)
                ->get()
                ->map(fn (Product $product) => $this->mapHomeProduct($product, $nameField, $customerUser));

            if ($customerUser && DbSchema::hasTable('wishlists')) {
                $wishlistCandidateIds = $featuredProducts->pluck('id')
                    ->merge($bestSellers->pluck('id'))
                    ->merge($newArrivals->pluck('id'))
                    ->unique()
                    ->values();

                $wishlistedProductIds = Wishlist::query()
                    ->where('user_id', $customerUser->id)
                    ->whereIn('product_id', $wishlistCandidateIds)
                    ->pluck('product_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();
            }

            $brands = Product::query()
                ->whereNotNull('brand')
                ->where('brand', '!=', '')
                ->distinct()
                ->orderBy('brand')
                ->limit(12)
                ->pluck('brand')
                ->values();
        }

        if ($featuredProducts->isEmpty()) {
            $featuredProducts = $this->fallbackProducts();
        }

        if ($brands->isEmpty()) {
            $brands = collect(['BMW', 'Mercedes', 'Toyota', 'Hyundai', 'Kia', 'Nissan', 'Audi', 'Lexus']);
        }

        return view('user.shop.home', [
            'categories' => $categories,
            'featuredProducts' => $featuredProducts,
            'bestSellers' => $bestSellers,
            'newArrivals' => $newArrivals,
            'wishlistedProductIds' => $wishlistedProductIds,
            'brands' => $brands,
            'brandOptions' => $brandOptions,
            'modelOptions' => $modelOptions,
            'engineOptions' => $engineOptions,
            'modelOptionsByBrand' => $vehicleFilters['modelOptionsByBrand'],
            'vehicleOptionsByModel' => $vehicleFilters['vehicleOptionsByModel'],
            'hasStructuredVehicleData' => $vehicleFilters['hasStructuredVehicleData'],
            'hasFitmentData' => $vehicleFilters['hasFitmentData'],
            'vehicleDataMessage' => $vehicleFilters['vehicleDataMessage'],
            'currencySymbol' => (string) Setting::getValue('currency_code', 'IQD'),
            'heroSettings' => [
                'title' => (string) Setting::getValue('storefront_hero_title', 'Find the right spare parts faster'),
                'subtitle' => (string) Setting::getValue('storefront_hero_subtitle', 'Browse saved categories, filter by vehicle, and shop available parts from one clean catalog.'),
                'button_label' => (string) Setting::getValue('storefront_hero_button_label', 'Shop now'),
                'button_url' => (string) Setting::getValue('storefront_hero_button_url', ''),
                'image' => (string) Setting::getValue('storefront_hero_image', ''),
                'video' => (string) Setting::getValue('storefront_hero_video', ''),
            ],
        ]);
    }

    public function shop(Request $request): View
    {
        $authUser = auth()->user();
        $customerUser = $authUser && ! $authUser->isAdminPanelUser() ? $authUser : null;
        $productsQuery = Product::query()
            ->with('category')
            ->where('is_active', true);

        $search = trim((string) $request->input('q', $request->input('search', '')));
        if ($search !== '') {
            $productsQuery->where(function ($query) use ($search): void {
                SqlSafe::whereLike($query, 'name_en', $search);
                SqlSafe::orWhereLike($query, 'name_ar', $search);
                SqlSafe::orWhereLike($query, 'name_ku', $search);
                SqlSafe::orWhereLike($query, 'brand', $search);
                SqlSafe::orWhereLike($query, 'sku', $search);
                SqlSafe::orWhereLike($query, 'oem_number', $search);
                SqlSafe::orWhereLike($query, 'part_number', $search);
            });
        }

        $categoryInput = trim((string) $request->input('category', ''));
        $activeCategory = 0;

        if ($categoryInput !== '') {
            if (ctype_digit($categoryInput)) {
                $activeCategory = (int) $categoryInput;
                $productsQuery->where('category_id', $activeCategory);
            } else {
                $normalizedCategoryInput = Str::slug($categoryInput);
                $normalizedCategoryName = str_replace('-', ' ', $normalizedCategoryInput);

                $category = Category::query()
                    ->select(['id', 'slug'])
                    ->where(function ($query) use ($categoryInput, $normalizedCategoryInput, $normalizedCategoryName): void {
                        $query->where('slug', $categoryInput)
                            ->orWhere('slug', $normalizedCategoryInput)
                            ->orWhere('name_en', $categoryInput)
                            ->orWhere('name_ar', $categoryInput)
                            ->orWhere('name_ku', $categoryInput)
                            ->orWhere(function ($likeQuery) use ($normalizedCategoryName): void {
                                SqlSafe::whereLike($likeQuery, 'name_en', $normalizedCategoryName);
                            });
                    })
                    ->first();

                if ($category) {
                    $activeCategory = (int) $category->id;
                    $productsQuery->where('category_id', $category->id);
                }
            }
        }

        $brand = trim((string) $request->input('brand', ''));
        $model = trim((string) $request->input('model', ''));
        $legacyVehicle = trim((string) $request->input('vehicle', ''));
        $engine = trim((string) $request->input('engine', ''));
        $yearInput = trim((string) $request->input('year', ''));
        $engine = $engine !== '' ? $engine : $this->extractVehicleEngine($legacyVehicle);
        $year = $this->extractVehicleYear($yearInput !== '' ? $yearInput : $legacyVehicle);

        if (($brand !== '' || $model !== '' || $engine !== '' || $year !== null) && DbSchema::hasTable('product_vehicle_fitments')) {

            // Keep every selected vehicle constraint on the same fitment row.
            // A product may fit several cars, so separate whereHas clauses could
            // otherwise combine the model from one car with another car's engine.
            $productsQuery->whereHas('vehicleFitments', function ($fitmentQuery) use ($brand, $model, $year, $engine): void {
                if ($brand !== '' && DbSchema::hasTable('vehicle_brands')) {
                    $fitmentQuery->whereHas('brand', fn ($brandQuery) => $brandQuery->where('name', $brand));
                }

                if ($model !== '' && DbSchema::hasTable('vehicle_models')) {
                    // The finder sends a variant id, which names one car exactly.
                    // A name is still accepted because older links, bookmarks and
                    // the catalogue landing pages carry one — but a name can now
                    // belong to more than one variant, so it matches all of them.
                    if (ctype_digit($model)) {
                        $fitmentQuery->where('vehicle_model_id', (int) $model);
                    } else {
                        $fitmentQuery->whereHas('model', fn ($modelQuery) => $modelQuery->where('name', $model));
                    }
                }

                if ($year !== null) {
                    $fitmentQuery->where(function ($yearQuery) use ($year): void {
                        $yearQuery->whereNull('year_from')->orWhere('year_from', '<=', $year);
                    })->where(function ($yearQuery) use ($year): void {
                        $yearQuery->whereNull('year_to')->orWhere('year_to', '>=', $year);
                    });
                }

                if ($engine !== '') {
                    $fitmentQuery->where(function ($engineQuery) use ($engine): void {
                        $engineQuery->whereNull('engine')->orWhere('engine', '');
                        SqlSafe::orWhereLike($engineQuery, 'engine', $engine);
                    });
                }
            });
        } elseif ($brand !== '' || $model !== '' || $engine !== '' || $year !== null) {
            // Legacy fallback for installations that have not created the
            // structured fitment table yet.
            $productsQuery->where(function ($query) use ($brand, $model, $engine, $year): void {
                if ($brand !== '') {
                    SqlSafe::whereLike($query, 'brand', $brand);
                }
                if ($model !== '') {
                    SqlSafe::whereLike($query, 'compatible_models', $model);
                }
                if ($engine !== '') {
                    SqlSafe::whereLike($query, 'compatible_models', $engine);
                }
                if ($year !== null) {
                    SqlSafe::whereLike($query, 'compatible_models', (string) $year);
                }
            });
        }

        $sort = (string) $request->input('sort', 'latest');
        match ($sort) {
            'price_asc' => $productsQuery->orderBy('price'),
            'price_desc' => $productsQuery->orderByDesc('price'),
            'stock_desc' => $productsQuery->orderByDesc('stock_quantity'),
            default => $productsQuery->latest(),
        };

        $products = $productsQuery->paginate(24)->withQueryString();

        if ($search !== '') {
            app(SearchTracker::class)->record($request, $search, (int) $products->total());
            app(ClientAnalytics::class)->record('search', ['search_term' => $search]);
        }

        $wishlistedProductIds = [];
        if ($customerUser && DbSchema::hasTable('wishlists')) {
            $wishlistedProductIds = Wishlist::query()
                ->where('user_id', $customerUser->id)
                ->whereIn('product_id', $products->pluck('id'))
                ->pluck('product_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        $vehicleFilters = $this->vehicleFilterOptions();

        return view('user.shop.index', [
            'products' => $products,
            'categories' => Category::query()
                ->withCount([
                    'products' => fn ($query) => $query->where('is_active', true),
                ])
                ->orderBy('name_en')
                ->get(),
            'currencySymbol' => (string) Setting::getValue('currency_code', 'IQD'),
            'activeCategory' => $activeCategory,
            'wishlistedProductIds' => $wishlistedProductIds,
            'search' => $search,
            'sort' => $sort,
            'brand' => $brand,
            'model' => $model,
            'engine' => $engine,
            'year' => $year,
            'brandOptions' => $vehicleFilters['brandOptions'],
            'modelOptions' => $vehicleFilters['modelOptions'],
            'engineOptions' => $vehicleFilters['engineOptions'],
            'modelOptionsByBrand' => $vehicleFilters['modelOptionsByBrand'],
            'vehicleOptionsByModel' => $vehicleFilters['vehicleOptionsByModel'],
            'hasStructuredVehicleData' => $vehicleFilters['hasStructuredVehicleData'],
            'hasFitmentData' => $vehicleFilters['hasFitmentData'],
            'vehicleDataMessage' => $vehicleFilters['vehicleDataMessage'],
        ]);
    }

    public function categories(): View
    {
        $hasCategoryImage = DbSchema::hasColumn('categories', 'image');
        $columns = ['id', 'slug', 'name_en', 'name_ar', 'name_ku', 'description'];

        if ($hasCategoryImage) {
            $columns[] = 'image';
        }

        $categories = Category::query()
            ->select($columns)
            ->withCount([
                'products as active_products_count' => fn ($query) => $query->where('is_active', true),
            ])
            ->orderBy('name_en')
            ->paginate(24);

        return view('user.shop.categories', [
            'categories' => $categories,
            'hasCategoryImage' => $hasCategoryImage,
            'totalActiveProducts' => DbSchema::hasTable('products') ? Product::query()->where('is_active', true)->count() : 0,
        ]);
    }

    public function category(string $category): RedirectResponse
    {
        $categoryModel = $this->resolveCategory($category);

        return redirect()->route('shop.index', [
            'category' => $categoryModel->slug ?: $categoryModel->id,
        ]);
    }

    private function resolveCategory(string $value): Category
    {
        $value = trim($value);
        $normalized = Str::slug($value);

        $category = Category::query()
            ->withCount([
                'products as active_products_count' => fn ($query) => $query->where('is_active', true),
            ])
            ->where(function ($query) use ($value, $normalized): void {
                $query->where('slug', $value)
                    ->orWhere('slug', $normalized);

                if (ctype_digit($value)) {
                    // Eloquent has whereKey but no orWhereKey, so match the
                    // primary key by name rather than calling a method that
                    // does not exist.
                    $query->orWhere($query->getModel()->getKeyName(), (int) $value);
                }
            })
            ->first();

        abort_if(! $category, 404);

        return $category;
    }

    private function vehicleFilterOptions(): array
    {
        return VehicleFilterCache::remember(fn (): array => $this->buildVehicleFilterOptions());
    }

    private function buildVehicleFilterOptions(): array
    {
        $brandOptions = collect();
        $modelOptions = collect();
        $engineOptions = collect();
        $modelOptionsByBrand = [];
        $vehicleOptionsByModel = [];
        $hasStructuredVehicleData = false;
        $hasFitmentData = false;
        // Display text per canonical engine value, filled in once the variants
        // are loaded. Stays empty when there is no vehicle table to read.
        $engineLabels = collect();

        if (! DbSchema::hasTable('products')) {
            return $this->vehicleFilterPayload($brandOptions, $modelOptions, $engineOptions, $modelOptionsByBrand, $vehicleOptionsByModel, false, false);
        }

        $withheldEngineNames = [];
        $brands = Product::query()
            ->whereNotNull('brand')
            ->where('brand', '!=', '')
            ->distinct()
            ->orderBy('brand')
            ->pluck('brand')
            ->filter()
            ->values();

        if (DbSchema::hasTable('vehicle_brands')) {
            $vehicleBrandRows = VehicleBrand::query()
                ->with(['models' => fn ($query) => $query
                    ->select(['id', 'vehicle_brand_id', 'vehicle_model_family_id', 'name', 'name_en', 'name_ar', 'name_ku', 'production_start_year', 'production_end_year'])
                    ->with(['family:id,name,name_en,name_ar,name_ku', 'engineTypes:id,vehicle_model_id,name,fuel_type,engine_size,aspiration'])])
                ->orderBy('name')
                ->get();

            if ($vehicleBrandRows->isNotEmpty()) {
                $hasStructuredVehicleData = true;
                // Engines configured on a variant carry a fuel type, so their
                // label is built from those parts rather than from the string.
                // Diesel cars stay on record, but a shop that sells petrol
                // parts should not ask a customer to pick a diesel engine. The
                // rule reads the structured fuel type, never the label.
                $offeredEngines = $vehicleBrandRows
                    ->flatMap(fn (VehicleBrand $brand) => $brand->models->pluck('engineTypes')->flatten())
                    ->filter(fn ($engine) => $engine->isOfferedInStorefront())
                    ->values();
                $engineLabels = $offeredEngines
                    ->mapWithKeys(fn ($engine) => [(string) $engine->name => $engine->localizedName()]);
                // The inverse list, for the engines a fitment names as free
                // text: only a name that is known and known not to be offered
                // is dropped. An unrecognised one is left alone rather than
                // ruled out on a guess.
                $withheldEngineNames = $vehicleBrandRows
                    ->flatMap(fn (VehicleBrand $brand) => $brand->models->pluck('engineTypes')->flatten())
                    ->reject(fn ($engine) => $engine->isOfferedInStorefront())
                    ->pluck('name')
                    ->map(fn ($name) => mb_strtolower(trim((string) $name)))
                    ->unique()
                    ->all();
                $brandOptions = $vehicleBrandRows->pluck('name')->filter()->values();
                // Keyed by variant id, not by name. Two variants can share a
                // name — a Tivoli built 2015-2019 and one built 2020-2023 — and
                // a map keyed by name would quietly keep only one of them.
                $modelOptionsByBrand = $vehicleBrandRows
                    ->mapWithKeys(fn (VehicleBrand $brand) => [
                        (string) $brand->name => $brand->models
                            ->map(fn (VehicleModel $model) => ['value' => (string) $model->id, 'label' => $model->listLabel()])
                            ->values()
                            ->all(),
                    ])
                    ->all();
                $vehicleOptionsByModel = $vehicleBrandRows
                    ->mapWithKeys(fn (VehicleBrand $brand) => [
                        (string) $brand->name => $brand->models
                            ->mapWithKeys(fn (VehicleModel $model) => [
                                (string) $model->id => [
                                    'model_id' => (int) $model->id,
                                    'label' => $model->listLabel(),
                                    'family' => $model->family?->localizedName(),
                                    'engines' => $model->engineTypes
                                        ->filter(fn ($engine) => $engine->isOfferedInStorefront())
                                        ->pluck('name')
                                        ->filter()
                                        ->unique()
                                        ->values()
                                        ->all(),
                                    'year_from' => $model->production_start_year ? (int) $model->production_start_year : null,
                                    'year_to' => $model->production_end_year ? (int) $model->production_end_year : null,
                                ],
                            ])
                            ->all(),
                    ])
                    ->all();
            }
        }

        if (DbSchema::hasTable('vehicle_models')) {
            $vehicleModels = VehicleModel::query()
                ->orderBy('name')
                ->get(['id', 'name', 'name_en', 'name_ar', 'name_ku', 'production_start_year', 'production_end_year'])
                ->map(fn (VehicleModel $model) => ['value' => (string) $model->id, 'label' => $model->listLabel()])
                ->values();

            if ($vehicleModels->isNotEmpty()) {
                $hasStructuredVehicleData = true;
                $modelOptions = $vehicleModels->unique('value')->values();
            }
        }

        if (DbSchema::hasTable('product_vehicle_fitments')) {
            $hasFitmentData = ProductVehicleFitment::query()->exists();

            $fitmentEngines = ProductVehicleFitment::query()
                ->whereNotNull('engine')
                ->pluck('engine')
                ->map(fn ($engine) => trim((string) $engine))
                ->filter()
                ->reject(fn (string $engine) => in_array(mb_strtolower($engine), $withheldEngineNames, true))
                ->unique()
                ->values();

            if ($fitmentEngines->isNotEmpty()) {
                $engineOptions = $fitmentEngines->map(fn (string $engine) => [
                    'value' => $engine,
                    'label' => $engineLabels[$engine] ?? VehicleLocalization::engine($engine),
                ]);
            }

            $fitmentsByModel = ProductVehicleFitment::query()
                ->whereNotNull('vehicle_model_id')
                ->get(['vehicle_model_id', 'engine', 'year_from', 'year_to'])
                ->groupBy('vehicle_model_id');

            foreach ($vehicleOptionsByModel as &$brandModels) {
                foreach ($brandModels as &$modelMetadata) {
                    $modelFitments = $fitmentsByModel->get($modelMetadata['model_id'], collect());
                    $fitmentModelEngines = $modelFitments
                        ->pluck('engine')
                        ->map(fn ($engine) => trim((string) $engine))
                        ->filter()
                        ->reject(fn (string $engine) => in_array(mb_strtolower($engine), $withheldEngineNames, true))
                        ->unique()
                        ->values()
                        ->all();

                    $modelMetadata['engines'] = collect($modelMetadata['engines'])
                        ->merge($fitmentModelEngines)
                        ->unique()
                        ->values()
                        ->all();
                    $modelMetadata['year_from'] ??= $modelFitments->pluck('year_from')->filter()->min();
                    $modelMetadata['year_to'] ??= $modelFitments->pluck('year_to')->filter()->max();
                }
                unset($modelMetadata);
            }
            unset($brandModels);
        }

        foreach ($vehicleOptionsByModel as &$brandModels) {
            foreach ($brandModels as &$modelMetadata) {
                $modelMetadata['engines'] = collect($modelMetadata['engines'])
                    ->map(fn (string $engine) => [
                        'value' => $engine,
                        'label' => $engineLabels[$engine] ?? VehicleLocalization::engine($engine),
                    ])
                    ->values()
                    ->all();
                unset($modelMetadata['model_id']);
            }
            unset($modelMetadata);
        }
        unset($brandModels);

        $compatibleValues = Product::query()
            ->whereNotNull('compatible_models')
            ->pluck('compatible_models')
            ->filter()
            ->flatMap(function ($value) {
                $items = is_array($value) ? $value : (json_decode((string) $value, true) ?: []);

                return collect($items)
                    ->map(fn ($item) => is_array($item) ? ($item['name'] ?? reset($item)) : $item)
                    ->filter()
                    ->map(fn ($item) => trim((string) $item))
                    ->filter();
            })
            ->unique()
            ->values();

        if (! $hasStructuredVehicleData && $brands->isNotEmpty()) {
            $brandOptions = $brands;
        }

        if (! $hasStructuredVehicleData && $compatibleValues->isNotEmpty()) {
            $detectedModelOptions = $compatibleValues
                ->reject(fn (string $value) => preg_match('/\b(19|20)\d{2}\b|\/|\d\.\d\s*l/i', $value) === 1)
                ->values();

            $detectedEngineOptions = $compatibleValues
                ->filter(fn (string $value) => preg_match('/\b(19|20)\d{2}\b|\/|\d\.\d\s*l/i', $value) === 1)
                ->values();

            if ($detectedModelOptions->isNotEmpty()) {
                $modelOptions = $detectedModelOptions;
            } else {
                $modelOptions = $compatibleValues->take(12)->values();
            }

            if ($detectedEngineOptions->isNotEmpty()) {
                $engineOptions = $detectedEngineOptions;
            }
        }

        return $this->vehicleFilterPayload($brandOptions, $modelOptions, $engineOptions, $modelOptionsByBrand, $vehicleOptionsByModel, $hasStructuredVehicleData, $hasFitmentData);
    }

    private function vehicleFilterPayload(
        Collection $brandOptions,
        Collection $modelOptions,
        Collection $engineOptions,
        array $modelOptionsByBrand,
        array $vehicleOptionsByModel,
        bool $hasStructuredVehicleData,
        bool $hasFitmentData
    ): array {
        $vehicleDataMessage = $hasStructuredVehicleData && $hasFitmentData
            ? ''
            : 'Vehicle compatibility data is still being prepared. Use search or product category filters if your vehicle is not listed.';

        return [
            'brandOptions' => $brandOptions->unique()->take(30)->values(),
            'modelOptions' => $modelOptions->unique(fn ($option) => is_array($option) ? $option['value'] : $option)->take(30)->values(),
            'engineOptions' => $engineOptions->unique(fn ($option) => is_array($option) ? $option['value'] : $option)->take(30)->values(),
            'modelOptionsByBrand' => $modelOptionsByBrand,
            'vehicleOptionsByModel' => $vehicleOptionsByModel,
            'hasStructuredVehicleData' => $hasStructuredVehicleData,
            'hasFitmentData' => $hasFitmentData,
            'vehicleDataMessage' => $vehicleDataMessage,
        ];
    }

    private function extractVehicleYear(string $vehicle): ?int
    {
        if (preg_match('/^year:(19|20)\d{2}$/', $vehicle) === 1) {
            return (int) substr($vehicle, 5);
        }

        if (preg_match('/\b(19|20)\d{2}\b/', $vehicle, $matches) !== 1) {
            return null;
        }

        return (int) $matches[0];
    }

    private function extractVehicleEngine(string $vehicle): string
    {
        if (str_starts_with($vehicle, 'engine:')) {
            return trim(substr($vehicle, 7));
        }

        if ($vehicle === '' || $this->extractVehicleYear($vehicle) !== null) {
            return '';
        }

        if (preg_match('/\b\d(?:\.\d)?\s*l\b/i', $vehicle, $matches) !== 1) {
            return $vehicle;
        }

        return strtoupper(str_replace(' ', '', $matches[0]));
    }

    private function mapHomeProduct(Product $product, string $nameField, $customerUser): array
    {
        $models = collect($product->compatible_models ?? [])
            ->map(fn ($item) => is_array($item) ? ($item['name'] ?? reset($item)) : $item)
            ->filter()
            ->values();

        $imagePath = trim((string) $product->image);
        $imageUrl = $imagePath !== ''
            ? asset('storage/'.ltrim($imagePath, '/'))
            : null;

        $pricing = $product->pricingFor($customerUser);

        return [
            'id' => $product->id,
            'name' => LocalizedText::first($product->{$nameField}, $product->name_en, $product->name_ar, $product->name_ku),
            'price' => (float) $pricing['price'],
            'base_price' => (float) $pricing['base_price'],
            'discount_amount' => (float) $pricing['discount_amount'],
            'discount_percent' => (int) $pricing['discount_percent'],
            'has_discount' => (bool) $pricing['has_discount'],
            'stock_quantity' => (int) $product->stock_quantity,
            'wishlist_count' => (int) ($product->wishlists_count ?? 0),
            'image' => $imageUrl,
            'brand' => $product->brand,
            'category_slug' => $product->category?->slug,
            'compatible_models' => $models,
            'detail_url' => route('shop.show', $product),
        ];
    }

    private function bestSellingProducts(string $nameField, $customerUser): Collection
    {
        if (! DbSchema::hasTable('order_items') || ! DbSchema::hasTable('orders')) {
            return collect();
        }

        $rankedProductIds = OrderItem::query()
            ->selectRaw('product_id, SUM(quantity) as total_qty')
            ->whereHas('order', fn ($query) => $query->where('status', '!=', Order::STATUS_CANCELLED))
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->limit(20)
            ->pluck('product_id');

        if ($rankedProductIds->isEmpty()) {
            return collect();
        }

        $products = Product::query()
            ->with('category:id,slug,name_en,name_ar,name_ku')
            ->where('is_active', true)
            ->whereIn('id', $rankedProductIds)
            ->get()
            ->keyBy('id');

        return $rankedProductIds
            ->map(fn ($id) => $products->get($id))
            ->filter()
            ->take(10)
            ->values()
            ->map(fn (Product $product) => $this->mapHomeProduct($product, $nameField, $customerUser));
    }

    private function fallbackProducts(): Collection
    {
        return collect([
            [
                'id' => 1,
                'name' => 'Premium Brake Pads',
                'price' => 65000,
                'stock_quantity' => 14,
                'image' => null,
                'brand' => 'Toyota',
                'category_slug' => 'brakes',
                'compatible_models' => collect(['Corolla', 'Camry', 'Yaris']),
                'detail_url' => route('shop.index', ['search' => 'Premium Brake Pads']),
            ],
            [
                'id' => 2,
                'name' => 'Oil Filter Kit',
                'price' => 18000,
                'stock_quantity' => 31,
                'image' => null,
                'brand' => 'BMW',
                'category_slug' => 'filters',
                'compatible_models' => collect(['X5', '320i']),
                'detail_url' => route('shop.index', ['search' => 'Oil Filter Kit']),
            ],
            [
                'id' => 3,
                'name' => 'Suspension Arm Set',
                'price' => 92000,
                'stock_quantity' => 6,
                'image' => null,
                'brand' => 'Mercedes',
                'category_slug' => 'suspension',
                'compatible_models' => collect(['E-Class', 'C-Class', 'GLC']),
                'detail_url' => route('shop.index', ['search' => 'Suspension Arm Set']),
            ],
            [
                'id' => 4,
                'name' => 'Spark Plug Pack',
                'price' => 24000,
                'stock_quantity' => 22,
                'image' => null,
                'brand' => 'Hyundai',
                'category_slug' => 'engine',
                'compatible_models' => collect(['Elantra', 'Tucson']),
                'detail_url' => route('shop.index', ['search' => 'Spark Plug Pack']),
            ],
            [
                'id' => 5,
                'name' => 'Headlamp Assembly',
                'price' => 110000,
                'stock_quantity' => 4,
                'image' => null,
                'brand' => 'Kia',
                'category_slug' => 'electrical',
                'compatible_models' => collect(['Sportage', 'Cerato']),
                'detail_url' => route('shop.index', ['search' => 'Headlamp Assembly']),
            ],
            [
                'id' => 6,
                'name' => 'Body Trim Set',
                'price' => 47000,
                'stock_quantity' => 12,
                'image' => null,
                'brand' => 'Nissan',
                'category_slug' => 'body',
                'compatible_models' => collect(['Sunny', 'Altima']),
                'detail_url' => route('shop.index', ['search' => 'Body Trim Set']),
            ],
            [
                'id' => 7,
                'name' => 'Synthetic Engine Oil',
                'price' => 38000,
                'stock_quantity' => 40,
                'image' => null,
                'brand' => 'Castrol',
                'category_slug' => 'oils-fluids',
                'compatible_models' => collect(['5W-30', '5W-40']),
                'detail_url' => route('shop.index', ['search' => 'Synthetic Engine Oil']),
            ],
            [
                'id' => 8,
                'name' => 'Alloy Wheel Cap Set',
                'price' => 29000,
                'stock_quantity' => 9,
                'image' => null,
                'brand' => 'Audi',
                'category_slug' => 'tires-wheels',
                'compatible_models' => collect(['A4', 'A6']),
                'detail_url' => route('shop.index', ['search' => 'Alloy Wheel Cap Set']),
            ],
        ])->map(function (array $product) {
            $product['compatible_models'] = collect($product['compatible_models']);
            $product['wishlist_count'] = (int) ($product['wishlist_count'] ?? 0);

            return $product;
        });
    }
}
