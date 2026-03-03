<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Product extends Model {
    use HasFactory, LogsActivity;

    protected $fillable = [
        'category_id','name_en','name_ar','name_ku',
        'description_en','description_ar','description_ku',
        'price','dealer_price','stock_quantity','sku','brand',
        'compatible_models','image','is_active','low_stock_threshold'
    ];

    protected $casts = [
        'compatible_models' => 'array',
        'price' => 'decimal:2',
        'dealer_price' => 'decimal:2',
        'low_stock_threshold' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function category() {
        return $this->belongsTo(Category::class);
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function scopeLowStock(Builder $query): Builder
    {
        $globalThresholdSubquery = DB::table('settings')
            ->selectRaw('CAST(value AS UNSIGNED)')
            ->where('key', 'low_stock_threshold')
            ->limit(1);

        return $query
            ->where('is_active', true)
            ->whereRaw(
                'stock_quantity <= COALESCE(low_stock_threshold, (' . $globalThresholdSubquery->toSql() . '), 0)',
                $globalThresholdSubquery->getBindings()
            );
    }

    public function priceFor(?User $user = null): float
    {
        $basePrice = (float) $this->price;

        if (!$user || !$user->isDealer()) {
            return $basePrice;
        }

        if ($user->dealer_status !== User::DEALER_STATUS_ACTIVE) {
            return $basePrice;
        }

        if ($this->dealer_price !== null) {
            return (float) $this->dealer_price;
        }

        $discount = max(0, min((float) $user->dealer_discount, 100));
        if ($discount <= 0) {
            return $basePrice;
        }

        return round($basePrice * (1 - ($discount / 100)), 2);
    }
}
