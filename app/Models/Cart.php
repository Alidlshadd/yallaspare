<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Cart extends Model
{
    protected $fillable = ['user_id', 'session_token'];

    protected $casts = [
        'reminder_stage' => 'integer',
        'reminded_at' => 'datetime',
    ];

    /**
     * True for a cart that belongs to a browser rather than to an account.
     */
    public function isGuestCart(): bool
    {
        return $this->user_id === null;
    }

    /**
     * When the customer last touched this cart.
     *
     * Deliberately not `updated_at`: a cart row is created once and then sits
     * still while its items come and go, so its own timestamp says nothing
     * about the customer. The newest item timestamp does.
     *
     * Reads from the loaded items when they are there — the reminder run has
     * them loaded already for the message it is about to write.
     */
    public function lastActivityAt(): ?Carbon
    {
        $latest = $this->relationLoaded('items')
            ? $this->items->max('updated_at')
            : $this->items()->max('updated_at');

        return $latest === null ? null : Carbon::parse($latest);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<CartItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }
}
