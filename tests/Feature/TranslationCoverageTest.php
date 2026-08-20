<?php

namespace Tests\Feature;

use Tests\TestCase;

class TranslationCoverageTest extends TestCase
{
    /**
     * Every string passed to __() has to exist in the language files. A missing
     * entry is invisible in English — the key is the English text, so it renders
     * correctly — and only shows up as an untranslated word once the site is
     * read in Arabic or Kurdish.
     */
    public function test_every_translated_string_has_an_entry(): void
    {
        $keys = array_keys($this->jsonKeys());
        $groups = $this->phpGroups();
        $missing = [];

        foreach ($this->translatableFiles() as $file) {
            $contents = file_get_contents($file);
            preg_match_all('/__\(\s*([\'"])(.+?)\1/s', $contents, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $string = $match[2];

                // Interpolated or package-namespaced keys resolve elsewhere.
                if (str_starts_with($string, ':') || str_contains($string, '::')) {
                    continue;
                }

                // A dotted key is answered by lang/{locale}/{group}.php, never
                // by the JSON files.
                if (str_contains($string, '.') && in_array(explode('.', $string)[0], $groups, true)) {
                    continue;
                }

                if (! in_array($string, $keys, true)) {
                    $missing[$string] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file);
                }
            }
        }

        $this->assertSame([], $missing, sprintf(
            "%d string(s) are passed to __() but have no entry in lang/en.json:\n%s",
            count($missing),
            collect($missing)->map(fn ($file, $string) => "  {$string}  ({$file})")->implode("\n")
        ));
    }

    /**
     * The three locales have to answer the same set of keys, or a page renders
     * partly in English depending on which strings happen to be missing.
     */
    public function test_the_locales_carry_the_same_keys(): void
    {
        $english = $this->jsonKeys('en');

        foreach (['ar', 'ku'] as $locale) {
            $translated = $this->jsonKeys($locale);

            $this->assertSame(
                [],
                array_keys(array_diff_key($english, $translated)),
                "lang/{$locale}.json is missing keys that lang/en.json has."
            );
            $this->assertSame(
                [],
                array_keys(array_diff_key($translated, $english)),
                "lang/{$locale}.json has keys that lang/en.json does not."
            );
        }
    }

    /**
     * @return array<string, string>
     */
    private function jsonKeys(string $locale = 'en'): array
    {
        return json_decode(file_get_contents(base_path("lang/{$locale}.json")), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<int, string>
     */
    private function phpGroups(): array
    {
        return array_map(
            fn (string $path) => pathinfo($path, PATHINFO_FILENAME),
            glob(base_path('lang/en/*.php')) ?: []
        );
    }

    /**
     * @return array<int, string>
     */
    private function translatableFiles(): array
    {
        $files = [];

        foreach ([base_path('resources/views'), base_path('app')] as $root) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));

            foreach ($iterator as $file) {
                if ($file->isFile() && in_array($file->getExtension(), ['php'], true)) {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }
}
