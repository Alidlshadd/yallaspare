<?php

namespace App\Models;

use App\Models\Concerns\FlushesVehicleFilterCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVehicleFitment extends Model
{
    use FlushesVehicleFilterCache;
    use HasFactory;

    protected $fillable = [
        'product_id',
        'vehicle_brand_id',
        'vehicle_model_id',
        'year_from',
        'year_to',
        'engine',
        'notes',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(VehicleBrand::class, 'vehicle_brand_id');
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'vehicle_model_id');
    }
}
