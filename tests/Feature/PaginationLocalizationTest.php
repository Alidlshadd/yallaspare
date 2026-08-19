<?php

namespace Tests\Feature;

use Tests\TestCase;

class PaginationLocalizationTest extends TestCase
{
    /**
     * The paginator asks for its labels by a dotted key, which the JSON files
     * cannot answer. When lang/{locale}/pagination.php is missing the lookup
     * falls through to the fallback locale and the buttons quietly render in
     * English on every paginated Arabic or Kurdish page.
     */
    public function test_pagination_labels_are_translated_in_every_locale(): void
    {
        $english = [
            'previous' => __('pagination.previous', [], 'en'),
            'next' => __('pagination.next', [], 'en'),
        ];

        $this->assertSame('&laquo; Previous', $english['previous']);
        $this->assertSame('Next &raquo;', $english['next']);

        foreach (['ar', 'ku'] as $locale) {
            foreach (['previous', 'next'] as $label) {
                $translated = __("pagination.{$label}", [], $locale);

                $this->assertNotSame(
                    "pagination.{$label}",
                    $translated,
                    "The {$label} label has no entry for {$locale} and renders as its own key."
                );
                $this->assertNotSame(
                    $english[$label],
                    $translated,
                    "The {$label} label falls back to English for {$locale}."
                );
            }
        }
    }

    /**
     * The surrounding chrome of the paginator lives in the JSON files, and a
     * string that was never added there reads in English regardless of locale.
     */
    public function test_pagination_chrome_strings_are_translated_in_every_locale(): void
    {
        $keys = ['Pagination Navigation', 'to', 'Go to page :page', 'Showing', 'of', 'results'];

        foreach (['ar', 'ku'] as $locale) {
            foreach ($keys as $key) {
                $translated = __($key, [], $locale);

                $this->assertNotSame(
                    $key,
                    $translated,
                    "'{$key}' has no {$locale} translation and renders in English."
                );
            }
        }
    }
}
