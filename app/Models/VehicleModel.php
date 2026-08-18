<?php

namespace App\Models;

use App\Models\Concerns\FlushesVehicleFilterCache;
use App\Support\VehicleLocalization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleModel extends Model
{
    use FlushesVehicleFilterCache;
    use HasFactory;

    protected $fillable = [
        'vehicle_brand_id',
        'vehicle_model_family_id',
        'name',
        'name_en',
        'name_ar',
        'name_ku',
        'slug',
        'production_start_year',
        'production_end_year',
        'image_path',
    ];

    /** @return BelongsTo<VehicleBrand, $this> */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(VehicleBrand::class, 'vehicle_brand_id');
    }

    /** @return BelongsTo<VehicleModelFamily, $this> */
    public function family(): BelongsTo
    {
        return $this->belongsTo(VehicleModelFamily::class, 'vehicle_model_family_id');
    }

    /** @return HasMany<VehicleModelEngineType, $this> */
    public function engineTypes(): HasMany
    {
        return $this->hasMany(VehicleModelEngineType::class)->orderBy('name');
    }

    /** @return HasMany<ProductVehicleFitment, $this> */
    public function fitments(): HasMany
    {
        return $this->hasMany(ProductVehicleFitment::class, 'vehicle_model_id');
    }

    public function localizedName(?string $locale = null): string
    {
        return VehicleLocalization::name($this, $locale);
    }

    public function getLocalizedNameAttribute(): string
    {
        return $this->localizedName();
    }
}
