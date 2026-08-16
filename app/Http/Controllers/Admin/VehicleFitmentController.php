<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVehicleFitment;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Support\SqlSafe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VehicleFitmentController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $brandFilter = (int) $request->query('brand', 0);
        $brands = VehicleBrand::query()
            ->with('models.engineTypes:id,vehicle_model_id,name')
            ->orderBy('name')
            ->get();

        // Initial dropdown payload — only the first 100 most recently created
        // active products. Additional matches are fetched on demand from the
        // searchProducts JSON endpoint as the operator types in the filter.
        $products = Product::query()
            ->select(['id', 'name_en', 'name_ar', 'name_ku', 'sku', 'brand', 'image'])
            ->where('is_active', true)
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        $fitments = ProductVehicleFitment::query()
            ->with([
                'product:id,name_en,name_ar,name_ku,sku,brand,image',
                'brand:id,name',
                'model:id,name,vehicle_brand_id',
            ])
            ->when($brandFilter > 0, fn ($query) => $query->where('vehicle_brand_id', $brandFilter))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    SqlSafe::whereLike($searchQuery, 'engine', $search);
                    SqlSafe::orWhereLike($searchQuery, 'notes', $search);
                    $searchQuery->orWhereHas('product', function ($productQuery) use ($search): void {
                        SqlSafe::whereLike($productQuery, 'name_en', $search);
                        SqlSafe::orWhereLike($productQuery, 'sku', $search);
                        SqlSafe::orWhereLike($productQuery, 'brand', $search);
                    });
                    $searchQuery->orWhereHas('brand', fn ($brandQuery) => SqlSafe::whereLike($brandQuery, 'name', $search));
                    $searchQuery->orWhereHas('model', fn ($modelQuery) => SqlSafe::whereLike($modelQuery, 'name', $search));
                });
            })
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $brandFitmentCounts = ProductVehicleFitment::query()
            ->selectRaw('vehicle_brand_id, COUNT(*) as total')
            ->groupBy('vehicle_brand_id')
            ->pluck('total', 'vehicle_brand_id');

        return view('admin.vehicle-fitments.index', [
            'fitments' => $fitments,
            'brands' => $brands,
            'products' => $products,
            'search' => $search,
            'brandFilter' => $brandFilter,
            'brandFitmentCounts' => $brandFitmentCounts,
            'stats' => [
                'brands' => $brands->count(),
                'models' => $brands->sum(fn ($brand) => $brand->models->count()),
                'engine_types' => $brands->sum(fn ($brand) => $brand->models->sum(fn ($model) => $model->engineTypes->count())),
                'fitments' => ProductVehicleFitment::query()->count(),
                'covered_products' => ProductVehicleFitment::query()->distinct('product_id')->count('product_id'),
                'total_products' => Product::query()->where('is_active', true)->count(),
            ],
        ]);
    }

    public function searchProducts(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));
        $perPage = max(10, min(50, (int) $request->query('per_page', 30)));

        $products = Product::query()
            ->select(['id', 'name_en', 'name_ar', 'name_ku', 'sku', 'brand'])
            ->where('is_active', true)
            ->when($query !== '', function ($builder) use ($query) {
                $builder->where(function ($nested) use ($query) {
                    SqlSafe::whereLike($nested, 'name_en', $query);
                    SqlSafe::orWhereLike($nested, 'name_ar', $query);
                    SqlSafe::orWhereLike($nested, 'name_ku', $query);
                    SqlSafe::orWhereLike($nested, 'sku', $query);
                    SqlSafe::orWhereLike($nested, 'oem_number', $query);
                    SqlSafe::orWhereLike($nested, 'part_number', $query);
                    SqlSafe::orWhereLike($nested, 'brand', $query);
                });
            })
            ->orderBy('name_en')
            ->limit($perPage)
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => (string) $product->name_en,
                'sku' => (string) ($product->sku ?? ''),
                'brand' => (string) ($product->brand ?? ''),
            ]);

        return response()->json([
            'query' => $query,
            'count' => $products->count(),
            'results' => $products,
        ]);
    }

    public function storeBrand(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:vehicle_brands,name'],
        ]);

        VehicleBrand::query()->create([
            'name' => trim((string) $data['name']),
            'slug' => $this->uniqueSlug(VehicleBrand::class, (string) $data['name']),
        ]);

        return back()->with('success', __('Vehicle brand created.'));
    }

    public function storeModel(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'vehicle_brand_id' => ['required', 'exists:vehicle_brands,id'],
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('vehicle_models', 'name')->where(fn ($query) => $query->where('vehicle_brand_id', $request->input('vehicle_brand_id'))),
            ],
            'engine_types' => ['nullable', 'array'],
            'engine_types.*' => ['nullable', 'string', 'max:500'],
            'engine_types_text' => ['nullable', 'string', 'max:2000'],
            'production_start_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'production_end_year' => ['nullable', 'integer', 'min:1900', 'max:2100', 'gte:production_start_year'],
        ]);

        $engineTypes = $this->validatedEngineTypes($data);

        DB::transaction(function () use ($data, $engineTypes): void {
            $model = VehicleModel::query()->create([
                'vehicle_brand_id' => (int) $data['vehicle_brand_id'],
                'name' => trim((string) $data['name']),
                'slug' => $this->uniqueModelSlug((int) $data['vehicle_brand_id'], (string) $data['name']),
                'production_start_year' => $data['production_start_year'] ?? null,
                'production_end_year' => $data['production_end_year'] ?? null,
            ]);

            $model->engineTypes()->createMany(
                array_map(fn (string $name) => ['name' => $name], $engineTypes)
            );
        });

        return back()->with('success', $engineTypes === []
            ? __('Vehicle model created.')
            : __('Vehicle model and engine types created.'));
    }

    public function updateBrand(Request $request, VehicleBrand $brand): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('vehicle_brands', 'name')->ignore($brand->id)],
        ]);

        $name = trim((string) $data['name']);
        $brand->update([
            'name' => $name,
            'slug' => $this->uniqueSlug(VehicleBrand::class, $name, $brand->id),
        ]);

        return back()->with('success', __('Vehicle brand updated.'));
    }

    public function updateModel(Request $request, VehicleModel $model): RedirectResponse
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('vehicle_models', 'name')
                    ->where(fn ($query) => $query->where('vehicle_brand_id', $model->vehicle_brand_id))
                    ->ignore($model->id),
            ],
            'engine_types' => ['nullable', 'array'],
            'engine_types.*' => ['nullable', 'string', 'max:500'],
            'engine_types_text' => ['nullable', 'string', 'max:2000'],
            'production_start_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'production_end_year' => ['nullable', 'integer', 'min:1900', 'max:2100', 'gte:production_start_year'],
        ]);

        $name = trim((string) $data['name']);
        $shouldSyncEngineTypes = $request->exists('engine_types') || $request->exists('engine_types_text');
        $engineTypes = $shouldSyncEngineTypes ? $this->validatedEngineTypes($data) : [];

        DB::transaction(function () use ($data, $model, $name, $shouldSyncEngineTypes, $engineTypes): void {
            $model->update([
                'name' => $name,
                'slug' => $this->uniqueModelSlug((int) $model->vehicle_brand_id, $name, $model->id),
                'production_start_year' => $data['production_start_year'] ?? null,
                'production_end_year' => $data['production_end_year'] ?? null,
            ]);

            if ($shouldSyncEngineTypes) {
                $model->engineTypes()->delete();
                $model->engineTypes()->createMany(
                    array_map(fn (string $engineName) => ['name' => $engineName], $engineTypes)
                );
            }
        });

        return back()->with('success', __('Vehicle model updated.'));
    }

    public function storeFitment(Request $request): RedirectResponse
    {
        $fitmentRows = $request->input('fitments');

        // Keep the endpoint compatible with older clients that still submit
        // one flat fitment instead of the batch-shaped fitments array.
        if (! is_array($fitmentRows) || $fitmentRows === []) {
            $fitmentRows = [[
                'vehicle_brand_id' => $request->input('vehicle_brand_id'),
                'vehicle_model_id' => $request->input('vehicle_model_id'),
                'year_from' => $request->input('year_from'),
                'year_to' => $request->input('year_to'),
                'engine' => $request->input('engine'),
                'notes' => $request->input('notes'),
            ]];
        }

        $payload = [
            'product_id' => $request->input('product_id'),
            'fitments' => array_values($fitmentRows),
        ];

        $validator = Validator::make($payload, [
            'product_id' => [
                'required',
                Rule::exists('products', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'fitments' => ['required', 'array', 'min:1', 'max:50'],
            'fitments.*.vehicle_brand_id' => ['required', 'integer', 'exists:vehicle_brands,id'],
            'fitments.*.vehicle_model_id' => ['nullable', 'integer', 'exists:vehicle_models,id'],
            'fitments.*.year_from' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'fitments.*.year_to' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'fitments.*.engine' => ['nullable', 'string', 'max:120'],
            'fitments.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $validator->after(function ($validator) use ($payload): void {
            foreach ($payload['fitments'] as $index => $row) {
                $brandId = (int) ($row['vehicle_brand_id'] ?? 0);
                $modelId = (int) ($row['vehicle_model_id'] ?? 0);

                if ($brandId > 0 && $modelId > 0 && ! VehicleModel::query()
                    ->whereKey($modelId)
                    ->where('vehicle_brand_id', $brandId)
                    ->exists()) {
                    $validator->errors()->add(
                        "fitments.{$index}.vehicle_model_id",
                        __('The selected model does not belong to this vehicle brand.'),
                    );
                }

                $yearFrom = isset($row['year_from']) && $row['year_from'] !== '' ? (int) $row['year_from'] : null;
                $yearTo = isset($row['year_to']) && $row['year_to'] !== '' ? (int) $row['year_to'] : null;
                if ($yearFrom !== null && $yearTo !== null && $yearTo < $yearFrom) {
                    $validator->errors()->add(
                        "fitments.{$index}.year_to",
                        __('The ending year must be greater than or equal to the starting year.'),
                    );
                }
            }
        });

        $data = $validator->validate();

        DB::transaction(function () use ($data): void {
            foreach ($data['fitments'] as $row) {
                ProductVehicleFitment::query()->create([
                    'product_id' => (int) $data['product_id'],
                    'vehicle_brand_id' => (int) $row['vehicle_brand_id'],
                    'vehicle_model_id' => isset($row['vehicle_model_id']) ? (int) $row['vehicle_model_id'] : null,
                    'year_from' => $row['year_from'] ?? null,
                    'year_to' => $row['year_to'] ?? null,
                    'engine' => trim((string) ($row['engine'] ?? '')) ?: null,
                    'notes' => trim((string) ($row['notes'] ?? '')) ?: null,
                ]);
            }
        });

        $count = count($data['fitments']);

        return back()->with('success', $count === 1
            ? __('Product fitment created.')
            : __(':count product fitments created.', ['count' => $count]));
    }

    public function destroyFitment(ProductVehicleFitment $fitment): RedirectResponse
    {
        $fitment->delete();

        return back()->with('success', __('Product fitment removed.'));
    }

    public function destroyBrand(VehicleBrand $brand): RedirectResponse
    {
        $brand->delete();

        return back()->with('success', __('Vehicle brand and its models removed.'));
    }

    public function destroyModel(VehicleModel $model): RedirectResponse
    {
        $model->delete();

        return back()->with('success', __('Vehicle model removed.'));
    }

    private function uniqueSlug(string $modelClass, string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'vehicle';
        $slug = $base;
        $suffix = 2;

        while ($modelClass::query()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function uniqueModelSlug(int $brandId, string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'model';
        $slug = $base;
        $suffix = 2;

        while (VehicleModel::query()
            ->where('vehicle_brand_id', $brandId)
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    /**
     * Accept tag inputs as well as comma, semicolon, or newline-separated text.
     *
     * @param  array<string, mixed>  $data
     * @return array<int, string>
     */
    private function validatedEngineTypes(array $data): array
    {
        $rawValues = array_merge(
            is_array($data['engine_types'] ?? null) ? $data['engine_types'] : [],
            isset($data['engine_types_text']) ? [(string) $data['engine_types_text']] : [],
        );

        $engineTypes = collect($rawValues)
            ->flatMap(fn ($value) => preg_split('/[,;\r\n]+/u', (string) $value) ?: [])
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique(fn (string $value) => mb_strtolower($value))
            ->values()
            ->all();

        Validator::make(
            ['engine_types' => $engineTypes],
            [
                'engine_types' => ['array', 'max:20'],
                'engine_types.*' => ['string', 'max:80'],
            ],
            [],
            ['engine_types' => __('Engine Types')],
        )->validate();

        return $engineTypes;
    }
}
