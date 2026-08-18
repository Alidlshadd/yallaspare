<?php

namespace App\Models;

use App\Models\Concerns\FlushesVehicleFilterCache;
use App\Support\VehicleFuelType;
use App\Support\VehicleLocalization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleModelEngineType extends Model
{
    use FlushesVehicleFilterCache;
    use HasFactory;

    protected $fillable = [
        'vehicle_model_id',
        'name',
        'fuel_type',
        'engine_size',
        'aspiration',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'engine_size' => 'decimal:1',
        ];
    }

    /** @return BelongsTo<VehicleModel, $this> */
    public function model(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'vehicle_model_id');
    }

    /**
     * Engines saved before the structured columns existed only have `name`, so
     * fall back to translating that text rather than showing nothing.
     */
    public function localizedName(?string $locale = null): string
    {
        if (VehicleFuelType::isValid($this->fuel_type)) {
            return VehicleFuelType::displayName($this->fuel_type, $this->engine_size, $this->aspiration, $locale);
        }

        return VehicleLocalization::engine($this->name, $locale);
    }

    public function localizedFuelLabel(?string $locale = null): string
    {
        return VehicleFuelType::label($this->fuel_type, $locale);
    }

    public function getLocalizedNameAttribute(): string
    {
        return $this->localizedName();
    }
}
