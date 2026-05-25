<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Support\LocalizedText;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Product extends Model {
    use HasFactory, LogsActivity;

    protected $fillable = [
        'category_id','name_en','name_ar','name_ku',
        'description_en','description_ar','description_ku',
        'price','dealer_price','stock_quantity','sku','oem_number','part_number','warranty','brand',
        'compatible_models','image','is_active','low_stock_threshold','slug'
    ];

    protected $casts = [
        'compatible_models' => 'array',
        'price' => 'decimal:2',
        'dealer_price' => 'decimal:2',
        'low_stock_threshold' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $product): void {
            if (
                blank($product->slug)
                || $product->isDirty('name_en')
            ) {
                $product->slug = self::generateUniqueSlug(
                    (string) ($product->name_en ?: $product->sku ?: 'product'),
                    $product->id
                );
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function category() {
        return $this->belongsTo(Category::class);
    }

    public function localizedName(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        $field = match (true) {
            str_starts_with($locale, 'ar') => 'name_ar',
            str_starts_with($locale, 'ku') => 'name_ku',
            default => 'name_en',
        };

        return LocalizedText::first($this->{$field}, $this->name_en, $this->name_ar, $this->name_ku, __('Product'));
    }

    public function localizedDescription(?string $locale = null): ?string
    {
        $locale = $locale ?: app()->getLocale();
        $field = match (true) {
            str_starts_with($locale, 'ar') => 'description_ar',
            str_starts_with($locale, 'ku') => 'description_ku',
            default => 'description_en',
        };

        return LocalizedText::nullable($this->{$field}, $this->description_en, $this->description_ar, $this->description_ku);
    }

    public function getNameAttribute(): string
    {
        return $this->localizedName();
    }

    public function getLocalizedNameAttribute(): string
    {
        return $this->localizedName();
    }

    public function getLocalizedDescriptionAttribute(): ?string
    {
        return $this->localizedDescription();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    public function vehicleFitments()
    {
        return $this->hasMany(ProductVehicleFitment::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true)->oldest('sort_order')->oldest('id');
    }

    public function scopeLowStock(Builder $query): Builder
    {
        $globalThresholdSubquery = DB::table('settings')
            ->selectRaw(self::integerCastExpression('value'))
            ->where('key', 'low_stock_threshold')
            ->limit(1);

        return $query
            ->where('is_active', true)
            ->whereRaw(
                'stock_quantity <= COALESCE(low_stock_threshold, (' . $globalThresholdSubquery->toSql() . '), 0)',
                $globalThresholdSubquery->getBindings()
            );
    }

    private static function integerCastExpression(string $column): string
    {
        $type = DB::connection()->getDriverName() === 'mysql' ? 'UNSIGNED' : 'INTEGER';

        return "CAST({$column} AS {$type})";
    }

    public function priceFor(?User $user = null): float
    {
        return (float) $this->pricingFor($user)['price'];
    }

    /**
     * @return array{base_price:float,price:float,discount_amount:float,discount_percent:int,has_discount:bool,discount_ids:array<int>}
     */
    public function pricingFor(?User $user = null): array
    {
        $basePrice = round($this->basePriceFor($user), 2);
        $resolved = $this->resolveDiscountedPrice($basePrice);
        $price = round((float) $resolved['price'], 2);
        $discountAmount = round(max(0, $basePrice - $price), 2);

        return [
            'base_price' => $basePrice,
            'price' => $price,
            'discount_amount' => $discountAmount,
            'discount_percent' => $basePrice > 0 && $discountAmount > 0
                ? (int) round(($discountAmount / $basePrice) * 100)
                : 0,
            'has_discount' => $discountAmount > 0,
            'discount_ids' => $resolved['discount_ids'],
        ];
    }

    /**
     * @return array<int>
     */
    public function appliedDiscountRuleIds(?User $user = null): array
    {
        return $this->resolveDiscountedPrice($this->basePriceFor($user))['discount_ids'];
    }

    private function basePriceFor(?User $user = null): float
    {
        $basePrice = (float) $this->price;

        if ($user && $user->isDealer() && $user->dealer_status === User::DEALER_STATUS_ACTIVE) {
            if ($this->dealer_price !== null) {
                $basePrice = (float) $this->dealer_price;
            } else {
                $dealerDiscount = max(0, min((float) $user->dealer_discount, 100));
                if ($dealerDiscount > 0) {
                    $basePrice = round($basePrice * (1 - ($dealerDiscount / 100)), 2);
                }
            }
        }

        return $basePrice;
    }

    /**
     * @return array{price:float,discount_ids:array<int>}
     */
    private function resolveDiscountedPrice(float $basePrice): array
    {
        if ($basePrice <= 0 || !Schema::hasTable('discounts')) {
            return [
                'price' => round(max(0, $basePrice), 2),
                'discount_ids' => [],
            ];
        }

        $now = now();
        $hasBrandScope = Schema::hasColumn('discounts', 'brand_names');
        $categoryId = (int) ($this->category_id ?? 0);
        $brand = trim((string) ($this->brand ?? ''));

        $discounts = Discount::query()
            ->select(['id', 'scope', 'type', 'value', 'minimum_subtotal', 'usage_limit', 'used_count'])
            ->where('is_active', true)
            ->where(function ($query) use ($now): void {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($query) use ($now): void {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $now);
            })
            ->where(function ($query): void {
                $query->whereNull('usage_limit')
                    ->orWhere('usage_limit', '<=', 0)
                    ->orWhereColumn('used_count', '<', 'usage_limit');
            })
            ->where(function ($query) use ($categoryId, $brand, $hasBrandScope): void {
                $query->where('scope', 'catalog')
                    ->orWhere(function ($productQuery): void {
                        $productQuery->where('scope', 'product')
                            ->whereHas('products', fn ($relation) => $relation->whereKey($this->getKey()));
                    });

                if ($categoryId > 0) {
                    $query->orWhere(function ($categoryQuery) use ($categoryId): void {
                        $categoryQuery->where('scope', 'category')
                            ->whereHas('categories', fn ($relation) => $relation->whereKey($categoryId));
                    });
                }

                if ($hasBrandScope && $brand !== '') {
                    $query->orWhere(function ($brandQuery) use ($brand): void {
                        $brandQuery->where('scope', 'brand')
                            ->whereJsonContains('brand_names', $brand);
                    });
                }
            })
            ->get();

        $bestPrice = $basePrice;
        $bestDiscountId = null;
        foreach ($discounts as $discount) {
            $minimumSubtotal = $discount->minimum_subtotal !== null ? (float) $discount->minimum_subtotal : 0.0;
            if ($minimumSubtotal > 0 && $basePrice < $minimumSubtotal) {
                continue;
            }

            $value = max(0.0, (float) $discount->value);
            $candidate = (string) $discount->type === 'percent'
                ? $basePrice * (1 - (min($value, 100) / 100))
                : $basePrice - $value;

            $candidate = max(0.0, $candidate);
            if ($candidate < $bestPrice) {
                $bestPrice = $candidate;
                $bestDiscountId = (int) $discount->id;
            }
        }

        return [
            'price' => round($bestPrice, 2),
            'discount_ids' => $bestDiscountId ? [$bestDiscountId] : [],
        ];
    }

    private static function generateUniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($source);
        if ($baseSlug === '') {
            $baseSlug = 'product';
        }

        $slug = $baseSlug;
        $suffix = 2;

        while (
            static::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }
}
