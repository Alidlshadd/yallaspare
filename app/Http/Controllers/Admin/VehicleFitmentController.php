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
            ->with('models:id,vehicle_brand_id,name')
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
        ]);

        VehicleModel::query()->create([
            'vehicle_brand_id' => (int) $data['vehicle_brand_id'],
            'name' => trim((string) $data['name']),
            'slug' => $this->uniqueModelSlug((int) $data['vehicle_brand_id'], (string) $data['name']),
        ]);

        return back()->with('success', __('Vehicle model created.'));
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
        ]);

        $name = trim((string) $data['name']);
        $model->update([
            'name' => $name,
            'slug' => $this->uniqueModelSlug((int) $model->vehicle_brand_id, $name, $model->id),
        ]);

        return back()->with('success', __('Vehicle model updated.'));
    }

    public function storeFitment(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => [
                'required',
                Rule::exists('products', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'vehicle_brand_id' => ['required', 'exists:vehicle_brands,id'],
            'vehicle_model_id' => [
                'nullable',
                Rule::exists('vehicle_models', 'id')->where(fn ($query) => $query->where('vehicle_brand_id', $request->input('vehicle_brand_id'))),
            ],
            'year_from' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'year_to' => ['nullable', 'integer', 'min:1900', 'max:2100', 'gte:year_from'],
            'engine' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        ProductVehicleFitment::query()->create([
            'product_id' => (int) $data['product_id'],
            'vehicle_brand_id' => (int) $data['vehicle_brand_id'],
            'vehicle_model_id' => isset($data['vehicle_model_id']) ? (int) $data['vehicle_model_id'] : null,
            'year_from' => $data['year_from'] ?? null,
            'year_to' => $data['year_to'] ?? null,
            'engine' => trim((string) ($data['engine'] ?? '')) ?: null,
            'notes' => trim((string) ($data['notes'] ?? '')) ?: null,
        ]);

        return back()->with('success', __('Product fitment created.'));
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
            $slug = $base . '-' . $suffix++;
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
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }
}
