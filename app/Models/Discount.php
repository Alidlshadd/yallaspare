<?php

namespace App\Models;

use App\Support\DbSchema;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Discount extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    private const ACTIVE_PRICING_CONTAINER_KEY = 'discounts.active_for_pricing';

    protected $fillable = [
        'name',
        'code',
        'scope',
        'type',
        'value',
        'minimum_subtotal',
        'starts_at',
        'ends_at',
        'usage_limit',
        'used_count',
        'is_active',
        'brand_names',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'minimum_subtotal' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'usage_limit' => 'integer',
        'used_count' => 'integer',
        'is_active' => 'boolean',
        'brand_names' => 'array',
    ];

    protected static function booted(): void
    {
        $flush = static function (): void {
            self::flushActivePricingCache();
        };

        static::saved($flush);
        static::deleted($flush);
        static::restored($flush);
    }

    /**
     * Active discounts usable for product pricing, loaded once per request
     * with their product/category id pivots so per-product scope matching
     * can happen in PHP instead of one query per product.
     *
     * @return Collection<int, self>
     */
    public static function activeForPricing(): Collection
    {
        $app = app();

        if (! $app->bound(self::ACTIVE_PRICING_CONTAINER_KEY)) {
            $app->instance(self::ACTIVE_PRICING_CONTAINER_KEY, self::loadActiveForPricing());
        }

        return $app->make(self::ACTIVE_PRICING_CONTAINER_KEY);
    }

    public static function flushActivePricingCache(): void
    {
        app()->forgetInstance(self::ACTIVE_PRICING_CONTAINER_KEY);
    }

    /**
     * @return Collection<int, self>
     */
    private static function loadActiveForPricing(): Collection
    {
        if (! DbSchema::hasTable('discounts')) {
            return new Collection();
        }

        $now = now();
        $columns = ['id', 'scope', 'type', 'value', 'minimum_subtotal', 'usage_limit', 'used_count'];

        if (DbSchema::hasColumn('discounts', 'brand_names')) {
            $columns[] = 'brand_names';
        }

        return self::query()
            ->select($columns)
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
            ->whereIn('scope', ['catalog', 'product', 'category', 'brand'])
            ->with(['products:products.id', 'categories:categories.id'])
            ->get()
            ->collect();
    }

    public function appliesToProduct(Product $product): bool
    {
        return match ((string) $this->scope) {
            'catalog' => true,
            'product' => $this->relationLoaded('products')
                ? $this->products->contains('id', $product->getKey())
                : $this->products()->whereKey($product->getKey())->exists(),
            'category' => $product->category_id !== null && ($this->relationLoaded('categories')
                ? $this->categories->contains('id', (int) $product->category_id)
                : $this->categories()->whereKey((int) $product->category_id)->exists()),
            'brand' => $this->matchesBrand(trim((string) ($product->brand ?? ''))),
            default => false,
        };
    }

    private function matchesBrand(string $brand): bool
    {
        if ($brand === '') {
            return false;
        }

        foreach ((array) ($this->brand_names ?? []) as $name) {
            if ((string) $name === $brand) {
                return true;
            }
        }

        return false;
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'discount_product')
            ->withTimestamps();
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'discount_category')
            ->withTimestamps();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logExcept(['used_count'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
