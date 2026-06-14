<?php

namespace Tests\Unit;

use App\Support\PdfArabicText;
use Tests\TestCase;

class PdfArabicTextTest extends TestCase
{
    public function test_returns_text_unchanged_when_not_rtl(): void
    {
        $input = 'مرحبا';
        $this->assertSame($input, PdfArabicText::forDompdf($input, false));
    }

    public function test_returns_non_arabic_text_unchanged(): void
    {
        $input = 'YallaSpare 2026 INV-001';
        $this->assertSame($input, PdfArabicText::forDompdf($input, true));
    }

    /**
     * 0x06D5 (Kurdish AE / schwa) used to be ignored by the shaper, so the previous
     * letter would skip past it and pick a wrong connection target. After the fix it
     * is treated as a right-joining letter — connections terminate at it.
     * Only base codepoints and known presentation forms should appear in the output.
     */
    public function test_kurdish_ae_is_recognized_by_shaper(): void
    {
        $shaped = PdfArabicText::forDompdf('بەر', true);

        $allowed = [
            0xFE8F, 0xFE90, 0xFE91, 0xFE92, // ب forms (isolated/final/initial/medial)
            0xFEAD, 0xFEAE,                 // ر forms (isolated/final)
            0x06D5,                         // ə kept as-is
        ];

        foreach (mb_str_split($shaped) as $char) {
            $this->assertContains(
                mb_ord($char, 'UTF-8'),
                $allowed,
                'Unexpected codepoint in shaped Kurdish word: U+' . dechex(mb_ord($char, 'UTF-8'))
            );
        }

        // ə must remain in the output (not stripped) — it carries the schwa sound.
        $this->assertStringContainsString(mb_chr(0x06D5, 'UTF-8'), $shaped);
    }

    /**
     * "لا" (lam + alef) is the canonical lam-alef pair. After the fix it must collapse
     * into a single ligature glyph (FEFB isolated or FEFC final), not stay as two separate
     * characters.
     */
    public function test_lam_alef_pair_collapses_into_single_ligature_glyph(): void
    {
        $shaped = PdfArabicText::forDompdf('لا', true);

        $hasLamAlefLigature = str_contains($shaped, mb_chr(0xFEFB, 'UTF-8'))
            || str_contains($shaped, mb_chr(0xFEFC, 'UTF-8'));

        $this->assertTrue(
            $hasLamAlefLigature,
            'Expected lam-alef ligature glyph (FEFB/FEFC) in shaped form of "لا".'
        );
        $this->assertSame(1, mb_strlen($shaped), 'لا must collapse to a single glyph.');
    }

    /**
     * Regression: a plain Arabic word without lam-alef pairs and without Kurdish ae
     * must not gain or lose characters because of the new fixes.
     */
    public function test_word_without_lam_alef_or_kurdish_ae_keeps_same_glyph_count(): void
    {
        $word = 'نور'; // n-o-r — no lam-alef pair, no ae
        $shaped = PdfArabicText::forDompdf($word, true);

        $this->assertSame(
            mb_strlen($word),
            mb_strlen($shaped),
            'Plain Arabic word "نور" must not gain or lose glyphs.'
        );
    }

    /**
     * Regression: mixed Arabic + Latin (common: brand names, SKUs in product rows).
     * Latin part must stay intact and readable.
     */
    public function test_mixed_arabic_and_latin_keeps_latin_intact(): void
    {
        $shaped = PdfArabicText::forDompdf('مرحبا Hello', true);

        $this->assertStringContainsString('Hello', $shaped);
    }
}
