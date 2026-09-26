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
        $config = (new HtmlSanitizerConfig)
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

    /**
     * Stands in for a {url} link while the sanitizer runs. A bare "{url}" is
     * not an http(s) address, so the sanitizer used to strip every templated
     * link — a saved password-reset template came back with no link at all.
     */
    private const TOKEN_HOST = 'https://template-token.invalid/';

    public function clean(string $html): string
    {
        // Only {url}: it is always a link the application built. Any other
        // token may carry customer text ("javascript:..." as a name).
        $protected = preg_replace(
            '/href=(["\'])\{(url)\}\1/i',
            'href="'.self::TOKEN_HOST.'$2"',
            $html
        ) ?? $html;

        $clean = $this->sanitizer->sanitize($protected);

        return preg_replace(
            '#href="'.preg_quote(self::TOKEN_HOST, '#').'(url)"#i',
            'href="{$1}"',
            $clean
        ) ?? $clean;
    }
}
