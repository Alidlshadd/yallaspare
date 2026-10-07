<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One product's selling price, before and after one change.
 *
 * Written by every operation that sets a price on purpose — converting a
 * dinar price to dollars, an edit on the price page, a bulk change — and
 * never by a change of the exchange rate, which moves the dinar figure of
 * every dollar product at once without touching what any of them is priced
 * at. Rows are only ever added.
 */
class ProductPriceChange extends Model
{
    public const ACTION_CONVERSION = 'conversion';

    public const ACTION_EDIT = 'edit';

    public const ACTION_BULK = 'bulk';

    public const UPDATED_AT = null;

    protected $fillable = [
        'product_id', 'product_name', 'product_sku', 'batch_id', 'action',
        'old_currency', 'new_currency',
        'old_price_iqd', 'new_price_iqd', 'old_price_usd', 'new_price_usd',
        'old_dealer_price_iqd', 'new_dealer_price_iqd', 'old_dealer_price_usd', 'new_dealer_price_usd',
        'conversion_rate_per_100', 'selling_rate_per_100', 'note', 'user_id',
    ];

    protected $casts = [
        'old_price_iqd' => 'float',
        'new_price_iqd' => 'float',
        'old_dealer_price_iqd' => 'float',
        'new_dealer_price_iqd' => 'float',
        'old_price_usd' => 'decimal:4',
        'new_price_usd' => 'decimal:4',
        'old_dealer_price_usd' => 'decimal:4',
        'new_dealer_price_usd' => 'decimal:4',
        'conversion_rate_per_100' => 'decimal:2',
        'selling_rate_per_100' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
