<?php

namespace Tests\Feature;

use Tests\TestCase;

class AccessibleControlNamesTest extends TestCase
{
    /**
     * Components that take their name from whoever renders them. The caller
     * passes an id and puts an <x-input-label for="..."> beside it, so the
     * component file on its own always looks unnamed.
     */
    private const LABELLED_BY_CALLER = [
        'resources/views/components/text-input.blade.php',
        'resources/views/components/password-input.blade.php',
    ];

    /**
     * A control with no label, no aria-label and no wrapping label is
     * announced as a bare "combo box" or "edit text". Reviewing these by eye
     * does not scale, so the shape is checked here instead.
     */
    public function test_every_form_control_can_be_named(): void
    {
        $unnamed = [];

        foreach ($this->bladeFiles() as $file) {
            $relative = str_replace([base_path().DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR], ['', '/'], $file);

            if (str_contains($relative, 'resources/views/emails/') || in_array($relative, self::LABELLED_BY_CALLER, true)) {
                continue;
            }

            $contents = file_get_contents($file);

            preg_match_all('/<(?:label|x-input-label)\b[^>]*\bfor\s*=\s*"([^"]*)"/i', $contents, $forMatches);
            $labelled = $forMatches[1];

            preg_match_all('/<label\b.*?<\/label>/is', $contents, $wrapping, PREG_OFFSET_CAPTURE);

            preg_match_all(
                '/<(input|select|textarea)\b((?:"[^"]*"|\'[^\']*\'|[^>])*?)>/is',
                $contents,
                $controls,
                PREG_OFFSET_CAPTURE | PREG_SET_ORDER
            );

            foreach ($controls as $control) {
                [$tag, $offset] = $control[0];
                $attributes = $control[2][0];

                if (preg_match('/\btype\s*=\s*"(hidden|submit|button|reset|image)"/i', $attributes)) {
                    continue;
                }

                if ($this->sitsInside($offset, $wrapping[0])) {
                    continue;
                }

                if (preg_match('/\b(aria-label|aria-labelledby|title|placeholder)\s*=/i', $attributes)
                    || str_contains($attributes, 'sr-only')) {
                    continue;
                }

                if (preg_match('/\bid\s*=\s*"([^"]*)"/i', $attributes, $id) && in_array($id[1], $labelled, true)) {
                    continue;
                }

                $line = substr_count($contents, "\n", 0, $offset) + 1;
                $unnamed[] = "  {$relative}:{$line}  ".trim(preg_replace('/\s+/', ' ', $tag));
            }
        }

        $this->assertSame([], $unnamed, sprintf(
            "%d form control(s) have no way to be named:\n%s",
            count($unnamed),
            implode("\n", $unnamed)
        ));
    }

    /**
     * A Font Awesome <i> is empty — the glyph comes from CSS — so anything
     * that reads it aloud reads noise. They all have to be hidden.
     */
    public function test_decorative_icons_are_hidden_from_assistive_technology(): void
    {
        $exposed = [];

        foreach ($this->bladeFiles() as $file) {
            $contents = file_get_contents($file);
            preg_match_all('/<i\b([^>]*\bclass="[^"]*\bfa[srlbd]?\b[^"]*"[^>]*)>/i', $contents, $icons, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

            foreach ($icons as $icon) {
                if (str_contains($icon[1][0], 'aria-hidden')) {
                    continue;
                }

                $relative = str_replace([base_path().DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR], ['', '/'], $file);
                $line = substr_count($contents, "\n", 0, $icon[0][1]) + 1;
                $exposed[] = "  {$relative}:{$line}";
            }
        }

        $this->assertSame([], $exposed, sprintf(
            "%d decorative icon(s) are not hidden with aria-hidden:\n%s",
            count($exposed),
            implode("\n", $exposed)
        ));
    }

    /**
     * @param  array<int, array{0: string, 1: int}>  $spans
     */
    private function sitsInside(int $offset, array $spans): bool
    {
        foreach ($spans as [$text, $start]) {
            if ($offset >= $start && $offset < $start + strlen($text)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function bladeFiles(): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(base_path('resources/views')));

        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }
}
