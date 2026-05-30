<?php

namespace App\Http\View\Composers;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Wishlist;
use App\Support\LocalizedText;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class HeaderComposer
{
    public const CATEGORY_CACHE_TTL_SECONDS = 600;

    public const CATEGORY_CACHE_PREFIX = 'header_dropdown_categories_';

    public function compose(View $view): void
    {
        $user = auth()->user();
        $locale = app()->getLocale();

        $view->with([
            'headerCart' => $this->cartFor($user),
            'headerCartCount' => $this->cartCountFor($user),
            'headerWishlistCount' => $this->wishlistCountFor($user),
            'dropdownCategories' => $this->dropdownCategories($locale),
        ]);
    }

    public function cartFor(?Authenticatable $user): ?Cart
    {
        if ($user === null) {
            return null;
        }

        try {
            return Cart::query()
                ->where('user_id', $user->getAuthIdentifier())
                ->with('items.product')
                ->first();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function cartCountFor(?Authenticatable $user): int
    {
        $cart = $this->cartFor($user);

        if ($cart === null) {
            return 0;
        }

        return (int) $cart->items->sum('quantity');
    }

    public function wishlistCountFor(?Authenticatable $user): int
    {
        if ($user === null) {
            return 0;
        }

        try {
            return (int) Wishlist::query()
                ->where('user_id', $user->getAuthIdentifier())
                ->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function dropdownCategories(string $locale): Collection
    {
        $cacheKey = self::CATEGORY_CACHE_PREFIX . $locale;

        return Cache::remember($cacheKey, self::CATEGORY_CACHE_TTL_SECONDS, function () use ($locale): Collection {
            try {
                if (! Schema::hasTable('categories')) {
                    return collect();
                }

                $hasImage = Schema::hasColumn('categories', 'image');
                $columns = ['id', 'slug', 'name_en', 'name_ar', 'name_ku', 'description'];
                if ($hasImage) {
                    $columns[] = 'image';
                }

                $localeField = match (true) {
                    str_starts_with($locale, 'ar') => 'name_ar',
                    str_starts_with($locale, 'ku') => 'name_ku',
                    default => 'name_en',
                };

                return Category::query()
                    ->select($columns)
                    ->orderBy('name_en')
                    ->take(8)
                    ->get()
                    ->map(function (Category $category) use ($localeField, $hasImage): array {
                        $imagePath = $hasImage ? trim((string) $category->image) : '';

                        return [
                            'label' => LocalizedText::first(
                                $category->{$localeField},
                                $category->name_en,
                                $category->name_ar,
                                $category->name_ku,
                            ),
                            'desc' => $category->localized_description,
                            'url' => route('shop.index', ['category' => $category->slug ?: $category->id]),
                            'image' => $imagePath !== '' ? asset('storage/' . ltrim($imagePath, '/')) : null,
                        ];
                    })
                    ->values();
            } catch (\Throwable $e) {
                return collect();
            }
        });
    }

    public static function forgetCategoryCache(): void
    {
        foreach (['en', 'ar', 'ku'] as $locale) {
            Cache::forget(self::CATEGORY_CACHE_PREFIX . $locale);
        }
    }
}
