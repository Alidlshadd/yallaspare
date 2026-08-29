<?php

namespace App\Models;

use App\Support\CatalogLandingCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ProductBrand extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'country_code',
        'logo_path',
    ];

    protected static function booted(): void
    {
        $flush = static function (): void {
            CatalogLandingCache::flush();
        };

        static::saved($flush);
        static::deleted($flush);
    }

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
