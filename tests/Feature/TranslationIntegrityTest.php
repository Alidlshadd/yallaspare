<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Checks the language files for damage rather than for gaps.
 *
 * TranslationCoverageTest already answers "is every key there". These answer
 * "does what is there make sense", which is a different failure: a translation
 * can be present, well-formed JSON and completely wrong. `category_name` was
 * translated into Arabic as `dealer_price` and into Kurdish as a half word
 * glued to `is_active`, and a sign-in warning kept a stray "I" from the English
 * "If" welded onto the next Kurdish word. None of that shows up as missing.
 */
class TranslationIntegrityTest extends TestCase
{
    private const LOCALES = ['en', 'ar', 'ku'];

    /** Arabic and Kurdish letters, without the punctuation that shares their block. */
    private const SCRIPT = '\x{0621}-\x{063A}\x{0641}-\x{064A}\x{0671}-\x{06D3}\x{06D5}\x{06EE}\x{06EF}\x{06FA}-\x{06FC}';

    public function test_a_column_name_still_names_that_column_after_translation(): void
    {
        // Keys whose English side is a bare identifier are column and field
        // names shown to an administrator. A translation may add a label around
        // one, but it cannot quietly turn category_name into dealer_price.
        $english = $this->lines('en');
        $offenders = [];

        foreach ($english as $key => $value) {
            if (preg_match('/^[a-z][a-z0-9]*(_[a-z0-9]+)+$/', (string) $value) !== 1) {
                continue;
            }

            foreach (['ar', 'ku'] as $locale) {
                $translated = (string) ($this->lines($locale)[$key] ?? '');

                if (! str_contains($translated, (string) $value)) {
                    $offenders[] = "{$locale}: “{$key}” became “{$translated}”, which no longer names “{$value}”";
                }
            }
        }

        $this->assertSame([], $offenders, implode("\n", $offenders));
    }

    public function test_no_translation_carries_a_stray_english_fragment(): void
    {
        // One or two Latin letters welded to an Arabic or Kurdish word is not a
        // word — it is the tail of an English one that survived an edit. Brand
        // names and acronyms are longer than two letters, so they do not trip
        // this.
        $offenders = [];

        foreach (['ar', 'ku'] as $locale) {
            foreach ($this->lines($locale) as $key => $value) {
                // The literal \n an alert carries is escape text, not a word.
                // Leaving it in both hides a real "I" welded to the word after
                // it and reports every harmless line break as a fragment.
                $text = str_replace(['\n', '\r', '\t'], ' ', (string) $value);

                $matches = [];
                preg_match_all(
                    '/(?<![A-Za-z])([A-Za-z]{1,2})['.self::SCRIPT.']/u',
                    $text,
                    $matches
                );

                foreach ($matches[1] ?? [] as $fragment) {
                    $offenders[] = "{$locale}: “{$key}” contains a stray “{$fragment}” glued to the text";
                }
            }
        }

        $this->assertSame([], $offenders, implode("\n", $offenders));
    }

    public function test_no_translation_is_blank(): void
    {
        foreach (self::LOCALES as $locale) {
            foreach ($this->lines($locale) as $key => $value) {
                $this->assertNotSame('', trim((string) $value), "{$locale}: “{$key}” has no text at all.");
            }
        }
    }

    public function test_every_placeholder_survives_translation(): void
    {
        // A dropped :count is invisible until the sentence renders without its
        // number.
        $offenders = [];

        foreach ($this->lines('en') as $key => $value) {
            preg_match_all('/:[A-Za-z_][A-Za-z0-9_]*/', (string) $key, $wanted);

            if ($wanted[0] === []) {
                continue;
            }

            foreach (['ar', 'ku'] as $locale) {
                $translated = (string) ($this->lines($locale)[$key] ?? '');

                foreach (array_unique($wanted[0]) as $placeholder) {
                    if (! str_contains($translated, $placeholder)) {
                        $offenders[] = "{$locale}: “{$key}” lost {$placeholder}";
                    }
                }
            }
        }

        $this->assertSame([], $offenders, implode("\n", $offenders));
    }

    /**
     * @return array<string, string>
     */
    private function lines(string $locale): array
    {
        static $cache = [];

        if (! isset($cache[$locale])) {
            $decoded = json_decode((string) file_get_contents(base_path("lang/{$locale}.json")), true);

            $this->assertIsArray($decoded, "lang/{$locale}.json is not valid JSON.");

            $cache[$locale] = $decoded;
        }

        return $cache[$locale];
    }
}
