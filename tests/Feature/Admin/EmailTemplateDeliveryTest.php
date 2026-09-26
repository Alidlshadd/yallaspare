<?php

namespace Tests\Feature\Admin;

use App\Mail\OperationalNotificationMail;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Notifications\ImmediateResetPassword;
use App\Notifications\ImmediateVerifyEmail;
use App\Services\Email\EmailHtmlSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * What is saved in the template editor has to reach the mail that is sent.
 */
class EmailTemplateDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_saved_verification_template_reaches_the_email_and_keeps_the_code_box(): void
    {
        $user = User::factory()->create(['name' => 'Ahmed']);
        $this->saveTemplate('verify-email', 'Hello {name}, confirm your account', '<p>Custom welcome copy for {email}.</p>');

        $mail = (new ImmediateVerifyEmail('482913'))->toMail($user);
        $html = (string) $mail->render();

        $this->assertSame('Hello Ahmed, confirm your account', $mail->subject);
        $this->assertStringContainsString('Custom welcome copy for '.e($user->email).'.', $html);
        // The default lead copy is replaced, the code box is not.
        $this->assertStringNotContainsString('unlock checkout, order tracking', $html);
        $this->assertStringContainsString('482913', $html);
    }

    public function test_a_password_reset_template_can_carry_its_link(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_SETTINGS_MANAGER]);
        $this->actingAs($admin)->patch(
            route('admin.email.templates.update', ['key' => 'reset-password', 'locale' => 'en']),
            ['subject' => 'Reset it', 'body_html' => '<p>Tap <a href="{url}">here</a> to choose a new password.</p>']
        );
        $this->assertStringContainsString('href="{url}"', (string) EmailTemplate::findOverride('reset-password', 'en')?->body_html);

        $user = User::factory()->create();
        $html = (string) (new ImmediateResetPassword('reset-token-abc'))->toMail($user)->render();

        $this->assertMatchesRegularExpression('#<a href="[^"]*reset-token-abc[^"]*" rel="noopener noreferrer">here</a>#', $html);
    }

    public function test_customer_text_is_escaped_inside_a_template(): void
    {
        $this->saveTemplate('order-status', 'Update for {order}', '<p>Hi {name}: {message}</p>');

        $mail = new OperationalNotificationMail('Order shipped', '<img src=x onerror=alert(1)>', [
            'type' => 'order_status_updated',
            'name' => '<script>alert(1)</script>',
            'to' => 'shipped',
        ]);
        $html = $mail->render();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_no_saved_template_leaves_the_default_email_untouched(): void
    {
        $user = User::factory()->create();

        $mail = (new ImmediateVerifyEmail('111222'))->toMail($user);

        $this->assertSame(__('Verify your YallaSpare email address'), $mail->subject);
        $this->assertStringContainsString('unlock checkout, order tracking', (string) $mail->render());
    }

    public function test_only_the_url_token_survives_inside_a_link(): void
    {
        $clean = app(EmailHtmlSanitizer::class)->clean('<a href="{url}">ok</a><a href="{name}">no</a>');

        $this->assertStringContainsString('href="{url}"', $clean);
        $this->assertStringNotContainsString('href="{name}"', $clean);
    }

    private function saveTemplate(string $key, string $subject, string $body): void
    {
        EmailTemplate::query()->create([
            'template_key' => $key,
            'locale' => 'en',
            'subject' => $subject,
            'body_html' => $body,
        ]);
    }
}
