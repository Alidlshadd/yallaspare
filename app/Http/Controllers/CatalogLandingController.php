<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\Wishlist;
use App\Support\CatalogLandingCache;
use App\Support\DbSchema;
use App\Support\Seo\StructuredData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

/**
 * Pages a search engine can actually rank.
 *
 * The shop filters the catalogue by part brand and by car, but a filtered URL
 * is one page wearing many hats: it carries no title of its own and search
 * engines will not index the combinations. Someone searching for "corolla brake
 * pads" had nowhere on this site to land. These routes give each part brand and
 * each car its own address, heading and description, built from catalogue data
 * that was already there.
 */
class CatalogLandingController extends Controller
{
    private const PER_PAGE = 24;

    /**
     * Every part brand the shop stocks.
     */
    public function brands(): View
    {
        $brands = CatalogLandingCache::remember('brands', function (): array {
            return ProductBrand::query()
                ->withCount(['products' => fn (Builder $query) => $query->where('is_active', true)])
                ->orderBy('name')
                ->get()
                ->map(fn (ProductBrand $brand): array => [
                    'name' => (string) $brand->name,
                    'slug' => (string) $brand->slug,
                    'logo_path' => $brand->logo_path,
                    'products_count' => (int) $brand->products_count,
                ])
                ->all();
        });

        return view('catalog.brands', [
            'brands' => $brands,
            'stockedBrandCount' => count(array_filter($brands, fn (array $brand): bool => $brand['products_count'] > 0)),
            'breadcrumbs' => $this->breadcrumbs([__('Part brands') => route('catalog.brands')]),
            'isIndexable' => collect($brands)->contains(fn (array $brand): bool => $brand['products_count'] > 0),
        ]);
    }

    /**
     * One part brand and everything the shop carries from it.
     */
    public function brand(Request $request, string $slug): View
    {
        $brand = ProductBrand::query()->where('slug', $slug)->firstOrFail();

        $products = $this->paginate(
            Product::query()
                ->with('category')
                ->where('is_active', true)
                ->where('product_brand_id', $brand->id)
        );

        return view('catalog.brand', [
            'brand' => $brand,
            'products' => $products,
            'wishlistedProductIds' => $this->wishlistedIds($products),
            'breadcrumbs' => $this->breadcrumbs([
                __('Part brands') => route('catalog.brands'),
                (string) $brand->name => route('catalog.brand', $brand->slug),
            ]),
            'isIndexable' => $products->total() > 0,
            'itemListSchema' => $this->productListSchema((string) $brand->name, $products),
        ]);
    }

    /**
     * Every make the fitment data covers.
     */
    public function vehicles(): View
    {
        $makes = CatalogLandingCache::remember('vehicle-makes', function (): array {
            if (! DbSchema::hasTable('vehicle_brands')) {
                return [];
            }

            return VehicleBrand::query()
                ->withCount([
                    'models',
                    'models as fitted_models_count' => fn (Builder $query) => $query->whereHas('fitments'),
                ])
                ->orderBy('name')
                ->get()
                ->map(fn (VehicleBrand $make): array => [
                    'name' => (string) $make->name,
                    'slug' => (string) $make->slug,
                    'models_count' => (int) $make->models_count,
                    // An aliased withCount lands as a plain attribute rather than a
                    // declared property, so it is read as one.
                    'fitted_models_count' => (int) $make->getAttribute('fitted_models_count'),
                ])
                ->all();
        });

        return view('catalog.vehicles', [
            'makes' => $makes,
            'breadcrumbs' => $this->breadcrumbs([__('Shop by car') => route('catalog.vehicles')]),
            'isIndexable' => collect($makes)->contains(fn (array $make): bool => $make['fitted_models_count'] > 0),
        ]);
    }

    /**
     * One make: the parts that fit any of its cars, and the list of those cars.
     */
    public function vehicleBrand(Request $request, string $makeSlug): View
    {
        $make = VehicleBrand::query()->where('slug', $makeSlug)->firstOrFail();

        $products = $this->paginate(
            $this->productsFittingQuery()
                ->whereHas('vehicleFitments', fn (Builder $query) => $query->where('vehicle_brand_id', $make->id))
        );

        $models = VehicleModel::query()
            ->where('vehicle_brand_id', $make->id)
            ->withCount(['fitments'])
            ->orderBy('name')
            ->get();

        return view('catalog.vehicle-brand', [
            'make' => $make,
            'models' => $models,
            'products' => $products,
            'wishlistedProductIds' => $this->wishlistedIds($products),
            'breadcrumbs' => $this->breadcrumbs([
                __('Shop by car') => route('catalog.vehicles'),
                (string) $make->name => route('catalog.vehicle-brand', $make->slug),
            ]),
            'isIndexable' => $products->total() > 0,
            'itemListSchema' => $this->productListSchema((string) $make->name, $products),
        ]);
    }

    /**
     * One car, which is the page most of this work exists for.
     */
    public function vehicleModel(Request $request, string $makeSlug, string $modelSlug): View
    {
        $make = VehicleBrand::query()->where('slug', $makeSlug)->firstOrFail();

        // Model slugs are only unique within a make, so the make has to be part
        // of the lookup rather than a decoration in the URL.
        $model = VehicleModel::query()
            ->where('vehicle_brand_id', $make->id)
            ->where('slug', $modelSlug)
            ->firstOrFail();

        $products = $this->paginate(
            $this->productsFittingQuery()
                ->whereHas('vehicleFitments', fn (Builder $query) => $query->where('vehicle_model_id', $model->id))
        );

        return view('catalog.vehicle-model', [
            'make' => $make,
            'model' => $model,
            'products' => $products,
            'wishlistedProductIds' => $this->wishlistedIds($products),
            'siblingModels' => VehicleModel::query()
                ->where('vehicle_brand_id', $make->id)
                ->whereKeyNot($model->id)
                ->whereHas('fitments')
                ->orderBy('name')
                ->limit(12)
                ->get(),
            'breadcrumbs' => $this->breadcrumbs([
                __('Shop by car') => route('catalog.vehicles'),
                (string) $make->name => route('catalog.vehicle-brand', $make->slug),
                $model->localizedName() => route('catalog.vehicle-model', [$make->slug, $model->slug]),
            ]),
            'isIndexable' => $products->total() > 0,
            'itemListSchema' => $this->productListSchema(
                trim($make->name.' '.$model->localizedName()),
                $products
            ),
        ]);
    }

    /**
     * The trail from the home page down to this one, rendered both as links and
     * as the structured data a search engine reads. One array so the two can
     * never disagree.
     *
     * @param  array<string, string>  $trail
     * @return array<int, array{label: string, url: string}>
     */
    private function breadcrumbs(array $trail): array
    {
        return StructuredData::trailFromHome($trail);
    }

    /**
     * The products on this page, in the order they are shown, as the list a
     * search engine can read. Only the page in hand: the schema describes what
     * the visitor sees, not the whole catalogue behind the pagination.
     *
     * @param  LengthAwarePaginator<int, Product>  $products
     * @return array<string, mixed>|null
     */
    private function productListSchema(string $name, LengthAwarePaginator $products): ?array
    {
        return StructuredData::productList(
            $name,
            $products->getCollection(),
            ($products->currentPage() - 1) * $products->perPage(),
            $products->total(),
        );
    }

    /**
     * @return Builder<Product>
     */
    private function productsFittingQuery(): Builder
    {
        return Product::query()
            ->with('category')
            ->where('is_active', true);
    }

    /**
     * @param  Builder<Product>  $query
     * @return LengthAwarePaginator<int, Product>
     */
    private function paginate(Builder $query): LengthAwarePaginator
    {
        // In stock first: a landing page that opens on things nobody can buy
        // wastes the visit that brought someone here.
        return $query
            ->orderByRaw('(stock_quantity > 0) desc')
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * @param  LengthAwarePaginator<int, Product>  $products
     * @return array<int, int>
     */
    private function wishlistedIds(LengthAwarePaginator $products): array
    {
        $user = auth()->user();

        if (! $user || $user->isAdminPanelUser() || ! DbSchema::hasTable('wishlists')) {
            return [];
        }

        return Wishlist::query()
            ->where('user_id', $user->id)
            ->whereIn('product_id', $products->pluck('id'))
            ->pluck('product_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }
}
