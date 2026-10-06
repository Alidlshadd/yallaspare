<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualInvoiceItem extends Model
{
    protected $fillable = [
        'manual_invoice_id',
        'product_id',
        'description',
        'sku',
        'quantity',
        'unit_price',
        'line_total',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'float',
        'line_total' => 'float',
        'sort_order' => 'integer',
    ];

    /** @return BelongsTo<ManualInvoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(ManualInvoice::class, 'manual_invoice_id');
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
