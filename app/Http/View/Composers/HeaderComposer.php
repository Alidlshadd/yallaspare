<?php

namespace App\Http\View\Composers;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Wishlist;
use App\Services\Cart\CartService;
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

    public const USER_COUNT_CACHE_TTL_SECONDS = 60;

    private const CART_SUMMARY_CACHE_PREFIX = 'header_cart_summary_user_';

    private const GUEST_CART_SUMMARY_CACHE_PREFIX = 'header_cart_summary_guest_';

    private const WISHLIST_COUNT_CACHE_PREFIX = 'header_wishlist_count_user_';

    public function compose(View $view): void
    {
        $user = auth()->user();
        $locale = app()->getLocale();
        $cartSummary = $this->cartSummaryFor($user);
        $headerCategories = $this->dropdownCategories($locale);

        $view->with([
            'headerCart' => null,
            'headerCartCount' => (int) $cartSummary['count'],
            'headerCartRef' => $cartSummary['ref'],
            'headerCartSubtotal' => (float) $cartSummary['subtotal'],
            'headerCartTotalFormatted' => $cartSummary['total_formatted'],
            'headerWishlistCount' => $this->wishlistCountFor($user),
            'headerCategories' => $headerCategories,
            'dropdownCategories' => $headerCategories,
        ]);
    }

    public function cartFor(?Authenticatable $user): ?Cart
    {
        try {
            $query = Cart::query()->with('items.product');

            if ($user !== null) {
                return $query->where('user_id', $user->getAuthIdentifier())->first();
            }

            // A guest who has not started a cart is not given a token just so
            // the header can look one up.
            $token = app(CartService::class)->guestToken();

            return $token === null ? null : $query->where('session_token', $token)->first();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function cartCountFor(?Authenticatable $user): int
    {
        return (int) $this->cartSummaryFor($user)['count'];
    }

    /**
     * @return array{count:int,ref:?string,subtotal:float,total_formatted:?string}
     */
    public function cartSummaryFor(?Authenticatable $user): array
    {
        $cacheKey = $this->cartSummaryCacheKey($user);

        if ($cacheKey === null) {
            return $this->emptyCartSummary();
        }

        return Cache::remember($cacheKey, self::USER_COUNT_CACHE_TTL_SECONDS, function () use ($user): array {
            try {
                $cart = $this->cartFor($user);

                if ($cart === null) {
                    return $this->emptyCartSummary();
                }

                $count = (int) $cart->items->sum('quantity');
                $subtotal = (float) $cart->items->sum(function ($item) use ($user): float {
                    $product = $item->product;

                    if (! $product) {
                        return 0.0;
                    }

                    return (float) $product->priceFor($user) * (int) $item->quantity;
                });

                return [
                    'count' => $count,
                    'ref' => '#'.str_pad((string) $cart->id, 6, '0', STR_PAD_LEFT),
                    'subtotal' => round($subtotal, 2),
                    'total_formatted' => null,
                ];
            } catch (\Throwable $e) {
                return $this->emptyCartSummary();
            }
        });
    }

    public function wishlistCountFor(?Authenticatable $user): int
    {
        if ($user === null) {
            return 0;
        }

        $userId = (int) $user->getAuthIdentifier();

        return (int) Cache::remember($this->wishlistCountCacheKey($userId), self::USER_COUNT_CACHE_TTL_SECONDS, function () use ($user): int {
            try {
                return (int) Wishlist::query()
                    ->where('user_id', $user->getAuthIdentifier())
                    ->count();
            } catch (\Throwable $e) {
                return 0;
            }
        });
    }

    public function dropdownCategories(string $locale): Collection
    {
        $cacheKey = self::CATEGORY_CACHE_PREFIX.$locale;

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
                            'image' => $imagePath !== '' ? asset('storage/'.ltrim($imagePath, '/')) : null,
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
            Cache::forget(self::CATEGORY_CACHE_PREFIX.$locale);
        }
    }

    public static function forgetCartCacheForUser(?int $userId): void
    {
        if ($userId === null || $userId < 1) {
            return;
        }

        Cache::forget(self::CART_SUMMARY_CACHE_PREFIX.$userId);
    }

    /**
     * Forget the header summary for whichever kind of cart changed.
     */
    public static function forgetCartCacheFor(?int $userId, ?string $sessionToken): void
    {
        self::forgetCartCacheForUser($userId);

        if (is_string($sessionToken) && $sessionToken !== '') {
            Cache::forget(self::GUEST_CART_SUMMARY_CACHE_PREFIX.sha1($sessionToken));
        }
    }

    public static function forgetWishlistCacheForUser(?int $userId): void
    {
        if ($userId === null || $userId < 1) {
            return;
        }

        Cache::forget(self::WISHLIST_COUNT_CACHE_PREFIX.$userId);
    }

    /**
     * @return array{count:int,ref:?string,subtotal:float,total_formatted:?string}
     */
    private function emptyCartSummary(): array
    {
        return [
            'count' => 0,
            'ref' => null,
            'subtotal' => 0.0,
            'total_formatted' => null,
        ];
    }

    /**
     * Null when there is nothing to summarise: a guest who has not started a
     * cart has no key, and therefore no cached empty summary to invalidate.
     */
    private function cartSummaryCacheKey(?Authenticatable $user): ?string
    {
        if ($user !== null) {
            return self::CART_SUMMARY_CACHE_PREFIX.(int) $user->getAuthIdentifier();
        }

        $token = app(CartService::class)->guestToken();

        return $token === null
            ? null
            : self::GUEST_CART_SUMMARY_CACHE_PREFIX.sha1($token);
    }

    private function wishlistCountCacheKey(int $userId): string
    {
        return self::WISHLIST_COUNT_CACHE_PREFIX.$userId;
    }
}
