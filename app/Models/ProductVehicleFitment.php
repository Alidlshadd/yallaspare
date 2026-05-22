<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVehicleFitment extends Model
{
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

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function brand()
    {
        return $this->belongsTo(VehicleBrand::class, 'vehicle_brand_id');
    }

    public function model()
    {
        return $this->belongsTo(VehicleModel::class, 'vehicle_model_id');
    }
}
