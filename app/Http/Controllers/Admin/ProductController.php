<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ImageUploadException;
use App\Exports\ProductsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductImage;
use App\Models\Setting;
use App\Support\AdminLogger;
use App\Support\Pricing\ExchangeRate;
use App\Support\ProductWarranty;
use App\Support\SecureImageStorage;
use App\Support\SqlSafe;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $lowStockThreshold = max((int) Setting::getValue('low_stock_threshold', config('inventory.low_stock_threshold', 5)), 0);
        $legacyLowStockFilter = $request->boolean('low_stock') && ! $request->filled('status');
        $status = strtolower(trim((string) $request->query('status', $legacyLowStockFilter ? 'low_stock' : 'all')));
        $allowedStatuses = ['all', 'active', 'inactive', 'low_stock', 'out_of_stock'];
        if (! in_array($status, $allowedStatuses, true)) {
            $status = 'all';
        }

        $query = Product::query()
            ->select([
                'id',
                'slug',
                'category_id',
                'product_brand_id',
                'name_en',
                'image',
                'sku',
                'brand',
                'is_active',
                'price',
                'dealer_price',
                'price_currency',
                'price_usd',
                'cost_price',
                'stock_quantity',
                'created_at',
            ])
            ->with([
                'analytics:id,product_id,views_count,last_viewed_at',
                'category:id,name_en,name_ar,name_ku,slug',
                'productBrand:id,name,logo_path',
            ]);

        if ($request->filled('search')) {
            $search = SqlSafe::searchTerm($request->search);
            $query->where(function ($q) use ($search) {
                SqlSafe::whereLike($q, 'name_en', $search);
                SqlSafe::orWhereLike($q, 'name_ar', $search);
                SqlSafe::orWhereLike($q, 'name_ku', $search);
                SqlSafe::orWhereLike($q, 'sku', $search);
                SqlSafe::orWhereLike($q, 'oem_number', $search);
                SqlSafe::orWhereLike($q, 'part_number', $search);
                SqlSafe::orWhereLike($q, 'brand', $search);
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $brand = trim((string) $request->query('brand', ''));
        if ($brand !== '') {
            $query->where('brand', $brand);
        }

        if ($request->filled('product_brand_id')) {
            $query->where('product_brand_id', (int) $request->query('product_brand_id'));
        }

        match ($status) {
            'active' => $query->where('is_active', true),
            'inactive' => $query->where('is_active', false),
            'low_stock' => $query
                ->where('stock_quantity', '>', 0)
                ->where('stock_quantity', '<=', $lowStockThreshold),
            'out_of_stock' => $query->where('stock_quantity', 0),
            default => null,
        };

        if ($legacyLowStockFilter) {
            $request->query->set('status', 'low_stock');
            $request->query->remove('low_stock');
        }

        $allowedSorts = [
            'id', 'name_en', 'name_ar', 'name_ku', 'price', 'stock_quantity', 'sku', 'brand', 'is_active', 'created_at',
        ];

        $sort = $request->get('sort', 'id');
        $direction = $request->get('dir', 'desc');

        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'id';
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }

        $query->orderBy($sort, $direction);

        if ($sort !== 'id') {
            $query->orderBy('id', $direction);
        }

        $products = $query->paginate(10)->withQueryString();
        $metaCacheTtl = max((int) config('performance.products_meta_cache_ttl', 300), 30);
        $categories = Cache::remember('admin:products:categories:v1', now()->addSeconds($metaCacheTtl), function () {
            return Category::query()->select(['id', 'name_en', 'name_ar', 'name_ku', 'slug'])->orderBy('name_en')->get();
        });
        $brands = ProductBrand::query()
            ->select(['id', 'name', 'logo_path'])
            ->orderBy('name')
            ->get();
        $lowStockCount = Cache::remember(
            "admin:products:low-stock-count:v2:threshold:{$lowStockThreshold}",
            now()->addSeconds(min($metaCacheTtl, 120)),
            fn () => Product::where('stock_quantity', '>', 0)->where('stock_quantity', '<=', $lowStockThreshold)->count()
        );
        $statusTabs = [
            'all' => [
                'label' => __('All Products'),
                'count' => Product::query()->count(),
                'empty' => __('No products found.'),
            ],
            'active' => [
                'label' => __('Active'),
                'count' => Product::query()->where('is_active', true)->count(),
                'empty' => __('No active products found.'),
            ],
            'inactive' => [
                'label' => __('Inactive'),
                'count' => Product::query()->where('is_active', false)->count(),
                'empty' => __('No inactive products found.'),
            ],
            'low_stock' => [
                'label' => __('Low Stock'),
                'count' => $lowStockCount,
                'empty' => __('No low stock products found.'),
            ],
            'out_of_stock' => [
                'label' => __('Out of Stock'),
                'count' => Product::query()->where('stock_quantity', 0)->count(),
                'empty' => __('No out of stock products found.'),
            ],
        ];
        $currencySymbol = (string) Setting::getValue('currency_symbol', 'IQD');
        $currencyCode = (string) Setting::getValue('currency_code', 'IQD');
        $currencyLabel = $currencyCode !== '' ? $currencyCode : $currencySymbol;
        $currencyDecimals = strtoupper($currencyCode) === 'IQD' ? 0 : 2;

        return view('admin.products.index', compact(
            'products',
            'categories',
            'brands',
            'sort',
            'direction',
            'status',
            'statusTabs',
            'lowStockThreshold',
            'lowStockCount',
            'currencySymbol',
            'currencyLabel',
            'currencyDecimals'
        ));
    }

    public function create(Request $request)
    {
        $categories = Category::orderBy('name_en')->get();
        $brands = ProductBrand::query()->orderBy('name')->get(['id', 'name', 'logo_path']);

        $currencySymbol = (string) Setting::getValue('currency_symbol', 'IQD');
        $currencyCode = (string) Setting::getValue('currency_code', 'IQD');
        $currencyLabel = $currencyCode !== '' ? $currencyCode : $currencySymbol;
        $currencyDecimals = strtoupper($currencyCode) === 'IQD' ? 0 : 2;
        $lowStockThreshold = max((int) Setting::getValue('low_stock_threshold', config('inventory.low_stock_threshold', 5)), 0);
        $returnTo = $this->productsIndexReturnUrl($request);

        return view('admin.products.create', compact('categories', 'brands', 'currencySymbol', 'currencyCode', 'currencyLabel', 'currencyDecimals', 'lowStockThreshold', 'returnTo'));
    }

    public function store(StoreProductRequest $request)
    {
        $selectedBrand = $this->resolveRequestedBrand($request);

        $compatibleModels = $request->filled('compatible_models')
            ? array_values(array_filter(array_map('trim', preg_split('/[,\n]+/', $request->compatible_models))))
            : null;

        $sku = $request->filled('sku')
            ? $request->sku
            : 'SKU-'.Str::upper(Str::random(10));

        $dealerPrice = $request->filled('dealer_price') ? (float) $request->dealer_price : null;
        $basePrice = (float) $request->price;
        $priceAttributes = $this->priceAttributes($request);

        // Every file written for this request, so a failure part-way through
        // can take them back out. The product and its pictures are saved
        // together or not at all.
        $storedPaths = [];

        try {
            DB::transaction(function () use ($request, $selectedBrand, $compatibleModels, $sku, $priceAttributes, &$storedPaths): void {
                $imagePath = null;
                if ($request->hasFile('image')) {
                    $imagePath = $storedPaths[] = SecureImageStorage::store($request->file('image'), 'products');
                }

                $product = Product::create([
                    'category_id' => $request->category_id,
                    'name_en' => $request->name_en,
                    'name_ar' => $request->name_ar,
                    'name_ku' => $request->name_ku,
                    'description_en' => $request->description_en,
                    'description_ar' => $request->description_ar,
                    'description_ku' => $request->description_ku,
                    ...$priceAttributes,
                    'stock_quantity' => $request->stock_quantity,
                    'sku' => $sku,
                    'oem_number' => $request->filled('oem_number') ? $request->oem_number : null,
                    'part_number' => $request->filled('part_number') ? $request->part_number : null,
                    'warranty' => $request->filled('warranty') ? $request->warranty : null,
                    'product_brand_id' => $selectedBrand?->id,
                    'brand' => $selectedBrand?->name,
                    'compatible_models' => $compatibleModels,
                    'image' => $imagePath,
                    'is_active' => $request->boolean('is_active'),
                ]);

                if ($imagePath) {
                    $product->images()->create([
                        'path' => $imagePath,
                        'disk' => 'public',
                        'alt_text' => $product->name_en,
                        'sort_order' => 0,
                        'is_primary' => true,
                    ]);
                }

                $this->storeGalleryImages($request, $product, $imagePath ? 1 : 0, $storedPaths);
            });
        } catch (\Throwable $e) {
            $this->abandonImageUpload($e, $request, $storedPaths);
        }

        $redirect = redirect()->to($this->productsIndexReturnUrl($request))
            ->with('success', __('Product added successfully'));

        if ($dealerPrice !== null && $dealerPrice >= $basePrice) {
            $redirect->with('warning', __('Dealer price is greater than or equal to base price.'));
        }

        return $redirect;
    }

    public function edit(Request $request, Product $product)
    {
        $categories = Category::orderBy('name_en')->get();
        $brands = ProductBrand::query()->orderBy('name')->get(['id', 'name', 'logo_path']);

        $currencySymbol = (string) Setting::getValue('currency_symbol', 'IQD');
        $currencyCode = (string) Setting::getValue('currency_code', 'IQD');
        $currencyLabel = $currencyCode !== '' ? $currencyCode : $currencySymbol;
        $currencyDecimals = strtoupper($currencyCode) === 'IQD' ? 0 : 2;
        $lowStockThreshold = max((int) Setting::getValue('low_stock_threshold', config('inventory.low_stock_threshold', 5)), 0);

        $product->load('analytics', 'images');
        $returnTo = $this->productsIndexReturnUrl($request);

        return view('admin.products.edit', compact('product', 'categories', 'brands', 'currencySymbol', 'currencyCode', 'currencyLabel', 'currencyDecimals', 'lowStockThreshold', 'returnTo'));
    }

    public function editByIdentifier(string $productIdentifier): RedirectResponse
    {
        $product = Product::query()
            ->where('slug', $productIdentifier)
            ->orWhere('id', $productIdentifier)
            ->firstOrFail();

        return redirect()->route('admin.products.edit', $product);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $brandWasSubmitted = $request->has('product_brand_id') || $request->has('brand');
        $selectedBrand = $brandWasSubmitted ? $this->resolveRequestedBrand($request) : null;
        $oldImagePath = $product->image;

        $compatibleModels = $request->filled('compatible_models')
            ? array_values(array_filter(array_map('trim', preg_split('/[,\n]+/', $request->compatible_models))))
            : null;

        $dealerPrice = $request->filled('dealer_price') ? (float) $request->dealer_price : null;
        $basePrice = (float) $request->price;
        $priceAttributes = $this->priceAttributes($request, $product);

        // New files are written first and old ones removed last, once the
        // database has accepted the change. Whatever goes wrong in between,
        // the product keeps the pictures it had.
        $storedPaths = [];
        $obsoletePaths = [];

        try {
            DB::transaction(function () use ($request, $product, $brandWasSubmitted, $selectedBrand, $compatibleModels, $priceAttributes, $oldImagePath, &$storedPaths, &$obsoletePaths): void {
                $newImagePath = null;
                $imagePath = $oldImagePath;
                if ($request->hasFile('image')) {
                    $imagePath = $newImagePath = $storedPaths[] = SecureImageStorage::store($request->file('image'), 'products');
                } elseif ($request->boolean('remove_image')) {
                    $imagePath = null;
                }

                $product->update([
                    'category_id' => $request->category_id,
                    'name_en' => $request->name_en,
                    'name_ar' => $request->name_ar,
                    'name_ku' => $request->name_ku,
                    'description_en' => $request->description_en,
                    'description_ar' => $request->description_ar,
                    'description_ku' => $request->description_ku,
                    ...$priceAttributes,
                    'stock_quantity' => $request->stock_quantity,
                    'sku' => $request->filled('sku') ? $request->sku : $product->sku,
                    'oem_number' => $request->filled('oem_number') ? $request->oem_number : null,
                    'part_number' => $request->filled('part_number') ? $request->part_number : null,
                    'warranty' => $request->filled('warranty') ? $request->warranty : null,
                    'product_brand_id' => $brandWasSubmitted ? $selectedBrand?->id : $product->product_brand_id,
                    'brand' => $brandWasSubmitted ? $selectedBrand?->name : $product->brand,
                    'compatible_models' => $compatibleModels,
                    'image' => $imagePath,
                    'is_active' => $request->boolean('is_active'),
                ]);

                // Replaced or removed, the old main picture leaves the gallery too.
                // Its row used to survive a replacement, still marked as the form's
                // chosen primary, and was promoted straight back over the new upload
                // while pointing at a file that had already been deleted.
                if ($oldImagePath && $imagePath !== $oldImagePath) {
                    $product->images()->where('path', $oldImagePath)->delete();
                    $obsoletePaths[] = $oldImagePath;
                }

                if ($newImagePath) {
                    $product->images()->update(['is_primary' => false]);
                    $product->images()->create([
                        'path' => $newImagePath,
                        'disk' => 'public',
                        'alt_text' => $product->name_en,
                        'sort_order' => 0,
                        'is_primary' => true,
                    ]);
                }

                $product->load('images');
                if ($imagePath && $product->images->isEmpty()) {
                    $product->images()->create([
                        'path' => $imagePath,
                        'disk' => 'public',
                        'alt_text' => $product->name_en,
                        'sort_order' => 0,
                        'is_primary' => true,
                    ]);
                    $product->load('images');
                }
                $obsoletePaths = array_merge($obsoletePaths, $this->updateExistingGalleryImages($request, $product));
                $this->storeGalleryImages($request, $product, (int) $product->images()->count(), $storedPaths);
                $this->syncPrimaryImage($request, $product, $newImagePath);
            });
        } catch (\Throwable $e) {
            $this->abandonImageUpload($e, $request, $storedPaths);
        }

        $this->deleteImageFiles($obsoletePaths);

        $redirect = redirect()->to($this->productsIndexReturnUrl($request))
            ->with('success', __('Product updated successfully'));

        if ($dealerPrice !== null && $dealerPrice >= $basePrice) {
            $redirect->with('warning', __('Dealer price is greater than or equal to base price.'));
        }

        return $redirect;
    }

    /**
     * The price columns for what the form sent.
     *
     * In dollars, the typed amounts are kept as the product's real price and
     * the dinar columns are left to the model, which derives them from the
     * current rate on every save. In dinars, the typed amounts are the price
     * and no dollar figure is kept, so the rate can never move it.
     *
     * A request that does not say which currency (an older client) keeps the
     * product's own, and a new product defaults to dinars.
     *
     * @return array<string, mixed>
     */
    private function priceAttributes(Request $request, ?Product $product = null): array
    {
        $currency = $request->filled('price_currency')
            ? ExchangeRate::normalizeCurrency($request->input('price_currency'))
            : ($product?->isUsdPriced() ? ExchangeRate::USD : ExchangeRate::IQD);

        $price = (string) $request->input('price');
        $dealerPrice = $request->filled('dealer_price') ? (string) $request->input('dealer_price') : null;
        // The purchase price is typed in the same currency as the price, so
        // the two are always comparable.
        $costPrice = $request->filled('cost_price') ? (string) $request->input('cost_price') : null;

        if ($currency === ExchangeRate::USD) {
            return [
                'price_currency' => ExchangeRate::USD,
                'price_usd' => ExchangeRate::decimal($price, ExchangeRate::USD_SCALE),
                'dealer_price_usd' => $dealerPrice !== null ? ExchangeRate::decimal($dealerPrice, ExchangeRate::USD_SCALE) : null,
                // The field sets or clears a dollar cost. A cost that is
                // on file in dinars only — entered before the product was
                // moved to dollars — is not this field's to clear.
                ...match (true) {
                    $costPrice !== null => ['cost_price_usd' => ExchangeRate::decimal($costPrice, ExchangeRate::USD_SCALE)],
                    $product?->cost_price_usd !== null => ['cost_price_usd' => null, 'cost_price' => null],
                    default => [],
                },
            ];
        }

        return [
            'price_currency' => ExchangeRate::IQD,
            'price_usd' => null,
            'dealer_price_usd' => null,
            'cost_price_usd' => null,
            'price' => (float) $price,
            'dealer_price' => $dealerPrice !== null ? (float) $dealerPrice : null,
            'cost_price' => $costPrice !== null ? (float) $costPrice : null,
        ];
    }

    /**
     * Remove a product from the catalogue for good.
     *
     * This used to be a delete in name only: a product that had ever been sold
     * was flipped inactive and reported as "archived", because an order line
     * held nothing but its id and the database was told to refuse. Order lines
     * now carry their own copy of what was sold, so the catalogue and the sales
     * history are separate records and this can do what it says.
     *
     * Only a super admin reaches here — see the gate on the route.
     */
    public function destroy(Request $request, Product $product)
    {
        Gate::authorize('products.delete');

        $returnTo = $this->productsIndexReturnUrl($request);
        $name = (string) $product->name_en;
        $sku = (string) $product->sku;

        // Read before the delete: the image rows go with the product.
        $imagePaths = $this->productImagePaths($product);

        try {
            DB::transaction(function () use ($product): void {
                $this->releaseHistoricalReferences($product);
                $product->delete();
            });
        } catch (QueryException $e) {
            Log::error('Product could not be deleted', [
                'product_id' => $product->id,
                'sku' => $sku,
                'error' => $e->getMessage(),
            ]);

            return redirect()->to($returnTo)
                ->with('error', __('Product could not be deleted because it is linked to existing records.'));
        }

        // Files come last, and only once the row is really gone. A failed
        // transaction must not leave a product pointing at pictures that were
        // already thrown away.
        $this->deleteImageFiles($imagePaths);

        AdminLogger::log('product.deleted', null, [
            'product_id' => $product->id,
            'name' => $name,
            'sku' => $sku,
        ]);

        return redirect()->to($returnTo)
            ->with('success', __('Product deleted permanently.'));
    }

    /**
     * Cut the product loose from records that outlive it.
     *
     * Two kinds of row point at a product, and they need opposite treatment.
     * An order line is history — it says what a customer bought and must
     * survive, so its product_id is cleared and its own snapshot carries the
     * name and code. Everything else here belongs to the product itself and
     * has no meaning without it.
     *
     * Rows the schema already cascades — cart items, wishlists, reviews,
     * images, fitments, stock movements, analytics — are left to the database.
     */
    private function releaseHistoricalReferences(Product $product): void
    {
        // History: kept, and let go of the product.
        DB::table('order_items')
            ->where('product_id', $product->id)
            ->update(['product_id' => null]);

        // These belong to the dead enterprise layer: nothing in the application
        // reads them, and each one is a record *about* this product rather than
        // a record of a transaction with a customer. They are refused by the
        // database on delete, so they go first.
        foreach (['price_histories', 'stock_transactions', 'purchase_invoice_items'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'product_id')) {
                DB::table($table)->where('product_id', $product->id)->delete();
            }
        }
    }

    /**
     * Every file this product owns, gallery and main picture alike.
     *
     * @return array<int, string>
     */
    private function productImagePaths(Product $product): array
    {
        $paths = [];

        if ($product->image) {
            $paths[] = (string) $product->image;
        }

        if (Schema::hasTable('product_images')) {
            foreach ($product->images()->get(['path']) as $image) {
                $paths[] = (string) $image->path;
            }
        }

        return array_values(array_unique(array_filter($paths)));
    }

    /**
     * Delete the files, unless another product is still using one.
     *
     * An image can be shared — an import that reuses one path across a range,
     * for instance — and removing it would blank a product nobody touched.
     *
     * @param  array<int, string>  $paths
     */
    private function deleteImageFiles(array $paths): void
    {
        foreach ($paths as $path) {
            // Through the model, so a gallery row that was itself removed —
            // they are soft-deleted — does not keep its file alive for ever.
            $stillUsed = Product::query()->where('image', $path)->exists()
                || (Schema::hasTable('product_images')
                    && ProductImage::query()->where('path', $path)->exists());

            if ($stillUsed) {
                continue;
            }

            Storage::disk('public')->delete($path);
        }
    }

    public function exportExcel()
    {
        try {
            return Excel::download(new ProductsExport, 'products.xlsx');
        } catch (\Throwable $e) {
            Log::error('Products Excel export failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', __('Failed to export products to Excel. Please try again.'));
        }
    }

    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), $this->importValidationRules());
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            if (! Schema::hasColumn('products', 'slug')) {
                return back()->with('error', __('Products table is missing the required slug column. Run the pending product slug migration, then retry the import.'));
            }

            $parsed = $this->parseImportFile($request->file('import_file'));
            $header = $parsed['header'];
            $requiredColumns = ['name_en', 'name_ar', 'name_ku', 'price', 'stock_quantity'];
            foreach ($requiredColumns as $column) {
                if (! in_array($column, $header, true)) {
                    return back()->with('error', __('Missing required column: :column', ['column' => $column]));
                }
            }

            $categories = Category::query()
                ->select(['id', 'name_en', 'slug'])
                ->get();
            $categoriesBySlug = $categories
                ->mapWithKeys(fn ($category) => [strtolower(trim((string) $category->slug)) => (int) $category->id]);
            $categoriesByName = $categories
                ->mapWithKeys(fn ($category) => [strtolower(trim((string) $category->name_en)) => (int) $category->id]);
            $categoriesById = $categories
                ->mapWithKeys(fn ($category) => [(int) $category->id => true]);
            if ($categories->isEmpty()) {
                return back()->with('error', __('No categories found. Please create a category before importing products.'));
            }

            $errors = [];
            $seenSkusInFile = [];
            $preparedRows = [];

            foreach ($parsed['rows'] as $entry) {
                $rowNumber = $entry['row'];
                $rowData = $entry['data'];
                if (! isset($rowData['category_name']) && isset($rowData['category'])) {
                    $rowData['category_name'] = $rowData['category'];
                }

                $rowValidator = Validator::make($rowData, $this->importRowValidationRules());

                if ($rowValidator->fails()) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'sku' => $rowData['sku'] ?? '',
                        'message' => implode('; ', $rowValidator->errors()->all()),
                    ];

                    continue;
                }

                $categoryId = $this->resolveCategoryId(
                    $rowData,
                    $categoriesById->all(),
                    $categoriesBySlug->all(),
                    $categoriesByName->all()
                );
                if ($categoryId === null) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'sku' => $rowData['sku'] ?? '',
                        'message' => __('Category is required and must match category_id, category_slug, or category_name.'),
                    ];

                    continue;
                }

                // A row is priced in dinars unless it says otherwise. A dollar
                // row takes its amounts from the price_usd columns, never from
                // `price`: an exported sheet carries the dinar equivalent
                // there, and reading that as dollars would be ruinous.
                $rowCurrency = ExchangeRate::normalizeCurrency($rowData['price_currency'] ?? '');
                $rowPriceUsd = ExchangeRate::decimal(trim((string) ($rowData['price_usd'] ?? '')), ExchangeRate::USD_SCALE);
                $rowDealerPriceUsd = trim((string) ($rowData['dealer_price_usd'] ?? '')) !== ''
                    ? ExchangeRate::decimal(trim((string) $rowData['dealer_price_usd']), ExchangeRate::USD_SCALE)
                    : null;
                $rowCostPriceUsd = trim((string) ($rowData['cost_price_usd'] ?? '')) !== ''
                    ? ExchangeRate::decimal(trim((string) $rowData['cost_price_usd']), ExchangeRate::USD_SCALE)
                    : null;

                if ($rowCurrency === ExchangeRate::USD) {
                    $usdProblem = match (true) {
                        ! ExchangeRate::isConfigured() => __('Set the exchange rate before importing USD-priced products.'),
                        $rowPriceUsd === null => __('A USD-priced row needs a valid price_usd value.'),
                        trim((string) ($rowData['dealer_price_usd'] ?? '')) !== '' && $rowDealerPriceUsd === null => __('The dealer_price_usd value is not a valid amount.'),
                        trim((string) ($rowData['cost_price_usd'] ?? '')) !== '' && $rowCostPriceUsd === null => __('The cost_price_usd value is not a valid amount.'),
                        default => null,
                    };

                    if ($usdProblem !== null) {
                        $errors[] = [
                            'row' => $rowNumber,
                            'sku' => $rowData['sku'] ?? '',
                            'message' => $usdProblem,
                        ];

                        continue;
                    }
                }

                $providedSku = trim((string) ($rowData['sku'] ?? ''));
                $sku = $providedSku;
                $skuKey = strtolower($sku);
                $existingProductId = null;

                if ($providedSku !== '') {
                    if (isset($seenSkusInFile[$skuKey])) {
                        $errors[] = [
                            'row' => $rowNumber,
                            'sku' => $providedSku,
                            'message' => __('Duplicate SKU found in file. Keep SKU unique per file.'),
                        ];

                        continue;
                    }

                    $existingProductId = Product::query()
                        ->where('sku', $providedSku)
                        ->value('id');
                } else {
                    do {
                        $sku = 'SKU-'.Str::upper(Str::random(10));
                        $skuKey = strtolower($sku);
                    } while (isset($seenSkusInFile[$skuKey]) || Product::where('sku', $sku)->exists());
                }

                $payload = [
                    'category_id' => $categoryId,
                    'name_en' => (string) $rowData['name_en'],
                    'name_ar' => (string) $rowData['name_ar'],
                    'name_ku' => (string) $rowData['name_ku'],
                    'description_en' => ($rowData['description_en'] ?? '') !== '' ? (string) $rowData['description_en'] : null,
                    'description_ar' => ($rowData['description_ar'] ?? '') !== '' ? (string) $rowData['description_ar'] : null,
                    'description_ku' => ($rowData['description_ku'] ?? '') !== '' ? (string) $rowData['description_ku'] : null,
                    ...($rowCurrency === ExchangeRate::USD
                        ? [
                            'price_currency' => ExchangeRate::USD,
                            'price_usd' => $rowPriceUsd,
                            'dealer_price_usd' => $rowDealerPriceUsd,
                        ]
                        : [
                            'price_currency' => ExchangeRate::IQD,
                            'price_usd' => null,
                            'dealer_price_usd' => null,
                            'price' => (float) $rowData['price'],
                            'dealer_price' => (($rowData['dealer_price'] ?? '') !== '') ? (float) $rowData['dealer_price'] : null,
                        ]),
                    // Purchase price is only written when the sheet has the
                    // column: a file without it must not wipe costs on file.
                    ...($rowCurrency === ExchangeRate::USD
                        ? (array_key_exists('cost_price_usd', $rowData) ? ['cost_price_usd' => $rowCostPriceUsd] : [])
                        : (array_key_exists('cost_price', $rowData)
                            ? ['cost_price' => (($rowData['cost_price'] ?? '') !== '') ? (float) $rowData['cost_price'] : null]
                            : [])),
                    'stock_quantity' => (int) $rowData['stock_quantity'],
                    'sku' => $sku,
                    'oem_number' => ($rowData['oem_number'] ?? '') !== '' ? (string) $rowData['oem_number'] : null,
                    'part_number' => ($rowData['part_number'] ?? '') !== '' ? (string) $rowData['part_number'] : null,
                    // A file may say "6 months" or carry the code; either way
                    // it is stored as the code when it names a known period.
                    'warranty' => ($rowData['warranty'] ?? '') !== ''
                        ? (ProductWarranty::normalize((string) $rowData['warranty']) ?? (string) $rowData['warranty'])
                        : null,
                    'brand' => ($rowData['brand'] ?? '') !== '' ? (string) $rowData['brand'] : null,
                    'is_active' => array_key_exists('is_active', $rowData)
                        ? $this->toBoolean($rowData['is_active'])
                        : true,
                ];

                $preparedRows[] = [
                    'row' => $rowNumber,
                    'sku' => $sku,
                    'sku_key' => $skuKey,
                    'existing_product_id' => $existingProductId,
                    'payload' => $payload,
                ];
            }

            if (! empty($errors)) {
                return redirect()
                    ->to($this->productsIndexReturnUrl($request))
                    ->with('error', __('Import validation failed. No rows were imported.'))
                    ->with('import_errors', $errors);
            }

            $created = 0;
            $updated = 0;
            foreach ($preparedRows as $preparedRow) {
                $rowNumber = $preparedRow['row'];
                $sku = $preparedRow['sku'];
                $skuKey = $preparedRow['sku_key'];
                $existingProductId = $preparedRow['existing_product_id'];
                $payload = $preparedRow['payload'];

                $importBrand = $this->resolveProductBrandByName($payload['brand'] ?? null);
                $payload['product_brand_id'] = $importBrand?->id;
                $payload['brand'] = $importBrand?->name;

                try {
                    if ($existingProductId !== null) {
                        $existing = Product::query()->find($existingProductId);
                        if ($existing) {
                            $existing->update($payload);
                            $updated++;
                        } else {
                            Product::create($payload);
                            $created++;
                        }
                    } else {
                        Product::create($payload);
                        $created++;
                    }

                    $seenSkusInFile[$skuKey] = true;
                } catch (\Throwable $e) {
                    $friendlyMessage = $this->friendlyImportSaveError($e);

                    Log::error('Product import row failed', [
                        'row' => $rowNumber,
                        'sku' => $sku,
                        'error' => $e->getMessage(),
                        'friendly_message' => $friendlyMessage,
                    ]);

                    $errors[] = [
                        'row' => $rowNumber,
                        'sku' => $sku,
                        'message' => $friendlyMessage,
                    ];
                }
            }

            $message = __('Import completed successfully. Created: :created, Updated: :updated.', ['created' => $created, 'updated' => $updated]);
            if (! empty($errors)) {
                $message .= ' '.__('Some rows were skipped. Please review the import errors.');
            }

            return redirect()
                ->to($this->productsIndexReturnUrl($request))
                ->with('success', $message)
                ->with('import_errors', $errors);
        } catch (\Throwable $e) {
            Log::error('Product import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', __('Import failed unexpectedly. Please verify the file format and try again.'));
        }
    }

    private function importValidationRules(): array
    {
        return [
            'import_file' => ['required', 'file', 'max:5120', 'mimes:csv,txt,xls,xlsx'],
        ];
    }

    private function importRowValidationRules(): array
    {
        return [
            'name_en' => ['required', 'string'],
            'name_ar' => ['required', 'string'],
            'name_ku' => ['required', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'dealer_price' => ['nullable', 'numeric', 'min:0'],
            'price_currency' => ['nullable', 'string', 'max:3'],
            'price_usd' => ['nullable', 'string', 'max:20'],
            'dealer_price_usd' => ['nullable', 'string', 'max:20'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'cost_price_usd' => ['nullable', 'string', 'max:20'],
            'sku' => ['nullable', 'string', 'max:64'],
            'oem_number' => ['nullable', 'string', 'max:120'],
            'part_number' => ['nullable', 'string', 'max:120'],
            'warranty' => ['nullable', 'string', 'max:160'],
            'brand' => ['nullable', 'string', 'max:100'],
            'description_en' => ['nullable', 'string'],
            'description_ar' => ['nullable', 'string'],
            'description_ku' => ['nullable', 'string'],
            'category_id' => ['nullable', 'integer', 'min:1'],
            'category_slug' => ['nullable', 'string'],
            'category_name' => ['nullable', 'string'],
            'category' => ['nullable', 'string'],
            'is_active' => ['nullable'],
        ];
    }

    private function friendlyImportSaveError(\Throwable $e): string
    {
        $message = $e->getMessage();

        if (str_contains($message, "Unknown column 'slug'")) {
            return __('Products table is missing the required slug column. Apply the product slug migration before importing.');
        }

        if (str_contains($message, 'products_sku_unique') || str_contains($message, 'Duplicate entry')) {
            return __('Duplicate SKU detected in the database for this row.');
        }

        if (str_contains($message, 'products_category_id_foreign')) {
            return __('Category not found for this row.');
        }

        if (str_contains($message, 'cannot be null')) {
            return __('A required product field is missing for this row.');
        }

        if (str_contains($message, 'Data too long for column')) {
            return __('One of the text fields is too long for this row.');
        }

        if ($e instanceof QueryException && $e->getCode() === '22007') {
            return __('Invalid numeric or date value detected in this row.');
        }

        return __('Could not save this row due to a database error: :message', ['message' => Str::limit($message, 180)]);
    }

    private function toBoolean(string|int|bool|null $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'yes', 'active'], true);
    }

    /**
     * @param  array<int, string>  $storedPaths  Collects each file written, for the caller to undo.
     */
    private function storeGalleryImages(Request $request, Product $product, int $startingOrder = 0, array &$storedPaths = []): void
    {
        if (! $request->hasFile('gallery_images')) {
            return;
        }

        foreach ($request->file('gallery_images') as $index => $file) {
            if (! $file || ! $file->isValid()) {
                continue;
            }

            $product->images()->create([
                'path' => $storedPaths[] = SecureImageStorage::store($file, 'products'),
                'disk' => 'public',
                'alt_text' => $product->name_en,
                'sort_order' => $startingOrder + $index,
                'is_primary' => false,
            ]);
        }
    }

    /**
     * @return array<int, string> Files no longer wanted, to delete once the change is committed.
     */
    private function updateExistingGalleryImages(Request $request, Product $product): array
    {
        $removedPaths = [];

        $removeIds = collect($request->input('remove_gallery_image_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        if ($removeIds->isNotEmpty()) {
            $images = $product->images()->whereIn('id', $removeIds)->get();
            foreach ($images as $image) {
                $removedPaths[] = (string) $image->path;
                $image->delete();
            }
        }

        $sortOrders = $request->input('gallery_sort_order', []);
        $altTexts = $request->input('gallery_alt_text', []);
        foreach ($product->images as $image) {
            if ($removeIds->contains((int) $image->id)) {
                continue;
            }

            $image->update([
                'sort_order' => isset($sortOrders[$image->id]) ? (int) $sortOrders[$image->id] : $image->sort_order,
                'alt_text' => isset($altTexts[$image->id]) ? trim((string) $altTexts[$image->id]) : $image->alt_text,
            ]);
        }

        return $removedPaths;
    }

    /**
     * Undo a save that failed while pictures were being handled.
     *
     * The files written so far go, since nothing points at them any more. A
     * picture that could not be kept sends the admin back to the form with
     * everything they typed and a reason they can act on; the detail goes to
     * the log. Anything else is not an upload problem and carries on up.
     *
     * @param  array<int, string>  $storedPaths
     */
    private function abandonImageUpload(\Throwable $e, Request $request, array $storedPaths): never
    {
        Storage::disk('public')->delete($storedPaths);

        if (! $e instanceof ImageUploadException) {
            throw $e;
        }

        Log::log($e->reason === ImageUploadException::NOT_SAVED ? 'error' : 'warning', 'Product image could not be stored', [
            'reason' => $e->reason,
            'error' => $e->getMessage(),
            'route' => $request->route()?->getName(),
            'product_id' => $request->route('product')?->id,
            'user_id' => $request->user()?->getAuthIdentifier(),
            'disk_root' => config('filesystems.disks.public.root'),
        ]);

        throw ValidationException::withMessages([
            'image' => match ($e->reason) {
                ImageUploadException::TOO_MANY_PIXELS => __('The image dimensions are too large to process. Please upload a smaller image.'),
                ImageUploadException::NOT_SAVED => __('The image could not be saved on the server. Please try again later.'),
                default => __('The image could not be read. Please upload a valid JPG, PNG or WEBP file.'),
            },
        ]);
    }

    private function syncPrimaryImage(Request $request, Product $product, ?string $newImagePath = null): void
    {
        // A picture uploaded as the main image is the main image. The form
        // always posts the previous choice alongside it, which says nothing
        // about what the admin wants now.
        $primaryImageId = (int) $request->input('primary_image_id', 0);
        $primaryImage = match (true) {
            $newImagePath !== null => $product->images()->where('path', $newImagePath)->first(),
            $primaryImageId > 0 => $product->images()->whereKey($primaryImageId)->first(),
            default => null,
        };

        if (! $primaryImage) {
            $primaryImage = $product->images()->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id')->first();
        }

        if (! $primaryImage) {
            $product->update(['image' => null]);

            return;
        }

        // The chosen row is left out: clearing it here behind the model's back
        // made the next line a no-op whenever it was already the primary, and
        // the product ended up with no primary at all.
        ProductImage::query()
            ->where('product_id', $product->id)
            ->whereKeyNot($primaryImage->id)
            ->update(['is_primary' => false]);

        $primaryImage->update(['is_primary' => true]);
        $product->update(['image' => $primaryImage->path]);
    }

    private function detectDelimiter(string $line): string
    {
        $delimiters = [',', ';', "\t"];
        $bestDelimiter = ',';
        $maxColumns = 0;

        foreach ($delimiters as $delimiter) {
            $columns = count(str_getcsv($line, $delimiter));
            if ($columns > $maxColumns) {
                $maxColumns = $columns;
                $bestDelimiter = $delimiter;
            }
        }

        return $bestDelimiter;
    }

    private function parseImportFile(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $path = $file->getRealPath();

        if ($path === false) {
            throw new \RuntimeException(__('Unable to read uploaded file.'));
        }

        return match ($extension) {
            'csv', 'txt' => $this->parseCsvFile($path),
            'xls', 'xlsx' => $this->parseExcelFile($path),
            default => throw new \RuntimeException(__('Unsupported file type. Please upload CSV or Excel (.xls/.xlsx).')),
        };
    }

    private function parseCsvFile(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new \RuntimeException(__('Unable to open uploaded file.'));
        }

        try {
            $firstLine = fgets($handle);
            if ($firstLine === false) {
                throw new \RuntimeException(__('Import file is empty.'));
            }

            $delimiter = $this->detectDelimiter($firstLine);
            rewind($handle);

            $rawHeader = fgetcsv($handle, 0, $delimiter);
            if (! $rawHeader) {
                throw new \RuntimeException(__('Import file is empty.'));
            }

            $header = array_map(fn ($h) => $this->normalizeHeader((string) $h), $rawHeader);
            $rows = [];
            $rowNumber = 1;

            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $rowNumber++;
                if ($this->isEmptyRow($row)) {
                    continue;
                }

                $rows[] = [
                    'row' => $rowNumber,
                    'data' => $this->mapRowToHeader($header, $row),
                ];
            }

            return ['header' => $header, 'rows' => $rows];
        } finally {
            fclose($handle);
        }
    }

    private function parseExcelFile(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rawRows = $sheet->toArray(null, false, false, false);

        if (empty($rawRows)) {
            throw new \RuntimeException(__('Import file is empty.'));
        }

        $rawHeader = array_shift($rawRows);
        $header = array_map(fn ($h) => $this->normalizeHeader((string) $h), (array) $rawHeader);

        if (count(array_filter($header, fn ($h) => $h !== '')) === 0) {
            throw new \RuntimeException(__('Import file is empty.'));
        }

        $rows = [];
        foreach ($rawRows as $index => $row) {
            if ($this->isEmptyRow((array) $row)) {
                continue;
            }

            $rows[] = [
                'row' => $index + 2,
                'data' => $this->mapRowToHeader($header, (array) $row),
            ];
        }

        return ['header' => $header, 'rows' => $rows];
    }

    private function mapRowToHeader(array $header, array $row): array
    {
        $rowData = [];
        foreach ($header as $index => $column) {
            if ($column === '') {
                continue;
            }

            $rowData[$column] = isset($row[$index]) ? trim((string) $row[$index]) : null;
        }

        return $rowData;
    }

    private function isEmptyRow(array $row): bool
    {
        return count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0;
    }

    private function normalizeHeader(string $header): string
    {
        $normalized = strtolower(trim($header));
        $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized) ?? '';

        return trim($normalized, '_');
    }

    private function resolveCategoryId(
        array $rowData,
        array $categoriesById,
        array $categoriesBySlug,
        array $categoriesByName
    ): ?int {
        $categoryIdRaw = trim((string) ($rowData['category_id'] ?? ''));
        if ($categoryIdRaw !== '') {
            if (! is_numeric($categoryIdRaw)) {
                return null;
            }

            $categoryId = (int) $categoryIdRaw;
            if ($categoryId <= 0) {
                return null;
            }

            return isset($categoriesById[$categoryId]) ? $categoryId : null;
        }

        $categorySlug = strtolower(trim((string) ($rowData['category_slug'] ?? '')));
        if ($categorySlug !== '') {
            return $categoriesBySlug[$categorySlug] ?? null;
        }

        $categoryName = strtolower(trim((string) ($rowData['category_name'] ?? '')));
        if ($categoryName !== '') {
            return $categoriesByName[$categoryName] ?? null;
        }

        return null;
    }

    private function resolveRequestedBrand(Request $request): ?ProductBrand
    {
        if ($request->has('product_brand_id')) {
            return $request->filled('product_brand_id')
                ? ProductBrand::query()->findOrFail((int) $request->input('product_brand_id'))
                : null;
        }

        return $this->resolveProductBrandByName($request->input('brand'));
    }

    private function resolveProductBrandByName(mixed $rawName): ?ProductBrand
    {
        $name = trim((string) $rawName);
        if ($name === '') {
            return null;
        }

        $brand = ProductBrand::withTrashed()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($brand) {
            if ($brand->trashed()) {
                $brand->restore();
            }

            return $brand;
        }

        $baseSlug = Str::slug($name) ?: 'brand';
        $slug = $baseSlug;
        $suffix = 2;

        while (ProductBrand::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        return ProductBrand::query()->create([
            'name' => $name,
            'slug' => $slug,
        ]);
    }

    private function productsIndexReturnUrl(Request $request): string
    {
        $returnTo = trim((string) ($request->input('return_to') ?: $request->query('return_to', '')));
        if ($returnTo === '') {
            return route('admin.products.index');
        }

        $parts = parse_url($returnTo);
        if ($parts === false) {
            return route('admin.products.index');
        }

        $path = (string) ($parts['path'] ?? '');
        $expectedPath = (string) parse_url(route('admin.products.index'), PHP_URL_PATH);
        if ($path !== $expectedPath) {
            return route('admin.products.index');
        }

        if (isset($parts['host']) && ! hash_equals($request->getHost(), (string) $parts['host'])) {
            return route('admin.products.index');
        }

        $query = isset($parts['query']) ? '?'.$parts['query'] : '';

        return $path.$query;
    }
}
