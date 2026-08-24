<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Five informational hues were collapsed onto one `info` token, and in a
 * number of chips both the tint and the ink landed on the bare token — so
 * light mode painted the label in the fill colour and the text vanished.
 * Dark mode had been given alpha and survived, which is why it went unseen.
 *
 * A soft chip needs three different values: a low-alpha fill, a mid-alpha
 * border and the solid token for the ink. Reviewing that by eye does not
 * scale across 200 blade files, so the shape is checked here.
 */
class SoftTokenChipContrastTest extends TestCase
{
    /** Tokens whose bare form is a saturated colour, not a surface. */
    private const TOKENS = ['info', 'success', 'warning', 'danger', 'accent', 'primary'];

    /**
     * .pulse-dot::after draws its ring in currentColor, so a dot deliberately
     * carries the same token as fill and as text. There is no glyph to hide.
     */
    private const RING_DRIVEN = 'pulse-dot';

    public function test_no_element_paints_its_text_in_its_own_fill_colour(): void
    {
        $clashes = [];

        foreach ($this->bladeFiles() as $file) {
            $relative = str_replace([base_path().DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR], ['', '/'], $file);
            $lines = file($file) ?: [];

            foreach ($lines as $index => $line) {
                if (str_contains($line, self::RING_DRIVEN)) {
                    continue;
                }

                preg_match_all('/"[^"]*"|\'[^\']*\'/', $line, $strings);

                foreach ($strings[0] as $chunk) {
                    foreach (self::TOKENS as $token) {
                        if ($this->hasBare($chunk, 'bg-'.$token) && $this->hasBare($chunk, 'text-'.$token)) {
                            $clashes[] = sprintf('  %s:%d  bg-%s + text-%s', $relative, $index + 1, $token, $token);
                        }
                    }
                }
            }
        }

        $this->assertSame([], $clashes, sprintf(
            "%d element(s) fill and letter themselves with the same token, so the text is invisible in light mode:\n%s",
            count($clashes),
            implode("\n", $clashes)
        ));
    }

    /**
     * Bare means the token with no alpha and no variant prefix: `bg-info`
     * clashes, `bg-info/10` and `dark:bg-info` do not.
     */
    private function hasBare(string $haystack, string $class): bool
    {
        return (bool) preg_match('/(?<![:\w-])'.preg_quote($class, '/').'(?![\/\w-])/', $haystack);
    }

    /**
     * @return list<string>
     */
    private function bladeFiles(): array
    {
        $files = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views'), \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }
}
