<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Guards the transactional email layer against the two ways it has broken for
 * Arabic and Sorani Kurdish readers:
 *
 *   1. Latin-only typography applied to Arabic script — the monospace and
 *      display stacks carry no Arabic glyphs, and `letter-spacing` pulls a
 *      cursive script apart. Same class of breakage the invoice PDFs hit
 *      before they moved to mPDF.
 *   2. Physical left/right CSS that never mirrors, because email clients
 *      support neither logical properties nor `[dir="rtl"]` selectors.
 */
class EmailLocalizationTest extends TestCase
{
    private const LOCALES = ['en', 'ar', 'ku'];

    private const RTL_LOCALES = ['ar', 'ku'];

    /**
     * Every email view with a representative payload. Rendering is the only
     * way to catch a component whose contract drifted.
     *
     * @return array<string, array<string, mixed>>
     */
    private function cases(): array
    {
        return [
            'emails.auth.welcome' => [
                'name' => 'Ali Dlshad',
                'email' => 'ali@example.com',
                'actionUrl' => 'https://yallaspare.test/account',
            ],
            'emails.auth.verify-email' => [
                'email' => 'ali@example.com',
                'verificationCode' => '493812',
                'expiresIn' => 60,
            ],
            'emails.auth.reset-password' => [
                'email' => 'ali@example.com',
                'actionUrl' => 'https://yallaspare.test/reset/abc',
                'expiresIn' => 60,
            ],
            'emails.admin.two-factor-code' => [
                'email' => 'admin@yallaspare.test',
                'code' => '774219',
                'ttlMinutes' => 10,
            ],
            'emails.admin.security-alert' => [
                'email' => 'admin@yallaspare.test',
                'bodyText' => "A new sign-in was detected.\nDevice: Chrome on Windows",
                'metaItems' => [['label' => 'IP address', 'value' => '5.62.61.10']],
                'actionUrl' => 'https://yallaspare.test/admin/security',
            ],
            'emails.orders.status' => [
                'recipientEmail' => 'ali@example.com',
                'recipientName' => 'Ali Dlshad',
                'orderStatus' => 'shipped',
                'intro' => 'Your order is on its way.',
                'metaItems' => [['label' => 'Order', 'value' => '#YS-10428']],
                'orderRows' => [
                    ['name' => 'Brake pad set', 'sku' => 'BP-4471', 'quantity' => 2, 'subtotal' => 'IQD 68,000'],
                ],
                'totals' => [
                    ['label' => 'Subtotal', 'value' => 'IQD 68,000'],
                    ['label' => 'Total', 'value' => 'IQD 73,000'],
                ],
                'actionUrl' => 'https://yallaspare.test/orders/10428',
                'shippingAddress' => 'Erbil, 100m Road',
            ],
            'emails.dealer.notification' => [
                'recipientEmail' => 'dealer@example.com',
                'bodyText' => 'Your dealer application has been approved.',
                'metaItems' => [['label' => 'Dealer ID', 'value' => 'D-2049']],
                'dealerStatus' => 'approved',
                'actionUrl' => 'https://yallaspare.test/dealer',
            ],
            'emails.inventory.low-stock-alert' => [
                'metaItems' => [['label' => 'Products', 'value' => '7']],
                'actionUrl' => 'https://yallaspare.test/admin/inventory',
            ],
            'emails.operational.notification' => [
                'recipientEmail' => 'ali@example.com',
                'subjectLine' => 'Scheduled maintenance',
                'bodyText' => 'We will be performing maintenance tonight.',
                'metaItems' => [['label' => 'Window', 'value' => '02:00 - 04:00']],
                'actionUrl' => 'https://yallaspare.test',
            ],
            'emails.support.contact-request' => [
                'name' => 'Sara',
                'email' => 'sara@example.com',
                'phone' => '+964 750 000 0000',
                'topic' => 'Order issue',
                'requestSubject' => 'Wrong part delivered',
                'messageText' => "I ordered a filter but received a brake pad.\nPlease advise.",
            ],
        ];
    }

    public function test_every_email_view_renders_in_every_locale(): void
    {
        foreach (self::LOCALES as $locale) {
            $this->app->setLocale($locale);

            foreach ($this->cases() as $view => $data) {
                $html = view($view, $data + ['locale' => $locale])->render();

                $this->assertNotSame('', trim($html), "$view rendered empty in $locale");
                $this->assertStringContainsString('</html>', $html, "$view did not finish rendering in $locale");
            }
        }
    }

    public function test_rtl_emails_declare_rtl_direction(): void
    {
        foreach (self::RTL_LOCALES as $locale) {
            $this->app->setLocale($locale);

            foreach ($this->cases() as $view => $data) {
                $html = view($view, $data + ['locale' => $locale])->render();

                $this->assertStringContainsString('dir="rtl"', $html, "$view is not marked RTL in $locale");
                $this->assertStringNotContainsString(
                    'lang="en"',
                    $html,
                    "$view still advertises lang=en in $locale"
                );
            }
        }
    }

    /**
     * The Latin display and monospace stacks have no Arabic coverage, so a
     * client renders those runs by falling back glyph by glyph and the line
     * loses its shaping. `letter-spacing` is worse — it separates letters that
     * must join. Neither may reach an element that actually holds Arabic text.
     *
     * Checked per element rather than per document, because the brand wordmark,
     * the OTP digits and the SKU/money runs stay Latin in every locale and are
     * meant to keep the Latin treatment.
     */
    public function test_rtl_emails_never_style_arabic_text_as_latin(): void
    {
        $forbidden = [
            'Space Grotesk' => 'the Latin display face',
            'SFMono-Regular' => 'the Latin monospace face',
            'letter-spacing' => 'letter-spacing',
            'text-transform:uppercase' => 'uppercasing',
        ];

        foreach (self::RTL_LOCALES as $locale) {
            $this->app->setLocale($locale);

            foreach ($this->cases() as $view => $data) {
                $html = view($view, $data + ['locale' => $locale])->render();

                foreach ($this->elementsHoldingArabicText($html) as $element) {
                    $style = $element->getAttribute('style');

                    foreach ($forbidden as $needle => $label) {
                        $this->assertStringNotContainsString(
                            $needle,
                            $style,
                            "$view applies $label to Arabic script in $locale: <"
                                .$element->nodeName.' style="'.$style.'"> '
                                .trim(mb_substr($element->textContent, 0, 40))
                        );
                    }
                }
            }
        }
    }

    /**
     * English must come out of the refactor byte-identical in spirit: the
     * display face, the tracking and the caps all still ship.
     */
    public function test_english_emails_keep_the_latin_typography(): void
    {
        $this->app->setLocale('en');

        $html = view('emails.orders.status', $this->cases()['emails.orders.status'] + ['locale' => 'en'])->render();

        $this->assertStringContainsString('Space Grotesk', $html);
        $this->assertStringContainsString('SFMono-Regular', $html);
        $this->assertStringContainsString('letter-spacing', $html);
        $this->assertStringContainsString('text-transform:uppercase', $html);
        $this->assertStringContainsString('dir="ltr"', $html);
    }

    /**
     * Every translatable string the email layer asks for must resolve in ar and
     * ku. A miss falls back to the English key, which is how a "translated"
     * email ends up half in English.
     */
    public function test_every_email_translation_key_resolves(): void
    {
        $keys = $this->translationKeysUsedByEmails();

        $this->assertNotEmpty($keys, 'Found no translation keys in the email layer — the scan is broken.');

        foreach (['ar', 'ku'] as $locale) {
            $catalogue = json_decode(
                (string) file_get_contents(base_path("lang/$locale.json")),
                true
            ) ?: [];

            $missing = [];

            foreach ($keys as $key => $sources) {
                if (! array_key_exists($key, $catalogue) || trim((string) $catalogue[$key]) === '') {
                    $missing[] = "\"$key\" (".implode(', ', $sources).')';
                }
            }

            $this->assertSame(
                [],
                $missing,
                "Untranslated in $locale:\n - ".implode("\n - ", $missing)
            );
        }
    }

    /**
     * Elements whose own text — not a descendant's — contains Arabic script.
     * That is the text the element's inline style actually governs.
     *
     * @return list<\DOMElement>
     */
    private function elementsHoldingArabicText(string $html): array
    {
        $document = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $holders = [];

        /** @var \DOMElement $element */
        foreach ($document->getElementsByTagName('*') as $element) {
            if ($element->nodeName === 'style' || $element->nodeName === 'title') {
                continue;
            }

            $own = '';

            foreach ($element->childNodes as $child) {
                if ($child instanceof \DOMText) {
                    $own .= $child->wholeText;
                }
            }

            if (preg_match('/\p{Arabic}/u', $own)) {
                $holders[] = $element;
            }
        }

        return $holders;
    }

    /**
     * @return array<string, list<string>> key => source files
     */
    private function translationKeysUsedByEmails(): array
    {
        $roots = [
            base_path('resources/views/emails'),
            base_path('app/Mail'),
            base_path('app/Notifications'),
        ];

        $files = [
            base_path('resources/views/components/email-kicker.blade.php'),
            base_path('resources/views/components/email-security-label.blade.php'),
            base_path('resources/views/components/email-title.blade.php'),
            base_path('resources/views/components/email-copy.blade.php'),
        ];

        foreach ($roots as $root) {
            if (! is_dir($root)) {
                continue;
            }

            /** @var \SplFileInfo $file */
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root)) as $file) {
                if ($file->isFile()) {
                    $files[] = $file->getPathname();
                }
            }
        }

        $keys = [];

        foreach (array_filter($files, 'is_file') as $file) {
            $source = (string) file_get_contents($file);
            $relative = str_replace([base_path().DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR], ['', '/'], $file);

            foreach (["/(?:__|trans|@lang)\\(\\s*'((?:[^'\\\\]|\\\\.)*)'/", '/(?:__|trans|@lang)\(\s*"((?:[^"\\\\]|\\\\.)*)"/'] as $pattern) {
                if (preg_match_all($pattern, $source, $matches)) {
                    foreach ($matches[1] as $key) {
                        $keys[stripcslashes($key)][] = $relative;
                    }
                }
            }
        }

        return array_map(fn (array $sources) => array_values(array_unique($sources)), $keys);
    }
}
