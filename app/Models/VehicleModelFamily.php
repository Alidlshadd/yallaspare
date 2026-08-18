<?php

namespace App\Models;

use App\Models\Concerns\FlushesVehicleFilterCache;
use App\Support\VehicleLocalization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleModelFamily extends Model
{
    use FlushesVehicleFilterCache;
    use HasFactory;

    protected $fillable = [
        'vehicle_brand_id',
        'name',
        'name_en',
        'name_ar',
        'name_ku',
        'slug',
    ];

    /** @return BelongsTo<VehicleBrand, $this> */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(VehicleBrand::class, 'vehicle_brand_id');
    }

    /** @return HasMany<VehicleModel, $this> */
    public function variants(): HasMany
    {
        return $this->hasMany(VehicleModel::class)->orderBy('name');
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
