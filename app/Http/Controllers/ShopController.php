<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Setting;
use App\Models\Wishlist;
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
        if (auth()->check() && Schema::hasTable('wishlists')) {
            $isWishlisted = Wishlist::query()
                ->where('user_id', auth()->id())
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
            'reviews' => $reviews,
            'reviewCount' => $reviewCount,
            'averageRating' => $averageRating,
        ]);
    }
}
