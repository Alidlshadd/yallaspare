<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\EmailTemplateController;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Services\Email\EmailHtmlSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use Tests\TestCase;

class EmailTemplateSanitizerTest extends TestCase
{
    use RefreshDatabase;

    private EmailHtmlSanitizer $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sanitizer = app(EmailHtmlSanitizer::class);
    }

    public function test_default_template_bodies_pass_through_intact_across_all_locales(): void
    {
        $controller = app(EmailTemplateController::class);
        $method = (new ReflectionClass(EmailTemplateController::class))->getMethod('defaults');

        foreach (EmailTemplate::KEYS as $key) {
            foreach (EmailTemplate::LOCALES as $locale) {
                $body = $method->invoke($controller, $key, $locale)['body_html'];

                $cleaned = $this->sanitizer->clean($body);

                $this->assertSame(
                    $this->stripAndNormalize($body),
                    $this->stripAndNormalize($cleaned),
                    "Text diverged for {$key}/{$locale}",
                );

                $this->assertMatchesRegularExpression(
                    '/<(p|a|strong|em|ul|ol|li|h[1-4])[\s>]/i',
                    $cleaned,
                    "No allowed tag survived for {$key}/{$locale}",
                );
            }
        }
    }

    public function test_arabic_default_body_preserves_arabic_text_and_placeholder(): void
    {
        $controller = app(EmailTemplateController::class);
        $method = (new ReflectionClass(EmailTemplateController::class))->getMethod('defaults');

        $body = $method->invoke($controller, 'welcome', 'ar')['body_html'];

        $cleaned = $this->sanitizer->clean($body);

        $this->assertStringContainsString('{name}', $cleaned, 'Placeholder token was dropped');

        $stripped = $this->stripAndNormalize($body);
        $this->assertNotSame('', trim($stripped), 'Default body was empty — fixture broken');
        $this->assertSame($stripped, $this->stripAndNormalize($cleaned));
    }

    public function test_thirty_thousand_character_body_is_not_truncated(): void
    {
        $paragraph = '<p>'
            . str_repeat('Legit sentence with <strong>bold</strong> and <em>italic</em> text. ', 250)
            . '</p>';
        $body = str_repeat($paragraph, 3);

        $this->assertGreaterThan(30000, mb_strlen($body), 'Fixture too short');
        $this->assertLessThan(65000, mb_strlen($body), 'Fixture exceeds max_input_length');

        $cleaned = $this->sanitizer->clean($body);

        $this->assertGreaterThan(
            30000,
            mb_strlen($cleaned),
            'Sanitizer truncated a 30k body — withMaxInputLength misconfigured?',
        );
        $this->assertStringContainsString('<strong>bold</strong>', $cleaned);
        $this->assertStringContainsString('<em>italic</em>', $cleaned);
    }

    /**
     * @param  list<string>  $mustNotContain
     * @param  list<string>  $mustContain
     */
    #[DataProvider('xssVectorsProvider')]
    public function test_xss_vectors_are_neutralized(string $payload, array $mustNotContain, array $mustContain): void
    {
        $cleaned = $this->sanitizer->clean($payload);

        foreach ($mustNotContain as $needle) {
            $this->assertStringNotContainsStringIgnoringCase(
                $needle,
                $cleaned,
                "Sanitizer left dangerous content: {$needle}",
            );
        }

        foreach ($mustContain as $needle) {
            $this->assertStringContainsString(
                $needle,
                $cleaned,
                "Sanitizer dropped legit content: {$needle}",
            );
        }
    }

    /**
     * @return array<string, array{0:string, 1:list<string>, 2:list<string>}>
     */
    public static function xssVectorsProvider(): array
    {
        return [
            'script tag' => [
                '<script>alert(1)</script>hello',
                ['<script', 'alert(1)'],
                ['hello'],
            ],
            'javascript href' => [
                '<a href="javascript:alert(1)">click</a>',
                ['javascript:', 'alert(1)'],
                ['click'],
            ],
            'javascript href mixed case' => [
                '<a href="JaVaScRiPt:alert(1)">click</a>',
                ['javascript:', 'jav', 'alert(1)'],
                ['click'],
            ],
            'javascript href html entity encoded' => [
                '<a href="&#106;avascript:alert(1)">click</a>',
                ['javascript:', 'alert(1)'],
                ['click'],
            ],
            'data url' => [
                '<a href="data:text/html,<script>alert(1)</script>">click</a>',
                ['data:', '<script', 'alert(1)'],
                ['click'],
            ],
            'img onerror unquoted' => [
                '<img src=x onerror=alert(1)>legit',
                ['onerror', 'alert(1)', '<img'],
                ['legit'],
            ],
            'div onclick' => [
                '<div onclick="alert(1)">text</div>',
                ['onclick', 'alert(1)'],
                ['text'],
            ],
            'svg onload' => [
                '<svg onload=alert(1)></svg>fallback',
                ['<svg', 'onload', 'alert(1)'],
                ['fallback'],
            ],
            'iframe srcdoc' => [
                '<iframe srcdoc="<script>alert(1)</script>">tail</iframe>next',
                ['<iframe', 'srcdoc', '<script'],
                ['next'],
            ],
            'legit http link forced rel' => [
                '<a href="http://example.com">example</a>',
                ['javascript:'],
                ['href="http://example.com"', 'example', 'rel="noopener noreferrer"'],
            ],
            'legit mailto link' => [
                '<a href="mailto:hi@example.com">write</a>',
                ['javascript:'],
                ['mailto:hi', 'example.com', 'write', 'rel="noopener noreferrer"'],
            ],
            'legit tel link' => [
                '<a href="tel:+9647501234567">call</a>',
                ['javascript:'],
                ['tel:', '9647501234567', 'call', 'rel="noopener noreferrer"'],
            ],
            'placeholder in bold survives' => [
                '<p>Hello <strong>{name}</strong>!</p>',
                [],
                ['{name}', '<strong>', '<p>'],
            ],
        ];
    }

    public function test_admin_update_persists_sanitized_body_without_xss(): void
    {
        $admin = $this->makeAdmin();

        $payload = '<p>Legit copy</p><script>alert("xss")</script><a href="javascript:bad()">click</a>';

        $this->actingAs($admin)
            ->patch(
                route('admin.email.templates.update', ['key' => 'welcome', 'locale' => 'en']),
                ['subject' => 'Welcome', 'body_html' => $payload],
            )
            ->assertRedirect();

        $stored = EmailTemplate::query()
            ->where('template_key', 'welcome')
            ->where('locale', 'en')
            ->firstOrFail();

        $this->assertStringNotContainsStringIgnoringCase('<script', $stored->body_html);
        $this->assertStringNotContainsStringIgnoringCase('javascript:', $stored->body_html);
        $this->assertStringNotContainsStringIgnoringCase('alert(', $stored->body_html);
        $this->assertStringContainsString('Legit copy', $stored->body_html);
    }

    public function test_admin_edit_view_renders_sanitized_body_when_stored_row_is_dirty(): void
    {
        $admin = $this->makeAdmin();

        EmailTemplate::query()->create([
            'template_key' => 'welcome',
            'locale' => 'en',
            'subject' => 'Welcome',
            'body_html' => '<p>hi <script>alert(1)</script> friend</p>',
            'updated_by' => $admin->id,
        ]);

        $html = $this->actingAs($admin)
            ->get(route('admin.email.templates.edit', ['key' => 'welcome', 'locale' => 'en']))
            ->assertOk()
            ->getContent();

        // NOTE: admin layout has legit inline <script> blocks; only assert the
        // payload-specific signature leaked out, not the bare <script> token.
        $this->assertStringNotContainsStringIgnoringCase('alert(1)', $html);
        $this->assertStringNotContainsStringIgnoringCase('<script>alert', $html);
        $this->assertStringContainsString('hi', $html);
        $this->assertStringContainsString('friend', $html);
    }

    private function stripAndNormalize(string $html): string
    {
        return trim(preg_replace('/\s+/', ' ', strip_tags($html)) ?? '');
    }

    private function makeAdmin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_SETTINGS_MANAGER,
            'email_verified_at' => now(),
        ]);
    }
}
