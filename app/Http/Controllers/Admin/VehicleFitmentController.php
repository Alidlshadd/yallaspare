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
use App\Support\VehicleFuelType;
use Illuminate\Database\Eloquent\Collection;
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
                'models.engineTypes:id,vehicle_model_id,name,fuel_type,engine_size,aspiration',
                'models.family:id,name,name_en,name_ar,name_ku',
                'modelFamilies.variants' => fn ($query) => $query
                    ->with(['engineTypes:id,vehicle_model_id,name,fuel_type,engine_size,aspiration'])
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

    /**
     * The variant form used to sit in a 350px column beside the hierarchy, which
     * left no room for per-engine fields. It gets its own page instead.
     */
    public function createModel(): View
    {
        return view('admin.vehicle-fitments.models.create', [
            'brands' => $this->brandsWithFamilies(),
            'fuelTypes' => $this->fuelTypeOptions(),
        ]);
    }

    public function editModel(VehicleModel $model): View
    {
        $model->load(['engineTypes', 'family:id,name,name_en,name_ar,name_ku', 'brand:id,name']);

        return view('admin.vehicle-fitments.models.edit', [
            'model' => $model,
            'families' => VehicleModelFamily::query()
                ->where('vehicle_brand_id', $model->vehicle_brand_id)
                ->orderBy('name')
                ->get(),
            'fuelTypes' => $this->fuelTypeOptions(),
            'fitmentCount' => $model->fitments()->count(),
        ]);
    }

    /** @return Collection<int, VehicleBrand> */
    private function brandsWithFamilies(): Collection
    {
        return VehicleBrand::query()
            ->with(['modelFamilies' => fn ($query) => $query->orderBy('name')])
            ->orderBy('name')
            ->get();
    }

    /**
     * Value stays canonical; only the label follows the operator's language.
     *
     * @return array<int, array{value: string, label: string, has_displacement: bool}>
     */
    private function fuelTypeOptions(): array
    {
        return array_map(fn (string $fuelType) => [
            'value' => $fuelType,
            'label' => VehicleFuelType::label($fuelType),
            'has_displacement' => VehicleFuelType::hasDisplacement($fuelType),
        ], VehicleFuelType::all());
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
            // Typing a family that already exists is not a mistake: the variant
            // joins that family rather than creating a second one with the same
            // name. Refusing it only made the administrator go and find the
            // dropdown entry themselves.
            'new_family_name_en' => ['nullable', 'string', 'max:120'],
            'new_family_name_ar' => ['nullable', 'string', 'max:120'],
            'new_family_name_ku' => ['nullable', 'string', 'max:120'],
            // Deliberately not unique. One name covers several cars: a Tivoli
            // built 2015-2019 and a Tivoli built 2020-2023 are different
            // variants with different engines and different parts, and the
            // shop has to be able to hold both. The slug is what has to stay
            // unique, and uniqueModelSlug() sees to that without asking the
            // administrator to invent a different name.
            'name_en' => ['required', 'string', 'max:120'],
            'name_ar' => ['nullable', 'string', 'max:120'],
            'name_ku' => ['nullable', 'string', 'max:120'],
            'engine_types' => ['nullable', 'array'],
            'engine_types.*' => ['nullable', 'string', 'max:80'],
            'engine_types_text' => ['nullable', 'string', 'max:2000'],
            'engines' => ['nullable', 'array', 'max:20'],
            'engines.*.fuel_type' => ['nullable', Rule::in(VehicleFuelType::all())],
            'engines.*.engine_size' => ['nullable', 'numeric', 'min:0.1', 'max:99.9'],
            'engines.*.aspiration' => ['nullable', 'string', 'max:16'],
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
        $attached = false;

        try {
            DB::transaction(function () use ($data, $engineTypes, $imagePath, &$attached): void {
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
                    // firstOrCreate may have handed back a family that already
                    // existed, translations and all. Only what was actually
                    // typed is written, so adding a variant to a family cannot
                    // wipe the Arabic and Kurdish names it already carried.
                    $family->fill(array_filter([
                        'name_en' => $familyName,
                        'name_ar' => $this->nullableText($data['new_family_name_ar'] ?? null),
                        'name_ku' => $this->nullableText($data['new_family_name_ku'] ?? null),
                    ], static fn ($value): bool => $value !== null && $value !== ''))->save();
                }

                $name = trim((string) $data['name_en']);
                $startYear = $data['production_start_year'] ?? null;
                $endYear = $data['production_end_year'] ?? null;

                // The same car, entered again. An operator adding a second
                // engine has no other way in — there is one form, and it makes
                // a variant — so this used to leave two Tivoli 2015-2019 rows
                // that the storefront then offered as two identical choices.
                // The engine belongs to the car that is already recorded.
                $existing = VehicleModel::findByIdentity(
                    $brandId,
                    (int) $family->id,
                    $name,
                    $startYear ? (int) $startYear : null,
                    $endYear ? (int) $endYear : null,
                );

                if ($existing !== null) {
                    $this->attachEngineTypes($existing, $engineTypes);
                    $attached = true;

                    return;
                }

                $model = VehicleModel::query()->create([
                    'vehicle_brand_id' => $brandId,
                    'vehicle_model_family_id' => $family->id,
                    'name' => $name,
                    'name_en' => $name,
                    'name_ar' => $this->nullableText($data['name_ar'] ?? null),
                    'name_ku' => $this->nullableText($data['name_ku'] ?? null),
                    'slug' => $this->uniqueModelSlug($brandId, $name),
                    'production_start_year' => $startYear,
                    'production_end_year' => $endYear,
                    'image_path' => $imagePath,
                ]);

                $model->engineTypes()->createMany($engineTypes);
            });
        } catch (\Throwable $exception) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
            throw $exception;
        }

        if ($attached) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            return redirect()
                ->route('admin.vehicle-fitments.index')
                ->with('success', __('This vehicle was already on record, so the engines were added to it.'));
        }

        return redirect()
            ->route('admin.vehicle-fitments.index')
            ->with('success', $engineTypes === []
                ? __('Vehicle model created.')
                : __('Vehicle model and engine types created.'));
    }

    /**
     * Add engines a variant does not already have.
     *
     * Compared on what an engine is — fuel, displacement, aspiration — rather
     * than on the text it is displayed by, so re-entering the same engine after
     * a labelling change does not leave the car with two of it.
     *
     * @param  array<int, array{name: string, fuel_type: string|null, engine_size: float|null, aspiration: string|null}>  $engineTypes
     */
    private function attachEngineTypes(VehicleModel $model, array $engineTypes): void
    {
        $model->loadMissing('engineTypes');

        $known = $model->engineTypes
            ->map(fn ($engine) => $this->engineSignature($engine->fuel_type, $engine->engine_size, $engine->aspiration))
            ->all();

        foreach ($engineTypes as $engine) {
            $signature = $this->engineSignature($engine['fuel_type'], $engine['engine_size'], $engine['aspiration']);

            if (in_array($signature, $known, true)) {
                continue;
            }

            $model->engineTypes()->firstOrCreate(['name' => $engine['name']], $engine);
            $known[] = $signature;
        }
    }

    private function engineSignature(?string $fuelType, int|float|string|null $engineSize, ?string $aspiration): string
    {
        return implode('|', [
            (string) $fuelType,
            $engineSize === null || $engineSize === '' ? '' : number_format((float) $engineSize, 1, '.', ''),
            strtolower(trim((string) $aspiration)),
        ]);
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
            // Not unique — see the note on the create rule.
            'name_en' => ['required', 'string', 'max:120'],
            'name_ar' => ['nullable', 'string', 'max:120'],
            'name_ku' => ['nullable', 'string', 'max:120'],
            'engine_types' => ['nullable', 'array'],
            'engine_types.*' => ['nullable', 'string', 'max:80'],
            'engine_types_text' => ['nullable', 'string', 'max:2000'],
            'engines' => ['nullable', 'array', 'max:20'],
            'engines.*.fuel_type' => ['nullable', Rule::in(VehicleFuelType::all())],
            'engines.*.engine_size' => ['nullable', 'numeric', 'min:0.1', 'max:99.9'],
            'engines.*.aspiration' => ['nullable', 'string', 'max:16'],
            'production_start_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'production_end_year' => ['nullable', 'integer', 'min:1900', 'max:2100', 'gte:production_start_year'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4000,max_height=4000'],
            'remove_image' => ['sometimes', 'boolean'],
        ]);

        $name = trim((string) $data['name_en']);

        // Nullable, and it has to stay that way: a variant may have no family,
        // and casting that to 0 writes a foreign key pointing at nothing.
        $familyId = $data['vehicle_model_family_id'] ?? $model->vehicle_model_family_id;
        $familyId = $familyId === null || $familyId === '' ? null : (int) $familyId;
        $startYear = isset($data['production_start_year']) ? (int) $data['production_start_year'] : null;
        $endYear = isset($data['production_end_year']) ? (int) $data['production_end_year'] : null;

        // Only an edit that changes which car this is can create a collision.
        // Leaving the name, the family and the years alone — to add an engine,
        // to fix a translation — cannot, so it is never checked: a copy already
        // sitting in the table is not this operator's doing, and blocking every
        // unrelated edit until somebody merges it would strand the record.
        $identityChanged = $familyId !== ($model->vehicle_model_family_id === null ? null : (int) $model->vehicle_model_family_id)
            || VehicleModel::normalizeName($name) !== VehicleModel::normalizeName((string) ($model->name_en ?: $model->name))
            || $startYear !== ($model->production_start_year === null ? null : (int) $model->production_start_year)
            || $endYear !== ($model->production_end_year === null ? null : (int) $model->production_end_year);

        // Editing must not walk a variant onto another one's identity. Merging
        // silently here would move a car's parts under a different row without
        // anyone asking for it; the merge command exists for that, deliberately.
        $clash = $identityChanged
            ? VehicleModel::findByIdentity($model->vehicle_brand_id, $familyId, $name, $startYear, $endYear, $model->id)
            : null;

        if ($clash !== null) {
            // Naming it matters. Without the id there is no way to tell a
            // record colliding with itself from a leftover copy still in the
            // table, and the operator is left with an edit they cannot save
            // and nothing to act on.
            return back()
                ->withInput()
                ->withErrors(['name_en' => __('Vehicle :name (:years) is already recorded as #:id. Merge the two records instead of keeping both.', [
                    'name' => $clash->name_en ?: $clash->name,
                    'years' => $clash->production_start_year.'–'.$clash->production_end_year,
                    'id' => $clash->id,
                ])]);
        }

        $shouldSyncEngineTypes = $request->exists('engine_types') || $request->exists('engine_types_text') || $request->exists('engines');
        $engineTypes = $shouldSyncEngineTypes ? $this->validatedEngineTypes($data) : [];

        $oldImagePath = $model->image_path;
        $newImagePath = $request->hasFile('image')
            ? SecureImageStorage::store($request->file('image'), 'vehicle-variants')
            : null;
        $imagePath = $newImagePath ?: ($request->boolean('remove_image') ? null : $oldImagePath);

        try {
            DB::transaction(function () use ($data, $model, $name, $familyId, $startYear, $endYear, $shouldSyncEngineTypes, $engineTypes, $imagePath): void {
                $model->update([
                    'vehicle_model_family_id' => $familyId,
                    'name' => $name,
                    'name_en' => $name,
                    'name_ar' => $this->nullableText($data['name_ar'] ?? null),
                    'name_ku' => $this->nullableText($data['name_ku'] ?? null),
                    'production_start_year' => $startYear,
                    'production_end_year' => $endYear,
                    'image_path' => $imagePath,
                ]);

                if ($shouldSyncEngineTypes) {
                    $model->engineTypes()->delete();
                    $model->engineTypes()->createMany($engineTypes);
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

        return redirect()
            ->route('admin.vehicle-fitments.index')
            ->with('success', __('Vehicle model updated.'));
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
        ], [
            // Laravel names the field by its array path, so the default read
            // "The fitments.0.vehicle_model_id field is required." — an index
            // and a column name tell an operator nothing about what to fix.
            'product_id.required' => __('Please choose a product.'),
            'product_id.exists' => __('That product is no longer available.'),
            'fitments.*.vehicle_brand_id.required' => __('Please select a vehicle brand.'),
            'fitments.*.vehicle_brand_id.exists' => __('That vehicle brand no longer exists.'),
            'fitments.*.vehicle_model_family_id.exists' => __('That model family no longer exists.'),
            'fitments.*.vehicle_model_id.required' => __('Please select a vehicle variant.'),
            'fitments.*.vehicle_model_id.exists' => __('That vehicle variant no longer exists.'),
            'fitments.*.year_from.integer' => __('The starting year must be a four-digit year.'),
            'fitments.*.year_to.integer' => __('The ending year must be a four-digit year.'),
            'fitments.*.engine.max' => __('That engine name is too long.'),
            'fitments.*.notes.max' => __('Fitment notes may not be longer than 255 characters.'),
        ]);

        $validator->after(function ($validator) use ($payload): void {
            foreach ($payload['fitments'] as $index => $row) {
                $brandId = (int) ($row['vehicle_brand_id'] ?? 0);
                $modelId = (int) ($row['vehicle_model_id'] ?? 0);
                $familyId = (int) ($row['vehicle_model_family_id'] ?? 0);

                $model = VehicleModel::query()
                    ->whereKey($modelId)
                    ->where('vehicle_brand_id', $brandId)
                    ->with('engineTypes:id,vehicle_model_id,name,fuel_type,engine_size,aspiration')
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
                if ($engine !== '' && $model && ! $model->engineTypes->contains('name', $engine)) {
                    // Name the engines this car does have. The operator picked
                    // one that belongs to the other variant of the same name,
                    // which is exactly the mistake this screen has to prevent.
                    $available = $model->engineTypes
                        ->map(fn ($engineType): string => $engineType->localizedName())
                        ->filter()
                        ->implode(', ');

                    $validator->errors()->add(
                        "fitments.{$index}.engine",
                        $available === ''
                            ? __('Please select an engine compatible with the selected variant. This variant has no engines recorded yet.')
                            : __('Please select an engine compatible with the selected variant. Available: :engines', ['engines' => $available]),
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
            // Not unique, for the same reason as the variant names below it.
            'name_en' => ['required', 'string', 'max:120'],
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
     * Build the engine rows to store, from either the structured repeater or the
     * older free-text field (tags, or comma/semicolon/newline separated text).
     *
     * Both paths end up as the same structured row so the storefront can filter
     * and translate an engine no matter which form created it.
     *
     * @param  array<string, mixed>  $data
     * @return array<int, array{name: string, fuel_type: string|null, engine_size: float|null, aspiration: string|null}>
     */
    private function validatedEngineTypes(array $data): array
    {
        $structured = collect(is_array($data['engines'] ?? null) ? $data['engines'] : [])
            ->map(function ($row): ?array {
                if (! is_array($row)) {
                    return null;
                }

                $fuelType = is_string($row['fuel_type'] ?? null) ? $row['fuel_type'] : null;
                if (! VehicleFuelType::isValid($fuelType)) {
                    return null;
                }

                // An electric drivetrain has no displacement, so never store one.
                $hasDisplacement = VehicleFuelType::hasDisplacement($fuelType);
                $engineSize = $hasDisplacement && ($row['engine_size'] ?? '') !== '' ? (float) $row['engine_size'] : null;
                $aspiration = $hasDisplacement && ($row['aspiration'] ?? '') !== '' ? (string) $row['aspiration'] : null;

                return [
                    // Stored in English so the display text stays stable; the
                    // localized string is built at render time from the parts.
                    'name' => VehicleFuelType::displayName($fuelType, $engineSize, $aspiration, 'en'),
                    'fuel_type' => $fuelType,
                    'engine_size' => $engineSize,
                    'aspiration' => $aspiration,
                ];
            })
            ->filter();

        $rawValues = array_merge(
            is_array($data['engine_types'] ?? null) ? $data['engine_types'] : [],
            isset($data['engine_types_text']) ? [(string) $data['engine_types_text']] : [],
        );

        $freeText = collect($rawValues)
            ->flatMap(fn ($value) => preg_split('/[,;\r\n]+/u', (string) $value) ?: [])
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->map(fn (string $name) => ['name' => $name] + VehicleFuelType::parse($name));

        $engineTypes = $structured
            ->concat($freeText)
            ->unique(fn (array $engine) => mb_strtolower($engine['name']))
            ->values()
            ->all();

        Validator::make(
            ['engine_types' => $engineTypes],
            [
                'engine_types' => ['array', 'max:20'],
                'engine_types.*.name' => ['string', 'max:80'],
                'engine_types.*.fuel_type' => ['nullable', Rule::in(VehicleFuelType::all())],
                'engine_types.*.engine_size' => ['nullable', 'numeric', 'min:0.1', 'max:99.9'],
            ],
            [],
            ['engine_types' => __('Engine Types')],
        )->validate();

        return $engineTypes;
    }
}
