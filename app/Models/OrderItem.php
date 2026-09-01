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
        'product_name',
        'product_sku',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    // 🔗 Product relation
    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // 🔗 Order relation
    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * What was sold, for showing on an order.
     *
     * The catalogue answers first while the part is still in it, because that
     * name is translated and the snapshot is not. Once the product is gone the
     * line's own copy takes over, so an old order still names what was bought
     * instead of going blank.
     */
    public function soldName(): string
    {
        $live = trim((string) ($this->product?->name ?? ''));

        if ($live !== '') {
            return $live;
        }

        $recorded = trim((string) $this->product_name);

        return $recorded !== '' ? $recorded : __('Deleted product');
    }

    /**
     * The code the part was sold under, or an empty string when there is none.
     */
    public function soldSku(): string
    {
        $live = trim((string) ($this->product?->sku ?? ''));

        if ($live !== '') {
            return $live;
        }

        return trim((string) $this->product_sku);
    }
}
