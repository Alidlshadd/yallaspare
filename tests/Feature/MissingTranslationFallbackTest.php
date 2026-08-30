<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Lang;
use Tests\TestCase;

/**
 * What the screen shows when a translation has not been written yet.
 *
 * The handler behind this has to serve two kinds of key at once: a nested name
 * like `errors.something_went_wrong`, where the last segment spelled out is a
 * decent stand-in, and a key that is already an English sentence, where the
 * sentence itself is the answer. Treating the second like the first is how a
 * message ending in a full stop turned into an empty space on the page.
 */
class MissingTranslationFallbackTest extends TestCase
{
    public function test_a_missing_sentence_ending_in_a_full_stop_is_shown_whole(): void
    {
        // The bug: everything after the last full stop was kept, and a sentence
        // that ends in one has nothing after it.
        $key = 'This sentence was never added to any language file.';

        $this->assertSame($key, __($key));
    }

    public function test_a_missing_sentence_with_several_full_stops_keeps_all_of_them(): void
    {
        $key = 'Nothing was applied. Check the list. Then try again.';

        $this->assertSame($key, __($key));
    }

    public function test_a_missing_sentence_is_never_rendered_as_nothing(): void
    {
        foreach ([
            'A missing sentence.',
            'Ends with two dots..',
            '.',
            '...',
            'v1.2',
            'Mr.',
        ] as $key) {
            $this->assertNotSame('', __($key), "“{$key}” came back empty.");
        }
    }

    public function test_placeholders_are_still_filled_in_on_a_missing_sentence(): void
    {
        $rendered = __('Only :available of :sku left in stock.', ['available' => 3, 'sku' => 'BRK-1001']);

        $this->assertSame('Only 3 of BRK-1001 left in stock.', $rendered);
    }

    public function test_a_missing_nested_key_still_reads_as_its_last_segment(): void
    {
        // This is the behaviour the handler exists for, and it has to survive
        // the fix: a name nobody has translated becomes a readable label.
        $this->assertSame(
            'Something went wrong here',
            __('errors.something_went_wrong_here')
        );

        $this->assertSame(
            'Rule missing',
            __('validation.custom.some_field.rule-missing')
        );
    }

    public function test_a_nested_key_that_does_exist_is_translated_normally(): void
    {
        // Not the fallback — the real line out of lang/en/errors.php.
        $this->assertTrue(Lang::has('errors.inventory_product_not_found'));

        $this->assertSame(
            "Product not found for SKU 'BRK-1001'.",
            __('errors.inventory_product_not_found', ['sku' => 'BRK-1001'])
        );
    }

    public function test_a_sentence_that_does_exist_is_translated_normally(): void
    {
        $this->assertSame('Home', __('Home'));

        $this->app->setLocale('ar');
        $this->assertNotSame('Home', __('Home'), 'Arabic should not fall through to the English key.');
        $this->app->setLocale('en');
    }
}
