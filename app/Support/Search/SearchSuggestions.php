<?php

namespace App\Support\Search;

use App\Models\Category;
use App\Models\VehicleBrand;
use App\Models\VehicleModelFamily;
use App\Support\DbSchema;
use Illuminate\Support\Facades\Cache;

/**
 * "Did you mean Rexton?"
 *
 * A shopper who types "rextn" gets nothing, and nothing is not an answer. This
 * looks the mistyped word up against the names the catalogue actually holds —
 * marques, car families and categories — and offers the closest one back.
 *
 * The comparison never touches the products table. That dictionary is a few
 * hundred short names at most, so a Levenshtein pass over it costs nothing;
 * running one per product row would be a different matter entirely. Suggestions
 * are also never mixed into the results: an approximate match is offered, and
 * the shopper decides.
 */
final class SearchSuggestions
{
    /** A word has to be long enough for a distance to mean anything. */
    private const MIN_WORD_LENGTH = 4;

    /** One or two slips. Past that it is a different word, not a typo. */
    private const MAX_DISTANCE = 2;

    private const CACHE_KEY = 'search.suggestion_dictionary';

    private const CACHE_TTL = 600;

    /**
     * The closest thing the catalogue knows to what the shopper typed.
     *
     * @return array{word: string, suggestion: string, query: string}|null
     */
    public static function forQuery(SearchQuery $search): ?array
    {
        if ($search->isEmpty()) {
            return null;
        }

        $dictionary = self::dictionary();

        if ($dictionary === []) {
            return null;
        }

        $best = null;

        foreach ($search->words() as $word) {
            if (mb_strlen($word) < self::MIN_WORD_LENGTH) {
                continue;
            }

            foreach ($dictionary as $candidate) {
                $lower = mb_strtolower($candidate);

                // Already right: there is nothing to suggest.
                if ($lower === $word) {
                    return null;
                }

                // Distance is meaningless across very different lengths.
                if (abs(mb_strlen($lower) - mb_strlen($word)) > self::MAX_DISTANCE) {
                    continue;
                }

                $distance = levenshtein($word, $lower);

                if ($distance === 0 || $distance > self::MAX_DISTANCE) {
                    continue;
                }

                if ($best === null || $distance < $best['distance']) {
                    $best = [
                        'distance' => $distance,
                        'word' => $word,
                        'suggestion' => $candidate,
                    ];
                }
            }
        }

        if ($best === null) {
            return null;
        }

        return [
            'word' => $best['word'],
            'suggestion' => $best['suggestion'],
            'query' => $search->withWordReplaced($best['word'], $best['suggestion']),
        ];
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * The names worth comparing against: marques, car families and categories.
     *
     * Deliberately small. Product names are not in here — they are long, there
     * are thousands, and a typo in one is better answered by the search itself
     * than by a guess.
     *
     * @return list<string>
     */
    private static function dictionary(): array
    {
        /** @var list<string> $names */
        $names = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, static function (): array {
            $names = BrandAliases::allNames();

            if (DbSchema::hasTable('vehicle_brands')) {
                $names = array_merge($names, VehicleBrand::query()->pluck('name')->all());
            }

            if (DbSchema::hasTable('vehicle_model_families')) {
                $names = array_merge($names, VehicleModelFamily::query()->pluck('name')->all());
            }

            if (DbSchema::hasTable('categories')) {
                $names = array_merge($names, Category::query()->pluck('name_en')->all());
            }

            $clean = [];

            foreach ($names as $name) {
                $name = trim((string) $name);

                if ($name !== '' && mb_strlen($name) >= self::MIN_WORD_LENGTH) {
                    $clean[mb_strtolower($name)] = $name;
                }
            }

            return array_values($clean);
        });

        return $names;
    }
}
