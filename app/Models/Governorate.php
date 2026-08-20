<?php

namespace App\Models;

use App\Support\LocalizedText;
use Database\Seeders\GovernorateSeeder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * An Iraqi governorate, carrying what shipping needs to know about it.
 *
 * @property int $id
 * @property string $code
 * @property string $name_en
 * @property string $name_ar
 * @property string $name_ku
 * @property int $delivery_days
 * @property int $shipping_fee
 * @property int $sort_order
 */
class Governorate extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name_en',
        'name_ar',
        'name_ku',
        'delivery_days',
        'shipping_fee',
        'sort_order',
    ];

    protected $casts = [
        'delivery_days' => 'integer',
        'shipping_fee' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * The name in the reader's language, falling back to English rather than
     * rendering an empty cell.
     *
     * VehicleLocalization does this job for the vehicle catalogue, but it
     * cannot be borrowed here: it reads a `name` attribute as one of its
     * fallbacks, which the accessor below would answer, and the two would
     * call each other forever.
     */
    public function localizedName(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();

        $field = match (true) {
            str_starts_with($locale, 'ar') => 'name_ar',
            str_starts_with($locale, 'ku') => 'name_ku',
            default => 'name_en',
        };

        return LocalizedText::first($this->getAttribute($field), $this->name_en);
    }

    public function getNameAttribute(): string
    {
        return $this->localizedName();
    }

    /**
     * True for the governorates that ship with the application. Their names
     * come from the seeder and a deploy puts them back, so the panel neither
     * renames nor removes them — only the days and the fee are the operator's.
     */
    public function isStandard(): bool
    {
        return in_array($this->code, GovernorateSeeder::codes(), true);
    }

    /**
     * @param  Builder<Governorate>  $query
     * @return Builder<Governorate>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
