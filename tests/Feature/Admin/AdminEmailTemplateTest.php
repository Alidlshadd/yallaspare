<?php

namespace Tests\Feature\Admin;

use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminEmailTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_all_templates(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SETTINGS_MANAGER,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.email.templates.index'))
            ->assertOk()
            ->assertSee('Transactional templates')
            ->assertSee('Email verification')
            ->assertSee('Order status')
            ->assertSee('Security alert');
    }

    public function test_edit_form_prefills_defaults_when_no_override(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SETTINGS_MANAGER,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.email.templates.edit', ['key' => 'verify-email', 'locale' => 'en']))
            ->assertOk()
            ->assertSee('Verify your email address')
            ->assertSee('Body (HTML)');
    }

    public function test_saving_stores_override(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SETTINGS_MANAGER,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.email.templates.update', ['key' => 'verify-email', 'locale' => 'en']), [
                'subject' => 'Custom verification subject',
                'body_html' => '<p>Custom body with {code}</p>',
            ])
            ->assertRedirect(route('admin.email.templates.edit', ['key' => 'verify-email', 'locale' => 'en']));

        $override = EmailTemplate::query()
            ->where('template_key', 'verify-email')
            ->where('locale', 'en')
            ->first();

        $this->assertNotNull($override);
        $this->assertSame('Custom verification subject', $override->subject);
        $this->assertStringContainsString('Custom body', $override->body_html);
        $this->assertSame($admin->getKey(), $override->updated_by);
    }

    public function test_saving_strips_script_tags(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SETTINGS_MANAGER,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.email.templates.update', ['key' => 'welcome', 'locale' => 'en']), [
                'subject' => 'Welcome',
                'body_html' => '<p>Hello</p><script>alert(1)</script><p onclick="alert(2)">click</p>',
            ])
            ->assertRedirect();

        $override = EmailTemplate::query()
            ->where('template_key', 'welcome')
            ->where('locale', 'en')
            ->first();

        $this->assertStringNotContainsString('<script>', $override->body_html);
        $this->assertStringNotContainsString('alert(1)', $override->body_html);
        $this->assertStringNotContainsString('onclick', $override->body_html);
    }

    public function test_unknown_template_key_returns_404(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SETTINGS_MANAGER,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.email.templates.edit', ['key' => 'nonexistent-key', 'locale' => 'en']))
            ->assertNotFound();
    }

    public function test_preview_renders_with_sample_data(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SETTINGS_MANAGER,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.email.templates.preview', ['key' => 'verify-email', 'locale' => 'en']))
            ->assertOk()
            ->assertSee('YALLA SPARE', false);
    }

    public function test_saving_requires_settings_manager(): void
    {
        $customer = User::factory()->create([
            'role' => User::ROLE_USER,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($customer)
            ->patch(route('admin.email.templates.update', ['key' => 'welcome', 'locale' => 'en']), [
                'subject' => 'x',
                'body_html' => '<p>x</p>',
            ])
            ->assertForbidden();
    }
}
