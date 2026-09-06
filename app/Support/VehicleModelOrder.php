<?php

namespace App\Support;

use App\Models\VehicleModel;
use Collator;
use Illuminate\Support\Collection;

/**
 * The order car variants are shown in, decided once.
 *
 * A dropdown listed in whatever order the rows were inserted asks the shopper
 * to read the whole list before answering "is my car here?" — and the finder
 * showed Rexton G4 above Tivoli above Kyron for exactly that reason: nothing
 * ordered it at all. Alphabetical is the only order a stranger can predict.
 *
 * The name compared is the one on screen, so the list reads A–Z in the locale
 * the shopper is actually in — `name_ar` in Arabic, `name_ku` in Kurdish — and
 * not by whatever the English column happens to hold. Where the intl extension
 * is present that comparison is a Collator, which is what puts Arabic letters
 * in Arabic order; without it the fallback is a case-insensitive natural
 * compare, which is still right for Latin names and no worse than nothing for
 * the rest.
 *
 * Two variants sharing a name are two different cars, so they are never merged:
 * they land next to each other and the years decide which comes first.
 */
final class VehicleModelOrder
{
    /** @var array<string, Collator|null> */
    private static array $collators = [];

    /**
     * @param  Collection<int, VehicleModel>  $models
     * @return Collection<int, VehicleModel>
     */
    public static function sort(Collection $models, ?string $locale = null): Collection
    {
        return $models
            ->sort(fn (VehicleModel $a, VehicleModel $b): int => self::compare($a, $b, $locale))
            ->values();
    }

    public static function compare(VehicleModel $a, VehicleModel $b, ?string $locale = null): int
    {
        $byName = self::compareNames($a->localizedName($locale), $b->localizedName($locale), $locale);

        if ($byName !== 0) {
            return $byName;
        }

        // Same name, so these are variants of one car. The older one first, and
        // a variant with no years recorded after the ones that have them —
        // an unknown year cannot be placed, only parked at the end.
        return [self::year($a->production_start_year), self::year($a->production_end_year), (int) $a->id]
            <=> [self::year($b->production_start_year), self::year($b->production_end_year), (int) $b->id];
    }

    private static function compareNames(string $first, string $second, ?string $locale): int
    {
        $first = self::normalize($first);
        $second = self::normalize($second);

        $collator = self::collator($locale);

        if ($collator instanceof Collator) {
            $result = $collator->compare($first, $second);

            if ($result !== false) {
                return $result <=> 0;
            }
        }

        // "Rexton G4" before "Rexton G10": a plain compare would read the 1 of
        // 10 and stop, so the numbers are compared as numbers either way.
        return strnatcasecmp($first, $second) <=> 0;
    }

    /**
     * Stray spacing is not part of a name. Neither is case: "tivoli" and
     * "Tivoli" are the same car recorded by two different hands.
     */
    private static function normalize(string $name): string
    {
        return trim(preg_replace('/\s+/u', ' ', $name) ?? $name);
    }

    private static function year(mixed $year): int
    {
        return $year === null || $year === '' ? PHP_INT_MAX : (int) $year;
    }

    private static function collator(?string $locale): ?Collator
    {
        $locale = VehicleLocalization::normalizedLocale($locale);

        if (array_key_exists($locale, self::$collators)) {
            return self::$collators[$locale];
        }

        if (! class_exists(Collator::class)) {
            return self::$collators[$locale] = null;
        }

        $collator = collator_create($locale) ?: null;

        if ($collator instanceof Collator) {
            // Case and accents are not what a shopper is sorting by, and "1.5"
            // has to sort under 1.5 rather than under the character "1".
            $collator->setStrength(Collator::PRIMARY);
            $collator->setAttribute(Collator::NUMERIC_COLLATION, Collator::ON);
        }

        return self::$collators[$locale] = $collator;
    }
}
