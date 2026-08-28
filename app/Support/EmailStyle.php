<?php

namespace App\Support;

/**
 * Locale-aware inline typography for the transactional email layer.
 *
 * Email clients cannot be trusted with logical CSS (`border-inline-start`,
 * `padding-inline`) or with attribute selectors like `[dir="rtl"]` — Outlook's
 * Word engine supports neither. But the locale is known at render time, so the
 * mirroring is done here in PHP and baked into the inline style attribute.
 *
 * Beyond mirroring, Arabic and Sorani Kurdish need three things the English
 * design takes for granted and gets wrong:
 *
 *   1. The monospace and display stacks ('SFMono-Regular', 'Space Grotesk')
 *      carry no Arabic glyphs, so the client falls back per character and the
 *      line loses its shaping.
 *   2. `letter-spacing` pulls the letters of a cursive script apart — the same
 *      class of breakage the invoice PDFs hit before they moved to mPDF.
 *   3. `text-transform:uppercase` is meaningless in a script with no case, and
 *      some clients still reflow the run trying to apply it.
 *
 * So for RTL locales the mono/display roles resolve to an Arabic-capable sans
 * stack, and tracking/caps resolve to nothing.
 */
final class EmailStyle
{
    /** @var list<string> */
    private const RTL_LOCALES = ['ar', 'ku'];

    private const SANS_LTR = "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif";

    private const DISPLAY_LTR = "'Space Grotesk','Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif";

    private const MONO_LTR = "'SFMono-Regular',Consolas,'Liberation Mono',Menlo,monospace";

    /**
     * Tahoma is the dependable Sorani face on Windows (it carries ڕ ڵ ۆ ێ and
     * joins them); -apple-system resolves to SF Arabic on Apple mail clients.
     */
    private const SANS_RTL = "-apple-system,BlinkMacSystemFont,'Segoe UI','Noto Naskh Arabic',Tahoma,Arial,sans-serif";

    public static function isRtl(?string $locale = null): bool
    {
        return in_array(strtolower($locale ?? app()->getLocale()), self::RTL_LOCALES, true);
    }

    /** Physical side the text flows from — 'left' in English, 'right' in ar/ku. */
    public static function start(?string $locale = null): string
    {
        return self::isRtl($locale) ? 'right' : 'left';
    }

    /** Physical side the text flows toward. */
    public static function end(?string $locale = null): string
    {
        return self::isRtl($locale) ? 'left' : 'right';
    }

    public static function sans(?string $locale = null): string
    {
        return self::isRtl($locale) ? self::SANS_RTL : self::SANS_LTR;
    }

    public static function display(?string $locale = null): string
    {
        return self::isRtl($locale) ? self::SANS_RTL : self::DISPLAY_LTR;
    }

    public static function mono(?string $locale = null): string
    {
        return self::isRtl($locale) ? self::SANS_RTL : self::MONO_LTR;
    }

    /**
     * `letter-spacing:<value>;` for Latin text, nothing for Arabic script.
     */
    public static function tracking(string $value, ?string $locale = null): string
    {
        return self::isRtl($locale) ? '' : 'letter-spacing:'.$value.';';
    }

    /**
     * `text-transform:uppercase;` for Latin text, nothing for Arabic script.
     */
    public static function caps(?string $locale = null): string
    {
        return self::isRtl($locale) ? '' : 'text-transform:uppercase;';
    }

    /**
     * Direction to force on a run that must stay Latin/numeric — order totals,
     * SKUs, email addresses, OTP digits. Always 'ltr'; paired with
     * `unicode-bidi:isolate` so it cannot leak into the surrounding paragraph.
     */
    public static function isolateLtr(): string
    {
        return 'unicode-bidi:isolate;';
    }
}
