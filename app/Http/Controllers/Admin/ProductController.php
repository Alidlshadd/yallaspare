<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ProductsExport;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Setting;
use App\Support\SecureImageStorage;
use App\Support\SqlSafe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
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
                'name_en',
                'image',
                'sku',
                'brand',
                'is_active',
                'price',
                'dealer_price',
                'stock_quantity',
                'created_at',
            ])
            ->with(['category:id,name_en,name_ar,name_ku,slug']);

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

        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'id';
        }

        if (!in_array($direction, ['asc', 'desc'], true)) {
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
        $brands = Cache::remember('admin:products:brands:v1', now()->addSeconds($metaCacheTtl), function () {
            return Product::query()
                ->whereNotNull('brand')
                ->where('brand', '<>', '')
                ->distinct()
                ->orderBy('brand')
                ->pluck('brand');
        });
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

    public function create()
    {
        $categories = Category::orderBy('name_en')->get();

        $currencySymbol = (string) Setting::getValue('currency_symbol', 'IQD');
        $currencyCode = (string) Setting::getValue('currency_code', 'IQD');
        $currencyLabel = $currencyCode !== '' ? $currencyCode : $currencySymbol;
        $currencyDecimals = strtoupper($currencyCode) === 'IQD' ? 0 : 2;
        $lowStockThreshold = max((int) Setting::getValue('low_stock_threshold', config('inventory.low_stock_threshold', 5)), 0);

        return view('admin.products.create', compact('categories', 'currencySymbol', 'currencyCode', 'currencyLabel', 'currencyDecimals', 'lowStockThreshold'));
    }

    public function store(StoreProductRequest $request)
    {
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = SecureImageStorage::store($request->file('image'), 'products');
        }

        $compatibleModels = $request->filled('compatible_models')
            ? array_values(array_filter(array_map('trim', preg_split('/[,\n]+/', $request->compatible_models))))
            : null;

        $sku = $request->filled('sku')
            ? $request->sku
            : 'SKU-' . Str::upper(Str::random(10));

        $dealerPrice = $request->filled('dealer_price') ? (float) $request->dealer_price : null;
        $basePrice = (float) $request->price;

        $product = Product::create([
            'category_id' => $request->category_id,
            'name_en' => $request->name_en,
            'name_ar' => $request->name_ar,
            'name_ku' => $request->name_ku,
            'description_en' => $request->description_en,
            'description_ar' => $request->description_ar,
            'description_ku' => $request->description_ku,
            'price' => $basePrice,
            'dealer_price' => $dealerPrice,
            'stock_quantity' => $request->stock_quantity,
            'sku' => $sku,
            'oem_number' => $request->filled('oem_number') ? $request->oem_number : null,
            'part_number' => $request->filled('part_number') ? $request->part_number : null,
            'warranty' => $request->filled('warranty') ? $request->warranty : null,
            'brand' => $request->brand,
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

        $this->storeGalleryImages($request, $product, $imagePath ? 1 : 0);

        $redirect = redirect()->route('admin.products.index')
            ->with('success', __('Product added successfully'));

        if ($dealerPrice !== null && $dealerPrice >= $basePrice) {
            $redirect->with('warning', __('Dealer price is greater than or equal to base price.'));
        }

        return $redirect;
    }

    public function edit(Request $request, Product $product)
    {
        $categories = Category::orderBy('name_en')->get();

        $currencySymbol = (string) Setting::getValue('currency_symbol', 'IQD');
        $currencyCode = (string) Setting::getValue('currency_code', 'IQD');
        $currencyLabel = $currencyCode !== '' ? $currencyCode : $currencySymbol;
        $currencyDecimals = strtoupper($currencyCode) === 'IQD' ? 0 : 2;
        $lowStockThreshold = max((int) Setting::getValue('low_stock_threshold', config('inventory.low_stock_threshold', 5)), 0);

        $product->load('images');
        $returnTo = $this->productsIndexReturnUrl($request);

        return view('admin.products.edit', compact('product', 'categories', 'currencySymbol', 'currencyCode', 'currencyLabel', 'currencyDecimals', 'lowStockThreshold', 'returnTo'));
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
        $oldImagePath = $product->image;
        $imagePath = $product->image;
        if ($request->boolean('remove_image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $imagePath = null;
        }
        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $imagePath = SecureImageStorage::store($request->file('image'), 'products');
        }

        $compatibleModels = $request->filled('compatible_models')
            ? array_values(array_filter(array_map('trim', preg_split('/[,\n]+/', $request->compatible_models))))
            : null;

        $dealerPrice = $request->filled('dealer_price') ? (float) $request->dealer_price : null;
        $basePrice = (float) $request->price;

        $product->update([
            'category_id' => $request->category_id,
            'name_en' => $request->name_en,
            'name_ar' => $request->name_ar,
            'name_ku' => $request->name_ku,
            'description_en' => $request->description_en,
            'description_ar' => $request->description_ar,
            'description_ku' => $request->description_ku,
            'price' => $basePrice,
            'dealer_price' => $dealerPrice,
            'stock_quantity' => $request->stock_quantity,
            'sku' => $request->filled('sku') ? $request->sku : $product->sku,
            'oem_number' => $request->filled('oem_number') ? $request->oem_number : null,
            'part_number' => $request->filled('part_number') ? $request->part_number : null,
            'warranty' => $request->filled('warranty') ? $request->warranty : null,
            'brand' => $request->brand,
            'compatible_models' => $compatibleModels,
            'image' => $imagePath,
            'is_active' => $request->boolean('is_active'),
        ]);

        if ($request->boolean('remove_image') && $oldImagePath) {
            $product->images()->where('path', $oldImagePath)->delete();
        }

        if ($request->hasFile('image') && $imagePath) {
            $product->images()->update(['is_primary' => false]);
            $product->images()->create([
                'path' => $imagePath,
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
        $this->updateExistingGalleryImages($request, $product);
        $this->storeGalleryImages($request, $product, (int) $product->images()->count());
        $this->syncPrimaryImage($request, $product);

        $redirect = redirect()->to($this->productsIndexReturnUrl($request))
            ->with('success', __('Product updated successfully'));

        if ($dealerPrice !== null && $dealerPrice >= $basePrice) {
            $redirect->with('warning', __('Dealer price is greater than or equal to base price.'));
        }

        return $redirect;
    }

    public function destroy(Product $product)
    {
        if ($product->orderItems()->exists()) {
            $product->forceFill(['is_active' => false])->save();

            return redirect()->route('admin.products.index')
                ->with('success', __('Product is linked to existing orders, so it was archived instead of deleted.'));
        }

        try {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $product->delete();
        } catch (QueryException $e) {
            return redirect()->route('admin.products.index')
                ->with('error', __('Product could not be deleted because it is linked to existing records.'));
        }

        return redirect()->route('admin.products.index')
            ->with('success', __('Product deleted successfully'));
    }

    public function exportExcel()
    {
        try {
            return Excel::download(new ProductsExport(), 'products.xlsx');
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
                if (!in_array($column, $header, true)) {
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
                if (!isset($rowData['category_name']) && isset($rowData['category'])) {
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
                        $sku = 'SKU-' . Str::upper(Str::random(10));
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
                    'price' => (float) $rowData['price'],
                    'dealer_price' => (($rowData['dealer_price'] ?? '') !== '') ? (float) $rowData['dealer_price'] : null,
                    'stock_quantity' => (int) $rowData['stock_quantity'],
                    'sku' => $sku,
                    'oem_number' => ($rowData['oem_number'] ?? '') !== '' ? (string) $rowData['oem_number'] : null,
                    'part_number' => ($rowData['part_number'] ?? '') !== '' ? (string) $rowData['part_number'] : null,
                    'warranty' => ($rowData['warranty'] ?? '') !== '' ? (string) $rowData['warranty'] : null,
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

            if (!empty($errors)) {
                return redirect()
                    ->route('admin.products.index')
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
            if (!empty($errors)) {
                $message .= ' ' . __('Some rows were skipped. Please review the import errors.');
            }

            return redirect()
                ->route('admin.products.index')
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

    private function storeGalleryImages(Request $request, Product $product, int $startingOrder = 0): void
    {
        if (! $request->hasFile('gallery_images')) {
            return;
        }

        foreach ($request->file('gallery_images') as $index => $file) {
            if (! $file || ! $file->isValid()) {
                continue;
            }

            $product->images()->create([
                'path' => SecureImageStorage::store($file, 'products'),
                'disk' => 'public',
                'alt_text' => $product->name_en,
                'sort_order' => $startingOrder + $index,
                'is_primary' => false,
            ]);
        }
    }

    private function updateExistingGalleryImages(Request $request, Product $product): void
    {
        $removeIds = collect($request->input('remove_gallery_image_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        if ($removeIds->isNotEmpty()) {
            $images = $product->images()->whereIn('id', $removeIds)->get();
            foreach ($images as $image) {
                Storage::disk($image->disk ?: 'public')->delete($image->path);
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
    }

    private function syncPrimaryImage(Request $request, Product $product): void
    {
        $primaryImageId = (int) $request->input('primary_image_id', 0);
        $primaryImage = $primaryImageId > 0
            ? $product->images()->whereKey($primaryImageId)->first()
            : null;

        if (! $primaryImage) {
            $primaryImage = $product->images()->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id')->first();
        }

        if (! $primaryImage) {
            $product->update(['image' => null]);
            return;
        }

        ProductImage::query()
            ->where('product_id', $product->id)
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

    private function parseImportFile(\Illuminate\Http\UploadedFile $file): array
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
            if (!$rawHeader) {
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
            if (!is_numeric($categoryIdRaw)) {
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

        $query = isset($parts['query']) ? '?' . $parts['query'] : '';

        return $path . $query;
    }
}
