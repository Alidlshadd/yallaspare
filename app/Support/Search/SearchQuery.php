<?php

namespace App\Support\Search;

/**
 * What a shopper actually typed, taken apart.
 *
 * The search used to hand the whole box of text to a LIKE, so "ssangyong
 * rexton" needed one column containing that exact phrase — and none does. The
 * brand lives on the vehicle brand, the model on the variant, and a year in a
 * range rather than as text anywhere. Splitting the query is what lets each
 * word be answered by whichever part of the catalogue knows about it.
 *
 * Nothing here touches the database. It is a value object over a string.
 */
final class SearchQuery
{
    /** Long enough for a part number and a car; past that it is not a search. */
    public const MAX_LENGTH = 120;

    /** Enough words for "ssangyong rexton 2024 oil filter" and a little more. */
    public const MAX_TOKENS = 8;

    /** Cars exist from about here, and next year's models are already listed. */
    private const EARLIEST_YEAR = 1950;

    /**
     * @param  list<Token>  $tokens
     */
    private function __construct(
        public readonly string $raw,
        public readonly string $normalized,
        public readonly array $tokens,
    ) {}

    public static function parse(?string $raw): self
    {
        $raw = (string) $raw;

        // Control characters, then case, then runs of whitespace.
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $raw) ?? '';
        $clean = mb_strtolower(trim($clean), 'UTF-8');
        $clean = preg_replace('/\s+/u', ' ', $clean) ?? '';
        $clean = mb_substr($clean, 0, self::MAX_LENGTH);

        if ($clean === '') {
            return new self($raw, '', []);
        }

        $latestYear = (int) date('Y') + 2;
        $tokens = [];

        foreach (explode(' ', $clean) as $word) {
            $word = trim($word, " \t\n\r\0\x0B.,;:!?()[]{}\"'");

            if ($word === '' || count($tokens) >= self::MAX_TOKENS) {
                continue;
            }

            $year = null;
            if (preg_match('/^\d{4}$/', $word) === 1) {
                $candidate = (int) $word;
                if ($candidate >= self::EARLIEST_YEAR && $candidate <= $latestYear) {
                    $year = $candidate;
                }
            }

            $tokens[] = new Token($word, BrandAliases::variantsFor($word), $year);
        }

        return new self($raw, $clean, $tokens);
    }

    public function isEmpty(): bool
    {
        return $this->tokens === [];
    }

    /**
     * Every four-digit token that could be a model year.
     *
     * @return list<int>
     */
    public function years(): array
    {
        return array_values(array_filter(array_map(
            static fn (Token $token): ?int => $token->year,
            $this->tokens
        )));
    }

    /**
     * The words that are not years — what a "did you mean" is built from.
     *
     * @return list<string>
     */
    public function words(): array
    {
        return array_values(array_map(
            static fn (Token $token): string => $token->text,
            array_filter($this->tokens, static fn (Token $token): bool => $token->year === null)
        ));
    }

    /**
     * The query with one word swapped, for a suggestion the shopper can click.
     */
    public function withWordReplaced(string $word, string $replacement): string
    {
        return trim((string) preg_replace(
            '/\b'.preg_quote($word, '/').'\b/iu',
            $replacement,
            $this->normalized
        ));
    }

    /**
     * A single-token query is the shape where an exact-match ordering matters:
     * one SKU, one OEM number, one part name.
     */
    public function isSingleToken(): bool
    {
        return count($this->tokens) === 1;
    }

    public function firstToken(): ?Token
    {
        return $this->tokens[0] ?? null;
    }

    /**
     * The query with punctuation dropped: "SY-1721840025" and "SY1721840025"
     * are the same part number to a person and different strings to a LIKE.
     */
    public static function bare(string $value): string
    {
        return (string) preg_replace('/[^\p{L}\p{N}]+/u', '', $value);
    }

    public function __toString(): string
    {
        return $this->normalized;
    }
}
