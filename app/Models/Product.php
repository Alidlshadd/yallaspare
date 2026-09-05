<?php

namespace App\Models;

use App\Support\CatalogLandingCache;
use App\Support\DbSchema;
use App\Support\ImageVariants;
use App\Support\LocalizedText;
use App\Support\Search\SearchQuery;
use App\Support\Search\Token;
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
     * The picture that stands for this product.
     *
     * The cover image first, then whatever image was uploaded first, then the
     * single legacy `image` column, and null when there is nothing — a caller
     * showing a placeholder is better than an <img> with a broken source.
     *
     * Reads loaded relations when it has them and queries only when it must, so
     * a list of results eager-loads `images` and this stays one query for all.
     */
    public function primaryImagePath(): ?string
    {
        $images = $this->relationLoaded('images')
            ? $this->images
            : $this->images()->get();

        $chosen = $images->firstWhere('is_primary', true) ?? $images->first();

        if ($chosen && trim((string) $chosen->path) !== '') {
            return ltrim((string) $chosen->path, '/');
        }

        return trim((string) $this->image) !== '' ? ltrim((string) $this->image, '/') : null;
    }

    /**
     * That picture as a URL, at a width the caller asks for. A width the
     * variant command generates gets the small WebP copy; anything else, and
     * anything with no variant yet, falls back to the original.
     */
    public function primaryImageUrl(?int $width = null): ?string
    {
        $path = $this->primaryImagePath();

        if ($path === null) {
            return null;
        }

        return $width === null
            ? asset('storage/'.$path)
            : ImageVariants::url($path, $width);
    }

    /**
     * Everything a shopper might type into the search box.
     *
     * The old version handed the whole query to a LIKE, which meant one column
     * had to contain the entire phrase. "ssangyong rexton" therefore found
     * nothing: the brand is on the vehicle brand, the model on the variant, and
     * no single column holds both. A four-digit year was worse still, because a
     * year is stored as a range and never as text.
     *
     * So the query is split into words, and a product has to answer *every*
     * word — but each word may be answered by whatever part of the catalogue
     * knows about it. "ssangyong rexton 2024 oil filter" is satisfied by the
     * brand relation, the variant, a year range and the product name working
     * together on the same product.
     *
     * The whole thing sits in one closure, so callers keep their own active and
     * visibility constraints. Every relation is reached with `whereHas`, which
     * compiles to EXISTS: a product with five matching fitment rows is still
     * one row and needs no distinct.
     */
    public function scopeMatchingSearchTerm(Builder $query, string $term): Builder
    {
        $search = SearchQuery::parse($term);

        if ($search->isEmpty()) {
            return $query;
        }

        return $query->where(function (Builder $all) use ($search): void {
            // AND between words, OR inside each one. Every word must land
            // somewhere on the product or on what it fits.
            foreach ($search->tokens as $token) {
                $all->where(function (Builder $any) use ($token): void {
                    $this->matchTokenAgainstProduct($any, $token);
                    $this->matchTokenAgainstCategory($any, $token);
                    $this->matchTokenAgainstFitments($any, $token);
                });
            }
        });
    }

    /**
     * The product's own columns.
     */
    private function matchTokenAgainstProduct(Builder $any, Token $token): void
    {
        $first = true;

        foreach ($token->variants as $variant) {
            foreach (['name_en', 'name_ar', 'name_ku', 'brand', 'sku'] as $column) {
                if ($first) {
                    SqlSafe::whereLike($any, $column, $variant);
                    $first = false;
                } else {
                    SqlSafe::orWhereLike($any, $column, $variant);
                }
            }

            foreach (['oem_number', 'part_number'] as $column) {
                if (DbSchema::hasColumn('products', $column)) {
                    SqlSafe::orWhereLike($any, $column, $variant);
                }
            }

            // A description match is real but weak; it is included so a part
            // described as fitting something is findable, and ranked last.
            foreach (['description_en', 'description_ar', 'description_ku'] as $column) {
                SqlSafe::orWhereLike($any, $column, $variant);
            }
        }

        // "SY-1721840025" typed for a "SY1721840025" on file, and the reverse.
        // Only for tokens that look like a part number: this strips separators
        // from the column as well, which no index can help with, and a word
        // like "filter" has nothing to gain from it.
        if ($token->looksLikePartNumber()) {
            foreach (['sku', 'oem_number', 'part_number'] as $column) {
                if ($column === 'sku' || DbSchema::hasColumn('products', $column)) {
                    SqlSafe::orWhereLikeIgnoringPunctuation($any, $column, $token->text);
                }
            }
        }
    }

    private function matchTokenAgainstCategory(Builder $any, Token $token): void
    {
        $any->orWhereHas('category', function (Builder $category) use ($token): void {
            $category->where(function (Builder $names) use ($token): void {
                $first = true;

                foreach ($token->variants as $variant) {
                    foreach (['name_en', 'name_ar', 'name_ku'] as $column) {
                        if ($first) {
                            SqlSafe::whereLike($names, $column, $variant);
                            $first = false;
                        } else {
                            SqlSafe::orWhereLike($names, $column, $variant);
                        }
                    }
                }
            });
        });
    }

    /**
     * What the part is recorded as fitting: the car, its years, its engines.
     */
    private function matchTokenAgainstFitments(Builder $any, Token $token): void
    {
        if (! DbSchema::hasTable('product_vehicle_fitments')) {
            return;
        }

        $any->orWhereHas('vehicleFitments', function (Builder $fitment) use ($token): void {
            $fitment->where(function (Builder $inner) use ($token): void {
                $first = true;

                foreach ($token->variants as $variant) {
                    if ($first) {
                        SqlSafe::whereLike($inner, 'engine', $variant);
                        $first = false;
                    } else {
                        SqlSafe::orWhereLike($inner, 'engine', $variant);
                    }
                }

                // A year is a range here, not a string: "2024" has to fall
                // inside the row rather than appear in it. A row that records
                // its own years answers from those alone — a part listed for
                // 2022-2023 must not answer 2026 just because the car is built
                // that long.
                if ($token->year !== null) {
                    $year = $token->year;

                    $inner->orWhere(function (Builder $years) use ($year): void {
                        $years->where(function (Builder $from) use ($year): void {
                            $from->whereNull('year_from')->orWhere('year_from', '<=', $year);
                        })->where(function (Builder $to) use ($year): void {
                            $to->whereNull('year_to')->orWhere('year_to', '>=', $year);
                        })->where(function (Builder $recorded): void {
                            $recorded->whereNotNull('year_from')->orWhereNotNull('year_to');
                        });
                    });
                }

                if (DbSchema::hasTable('vehicle_brands')) {
                    $inner->orWhereHas('brand', function (Builder $brand) use ($token): void {
                        $brand->where(function (Builder $names) use ($token): void {
                            $first = true;

                            foreach ($token->variants as $variant) {
                                if ($first) {
                                    SqlSafe::whereLike($names, 'name', $variant);
                                    $first = false;
                                } else {
                                    SqlSafe::orWhereLike($names, 'name', $variant);
                                }
                            }
                        });
                    });
                }

                if (DbSchema::hasTable('vehicle_models')) {
                    $inner->orWhere(function (Builder $viaVariant) use ($token): void {
                        // A year asked of the variant is only meaningful for a
                        // fitment row that left its own years open; a row with
                        // years has already had its say above.
                        if ($token->year !== null) {
                            $viaVariant->whereNull('year_from')->whereNull('year_to');
                        }

                        $viaVariant->whereHas(
                            'model',
                            fn (Builder $model) => $this->matchTokenAgainstVariant($model, $token)
                        );
                    });
                }
            });
        });
    }

    private function matchTokenAgainstVariant(Builder $model, Token $token): void
    {
        $model->where(function (Builder $variant) use ($token): void {
            $first = true;

            foreach ($token->variants as $spelling) {
                foreach (['name', 'name_en', 'name_ar', 'name_ku'] as $column) {
                    if ($column !== 'name' && ! DbSchema::hasColumn('vehicle_models', $column)) {
                        continue;
                    }

                    if ($first) {
                        SqlSafe::whereLike($variant, $column, $spelling);
                        $first = false;
                    } else {
                        SqlSafe::orWhereLike($variant, $column, $spelling);
                    }
                }
            }

            // The car's own build years, for a variant whose fitment rows left
            // the years open.
            if ($token->year !== null && DbSchema::hasColumn('vehicle_models', 'production_start_year')) {
                $year = $token->year;

                $variant->orWhere(function (Builder $years) use ($year): void {
                    $years->where('production_start_year', '<=', $year)
                        ->where(function (Builder $end) use ($year): void {
                            $end->whereNull('production_end_year')->orWhere('production_end_year', '>=', $year);
                        });
                });
            }

            // The family is the name shared across a car's variants — a shopper
            // types "Rexton", not the 2022-2026 variant of it.
            if (DbSchema::hasTable('vehicle_model_families')) {
                $variant->orWhereHas('family', function (Builder $family) use ($token): void {
                    $family->where(function (Builder $names) use ($token): void {
                        $first = true;

                        foreach ($token->variants as $spelling) {
                            foreach (['name', 'name_en', 'name_ar', 'name_ku'] as $column) {
                                if ($column !== 'name' && ! DbSchema::hasColumn('vehicle_model_families', $column)) {
                                    continue;
                                }

                                if ($first) {
                                    SqlSafe::whereLike($names, $column, $spelling);
                                    $first = false;
                                } else {
                                    SqlSafe::orWhereLike($names, $column, $spelling);
                                }
                            }
                        }
                    });
                });
            }

            // "1.6" and "petrol" are both things people search by.
            if (DbSchema::hasTable('vehicle_model_engine_types')) {
                $variant->orWhereHas('engineTypes', function (Builder $engine) use ($token): void {
                    $engine->where(function (Builder $names) use ($token): void {
                        $first = true;

                        foreach ($token->variants as $spelling) {
                            foreach (['name', 'fuel_type', 'engine_size', 'aspiration'] as $column) {
                                if ($column !== 'name' && ! DbSchema::hasColumn('vehicle_model_engine_types', $column)) {
                                    continue;
                                }

                                if ($first) {
                                    SqlSafe::whereLike($names, $column, $spelling);
                                    $first = false;
                                } else {
                                    SqlSafe::orWhereLike($names, $column, $spelling);
                                }
                            }
                        }
                    });
                });
            }
        });
    }

    /**
     * Best answer first, rather than newest first.
     *
     * A shopper who types a part number wants that part at the top, not the
     * most recently added thing that happens to mention it. The ladder is
     * built from the query rather than from the row, so it costs one CASE over
     * the page being returned and no extra queries.
     */
    public function scopeOrderBySearchRelevance(Builder $query, string $term): Builder
    {
        $search = SearchQuery::parse($term);

        if ($search->isEmpty()) {
            return $query;
        }

        $grammar = $query->getQuery()->getGrammar();
        $phrase = $search->normalized;

        $cases = [];
        $bindings = [];

        $exact = static function (string $column, int $rank) use (&$cases, &$bindings, $grammar, $phrase): void {
            // LOWER() on both sides: MySQL's default collation folds case and
            // SQLite's `=` does not, and a ranking must not depend on which.
            $cases[] = 'WHEN LOWER('.$grammar->wrap($column).') = ? THEN '.$rank;
            $bindings[] = $phrase;
        };

        // 1-2: the shopper knows the number.
        $exact('sku', 1);

        if (DbSchema::hasColumn('products', 'oem_number')) {
            $exact('oem_number', 2);
        }

        if (DbSchema::hasColumn('products', 'part_number')) {
            $exact('part_number', 2);
        }

        // 3-4: the name, whole and then as a beginning.
        $exact('name_en', 3);

        $cases[] = 'WHEN LOWER('.$grammar->wrap('name_en').") LIKE ? ESCAPE '!' THEN 4";
        $bindings[] = SqlSafe::startsWithPattern($phrase);

        // 5: the query appears somewhere in the name.
        $cases[] = 'WHEN LOWER('.$grammar->wrap('name_en').") LIKE ? ESCAPE '!' THEN 5";
        $bindings[] = SqlSafe::containsPattern($phrase);

        return $query->orderByRaw('CASE '.implode(' ', $cases).' ELSE 9 END', $bindings);
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
