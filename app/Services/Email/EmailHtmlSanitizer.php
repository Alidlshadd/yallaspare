<?php

namespace App\Services\Email;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Defense-in-depth sanitizer for admin-authored email template HTML.
 * Allowlist matches the legacy strip_tags whitelist 1:1 (20 tags).
 */
class EmailHtmlSanitizer
{
    /** @var list<string> */
    private const SIMPLE_TAGS = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's',
        'ul', 'ol', 'li',
        'h1', 'h2', 'h3', 'h4',
        'blockquote', 'hr', 'span', 'div',
    ];

    private HtmlSanitizer $sanitizer;

    public function __construct()
    {
        $config = (new HtmlSanitizerConfig())
            ->withMaxInputLength(65000)
            ->allowLinkSchemes(['http', 'https', 'mailto', 'tel']);

        foreach (self::SIMPLE_TAGS as $tag) {
            $config = $config->allowElement($tag);
        }

        $config = $config
            ->allowElement('a', ['href', 'title', 'target'])
            ->forceAttribute('a', 'rel', 'noopener noreferrer');

        $this->sanitizer = new HtmlSanitizer($config);
    }

    public function clean(string $html): string
    {
        return $this->sanitizer->sanitize($html);
    }
}
