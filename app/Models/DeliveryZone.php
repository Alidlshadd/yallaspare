<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'city',
        'district',
        'shipping_fee',
        'free_shipping_min',
        'delivery_days_min',
        'delivery_days_max',
        'cash_on_delivery_enabled',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'shipping_fee' => 'decimal:2',
        'free_shipping_min' => 'decimal:2',
        'delivery_days_min' => 'integer',
        'delivery_days_max' => 'integer',
        'cash_on_delivery_enabled' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function getNameAttribute(): string
    {
        $district = trim((string) $this->district);

        return $district !== '' ? "{$this->city} / {$district}" : (string) $this->city;
    }
}
