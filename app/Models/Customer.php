<?php

namespace App\Models;

use App\Support\IraqiPhoneNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Someone the shop sells to without a site account: a walk-in, a phone order,
 * a workshop. A directory entry only — it cannot sign in and is never sent
 * anything automatically.
 */
class Customer extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'whatsapp',
        'city',
        'address',
        'notes',
        'created_by',
    ];

    /** @return HasMany<ManualInvoice, $this> */
    public function invoices(): HasMany
    {
        return $this->hasMany(ManualInvoice::class);
    }

    /**
     * The row holding this number, however it was typed.
     */
    public static function findByPhone(mixed $phone): ?self
    {
        $e164 = IraqiPhoneNumber::toE164($phone);

        return $e164 === null ? null : self::query()->where('phone', $e164)->first();
    }

    /** The number WhatsApp should open: its own if given, otherwise the phone. */
    public function whatsappNumber(): string
    {
        return (string) ($this->whatsapp ?: $this->phone);
    }
}
