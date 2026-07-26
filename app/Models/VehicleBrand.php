<?php

namespace App\Models;

use App\Models\Concerns\FlushesVehicleFilterCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleBrand extends Model
{
    use FlushesVehicleFilterCache;
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    public function models(): HasMany
    {
        return $this->hasMany(VehicleModel::class);
    }
}
