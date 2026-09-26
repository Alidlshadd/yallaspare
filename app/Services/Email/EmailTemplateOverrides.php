<?php

namespace App\Services\Email;

use App\Models\EmailTemplate;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * What an administrator wrote in the email template editor, applied to a real
 * message.
 *
 * An override replaces the subject and the lead copy only. The code box, the
 * button, the order table and the rest of the layout stay where the view puts
 * them, so an edited template can reword a password reset but cannot send one
 * without its link, or a verification email without its code.
 */
class EmailTemplateOverrides
{
    public function __construct(private readonly EmailHtmlSanitizer $sanitizer) {}

    /**
     * View data to merge into the message: `customBodyHtml` for the view and
     * `intro` for the plain-text part, or nothing when no override is saved.
     *
     * @param  array<string, scalar|null>  $vars  values for {token} placeholders
     * @return array{customBodyHtml?: HtmlString, intro?: string}
     */
    public function viewData(string $key, array $vars = [], ?string $locale = null): array
    {
        $override = $this->find($key, $locale);
        $body = $override ? trim($this->sanitizer->clean((string) $override->body_html)) : '';

        if ($body === '') {
            return [];
        }

        $html = $body;
        foreach ($vars as $token => $value) {
            $html = str_replace('{'.$token.'}', e((string) $value), $html);
        }

        return [
            'customBodyHtml' => new HtmlString($html),
            'intro' => trim(html_entity_decode(strip_tags(str_replace(['</p>', '<br>', '<br/>', '<br />'], "\n", $html)), ENT_QUOTES)),
        ];
    }

    /**
     * The saved subject with its placeholders filled, or the fallback.
     *
     * @param  array<string, scalar|null>  $vars
     */
    public function subject(string $key, string $fallback, array $vars = [], ?string $locale = null): string
    {
        $subject = trim((string) $this->find($key, $locale)?->subject);

        if ($subject === '') {
            return $fallback;
        }

        foreach ($vars as $token => $value) {
            $subject = str_replace('{'.$token.'}', (string) $value, $subject);
        }

        // A header line: no markup, no line breaks.
        return Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($subject)) ?? ''), 200, '') ?: $fallback;
    }

    private function find(string $key, ?string $locale): ?EmailTemplate
    {
        $locale = in_array($locale ?? app()->getLocale(), ['en', 'ar', 'ku'], true)
            ? ($locale ?? app()->getLocale())
            : 'en';

        return EmailTemplate::findOverride($key, $locale);
    }
}
