<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_brand_id',
        'name',
        'slug',
    ];

    public function brand()
    {
        return $this->belongsTo(VehicleBrand::class, 'vehicle_brand_id');
    }
}
