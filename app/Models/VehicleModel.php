<?php

namespace App\Models;

use App\Models\Concerns\FlushesVehicleFilterCache;
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
        'name',
        'slug',
        'production_start_year',
        'production_end_year',
    ];

    /** @return BelongsTo<VehicleBrand, $this> */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(VehicleBrand::class, 'vehicle_brand_id');
    }

    /** @return HasMany<VehicleModelEngineType, $this> */
    public function engineTypes(): HasMany
    {
        return $this->hasMany(VehicleModelEngineType::class)->orderBy('name');
    }
}
