<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ProductsExport;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $lowStockThreshold = max((int) Setting::getValue('low_stock_threshold', config('inventory.low_stock_threshold', 5)), 0);
        $query = Product::query()
            ->select([
                'id',
                'category_id',
                'name_en',
                'sku',
                'brand',
                'is_active',
                'price',
                'dealer_price',
                'stock_quantity',
                'created_at',
            ])
            ->with(['category:id,name_en,slug']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name_en', 'like', '%' . $search . '%')
                    ->orWhere('name_ar', 'like', '%' . $search . '%')
                    ->orWhere('name_ku', 'like', '%' . $search . '%')
                    ->orWhere('sku', 'like', '%' . $search . '%')
                    ->orWhere('brand', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->boolean('low_stock')) {
            $query->where('stock_quantity', '<=', $lowStockThreshold);
        }

        $allowedSorts = [
            'id', 'name_en', 'price', 'stock_quantity', 'sku', 'brand', 'is_active', 'created_at',
        ];

        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('dir', 'desc');

        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'created_at';
        }

        if (!in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }

        $query->orderBy($sort, $direction);

        $products = $query->paginate(10)->withQueryString();
        $metaCacheTtl = max((int) config('performance.products_meta_cache_ttl', 300), 30);
        $categories = Cache::remember('admin:products:categories:v1', now()->addSeconds($metaCacheTtl), function () {
            return Category::query()->select(['id', 'name_en', 'slug'])->orderBy('name_en')->get();
        });
        $lowStockCount = Cache::remember(
            "admin:products:low-stock-count:v1:threshold:{$lowStockThreshold}",
            now()->addSeconds(min($metaCacheTtl, 120)),
            fn () => Product::where('stock_quantity', '<=', $lowStockThreshold)->count()
        );
        $currencySymbol = (string) Setting::getValue('currency_symbol', 'IQD');
        $currencyCode = (string) Setting::getValue('currency_code', 'IQD');
        $currencyLabel = $currencyCode !== '' ? $currencyCode : $currencySymbol;
        $currencyDecimals = strtoupper($currencyCode) === 'IQD' ? 0 : 2;

        return view('admin.products.index', compact(
            'products',
            'categories',
            'sort',
            'direction',
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

    public function store(Request $request)
    {
        $request->validate([
            'name_en' => 'required',
            'name_ar' => 'required',
            'name_ku' => 'required',
            'description_en' => 'nullable|string',
            'description_ar' => 'nullable|string',
            'description_ku' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'dealer_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'sku' => 'nullable|string|max:64|unique:products,sku',
            'brand' => 'nullable|string|max:100',
            'compatible_models' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'is_active' => 'sometimes|boolean',
            'category_id' => 'required|exists:categories,id',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        $compatibleModels = $request->filled('compatible_models')
            ? array_values(array_filter(array_map('trim', preg_split('/[,\n]+/', $request->compatible_models))))
            : null;

        $sku = $request->filled('sku')
            ? $request->sku
            : 'SKU-' . Str::upper(Str::random(10));

        $dealerPrice = $request->filled('dealer_price') ? (float) $request->dealer_price : null;
        $basePrice = (float) $request->price;

        Product::create([
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
            'brand' => $request->brand,
            'compatible_models' => $compatibleModels,
            'image' => $imagePath,
            'is_active' => $request->boolean('is_active'),
        ]);

        $redirect = redirect()->route('admin.products.index')
            ->with('success', 'Product added successfully');

        if ($dealerPrice !== null && $dealerPrice >= $basePrice) {
            $redirect->with('warning', 'Dealer price is greater than or equal to base price.');
        }

        return $redirect;
    }

    public function edit(Product $product)
    {
        $categories = Category::orderBy('name_en')->get();

        $currencySymbol = (string) Setting::getValue('currency_symbol', 'IQD');
        $currencyCode = (string) Setting::getValue('currency_code', 'IQD');
        $currencyLabel = $currencyCode !== '' ? $currencyCode : $currencySymbol;
        $currencyDecimals = strtoupper($currencyCode) === 'IQD' ? 0 : 2;
        $lowStockThreshold = max((int) Setting::getValue('low_stock_threshold', config('inventory.low_stock_threshold', 5)), 0);

        return view('admin.products.edit', compact('product', 'categories', 'currencySymbol', 'currencyCode', 'currencyLabel', 'currencyDecimals', 'lowStockThreshold'));
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name_en' => 'required',
            'name_ar' => 'required',
            'name_ku' => 'required',
            'description_en' => 'nullable|string',
            'description_ar' => 'nullable|string',
            'description_ku' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'dealer_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'sku' => [
                'nullable',
                'string',
                'max:64',
                Rule::unique('products', 'sku')->ignore($product->id),
            ],
            'brand' => 'nullable|string|max:100',
            'compatible_models' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'is_active' => 'sometimes|boolean',
            'remove_image' => 'sometimes|boolean',
            'category_id' => 'required|exists:categories,id',
        ]);

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
            $imagePath = $request->file('image')->store('products', 'public');
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
            'brand' => $request->brand,
            'compatible_models' => $compatibleModels,
            'image' => $imagePath,
            'is_active' => $request->boolean('is_active'),
        ]);

        $redirect = redirect()->route('admin.products.index')
            ->with('success', 'Product updated successfully');

        if ($dealerPrice !== null && $dealerPrice >= $basePrice) {
            $redirect->with('warning', 'Dealer price is greater than or equal to base price.');
        }

        return $redirect;
    }

    public function destroy(Product $product)
    {
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }
        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', 'Product deleted successfully');
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

            return back()->with('error', 'Failed to export products to Excel. Please try again.');
        }
    }

    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), $this->importValidationRules());
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $handle = null;

        try {
            $path = $request->file('import_file')->getRealPath();
            if ($path === false) {
                return back()->with('error', 'Unable to read uploaded file.');
            }

            $handle = fopen($path, 'r');
            if ($handle === false) {
                return back()->with('error', 'Unable to open uploaded file.');
            }

            $firstLine = fgets($handle);
            if ($firstLine === false) {
                return back()->with('error', 'Import file is empty.');
            }

            $delimiter = $this->detectDelimiter($firstLine);
            rewind($handle);

            $header = fgetcsv($handle, 0, $delimiter);
            if (!$header) {
                return back()->with('error', 'Import file is empty.');
            }

            $header = array_map(fn ($h) => strtolower(trim((string) $h)), $header);
            $requiredColumns = ['name_en', 'name_ar', 'name_ku', 'price', 'stock_quantity'];
            foreach ($requiredColumns as $column) {
                if (!in_array($column, $header, true)) {
                    return back()->with('error', "Missing required column: {$column}");
                }
            }

            $categoriesBySlug = Category::pluck('id', 'slug')
                ->mapWithKeys(fn ($id, $slug) => [strtolower(trim((string) $slug)) => (int) $id]);
            $defaultCategoryId = Category::query()->value('id');
            if ($defaultCategoryId === null) {
                return back()->with('error', 'No categories found. Please create a category before importing products.');
            }

            $rowNumber = 1;
            $imported = 0;
            $errors = [];
            $seenSkusInFile = [];

            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $rowNumber++;

                if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                    continue;
                }

                $rowData = [];
                foreach ($header as $index => $column) {
                    $rowData[$column] = isset($row[$index]) ? trim((string) $row[$index]) : null;
                }

                $rowValidator = Validator::make($rowData, $this->importRowValidationRules(), [
                    'category_slug.exists' => 'The selected category_slug does not exist in categories.',
                ]);

                if ($rowValidator->fails()) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'sku' => $rowData['sku'] ?? '',
                        'message' => implode('; ', $rowValidator->errors()->all()),
                    ];
                    continue;
                }

                $categorySlug = strtolower(trim((string) ($rowData['category_slug'] ?? '')));
                $categoryId = $categorySlug !== '' ? ($categoriesBySlug[$categorySlug] ?? null) : (int) $defaultCategoryId;
                if ($categoryId === null) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'sku' => $rowData['sku'] ?? '',
                        'message' => 'The selected category_slug does not exist in categories.',
                    ];
                    continue;
                }

                $providedSku = trim((string) ($rowData['sku'] ?? ''));
                $sku = $providedSku;
                $skuKey = strtolower($sku);

                if ($providedSku !== '') {
                    if (isset($seenSkusInFile[$skuKey]) || Product::where('sku', $providedSku)->exists()) {
                        $errors[] = [
                            'row' => $rowNumber,
                            'sku' => $providedSku,
                            'message' => 'SKU already exists. Duplicate SKU insertion is not allowed.',
                        ];
                        continue;
                    }
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
                    'description_ku' => ($rowData['description_ku'] ?? '') !== '' ? (string) $rowData['description_ku'] : null,
                    'price' => (float) $rowData['price'],
                    'dealer_price' => (($rowData['dealer_price'] ?? '') !== '') ? (float) $rowData['dealer_price'] : null,
                    'stock_quantity' => (int) $rowData['stock_quantity'],
                    'sku' => $sku,
                    'brand' => ($rowData['brand'] ?? '') !== '' ? (string) $rowData['brand'] : null,
                ];

                try {
                    Product::create($payload);
                    $imported++;
                    $seenSkusInFile[$skuKey] = true;
                } catch (\Throwable $e) {
                    Log::error('Product import row failed', [
                        'row' => $rowNumber,
                        'sku' => $sku,
                        'error' => $e->getMessage(),
                    ]);

                    $errors[] = [
                        'row' => $rowNumber,
                        'sku' => $sku,
                        'message' => 'Could not save this row due to a database error.',
                    ];
                }
            }

            $message = "Import completed successfully. Total imported rows: {$imported}.";
            if (!empty($errors)) {
                $message .= ' Some rows were skipped. Please review the import errors.';
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

            return back()->with('error', 'Import failed unexpectedly. Please verify the file format and try again.');
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }
    }

    private function importValidationRules(): array
    {
        return [
            'import_file' => ['required', 'file', 'max:5120', 'mimes:csv,txt,xls'],
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
            'brand' => ['nullable', 'string', 'max:100'],
            'description_ku' => ['nullable', 'string'],
            'category_slug' => ['nullable', 'string', 'exists:categories,slug'],
        ];
    }

    private function toBoolean(string|int|bool|null $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));
        return in_array($normalized, ['1', 'true', 'yes', 'active'], true);
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
}
