<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'label',
        'country',
        'city',
        'address_line1',
        'address_line2',
        'phone',
        'is_default',
        'notes',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(function (self $address): void {
            if (! $address->is_default || ! $address->user_id) {
                return;
            }

            self::query()
                ->where('user_id', $address->user_id)
                ->whereKeyNot($address->getKey())
                ->update(['is_default' => false]);
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
