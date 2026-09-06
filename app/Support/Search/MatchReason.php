<?php

namespace App\Support\Search;

use App\Models\Product;

/**
 * Why this product is in the list.
 *
 * A suggestion panel that cannot say why it is showing something is asking the
 * shopper to trust it. "Exact SKU match" and "Compatible vehicle match" are the
 * difference between a list and an answer.
 *
 * The reasons are read off the row that has already been fetched, using the
 * same comparisons the relevance ordering ranks by — so the caption and the
 * position in the list can never disagree, and neither costs a query. A product
 * that matched for several reasons reports the strongest true one, once.
 */
final class MatchReason
{
    public const SKU_EXACT = 'sku_exact';

    public const OEM_EXACT = 'oem_exact';

    public const PART_NUMBER_EXACT = 'part_number_exact';

    public const SKU_PREFIX = 'sku_prefix';

    public const OEM_PREFIX = 'oem_prefix';

    public const NAME = 'name';

    public const VEHICLE_PART = 'vehicle_part_match';

    public const FITMENT = 'fitment';

    private function __construct(
        public readonly string $type,
    ) {}

    /**
     * @param  list<int>  $matchedCategoryIds  Categories the query itself named.
     */
    public static function for(
        Product $product,
        SearchQuery $search,
        ?SearchInterpretation $interpretation = null,
        array $matchedCategoryIds = [],
    ): self {
        $phrase = $search->normalized;

        if ($phrase === '') {
            return new self(self::NAME);
        }

        $bare = SearchQuery::bare($phrase);

        // 1-2: the shopper knows the number. Same ladder as the ordering, so
        // the top row's caption matches the reason it is on top.
        if (self::equals($product->sku, $phrase, $bare)) {
            return new self(self::SKU_EXACT);
        }

        if (self::equals($product->oem_number, $phrase, $bare)) {
            return new self(self::OEM_EXACT);
        }

        if (self::equals($product->part_number, $phrase, $bare)) {
            return new self(self::PART_NUMBER_EXACT);
        }

        // 3-5: the name, whole, begun or contained. Above the partial
        // identifier below it, because that is the order the relevance ladder
        // itself ranks by — a caption that outranked the sort would be telling
        // the shopper a different story from the one the list is telling.
        foreach ([$product->name_en, $product->name_ar, $product->name_ku] as $name) {
            if (self::contains($name, $phrase)) {
                return new self(self::NAME);
            }
        }

        // The number, but only the beginning of it — still an identifier match,
        // and worth saying so rather than calling it a compatible vehicle.
        if ($search->isSingleToken() && $search->firstToken()?->looksLikePartNumber()) {
            if (self::startsWith($product->sku, $bare)) {
                return new self(self::SKU_PREFIX);
            }

            if (self::startsWith($product->oem_number, $bare) || self::startsWith($product->part_number, $bare)) {
                return new self(self::OEM_PREFIX);
            }
        }

        // Half the query answered by the part, the other half by the car it
        // fits. "camry filter" is not a vehicle match — the shopper named a
        // part type too, and a caption that mentions only the car makes the
        // list look like it ignored half of what was typed.
        if ($interpretation !== null && self::isVehicleAndPart($product, $search, $interpretation, $matchedCategoryIds)) {
            return new self(self::VEHICLE_PART);
        }

        // Nothing on the row itself answered the query as typed, so what got
        // this product here was the car it is recorded as fitting.
        return new self(self::FITMENT);
    }

    /**
     * Does the query split cleanly into a part word and a vehicle word?
     *
     * Both halves have to be real: a word that appears on the product itself
     * (its name, or the category the query also named) and a *different* word
     * the catalogue recognised as a car, a marque, a year, an engine or a fuel.
     * A single word cannot be both, and a vehicle word the interpretation never
     * resolved is not a vehicle word at all.
     *
     * Reads the loaded row and the interpretation already in hand. No queries,
     * and no effect on which products come back or in what order.
     *
     * @param  list<int>  $matchedCategoryIds
     */
    private static function isVehicleAndPart(
        Product $product,
        SearchQuery $search,
        SearchInterpretation $interpretation,
        array $matchedCategoryIds,
    ): bool {
        if (! $interpretation->hasVehicle() && $interpretation->brand === null) {
            return false;
        }

        $vehicleWords = $interpretation->vehicleWordsUsed();

        if ($vehicleWords === []) {
            return false;
        }

        $inCategory = $matchedCategoryIds !== []
            && $product->category_id !== null
            && in_array((int) $product->category_id, $matchedCategoryIds, true);

        foreach ($search->tokens as $token) {
            // The year is never the part half of the query.
            if ($token->year !== null || in_array($token->text, $vehicleWords, true)) {
                continue;
            }

            $onProduct = self::contains($product->name_en, $token->text)
                || self::contains($product->name_ar, $token->text)
                || self::contains($product->name_ku, $token->text);

            if ($onProduct || $inCategory) {
                return true;
            }
        }

        return false;
    }

    /**
     * The label a shopper reads, in the locale of the request.
     */
    public function label(): string
    {
        return match ($this->type) {
            self::SKU_EXACT => __('Exact SKU match'),
            self::OEM_EXACT, self::PART_NUMBER_EXACT => __('Exact OEM match'),
            self::SKU_PREFIX => __('SKU match'),
            self::OEM_PREFIX => __('OEM match'),
            self::NAME => __('Name match'),
            self::VEHICLE_PART => __('Vehicle and part match'),
            default => __('Compatible vehicle match'),
        };
    }

    /**
     * Whether the "fits" line already says everything this caption would.
     *
     * A row captioned "Compatible vehicle match" above a line reading
     * "Fits: Camry · 2022–2026" is telling the shopper the same thing twice.
     * A combined match is not redundant — the caption carries the half the
     * fits line cannot.
     */
    public function isRedundantBeside(?string $fitsLabel): bool
    {
        return $this->type === self::FITMENT && $fitsLabel !== null && $fitsLabel !== '';
    }

    public function isExact(): bool
    {
        return in_array($this->type, [self::SKU_EXACT, self::OEM_EXACT, self::PART_NUMBER_EXACT], true);
    }

    /**
     * @return array{type: string, label: string}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'label' => $this->label(),
        ];
    }

    private static function equals(mixed $value, string $phrase, string $bare): bool
    {
        $value = trim((string) $value);

        if ($value === '') {
            return false;
        }

        $lower = mb_strtolower($value);

        // "SY-1721840025" and "SY1721840025" are one number to a person, which
        // is exactly the equivalence the search itself already matches on.
        return $lower === $phrase
            || ($bare !== '' && mb_strtolower(SearchQuery::bare($lower)) === $bare);
    }

    private static function startsWith(mixed $value, string $bare): bool
    {
        $value = trim((string) $value);

        if ($value === '' || $bare === '') {
            return false;
        }

        return str_starts_with(mb_strtolower(SearchQuery::bare($value)), $bare);
    }

    private static function contains(mixed $value, string $phrase): bool
    {
        $value = trim((string) $value);

        return $value !== '' && str_contains(mb_strtolower($value), $phrase);
    }
}
