<?php

namespace App\Models;

use App\Models\Concerns\FlushesVehicleFilterCache;
use App\Support\VehicleLocalization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class VehicleModel extends Model
{
    use FlushesVehicleFilterCache;
    use HasFactory;

    protected $fillable = [
        'vehicle_brand_id',
        'vehicle_model_family_id',
        'name',
        'name_en',
        'name_ar',
        'name_ku',
        'slug',
        'production_start_year',
        'production_end_year',
        'image_path',
    ];

    /** @return BelongsTo<VehicleBrand, $this> */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(VehicleBrand::class, 'vehicle_brand_id');
    }

    /** @return BelongsTo<VehicleModelFamily, $this> */
    public function family(): BelongsTo
    {
        return $this->belongsTo(VehicleModelFamily::class, 'vehicle_model_family_id');
    }

    /** @return HasMany<VehicleModelEngineType, $this> */
    public function engineTypes(): HasMany
    {
        return $this->hasMany(VehicleModelEngineType::class)->orderBy('name');
    }

    /** @return HasMany<ProductVehicleFitment, $this> */
    public function fitments(): HasMany
    {
        return $this->hasMany(ProductVehicleFitment::class, 'vehicle_model_id');
    }

    public function localizedName(?string $locale = null): string
    {
        return VehicleLocalization::name($this, $locale);
    }

    public function getLocalizedNameAttribute(): string
    {
        return $this->localizedName();
    }

    /**
     * The name with the years it was built, for lists an administrator or a
     * customer has to choose from.
     *
     * Two variants may share a name — a Tivoli built 2015-2019 and one built
     * 2020-2023 are different cars taking different parts — so the years are
     * what tells them apart in a dropdown. A variant with no years recorded
     * simply reads as its name.
     */
    public function listLabel(?string $locale = null): string
    {
        $name = $this->localizedName($locale);
        $years = $this->productionYears();

        return $years === null ? $name : $name.' — '.$years;
    }

    /**
     * What makes this variant a different car from the next one.
     *
     * A name alone is not enough — a Tivoli built 2015-2019 and one built
     * 2020-2023 share a name and take different parts — and an engine is not
     * part of it at all: a 1.6 petrol Tivoli and a 1.6 diesel Tivoli of the
     * same years are one car with two engines, not two cars. Two rows that
     * agree on everything below are the same vehicle recorded twice.
     */
    public function identityKey(): string
    {
        return implode('|', [
            (int) $this->vehicle_brand_id,
            (int) $this->vehicle_model_family_id,
            self::normalizeName((string) ($this->name_en ?: $this->name)),
            (int) $this->production_start_year,
            (int) $this->production_end_year,
        ]);
    }

    /**
     * Spelling differences that do not make a different car.
     */
    public static function normalizeName(string $name): string
    {
        return (string) preg_replace('/\s+/u', ' ', mb_strtolower(trim($name)));
    }

    /**
     * Both bounds recorded.
     *
     * Nothing is merged on a partial identity. A variant with no years may be
     * the same car as one with years, or a different generation nobody has
     * filled in yet, and guessing between those would silently rewrite what a
     * part is sold as fitting.
     */
    public function hasCompleteYears(): bool
    {
        return (int) $this->production_start_year > 0 && (int) $this->production_end_year > 0;
    }

    /**
     * The sibling that already records this exact car, if there is one.
     */
    public static function findByIdentity(
        int $brandId,
        ?int $familyId,
        string $name,
        ?int $startYear,
        ?int $endYear,
        ?int $ignoreId = null,
    ): ?self {
        if (! $startYear || ! $endYear) {
            return null;
        }

        return self::query()
            ->where('vehicle_brand_id', $brandId)
            ->where('vehicle_model_family_id', $familyId)
            ->where('production_start_year', $startYear)
            ->where('production_end_year', $endYear)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->get(['id', 'name', 'name_en', 'production_start_year', 'production_end_year'])
            ->first(fn (self $model) => self::normalizeName((string) ($model->name_en ?: $model->name)) === self::normalizeName($name));
    }

    /**
     * "2015–2019", "2020–" or null when nothing is recorded.
     */
    public function productionYears(): ?string
    {
        $from = $this->production_start_year ? (int) $this->production_start_year : null;
        $to = $this->production_end_year ? (int) $this->production_end_year : null;

        return match (true) {
            $from !== null && $to !== null && $from === $to => (string) $from,
            $from !== null && $to !== null => $from.'–'.$to,
            $from !== null => $from.'–',
            $to !== null => '–'.$to,
            default => null,
        };
    }

    /**
     * The engines recorded for this variant, as an operator would read them.
     *
     * Nothing is queried: a caller that did not eager load engineTypes gets an
     * empty list rather than one query per row in a dropdown.
     *
     * @return list<string>
     */
    public function engineLabels(?string $locale = null): array
    {
        if (! $this->relationLoaded('engineTypes')) {
            return [];
        }

        return $this->engineTypes
            ->map(fn (VehicleModelEngineType $engine): string => $engine->localizedName($locale))
            ->filter(fn (string $label): bool => trim($label) !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * The full identity of the variant, for somewhere with room to print it:
     *
     *   "Tivoli • 2015–2019 • 1.6 Petrol / 1.6 Turbo Diesel"
     *
     * Two variants may legitimately share a name — that is the whole point of
     * the variant table — so a dropdown that prints only the name leaves an
     * operator choosing between two identical lines.
     */
    public function selectionLabel(?string $locale = null): string
    {
        $parts = [$this->localizedName($locale)];

        $years = $this->productionYears();
        if ($years !== null) {
            $parts[] = $years;
        }

        $engines = $this->engineLabels($locale);
        if ($engines !== []) {
            $parts[] = implode(' / ', $engines);
        }

        return implode(' • ', $parts);
    }

    /**
     * The same identity, short enough for a narrow control:
     *
     *   "Tivoli (2015–2019) — 1.6 Petrol +1"
     *
     * A native select truncates its own text on a phone, and there is no
     * per-breakpoint option label to swap in, so this is what the dropdown
     * shows on every screen. The trailing count is what keeps it honest: the
     * other engines are summarised, never hidden, and the summary beside the
     * control names them in full.
     */
    public function shortSelectionLabel(?string $locale = null): string
    {
        $label = $this->localizedName($locale);

        $years = $this->productionYears();
        if ($years !== null) {
            $label .= ' ('.$years.')';
        }

        $engines = $this->engineLabels($locale);
        if ($engines === []) {
            return $label;
        }

        $label .= ' — '.$engines[0];
        $remaining = count($engines) - 1;

        return $remaining > 0 ? $label.' +'.$remaining : $label;
    }

    /**
     * Lower-cased text a filter box can match a variant against: its name in
     * every language it has one, its years, and its engines — so "2019",
     * "diesel" and "1.6" all find the right row.
     */
    public function selectionHaystack(?string $locale = null): string
    {
        $parts = array_filter([
            (string) $this->name,
            (string) $this->name_en,
            (string) $this->name_ar,
            (string) $this->name_ku,
            (string) $this->localizedName($locale),
            (string) $this->production_start_year,
            (string) $this->production_end_year,
            implode(' ', $this->engineLabels($locale)),
            $this->relationLoaded('engineTypes')
                ? $this->engineTypes->map(fn (VehicleModelEngineType $engine): string => trim($engine->name.' '.$engine->fuel_type.' '.$engine->engine_size))->implode(' ')
                : '',
        ], fn (string $part): bool => trim($part) !== '');

        return mb_strtolower(implode(' ', $parts));
    }

    /**
     * One option in the storefront vehicle finder, as a two-line entry.
     *
     * The engines are passed in rather than read off the relation, because the
     * storefront hides fuel types the shop does not sell. The line under the
     * name must never advertise an engine the engine dropdown will refuse to
     * offer, so both are built from the same filtered collection.
     *
     * @param  Collection<int, VehicleModelEngineType>  $engines
     * @return array{value: string, label: string, primary: string, secondary: string}
     */
    public function finderOption(Collection $engines, ?string $locale = null): array
    {
        $engineLabels = $engines
            ->map(fn (VehicleModelEngineType $engine): string => $engine->localizedName($locale))
            ->filter(fn (string $label): bool => trim($label) !== '')
            ->unique()
            ->values();

        $secondary = [];

        $years = $this->productionYears();
        if ($years !== null) {
            $secondary[] = $years;
        }

        if ($engineLabels->isNotEmpty()) {
            $secondary[] = (string) $engineLabels->first();

            $remaining = $engineLabels->count() - 1;
            if ($remaining > 0) {
                $secondary[] = trans_choice('+:count engine|+:count engines', $remaining, ['count' => $remaining]);
            }
        }

        return [
            'value' => (string) $this->id,
            // The closed control shows this, so it has to carry the years: two
            // variants sharing a name are the reason this field exists.
            'label' => $this->listLabel($locale),
            'primary' => $this->localizedName($locale),
            'secondary' => implode(' · ', $secondary),
        ];
    }
}
