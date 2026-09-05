<?php

namespace App\Support\Search;

use Illuminate\Support\Str;

/**
 * One word of a search query, with the other spellings it stands for.
 */
final class Token
{
    /**
     * @param  list<string>  $variants  The word plus any brand aliases; always
     *                                  contains the word itself.
     * @param  int|null  $year  Set when the word is a plausible model year.
     */
    public function __construct(
        public readonly string $text,
        public readonly array $variants,
        public readonly ?int $year = null,
    ) {}

    /**
     * The variants with punctuation stripped, for matching part numbers stored
     * with or without their separators.
     *
     * @return list<string>
     */
    public function bareVariants(): array
    {
        $bare = [];

        foreach ($this->variants as $variant) {
            $stripped = SearchQuery::bare($variant);

            if ($stripped !== '' && $stripped !== $variant && ! in_array($stripped, $bare, true)) {
                $bare[] = $stripped;
            }
        }

        return $bare;
    }

    /**
     * Long enough and numeric enough to be a part number rather than a word.
     */
    public function looksLikePartNumber(): bool
    {
        return Str::of($this->text)->test('/\d/') && mb_strlen($this->text) >= 4;
    }
}
