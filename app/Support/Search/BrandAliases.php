<?php

namespace App\Support\Search;

/**
 * The other names a marque is sold under.
 *
 * SsangYong became KGM, and shoppers type both — as well as "ssang yong" with
 * the space. A search for one has to find cars recorded under the other, and
 * this is the one place that knows so; nothing downstream rewrites a product's
 * actual brand, it only widens what a word will match.
 *
 * The groups live in config/vehicles.php so a new marque needs no code. Moving
 * them into a table an administrator can edit is the natural next step, and the
 * shape here does not change when that happens.
 */
final class BrandAliases
{
    /** @var array<string, list<string>>|null */
    private static ?array $index = null;

    /**
     * Every spelling the given word stands for, itself always included.
     *
     * @return list<string>
     */
    public static function variantsFor(string $word): array
    {
        $key = self::key($word);

        if ($key === '') {
            return [$word];
        }

        $variants = self::index()[$key] ?? [];

        // The typed word first: it is the one the shopper will recognise in a
        // highlighted result, and the extras only widen the net behind it.
        return array_values(array_unique(array_merge([$word], $variants)));
    }

    /**
     * Every name in every group, for the "did you mean" dictionary.
     *
     * @return list<string>
     */
    public static function allNames(): array
    {
        $names = [];

        foreach (self::groups() as $group) {
            foreach ($group as $name) {
                $names[] = $name;
            }
        }

        return array_values(array_unique($names));
    }

    public static function flush(): void
    {
        self::$index = null;
    }

    /**
     * Lower-cased and stripped of spacing and punctuation, so "Ssang Yong",
     * "ssangyong" and "SSANG-YONG" are one key.
     */
    private static function key(string $value): string
    {
        return mb_strtolower((string) preg_replace('/[^\p{L}\p{N}]+/u', '', $value), 'UTF-8');
    }

    /**
     * @return array<string, list<string>>
     */
    private static function index(): array
    {
        if (self::$index !== null) {
            return self::$index;
        }

        $index = [];

        foreach (self::groups() as $group) {
            foreach ($group as $name) {
                $key = self::key($name);

                if ($key === '') {
                    continue;
                }

                // A name maps to the whole group, itself included: matching the
                // spelling as typed still has to work.
                $index[$key] = array_values(array_unique(array_merge(
                    $index[$key] ?? [],
                    array_map(mb_strtolower(...), $group)
                )));
            }
        }

        return self::$index = $index;
    }

    /**
     * @return list<list<string>>
     */
    private static function groups(): array
    {
        /** @var list<list<string>> $groups */
        $groups = (array) config('vehicles.brand_aliases', []);

        return array_values(array_filter(
            array_map(
                static fn ($group): array => array_values(array_filter(
                    array_map(static fn ($name): string => trim((string) $name), (array) $group),
                    static fn (string $name): bool => $name !== '',
                )),
                $groups
            ),
            static fn (array $group): bool => count($group) > 1,
        ));
    }
}
