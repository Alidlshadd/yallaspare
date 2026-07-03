<?php

namespace Tests\Feature;

use Symfony\Component\Finder\Finder;
use Tests\TestCase;

class CspInlineHandlerGuardTest extends TestCase
{
    /**
     * The enforced CSP uses a nonce for <script> tags, but inline event
     * handler attributes (onclick=, onsubmit=, ...) can never carry a nonce
     * and are silently blocked. Views must use delegated listeners
     * (data-* hooks in resources/js/app.js) or Alpine directives instead.
     */
    public function test_blade_views_contain_no_inline_event_handler_attributes(): void
    {
        $finder = Finder::create()
            ->files()
            ->in(resource_path('views'))
            ->name('*.blade.php');

        $violations = [];

        foreach ($finder as $file) {
            $lines = preg_split('/\r?\n/', $file->getContents()) ?: [];
            foreach ($lines as $index => $line) {
                if (preg_match('/\son[a-z]{3,20}\s*=\s*["\']/i', $line)) {
                    $violations[] = $file->getRelativePathname() . ':' . ($index + 1);
                }
            }
        }

        $this->assertSame(
            [],
            $violations,
            "Inline event handlers found (blocked by nonce CSP):\n" . implode("\n", $violations)
        );
    }
}
