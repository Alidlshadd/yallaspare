<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Collapses the whitespace that templating leaves in rendered HTML so the
 * page source is compact instead of thousands of indented lines.
 *
 * This is a cosmetic / bandwidth optimisation — NOT a security control.
 * Minified front-end code can still be read by anyone determined; real
 * secrets live server-side and never reach the browser.
 *
 * The transform is deliberately spec-aligned and conservative:
 *  - <script>, <style>, <pre>, <textarea> bodies are preserved byte-for-byte
 *    (JS newlines matter for ASI; pre/textarea whitespace is visible).
 *  - Quoted attribute values are preserved (their spacing may be meaningful).
 *  - Whitespace runs collapse to a SINGLE space, never to nothing. In HTML a
 *    run of whitespace already renders as one space, and the space between
 *    inline elements is significant, so keeping one space changes no layout.
 */
class MinifyHtmlResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldMinify($response)) {
            return $response;
        }

        $content = $response->getContent();
        if (! is_string($content) || $content === '') {
            return $response;
        }

        $response->setContent($this->minify($content));

        return $response;
    }

    private function shouldMinify(Response $response): bool
    {
        if (! (bool) config('app.minify_html', true)) {
            return false;
        }

        if ($response instanceof StreamedResponse || $response instanceof BinaryFileResponse) {
            return false;
        }

        $contentType = (string) $response->headers->get('Content-Type');

        return str_contains(strtolower($contentType), 'text/html');
    }

    private function minify(string $html): string
    {
        $preserved = [];
        $stash = function (string $value) use (&$preserved): string {
            $key = "\x01MIN" . count($preserved) . "\x01";
            $preserved[$key] = $value;

            return $key;
        };

        // 1) Protect whitespace-sensitive / executable blocks in full.
        $html = preg_replace_callback(
            '#<(pre|textarea|script|style)\b[^>]*>.*?</\1\s*>#is',
            fn (array $m): string => $stash($m[0]),
            $html
        ) ?? $html;

        // 2) Drop HTML comments (keep IE conditional comments intact).
        $html = preg_replace('/<!--(?!\[if\b)(?!<!\[endif\b).*?-->/s', '', $html) ?? $html;

        // 3) Protect quoted attribute values so their spacing is untouched.
        $html = preg_replace_callback(
            '/"[^"]*"|\'[^\']*\'/s',
            fn (array $m): string => $stash($m[0]),
            $html
        ) ?? $html;

        // 4) Collapse every remaining whitespace run to a single space.
        $html = preg_replace('/\s+/', ' ', $html) ?? $html;

        $html = trim($html);

        // 5) Restore protected content.
        return strtr($html, $preserved);
    }
}
