<?php

namespace Tests\Feature;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Arabic and Kurdish get their own validation messages, not the English ones.
 *
 * A message missing from lang/{ar,ku}/validation.php does not fail — it falls
 * back to English without a sound. Compared against the framework's own list,
 * so a rule added by a Laravel upgrade shows up here as untranslated.
 */
class ValidationLocalizationTest extends TestCase
{
    /**
     * @return array<string, string>
     */
    private function messages(string $locale): array
    {
        $lines = Arr::except(trans('validation', [], $locale), ['custom', 'attributes']);

        return array_filter(Arr::dot($lines), 'is_string');
    }

    public function test_every_validation_message_is_translated_with_its_placeholders(): void
    {
        $english = $this->messages('en');

        $this->assertGreaterThan(100, count($english));

        foreach (['ar', 'ku'] as $locale) {
            $translated = $this->messages($locale);

            foreach ($english as $key => $line) {
                $this->assertArrayHasKey($key, $translated, "validation.{$key} is not translated for {$locale}.");
                $this->assertNotSame($line, $translated[$key], "validation.{$key} is still English for {$locale}.");

                preg_match_all('/:[a-z_]+/', $line, $expected);
                foreach (array_unique($expected[0]) as $placeholder) {
                    $this->assertStringContainsString(
                        $placeholder,
                        $translated[$key],
                        "validation.{$key} lost {$placeholder} in {$locale}."
                    );
                }
            }
        }
    }

    public function test_a_failed_rule_reads_in_the_visitors_language_with_the_field_named(): void
    {
        $this->app->setLocale('ar');
        $errors = Validator::make(['price' => 'abc'], ['name_en' => 'required', 'price' => 'numeric'])->errors();

        $this->assertSame('حقل الاسم بالإنجليزية مطلوب.', $errors->first('name_en'));
        $this->assertSame('يجب أن يكون حقل السعر رقماً.', $errors->first('price'));

        $this->app->setLocale('ku');
        $errors = Validator::make(['price' => 'abc'], ['name_en' => 'required', 'price' => 'numeric'])->errors();

        $this->assertSame('ناو بە ئینگلیزی پێویستە.', $errors->first('name_en'));
        $this->assertSame('پێویستە نرخ ژمارە بێت.', $errors->first('price'));
    }
}
