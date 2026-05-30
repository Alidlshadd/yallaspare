<?php

namespace Tests\Feature;

use App\Mail\SupportContactRequestMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MobileLegalAndContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_legal_index_lists_seven_content_pages(): void
    {
        $response = $this->getJson('/api/mobile/legal');

        $response->assertOk();
        $slugs = collect($response->json('data'))->pluck('slug')->all();
        $this->assertEqualsCanonicalizing(
            ['privacy', 'terms', 'support', 'about', 'return-exchange', 'shipping-delivery', 'distance-sales-agreement'],
            $slugs,
        );
    }

    public function test_legal_index_entries_carry_localized_title(): void
    {
        $response = $this->withHeaders(['Accept-Language' => 'ar'])
            ->getJson('/api/mobile/legal');

        $response->assertOk();
        $privacy = collect($response->json('data'))->firstWhere('slug', 'privacy');
        $this->assertNotEmpty($privacy['title']);
        $this->assertStringContainsString('سياسة', $privacy['title']);
    }

    public function test_legal_show_returns_title_html_and_slug(): void
    {
        $response = $this->getJson('/api/mobile/legal/privacy');

        $response->assertOk();
        $data = $response->json('data');

        $this->assertSame('privacy', $data['slug']);
        $this->assertNotEmpty($data['title']);
        $this->assertStringContainsString('Privacy', $data['title']);
        $this->assertNotEmpty($data['html']);
        $this->assertStringNotContainsString('<!DOCTYPE html>', $data['html'], 'html field must be content fragment, not full page');
        $this->assertStringContainsString('Personal Information', $data['html']);
    }

    public function test_legal_show_arabic_content_via_accept_language(): void
    {
        $response = $this->withHeaders(['Accept-Language' => 'ar'])
            ->getJson('/api/mobile/legal/terms');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertNotEmpty($data['html']);
        // Arabic locale should produce Arabic content
        $this->assertMatchesRegularExpression('/[\x{0600}-\x{06FF}]/u', $data['html']);
    }

    public function test_legal_show_unknown_slug_returns_404(): void
    {
        $this->getJson('/api/mobile/legal/this-does-not-exist')->assertStatus(404);
    }

    public function test_legal_show_contact_slug_returns_404_since_contact_is_a_form(): void
    {
        $this->getJson('/api/mobile/legal/contact')->assertStatus(404);
    }

    public function test_contact_form_submission_queues_mail_and_returns_success_message(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/mobile/legal/contact', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'topic' => 'general',
            'subject' => 'Question about brake pads',
            'message' => 'Do you carry brake pads for a 2018 Hyundai Elantra?',
        ]);

        $response->assertOk();
        $this->assertNotEmpty($response->json('message'));
        Mail::assertQueued(SupportContactRequestMail::class, function (SupportContactRequestMail $mail) {
            return $mail->data['email'] === 'test@example.com'
                && $mail->data['topic'] === 'general';
        });
    }

    public function test_contact_form_rejects_missing_required_fields(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/mobile/legal/contact', []);

        $response->assertStatus(422);
        Mail::assertNothingQueued();
    }

    public function test_contact_form_rejects_invalid_email(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/mobile/legal/contact', [
            'name' => 'Test',
            'email' => 'not-an-email',
            'topic' => 'general',
            'subject' => 'Test',
            'message' => 'Test message',
        ]);

        $response->assertStatus(422);
        Mail::assertNothingQueued();
    }

    public function test_contact_form_accepts_optional_phone_field(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/mobile/legal/contact', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '+964 770 123 4567',
            'topic' => 'general',
            'subject' => 'Test',
            'message' => 'Test message',
        ]);

        $response->assertOk();
        Mail::assertQueued(SupportContactRequestMail::class, fn ($mail) =>
            $mail->data['phone'] === '+964 770 123 4567'
        );
    }
}
