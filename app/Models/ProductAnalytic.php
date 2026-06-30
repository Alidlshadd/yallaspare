<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAnalytic extends Model
{
    use HasFactory;

    protected $table = 'product_analytics';

    protected $fillable = [
        'product_id', 'views_count', 'add_to_cart_count',
        'wishlist_count', 'last_viewed_at',
    ];

    protected $casts = [
        'last_viewed_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
