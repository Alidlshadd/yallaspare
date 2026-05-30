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
use App\Support\SqlSafe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function show(Request $request, Product $product): View|RedirectResponse
    {
        abort_unless($product->is_active, 404);

        $rawIdentifier = (string) last($request->segments());
        if ($rawIdentifier !== (string) $product->slug) {
            return redirect()->route('shop.show', $product, 301);
        }

        $productRelations = ['category', 'images'];
        if (Schema::hasTable('product_reviews')) {
            $productRelations['reviews'] = fn ($query) => $query
                ->where('is_approved', true)
                ->with('user:id,name')
                ->latest('reviewed_at')
                ->latest('id')
                ->take(8);
        }

        $product->load($productRelations);

        $currencyLabel = (string) Setting::getValue('currency_code', 'IQD');

        $relatedProducts = Product::query()
            ->with('category')
            ->where('is_active', 1)
            ->whereKeyNot($product->id)
            ->when($product->category_id, fn ($query) => $query->where('category_id', $product->category_id))
            ->latest()
            ->take(4)
            ->get();

        $isWishlisted = false;
        $isBackInStockSubscribed = false;
        $recentlyViewedProducts = collect();
        if (auth()->check() && Schema::hasTable('wishlists')) {
            $isWishlisted = Wishlist::query()
                ->where('user_id', auth()->id())
                ->where('product_id', $product->id)
                ->exists();
        }

        if ($request->user() && Schema::hasTable('recently_viewed_products')) {
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

        if ($request->user() && Schema::hasTable('back_in_stock_subscriptions')) {
            $isBackInStockSubscribed = BackInStockSubscription::query()
                ->where('user_id', $request->user()->id)
                ->where('product_id', $product->id)
                ->exists();
        }

        $reviews = $product->relationLoaded('reviews') ? $product->reviews : collect();
        $reviewCount = Schema::hasTable('product_reviews')
            ? ProductReview::query()->where('product_id', $product->id)->where('is_approved', true)->count()
            : 0;
        $averageRating = Schema::hasTable('product_reviews')
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
            ->where(function ($query) use ($term): void {
                SqlSafe::whereLike($query, 'name_en', $term);
                SqlSafe::orWhereLike($query, 'name_ar', $term);
                SqlSafe::orWhereLike($query, 'name_ku', $term);
                SqlSafe::orWhereLike($query, 'sku', $term);
                SqlSafe::orWhereLike($query, 'oem_number', $term);
                SqlSafe::orWhereLike($query, 'part_number', $term);
                SqlSafe::orWhereLike($query, 'brand', $term);
            })
            ->orderByRaw('CASE WHEN stock_quantity > 0 THEN 0 ELSE 1 END')
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
                    'price_formatted' => trim(number_format($price, 2) . ' ' . $currencyLabel),
                    'stock_quantity' => (int) $product->stock_quantity,
                    'image_url' => $this->primaryImageUrl($product),
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
                'url' => route('shop.index', ['brand' => $brand]),
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

        return back()->with('status', __('Back-in-stock notification enabled.'));
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

    private function primaryImageUrl(Product $product): ?string
    {
        $firstImage = $product->relationLoaded('images') ? $product->images->first() : $product->images()->first();
        if ($firstImage) {
            return asset('storage/' . ltrim((string) $firstImage->path, '/'));
        }

        return $product->image ? asset('storage/' . ltrim((string) $product->image, '/')) : null;
    }
}
