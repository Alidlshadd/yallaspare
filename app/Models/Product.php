<?php

namespace App\Models;

use App\Support\CatalogLandingCache;
use App\Support\DbSchema;
use App\Support\LocalizedText;
use App\Support\SqlSafe;
use App\Support\VehicleFilterCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Product extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'category_id', 'product_brand_id', 'name_en', 'name_ar', 'name_ku',
        'description_en', 'description_ar', 'description_ku',
        'price', 'dealer_price', 'stock_quantity', 'sku', 'oem_number', 'part_number', 'warranty', 'brand',
        'compatible_models', 'image', 'is_active', 'low_stock_threshold', 'slug',
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

        // Only brand / compatible_models feed the storefront vehicle filter
        // options, so routine edits (stock, price) keep the cache warm.
        // created/updated instead of saved: wasRecentlyCreated stays true on
        // the instance after insert, which would flush on every later save.
        static::created(function (): void {
            VehicleFilterCache::flush();
            CatalogLandingCache::flush();
        });

        static::updated(function (self $product): void {
            if ($product->wasChanged(['brand', 'compatible_models'])) {
                VehicleFilterCache::flush();
            }

            // The brand landing index counts what is on sale per brand, so it
            // turns stale on a brand move or on a product leaving the catalogue.
            if ($product->wasChanged(['product_brand_id', 'is_active'])) {
                CatalogLandingCache::flush();
            }
        });

        static::deleted(function (): void {
            VehicleFilterCache::flush();
            CatalogLandingCache::flush();
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<ProductBrand, $this> */
    public function productBrand(): BelongsTo
    {
        return $this->belongsTo(ProductBrand::class);
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

    /** @return HasMany<InventoryMovement, $this> */
    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    /** @return HasMany<OrderItem, $this> */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** @return HasMany<Wishlist, $this> */
    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    /** @return HasMany<ProductReview, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    /** @return HasMany<ProductView, $this> */
    public function views(): HasMany
    {
        return $this->hasMany(ProductView::class);
    }

    /** @return HasOne<ProductAnalytic, $this> */
    public function analytics(): HasOne
    {
        return $this->hasOne(ProductAnalytic::class);
    }

    /** @return HasMany<ProductVehicleFitment, $this> */
    public function vehicleFitments(): HasMany
    {
        return $this->hasMany(ProductVehicleFitment::class);
    }

    /** @return HasMany<ProductImage, $this> */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    /** @return HasOne<ProductImage, $this> */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true)->oldest('sort_order')->oldest('id');
    }

    /**
     * Everything a shopper might type into the search box.
     *
     * A part's own columns are only half of what identifies it. The other half
     * is what it fits, and that lives in the fitment relation on purpose — the
     * product is called "Engine Oil Filter", not "Tivoli Engine Oil Filter",
     * because the same filter fits several cars. A search that read only the
     * products table therefore returned nothing for "Tivoli" even though the
     * admin list and the product page both showed the compatibility.
     *
     * The whole thing is wrapped in one closure, so callers keep their own
     * constraints. `whereHas` is used rather than a join throughout: it compiles
     * to an EXISTS subquery, so a product with five matching fitment rows is
     * still one row in the result and needs no distinct.
     */
    public function scopeMatchingSearchTerm(Builder $query, string $term): Builder
    {
        $term = SqlSafe::searchTerm($term);

        if ($term === '') {
            return $query;
        }

        // A four-digit number is read as a model year as well as ordinary text,
        // so "2017" finds the parts recorded as fitting a car built that year.
        $year = preg_match('/^\d{4}$/', $term) === 1 ? (int) $term : null;

        return $query->where(function (Builder $group) use ($term, $year): void {
            SqlSafe::whereLike($group, 'name_en', $term);
            SqlSafe::orWhereLike($group, 'name_ar', $term);
            SqlSafe::orWhereLike($group, 'name_ku', $term);
            SqlSafe::orWhereLike($group, 'brand', $term);
            SqlSafe::orWhereLike($group, 'sku', $term);

            foreach (['oem_number', 'part_number'] as $column) {
                if (DbSchema::hasColumn('products', $column)) {
                    SqlSafe::orWhereLike($group, $column, $term);
                }
            }

            $group->orWhereHas('category', function (Builder $category) use ($term): void {
                $category->where(function (Builder $names) use ($term): void {
                    SqlSafe::whereLike($names, 'name_en', $term);
                    SqlSafe::orWhereLike($names, 'name_ar', $term);
                    SqlSafe::orWhereLike($names, 'name_ku', $term);
                });
            });

            if (! DbSchema::hasTable('product_vehicle_fitments')) {
                return;
            }

            $group->orWhereHas('vehicleFitments', function (Builder $fitment) use ($term, $year): void {
                $fitment->where(function (Builder $any) use ($term, $year): void {
                    // The fitment row's own columns. `notes` is left out: it is
                    // where an operator writes internal remarks, and a search
                    // has no business surfacing a product because of them.
                    SqlSafe::whereLike($any, 'engine', $term);

                    if ($year !== null) {
                        $any->orWhere(function (Builder $years) use ($year): void {
                            $years->where('year_from', '<=', $year)
                                ->where(function (Builder $end) use ($year): void {
                                    $end->whereNull('year_to')->orWhere('year_to', '>=', $year);
                                });
                        });
                    }

                    if (DbSchema::hasTable('vehicle_brands')) {
                        $any->orWhereHas('brand', function (Builder $brand) use ($term): void {
                            SqlSafe::whereLike($brand, 'name', $term);
                        });
                    }

                    if (! DbSchema::hasTable('vehicle_models')) {
                        return;
                    }

                    $any->orWhereHas('model', function (Builder $model) use ($term, $year): void {
                        $model->where(function (Builder $variant) use ($term, $year): void {
                            SqlSafe::whereLike($variant, 'name', $term);

                            foreach (['name_en', 'name_ar', 'name_ku'] as $column) {
                                if (DbSchema::hasColumn('vehicle_models', $column)) {
                                    SqlSafe::orWhereLike($variant, $column, $term);
                                }
                            }

                            if ($year !== null && DbSchema::hasColumn('vehicle_models', 'production_start_year')) {
                                $variant->orWhere(function (Builder $years) use ($year): void {
                                    $years->where('production_start_year', '<=', $year)
                                        ->where(function (Builder $end) use ($year): void {
                                            $end->whereNull('production_end_year')
                                                ->orWhere('production_end_year', '>=', $year);
                                        });
                                });
                            }

                            // The family is the car's shared name across its
                            // variants — a shopper types "Tivoli", not the
                            // particular 2015-2019 variant.
                            if (DbSchema::hasTable('vehicle_model_families')) {
                                $variant->orWhereHas('family', function (Builder $family) use ($term): void {
                                    $family->where(function (Builder $names) use ($term): void {
                                        SqlSafe::whereLike($names, 'name', $term);

                                        foreach (['name_en', 'name_ar', 'name_ku'] as $column) {
                                            if (DbSchema::hasColumn('vehicle_model_families', $column)) {
                                                SqlSafe::orWhereLike($names, $column, $term);
                                            }
                                        }
                                    });
                                });
                            }

                            // Engine size and fuel: "1.6" and "Petrol" are both
                            // things people search by.
                            if (DbSchema::hasTable('vehicle_model_engine_types')) {
                                $variant->orWhereHas('engineTypes', function (Builder $engine) use ($term): void {
                                    $engine->where(function (Builder $names) use ($term): void {
                                        SqlSafe::whereLike($names, 'name', $term);

                                        foreach (['fuel_type', 'engine_size', 'aspiration'] as $column) {
                                            if (DbSchema::hasColumn('vehicle_model_engine_types', $column)) {
                                                SqlSafe::orWhereLike($names, $column, $term);
                                            }
                                        }
                                    });
                                });
                            }
                        });
                    });
                });
            });
        });
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
                'stock_quantity <= COALESCE(low_stock_threshold, ('.$globalThresholdSubquery->toSql().'), 0)',
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
        if ($basePrice <= 0 || ! DbSchema::hasTable('discounts')) {
            return [
                'price' => round(max(0, $basePrice), 2),
                'discount_ids' => [],
            ];
        }

        $discounts = Discount::activeForPricing()
            ->filter(fn (Discount $discount): bool => $discount->appliesToProduct($this));

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
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
