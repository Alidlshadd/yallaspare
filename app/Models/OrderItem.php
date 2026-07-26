<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    // 🔗 Product relation
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // 🔗 Order relation
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
