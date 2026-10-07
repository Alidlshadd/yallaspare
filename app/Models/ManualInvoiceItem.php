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
        'usd_unit_price',
        'usd_rate_per_100',
        'unit_cost',
        'description_translations',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'float',
        'line_total' => 'float',
        'sort_order' => 'integer',
        'description_translations' => 'array',
    ];

    /**
     * What this line is called on a document written in the given language.
     *
     * A catalogue line carries the product's name in each language, copied
     * when the line was saved, and answers with the right one. A line typed
     * or reworded by hand has no translations and reads as written.
     */
    public function descriptionFor(string $locale): string
    {
        $key = match (true) {
            str_starts_with($locale, 'ar') => 'ar',
            str_starts_with($locale, 'ku') => 'ku',
            default => 'en',
        };

        $translated = trim((string) (($this->description_translations ?? [])[$key] ?? ''));

        return $translated !== '' ? $translated : (string) $this->description;
    }

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
