<?php

namespace App\Support;

class PdfArabicText
{
    /**
     * Convert Arabic-script text to visual presentation forms for Dompdf.
     */
    public static function forDompdf(string $text, bool $isRtl): string
    {
        if (! $isRtl || ! preg_match('/\p{Arabic}/u', $text)) {
            return $text;
        }

        $lines = preg_split("/(\r\n|\n|\r)/u", $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($lines === false) {
            return $text;
        }

        return implode('', array_map(
            fn (string $line): string => preg_match('/\p{Arabic}/u', $line) ? self::shapeLine($line) : $line,
            $lines
        ));
    }

    private static function shapeLine(string $line): string
    {
        $tokens = self::tokenize($line);

        return implode('', array_reverse(array_map(
            fn (array $token): string => $token['arabic'] ? self::shapeArabicRun($token['text']) : $token['text'],
            $tokens
        )));
    }

    /**
     * @return array<int, array{arabic: bool, text: string}>
     */
    private static function tokenize(string $text): array
    {
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
        if ($chars === false) {
            return [['arabic' => false, 'text' => $text]];
        }

        $tokens = [];
        $current = '';
        $currentArabic = null;

        foreach ($chars as $char) {
            $isArabic = self::isArabic($char);
            if ($currentArabic === null || $isArabic === $currentArabic) {
                $current .= $char;
                $currentArabic = $isArabic;
                continue;
            }

            $tokens[] = ['arabic' => $currentArabic, 'text' => $current];
            $current = $char;
            $currentArabic = $isArabic;
        }

        if ($current !== '') {
            $tokens[] = ['arabic' => (bool) $currentArabic, 'text' => $current];
        }

        return $tokens;
    }

    private static function shapeArabicRun(string $text): string
    {
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
        if ($chars === false) {
            return $text;
        }

        $shaped = [];
        $count = count($chars);

        for ($i = 0; $i < $count; $i++) {
            $char = $chars[$i];
            $forms = self::forms($char);

            if ($forms === null) {
                $shaped[] = $char;
                continue;
            }

            $previous = self::previousArabicLetter($chars, $i);
            $next = self::nextArabicLetter($chars, $i);
            $connectPrevious = $previous !== null && self::connectsAfter($previous) && self::connectsBefore($char);
            $connectNext = $next !== null && self::connectsAfter($char) && self::connectsBefore($next);

            $form = match (true) {
                $connectPrevious && $connectNext => 'medial',
                $connectPrevious => 'final',
                $connectNext => 'initial',
                default => 'isolated',
            };

            $shaped[] = self::chr($forms[$form] ?? $forms['isolated'] ?? self::ord($char));
        }

        return implode('', array_reverse($shaped));
    }

    private static function previousArabicLetter(array $chars, int $index): ?string
    {
        for ($i = $index - 1; $i >= 0; $i--) {
            if (self::forms($chars[$i]) !== null) {
                return $chars[$i];
            }
        }

        return null;
    }

    private static function nextArabicLetter(array $chars, int $index): ?string
    {
        $count = count($chars);
        for ($i = $index + 1; $i < $count; $i++) {
            if (self::forms($chars[$i]) !== null) {
                return $chars[$i];
            }
        }

        return null;
    }

    private static function connectsBefore(string $char): bool
    {
        $forms = self::forms($char);

        return $forms !== null && (isset($forms['final']) || isset($forms['medial']));
    }

    private static function connectsAfter(string $char): bool
    {
        $forms = self::forms($char);

        return $forms !== null && (isset($forms['initial']) || isset($forms['medial']));
    }

    private static function isArabic(string $char): bool
    {
        return (bool) preg_match('/\p{Arabic}/u', $char);
    }

    /**
     * @return array<string, int>|null
     */
    private static function forms(string $char): ?array
    {
        return self::FORM_MAP[self::ord($char)] ?? null;
    }

    private static function ord(string $char): int
    {
        return mb_ord($char, 'UTF-8');
    }

    private static function chr(int $codepoint): string
    {
        return mb_chr($codepoint, 'UTF-8');
    }

    private const FORM_MAP = [
        0x0621 => ['isolated' => 0xFE80],
        0x0622 => ['isolated' => 0xFE81, 'final' => 0xFE82],
        0x0623 => ['isolated' => 0xFE83, 'final' => 0xFE84],
        0x0624 => ['isolated' => 0xFE85, 'final' => 0xFE86],
        0x0625 => ['isolated' => 0xFE87, 'final' => 0xFE88],
        0x0626 => ['isolated' => 0xFE89, 'final' => 0xFE8A, 'initial' => 0xFE8B, 'medial' => 0xFE8C],
        0x0627 => ['isolated' => 0xFE8D, 'final' => 0xFE8E],
        0x0628 => ['isolated' => 0xFE8F, 'final' => 0xFE90, 'initial' => 0xFE91, 'medial' => 0xFE92],
        0x0629 => ['isolated' => 0xFE93, 'final' => 0xFE94],
        0x062A => ['isolated' => 0xFE95, 'final' => 0xFE96, 'initial' => 0xFE97, 'medial' => 0xFE98],
        0x062B => ['isolated' => 0xFE99, 'final' => 0xFE9A, 'initial' => 0xFE9B, 'medial' => 0xFE9C],
        0x062C => ['isolated' => 0xFE9D, 'final' => 0xFE9E, 'initial' => 0xFE9F, 'medial' => 0xFEA0],
        0x062D => ['isolated' => 0xFEA1, 'final' => 0xFEA2, 'initial' => 0xFEA3, 'medial' => 0xFEA4],
        0x062E => ['isolated' => 0xFEA5, 'final' => 0xFEA6, 'initial' => 0xFEA7, 'medial' => 0xFEA8],
        0x062F => ['isolated' => 0xFEA9, 'final' => 0xFEAA],
        0x0630 => ['isolated' => 0xFEAB, 'final' => 0xFEAC],
        0x0631 => ['isolated' => 0xFEAD, 'final' => 0xFEAE],
        0x0632 => ['isolated' => 0xFEAF, 'final' => 0xFEB0],
        0x0633 => ['isolated' => 0xFEB1, 'final' => 0xFEB2, 'initial' => 0xFEB3, 'medial' => 0xFEB4],
        0x0634 => ['isolated' => 0xFEB5, 'final' => 0xFEB6, 'initial' => 0xFEB7, 'medial' => 0xFEB8],
        0x0635 => ['isolated' => 0xFEB9, 'final' => 0xFEBA, 'initial' => 0xFEBB, 'medial' => 0xFEBC],
        0x0636 => ['isolated' => 0xFEBD, 'final' => 0xFEBE, 'initial' => 0xFEBF, 'medial' => 0xFEC0],
        0x0637 => ['isolated' => 0xFEC1, 'final' => 0xFEC2, 'initial' => 0xFEC3, 'medial' => 0xFEC4],
        0x0638 => ['isolated' => 0xFEC5, 'final' => 0xFEC6, 'initial' => 0xFEC7, 'medial' => 0xFEC8],
        0x0639 => ['isolated' => 0xFEC9, 'final' => 0xFECA, 'initial' => 0xFECB, 'medial' => 0xFECC],
        0x063A => ['isolated' => 0xFECD, 'final' => 0xFECE, 'initial' => 0xFECF, 'medial' => 0xFED0],
        0x0641 => ['isolated' => 0xFED1, 'final' => 0xFED2, 'initial' => 0xFED3, 'medial' => 0xFED4],
        0x0642 => ['isolated' => 0xFED5, 'final' => 0xFED6, 'initial' => 0xFED7, 'medial' => 0xFED8],
        0x0643 => ['isolated' => 0xFED9, 'final' => 0xFEDA, 'initial' => 0xFEDB, 'medial' => 0xFEDC],
        0x0644 => ['isolated' => 0xFEDD, 'final' => 0xFEDE, 'initial' => 0xFEDF, 'medial' => 0xFEE0],
        0x0645 => ['isolated' => 0xFEE1, 'final' => 0xFEE2, 'initial' => 0xFEE3, 'medial' => 0xFEE4],
        0x0646 => ['isolated' => 0xFEE5, 'final' => 0xFEE6, 'initial' => 0xFEE7, 'medial' => 0xFEE8],
        0x0647 => ['isolated' => 0xFEE9, 'final' => 0xFEEA, 'initial' => 0xFEEB, 'medial' => 0xFEEC],
        0x0648 => ['isolated' => 0xFEED, 'final' => 0xFEEE],
        0x0649 => ['isolated' => 0xFEEF, 'final' => 0xFEF0, 'initial' => 0xFBE8, 'medial' => 0xFBE9],
        0x064A => ['isolated' => 0xFEF1, 'final' => 0xFEF2, 'initial' => 0xFEF3, 'medial' => 0xFEF4],
        0x067E => ['isolated' => 0xFB56, 'final' => 0xFB57, 'initial' => 0xFB58, 'medial' => 0xFB59],
        0x0686 => ['isolated' => 0xFB7A, 'final' => 0xFB7B, 'initial' => 0xFB7C, 'medial' => 0xFB7D],
        0x0691 => ['isolated' => 0xFB8C, 'final' => 0xFB8D],
        0x0698 => ['isolated' => 0xFB8A, 'final' => 0xFB8B],
        0x06A4 => ['isolated' => 0xFB6A, 'final' => 0xFB6B, 'initial' => 0xFB6C, 'medial' => 0xFB6D],
        0x06A9 => ['isolated' => 0xFB8E, 'final' => 0xFB8F, 'initial' => 0xFB90, 'medial' => 0xFB91],
        0x06AF => ['isolated' => 0xFB92, 'final' => 0xFB93, 'initial' => 0xFB94, 'medial' => 0xFB95],
        0x06C6 => ['isolated' => 0xFBD9, 'final' => 0xFBDA],
        0x06CC => ['isolated' => 0xFBFC, 'final' => 0xFBFD, 'initial' => 0xFBFE, 'medial' => 0xFBFF],
        0x06D0 => ['isolated' => 0xFBE4, 'final' => 0xFBE5, 'initial' => 0xFBE6, 'medial' => 0xFBE7],
    ];
}
