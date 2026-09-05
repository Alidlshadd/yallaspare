<?php

namespace App\Http\Controllers;

use App\Models\BackInStockSubscription;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\RecentlyViewedProduct;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wishlist;
use App\Services\Analytics\ClientAnalytics;
use App\Services\Analytics\MeasurementPayload;
use App\Services\Analytics\ProductViewTracker;
use App\Support\DbSchema;
use App\Support\SqlSafe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function show(Request $request, Product $product, ProductViewTracker $productViews): View|RedirectResponse
    {
        abort_unless($product->is_active, 404);

        $rawIdentifier = (string) last($request->segments());
        if ($rawIdentifier !== (string) $product->slug) {
            return redirect()->route('shop.show', $product, 301);
        }

        $productRelations = ['category', 'images', 'productBrand:id,name,slug'];
        if (DbSchema::hasTable('product_reviews')) {
            $productRelations['reviews'] = fn ($query) => $query
                ->where('is_approved', true)
                ->with('user:id,name')
                ->latest('reviewed_at')
                ->latest('id')
                ->take(8);
        }
        if (DbSchema::hasTable('product_vehicle_fitments')) {
            $productRelations['vehicleFitments'] = fn ($query) => $query
                ->with([
                    'brand:id,name,slug',
                    // The build years belong to the variant, and the fitment's
                    // own year columns only narrow them. Leaving them out of
                    // this list is what made the page say "Any year" over a
                    // variant whose years were recorded all along.
                    'model:id,name,slug,name_en,name_ar,name_ku,vehicle_brand_id,vehicle_model_family_id,image_path,production_start_year,production_end_year',
                    'model.family:id,name,name_en,name_ar,name_ku',
                    // What the engine actually is, rather than the text it was
                    // chosen by. One query for the page, not one per row.
                    'model.engineTypes:id,vehicle_model_id,name,fuel_type,engine_size,aspiration',
                    // The vehicle landing links on the page are built from the
                    // model's own make, so it has to come along rather than be
                    // fetched once per fitment row.
                    'model.brand:id,name,slug',
                ])
                ->orderBy('vehicle_brand_id')
                ->orderBy('vehicle_model_id')
                ->orderBy('year_from');
        }

        $product->load($productRelations);
        $productViews->record($request, $product);

        // Reported on every view, unlike the tracker above, which dedupes to
        // keep our own tables honest. An ad platform counts occurrences.
        app(ClientAnalytics::class)->record(
            'view_item',
            MeasurementPayload::forProduct($product, 1, $request->user())
        );

        $currencyLabel = (string) Setting::getValue('currency_code', 'IQD');

        $relatedProducts = Product::query()
            ->with(['category', 'vehicleFitments.model'])
            ->where('is_active', 1)
            ->whereKeyNot($product->id)
            ->when($product->category_id, fn ($query) => $query->where('category_id', $product->category_id))
            ->latest()
            ->take(4)
            ->get();

        $isWishlisted = false;
        $isBackInStockSubscribed = false;
        $recentlyViewedProducts = collect();
        if (auth()->check() && DbSchema::hasTable('wishlists')) {
            $isWishlisted = Wishlist::query()
                ->where('user_id', auth()->id())
                ->where('product_id', $product->id)
                ->exists();
        }

        if ($request->user() && DbSchema::hasTable('recently_viewed_products')) {
            $this->recordRecentlyViewedProduct($request->user(), $product);
            $recentlyViewedProducts = RecentlyViewedProduct::query()
                ->where('user_id', $request->user()->id)
                ->where('product_id', '!=', $product->id)
                ->whereHas('product', fn ($query) => $query->where('is_active', true))
                ->with('product.category')
                ->latest('viewed_at')
                ->limit(4)
                ->get()
                ->pluck('product')
                ->filter()
                ->values();
        }

        if ($request->user() && DbSchema::hasTable('back_in_stock_subscriptions')) {
            $isBackInStockSubscribed = BackInStockSubscription::query()
                ->where('user_id', $request->user()->id)
                ->where('product_id', $product->id)
                ->exists();
        }

        $reviews = $product->relationLoaded('reviews') ? $product->reviews : collect();
        $reviewCount = DbSchema::hasTable('product_reviews')
            ? ProductReview::query()->where('product_id', $product->id)->where('is_approved', true)->count()
            : 0;
        $averageRating = DbSchema::hasTable('product_reviews')
            ? (float) ProductReview::query()->where('product_id', $product->id)->where('is_approved', true)->avg('rating')
            : 0.0;

        return view('shop.show', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
            'currencySymbol' => $currencyLabel,
            'isWishlisted' => $isWishlisted,
            'isBackInStockSubscribed' => $isBackInStockSubscribed,
            'recentlyViewedProducts' => $recentlyViewedProducts,
            'reviews' => $reviews,
            'reviewCount' => $reviewCount,
            'averageRating' => $averageRating,
        ]);
    }

    public function autocomplete(Request $request): JsonResponse
    {
        $term = SqlSafe::searchTerm($request->query('q', $request->query('search', '')), 80);
        $limit = min(max((int) $request->query('limit', 6), 1), 10);
        $currencyLabel = (string) Setting::getValue('currency_code', 'IQD');

        if (mb_strlen($term) < 2) {
            return response()->json(['data' => [
                'query' => $term,
                'products' => [],
                'categories' => [],
                'brands' => [],
            ]]);
        }

        $products = Product::query()
            ->with(['category', 'images'])
            ->where('is_active', true)
            // The same definition the shop listing uses, so a suggestion list
            // can never disagree with the results page it leads to.
            ->matchingSearchTerm($term)
            ->orderByRaw('CASE WHEN stock_quantity > 0 THEN 0 ELSE 1 END')
            // The same ladder the results page uses, so a suggestion and the
            // page it leads to cannot disagree about what the best answer is.
            ->orderBySearchRelevance($term)
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(function (Product $product) use ($request, $currencyLabel): array {
                $price = $product->priceFor($request->user());

                return [
                    'id' => $product->id,
                    'label' => $product->localizedName(),
                    'sku' => (string) $product->sku,
                    'brand' => (string) $product->brand,
                    'price' => $price,
                    'price_formatted' => trim(number_format($price, 2).' '.$currencyLabel),
                    'stock_quantity' => (int) $product->stock_quantity,
                    // One resolver, shared with the admin product picker: cover
                    // image, else the first uploaded one, else the legacy column.
                    'image_url' => $product->primaryImageUrl(400),
                    'url' => route('shop.show', $product),
                ];
            })
            ->values();

        $categories = Category::query()
            ->where(function ($query) use ($term): void {
                SqlSafe::whereLike($query, 'name_en', $term);
                SqlSafe::orWhereLike($query, 'name_ar', $term);
                SqlSafe::orWhereLike($query, 'name_ku', $term);
            })
            ->withCount('products')
            ->orderByDesc('products_count')
            ->limit($limit)
            ->get()
            ->map(fn (Category $category): array => [
                'id' => $category->id,
                'label' => $category->localizedName(),
                'product_count' => (int) $category->products_count,
                'url' => route('categories.show', $category->slug ?: $category->id),
            ])
            ->values();

        $brands = Product::query()
            ->where('is_active', true)
            ->whereNotNull('brand')
            ->where('brand', '!=', '')
            ->where(fn ($query) => SqlSafe::whereLike($query, 'brand', $term))
            ->select('brand')
            ->distinct()
            ->orderBy('brand')
            ->limit($limit)
            ->pluck('brand')
            ->map(fn (string $brand): array => [
                'label' => $brand,
                // The listing's `brand` parameter names a *vehicle* brand — it
                // feeds the fitment filter — while these suggestions come from
                // the part's own brand column. Sending one as the other left
                // every suggestion landing on an empty page. The search covers
                // the part brand, so that is what the suggestion follows.
                'url' => route('shop.index', ['search' => $brand]),
            ])
            ->values();

        return response()->json(['data' => [
            'query' => $term,
            'products' => $products,
            'categories' => $categories,
            'brands' => $brands,
        ]]);
    }

    public function subscribeBackInStock(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->is_active, 404);

        if ((int) $product->stock_quantity > 0) {
            return back()->with('status', __('This product is already back in stock.'));
        }

        BackInStockSubscription::query()->firstOrCreate([
            'user_id' => $request->user()->id,
            'product_id' => $product->id,
        ]);

        return back()->with('status', __('Request sent. We will notify you when this product is back in stock.'));
    }

    public function unsubscribeBackInStock(Request $request, Product $product): RedirectResponse
    {
        BackInStockSubscription::query()
            ->where('user_id', $request->user()->id)
            ->where('product_id', $product->id)
            ->delete();

        return back()->with('status', __('Back-in-stock notification removed.'));
    }

    private function recordRecentlyViewedProduct(User $user, Product $product): void
    {
        RecentlyViewedProduct::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'product_id' => $product->id,
            ],
            ['viewed_at' => now()],
        );

        $staleIds = RecentlyViewedProduct::query()
            ->where('user_id', $user->id)
            ->latest('viewed_at')
            ->skip(60)
            ->limit(1000)
            ->pluck('id');

        if ($staleIds->isNotEmpty()) {
            RecentlyViewedProduct::query()->whereKey($staleIds)->delete();
        }
    }
}
