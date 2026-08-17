<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Support\SecureImageStorage;
use App\Support\SqlSafe;
use App\Support\VehicleFilterCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductBrandController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $assignment = trim((string) $request->query('assignment', ''));
        $query = ProductBrand::query()->withCount('products');

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                SqlSafe::whereLike($builder, 'name', $search);
                SqlSafe::orWhereLike($builder, 'slug', $search);
            });
        }

        if ($assignment === 'assigned') {
            $query->has('products');
        } elseif ($assignment === 'empty') {
            $query->doesntHave('products');
        }

        $brands = $query
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        $totalBrands = ProductBrand::query()->count();
        $assignedBrands = ProductBrand::query()->has('products')->count();
        $totalAssignedProducts = Product::query()->whereNotNull('product_brand_id')->count();

        return view('admin.product-brands.index', compact(
            'brands',
            'search',
            'assignment',
            'totalBrands',
            'assignedBrands',
            'totalAssignedProducts'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('product_brands', 'name')->whereNull('deleted_at')],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        ProductBrand::query()->create([
            'name' => trim($data['name']),
            'slug' => $this->makeUniqueSlug((string) $data['name']),
            'logo_path' => $request->hasFile('logo')
                ? SecureImageStorage::store($request->file('logo'), 'product-brands')
                : null,
        ]);

        return redirect()->route('admin.product-brands.index')
            ->with('success', __('Brand created successfully.'));
    }

    public function update(Request $request, ProductBrand $productBrand): RedirectResponse
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('product_brands', 'name')->ignore($productBrand->id)->whereNull('deleted_at'),
            ],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_logo' => ['sometimes', 'boolean'],
        ]);

        $oldName = $productBrand->name;
        $logoPath = $productBrand->logo_path;

        if ($request->boolean('remove_logo')) {
            if ($logoPath) {
                Storage::disk('public')->delete($logoPath);
            }
            $logoPath = null;
        }

        if ($request->hasFile('logo')) {
            if ($logoPath) {
                Storage::disk('public')->delete($logoPath);
            }
            $logoPath = SecureImageStorage::store($request->file('logo'), 'product-brands');
        }

        $name = trim($data['name']);

        DB::transaction(function () use ($productBrand, $oldName, $name, $logoPath): void {
            $productBrand->update([
                'name' => $name,
                'slug' => $this->makeUniqueSlug($name, $productBrand->id),
                'logo_path' => $logoPath,
            ]);

            if ($oldName !== $name) {
                $productBrand->products()->update(['brand' => $name]);
            }
        });

        if ($oldName !== $name) {
            VehicleFilterCache::flush();
        }

        return redirect()->route('admin.product-brands.index')
            ->with('success', __('Brand updated successfully.'));
    }

    public function destroy(ProductBrand $productBrand): RedirectResponse
    {
        if ($productBrand->products()->exists() || Product::query()->where('brand', $productBrand->name)->exists()) {
            return back()->with('error', __('Cannot delete a brand with assigned products.'));
        }

        if ($productBrand->logo_path) {
            Storage::disk('public')->delete($productBrand->logo_path);
        }

        $productBrand->delete();

        return redirect()->route('admin.product-brands.index')
            ->with('success', __('Brand deleted successfully.'));
    }

    private function makeUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'brand';
        $slug = $base;
        $suffix = 2;

        while (ProductBrand::withTrashed()
            ->when($ignoreId, fn ($query) => $query->where('id', '<>', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
