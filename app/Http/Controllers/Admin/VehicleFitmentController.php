<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVehicleFitment;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\VehicleModelFamily;
use App\Support\SecureImageStorage;
use App\Support\SqlSafe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
            ->with([
                'models.engineTypes:id,vehicle_model_id,name',
                'models.family:id,name,name_en,name_ar,name_ku',
                'modelFamilies.variants' => fn ($query) => $query
                    ->with(['engineTypes:id,vehicle_model_id,name'])
                    ->withCount('fitments')
                    ->orderBy('name'),
            ])
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
                'model:id,name,name_en,name_ar,name_ku,vehicle_brand_id,vehicle_model_family_id',
                'model.family:id,name,name_en,name_ar,name_ku',
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
                    $searchQuery->orWhereHas('model', function ($modelQuery) use ($search): void {
                        SqlSafe::whereLike($modelQuery, 'name', $search);
                        SqlSafe::orWhereLike($modelQuery, 'name_ar', $search);
                        SqlSafe::orWhereLike($modelQuery, 'name_ku', $search);
                    });
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
                'families' => $brands->sum(fn ($brand) => $brand->modelFamilies->count()),
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
        $request->merge([
            'name_en' => trim((string) ($request->input('name_en') ?: $request->input('name'))),
            'new_family_name_en' => trim((string) ($request->input('new_family_name_en') ?: $request->input('new_family_name'))),
        ]);

        $validator = Validator::make($request->all(), [
            'vehicle_brand_id' => ['required', 'exists:vehicle_brands,id'],
            'vehicle_model_family_id' => ['nullable', 'integer', 'exists:vehicle_model_families,id'],
            'new_family_name_en' => [
                'nullable',
                'string',
                'max:120',
                Rule::unique('vehicle_model_families', 'name')->where(fn ($query) => $query->where('vehicle_brand_id', $request->input('vehicle_brand_id'))),
            ],
            'new_family_name_ar' => ['nullable', 'string', 'max:120'],
            'new_family_name_ku' => ['nullable', 'string', 'max:120'],
            'name_en' => [
                'required',
                'string',
                'max:120',
                Rule::unique('vehicle_models', 'name')->where(fn ($query) => $query->where('vehicle_brand_id', $request->input('vehicle_brand_id'))),
            ],
            'name_ar' => ['nullable', 'string', 'max:120'],
            'name_ku' => ['nullable', 'string', 'max:120'],
            'engine_types' => ['nullable', 'array'],
            'engine_types.*' => ['nullable', 'string', 'max:500'],
            'engine_types_text' => ['nullable', 'string', 'max:2000'],
            'production_start_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'production_end_year' => ['nullable', 'integer', 'min:1900', 'max:2100', 'gte:production_start_year'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4000,max_height=4000'],
        ]);

        $validator->after(function ($validator) use ($request): void {
            $familyId = (int) $request->input('vehicle_model_family_id');
            $brandId = (int) $request->input('vehicle_brand_id');
            if ($familyId > 0 && ! VehicleModelFamily::query()->whereKey($familyId)->where('vehicle_brand_id', $brandId)->exists()) {
                $validator->errors()->add('vehicle_model_family_id', __('The selected family does not belong to this vehicle brand.'));
            }
        });

        $data = $validator->validate();

        $engineTypes = $this->validatedEngineTypes($data);
        $imagePath = $request->hasFile('image')
            ? SecureImageStorage::store($request->file('image'), 'vehicle-variants')
            : null;

        try {
            DB::transaction(function () use ($data, $engineTypes, $imagePath): void {
                $brandId = (int) $data['vehicle_brand_id'];
                if (! empty($data['vehicle_model_family_id'])) {
                    $family = VehicleModelFamily::query()->findOrFail((int) $data['vehicle_model_family_id']);
                } else {
                    $familyName = trim((string) ($data['new_family_name_en'] ?: $data['name_en']));
                    $family = VehicleModelFamily::query()->firstOrCreate(
                        [
                            'vehicle_brand_id' => $brandId,
                            'name' => $familyName,
                        ],
                        ['slug' => $this->uniqueFamilySlug($brandId, $familyName)],
                    );
                    $family->fill([
                        'name_en' => $familyName,
                        'name_ar' => $this->nullableText($data['new_family_name_ar'] ?? null),
                        'name_ku' => $this->nullableText($data['new_family_name_ku'] ?? null),
                    ])->save();
                }

                $model = VehicleModel::query()->create([
                    'vehicle_brand_id' => $brandId,
                    'vehicle_model_family_id' => $family->id,
                    'name' => trim((string) $data['name_en']),
                    'name_en' => trim((string) $data['name_en']),
                    'name_ar' => $this->nullableText($data['name_ar'] ?? null),
                    'name_ku' => $this->nullableText($data['name_ku'] ?? null),
                    'slug' => $this->uniqueModelSlug($brandId, (string) $data['name_en']),
                    'production_start_year' => $data['production_start_year'] ?? null,
                    'production_end_year' => $data['production_end_year'] ?? null,
                    'image_path' => $imagePath,
                ]);

                $model->engineTypes()->createMany(
                    array_map(fn (string $name) => ['name' => $name], $engineTypes)
                );
            });
        } catch (\Throwable $exception) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
            throw $exception;
        }

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
        $request->merge([
            'name_en' => trim((string) ($request->input('name_en') ?: $request->input('name'))),
        ]);

        $data = $request->validate([
            'vehicle_model_family_id' => ['nullable', 'integer', Rule::exists('vehicle_model_families', 'id')->where(fn ($query) => $query->where('vehicle_brand_id', $model->vehicle_brand_id))],
            'name_en' => [
                'required',
                'string',
                'max:120',
                Rule::unique('vehicle_models', 'name')
                    ->where(fn ($query) => $query->where('vehicle_brand_id', $model->vehicle_brand_id))
                    ->ignore($model->id),
            ],
            'name_ar' => ['nullable', 'string', 'max:120'],
            'name_ku' => ['nullable', 'string', 'max:120'],
            'engine_types' => ['nullable', 'array'],
            'engine_types.*' => ['nullable', 'string', 'max:500'],
            'engine_types_text' => ['nullable', 'string', 'max:2000'],
            'production_start_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'production_end_year' => ['nullable', 'integer', 'min:1900', 'max:2100', 'gte:production_start_year'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4000,max_height=4000'],
            'remove_image' => ['sometimes', 'boolean'],
        ]);

        $name = trim((string) $data['name_en']);
        $shouldSyncEngineTypes = $request->exists('engine_types') || $request->exists('engine_types_text');
        $engineTypes = $shouldSyncEngineTypes ? $this->validatedEngineTypes($data) : [];

        $oldImagePath = $model->image_path;
        $newImagePath = $request->hasFile('image')
            ? SecureImageStorage::store($request->file('image'), 'vehicle-variants')
            : null;
        $imagePath = $newImagePath ?: ($request->boolean('remove_image') ? null : $oldImagePath);

        try {
            DB::transaction(function () use ($data, $model, $name, $shouldSyncEngineTypes, $engineTypes, $imagePath): void {
                $model->update([
                    'vehicle_model_family_id' => $data['vehicle_model_family_id'] ?? $model->vehicle_model_family_id,
                    'name' => $name,
                    'name_en' => $name,
                    'name_ar' => $this->nullableText($data['name_ar'] ?? null),
                    'name_ku' => $this->nullableText($data['name_ku'] ?? null),
                    'production_start_year' => $data['production_start_year'] ?? null,
                    'production_end_year' => $data['production_end_year'] ?? null,
                    'image_path' => $imagePath,
                ]);

                if ($shouldSyncEngineTypes) {
                    $model->engineTypes()->delete();
                    $model->engineTypes()->createMany(
                        array_map(fn (string $engineName) => ['name' => $engineName], $engineTypes)
                    );
                }
            });
        } catch (\Throwable $exception) {
            if ($newImagePath) {
                Storage::disk('public')->delete($newImagePath);
            }
            throw $exception;
        }

        if ($oldImagePath && $oldImagePath !== $imagePath) {
            $this->deleteVariantImageIfUnused($oldImagePath);
        }

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
                'vehicle_model_family_id' => $request->input('vehicle_model_family_id'),
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
            'fitments.*.vehicle_model_family_id' => ['nullable', 'integer', 'exists:vehicle_model_families,id'],
            'fitments.*.vehicle_model_id' => ['required', 'integer', 'exists:vehicle_models,id'],
            'fitments.*.year_from' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'fitments.*.year_to' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'fitments.*.engine' => ['nullable', 'string', 'max:120'],
            'fitments.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $validator->after(function ($validator) use ($payload): void {
            foreach ($payload['fitments'] as $index => $row) {
                $brandId = (int) ($row['vehicle_brand_id'] ?? 0);
                $modelId = (int) ($row['vehicle_model_id'] ?? 0);
                $familyId = (int) ($row['vehicle_model_family_id'] ?? 0);

                $model = VehicleModel::query()
                    ->whereKey($modelId)
                    ->where('vehicle_brand_id', $brandId)
                    ->with('engineTypes:id,vehicle_model_id,name')
                    ->first();

                if ($model && $familyId > 0 && (int) $model->vehicle_model_family_id !== $familyId) {
                    $model = null;
                }

                if ($brandId > 0 && $modelId > 0 && ! $model) {
                    $validator->errors()->add(
                        "fitments.{$index}.vehicle_model_id",
                        __('The selected variant does not belong to this brand and model family.') ?: 'The selected variant does not belong to this brand and model family.',
                    );
                }

                $engine = trim((string) ($row['engine'] ?? ''));
                if ($engine !== '' && preg_match('/\bdiesel\b/i', $engine)) {
                    $validator->errors()->add("fitments.{$index}.engine", __('Diesel engines are not supported.') ?: 'Diesel engines are not supported.');
                } elseif ($engine !== '' && $model && ! $model->engineTypes->contains('name', $engine)) {
                    $validator->errors()->add("fitments.{$index}.engine", __('The selected engine is not configured for this variant.') ?: 'The selected engine is not configured for this variant.');
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
        if ($brand->models()->exists()) {
            return back()->with('error', __('Cannot delete a brand that still contains variants.'));
        }

        $brand->delete();

        return back()->with('success', __('Vehicle brand and its models removed.'));
    }

    public function destroyModel(VehicleModel $model): RedirectResponse
    {
        if ($model->fitments()->exists()) {
            return back()->with('error', __('Cannot delete a variant used by product fitments.'));
        }

        $imagePath = $model->image_path;
        $model->delete();
        if ($imagePath) {
            $this->deleteVariantImageIfUnused($imagePath);
        }

        return back()->with('success', __('Vehicle variant removed.'));
    }

    public function updateFamily(Request $request, VehicleModelFamily $family): RedirectResponse
    {
        $request->merge([
            'name_en' => trim((string) ($request->input('name_en') ?: $request->input('name'))),
        ]);

        $data = $request->validate([
            'name_en' => [
                'required',
                'string',
                'max:120',
                Rule::unique('vehicle_model_families', 'name')
                    ->where(fn ($query) => $query->where('vehicle_brand_id', $family->vehicle_brand_id))
                    ->ignore($family->id),
            ],
            'name_ar' => ['nullable', 'string', 'max:120'],
            'name_ku' => ['nullable', 'string', 'max:120'],
        ]);
        $name = trim((string) $data['name_en']);
        $family->update([
            'name' => $name,
            'name_en' => $name,
            'name_ar' => $this->nullableText($data['name_ar'] ?? null),
            'name_ku' => $this->nullableText($data['name_ku'] ?? null),
        ]);

        return back()->with('success', __('Model family updated.'));
    }

    private function nullableText(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    public function destroyFamily(VehicleModelFamily $family): RedirectResponse
    {
        if ($family->variants()->exists()) {
            return back()->with('error', __('Move or delete every variant before deleting this family.'));
        }

        $family->delete();

        return back()->with('success', __('Model family removed.'));
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

    private function uniqueFamilySlug(int $brandId, string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'family';
        $slug = $base;
        $suffix = 2;

        while (VehicleModelFamily::query()
            ->where('vehicle_brand_id', $brandId)
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function deleteVariantImageIfUnused(string $path): void
    {
        if (! VehicleModel::query()->where('image_path', $path)->exists()) {
            Storage::disk('public')->delete($path);
        }
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

        if (collect($engineTypes)->contains(fn (string $engine) => preg_match('/\bdiesel\b/i', $engine) === 1)) {
            Validator::make(
                ['engine_types' => 'diesel'],
                ['engine_types' => [fn ($attribute, $value, $fail) => $fail(__('Diesel engines are not supported.') ?: 'Diesel engines are not supported.')]],
            )->validate();
        }

        return $engineTypes;
    }
}
