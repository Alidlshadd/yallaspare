<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_settings_returns_full_grouped_payload(): void
    {
        $user = User::factory()->create([
            'theme_preference' => 'dark',
            'locale_preference' => 'ar',
            'notify_order_updates' => true,
            'notify_promotions' => false,
            'notify_stock_alerts' => true,
            'login_alerts' => true,
            'session_timeout' => '60',
            'email_notifications' => true,
            'sms_notifications' => false,
            'whatsapp_notifications' => true,
            'marketing_consent' => false,
            'currency_preference' => 'IQD',
            'timezone_preference' => 'Asia/Baghdad',
            'date_format_preference' => 'dmy',
            'default_contact_method' => 'whatsapp',
            'default_delivery_note' => 'Leave at door',
            'express_checkout' => true,
            'font_size_preference' => 'large',
            'reduced_motion' => true,
            'high_contrast_mode' => false,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/mobile/settings');

        $response->assertOk();
        $data = $response->json('data');

        $this->assertSame('dark', $data['appearance']['theme_preference']);
        $this->assertSame('large', $data['accessibility']['font_size_preference']);
        $this->assertSame('ar', $data['language']['locale_preference']);
        $this->assertTrue($data['notifications']['notify_order_updates']);
        $this->assertFalse($data['notifications']['notify_promotions']);
        $this->assertSame('60', $data['security']['session_timeout']);
        $this->assertTrue($data['communication']['whatsapp_notifications']);
        $this->assertSame('whatsapp', $data['checkout']['default_contact_method']);
        $this->assertSame('Leave at door', $data['checkout']['default_delivery_note']);
        $this->assertSame('IQD', $data['general']['currency_preference']);
        $this->assertSame('Asia/Baghdad', $data['general']['timezone_preference']);
        $this->assertSame('dmy', $data['general']['date_format_preference']);
    }

    public function test_patch_settings_appearance_updates_only_theme(): void
    {
        $user = User::factory()->create(['theme_preference' => 'light', 'locale_preference' => 'en']);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/mobile/settings/appearance', ['theme_preference' => 'dark'])
            ->assertOk();

        $this->assertSame('dark', $user->fresh()->theme_preference);
        $this->assertSame('en', $user->fresh()->locale_preference, 'unrelated fields must not change');
    }

    public function test_patch_settings_appearance_rejects_invalid_theme(): void
    {
        $user = User::factory()->create(['theme_preference' => 'light']);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/mobile/settings/appearance', ['theme_preference' => 'rainbow'])
            ->assertStatus(422);

        $this->assertSame('light', $user->fresh()->theme_preference);
    }

    public function test_patch_settings_language_updates_locale_preference(): void
    {
        $user = User::factory()->create(['locale_preference' => 'en']);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/mobile/settings/language', ['locale_preference' => 'ku'])
            ->assertOk();

        $this->assertSame('ku', $user->fresh()->locale_preference);
    }

    public function test_patch_settings_notifications_updates_three_flags(): void
    {
        $user = User::factory()->create([
            'notify_order_updates' => true,
            'notify_promotions' => true,
            'notify_stock_alerts' => true,
        ]);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/mobile/settings/notifications', [
                'notify_order_updates' => false,
                'notify_promotions' => false,
                'notify_stock_alerts' => true,
            ])
            ->assertOk();

        $fresh = $user->fresh();
        $this->assertFalse((bool) $fresh->notify_order_updates);
        $this->assertFalse((bool) $fresh->notify_promotions);
        $this->assertTrue((bool) $fresh->notify_stock_alerts);
    }

    public function test_patch_settings_security_updates_login_alerts_and_session_timeout(): void
    {
        $user = User::factory()->create(['login_alerts' => false, 'session_timeout' => '15']);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/mobile/settings/security', [
                'login_alerts' => true,
                'session_timeout' => '120',
            ])
            ->assertOk();

        $fresh = $user->fresh();
        $this->assertTrue((bool) $fresh->login_alerts);
        $this->assertSame('120', (string) $fresh->session_timeout);
    }

    public function test_patch_settings_security_rejects_invalid_timeout(): void
    {
        $user = User::factory()->create(['session_timeout' => '30']);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/mobile/settings/security', ['session_timeout' => '999'])
            ->assertStatus(422);

        $this->assertSame('30', (string) $user->fresh()->session_timeout);
    }

    public function test_patch_settings_communication_updates_four_flags(): void
    {
        $user = User::factory()->create([
            'email_notifications' => false,
            'sms_notifications' => false,
            'whatsapp_notifications' => false,
            'marketing_consent' => false,
        ]);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/mobile/settings/communication', [
                'email_notifications' => true,
                'sms_notifications' => true,
                'whatsapp_notifications' => true,
                'marketing_consent' => true,
            ])
            ->assertOk();

        $fresh = $user->fresh();
        $this->assertTrue((bool) $fresh->email_notifications);
        $this->assertTrue((bool) $fresh->sms_notifications);
        $this->assertTrue((bool) $fresh->whatsapp_notifications);
        $this->assertTrue((bool) $fresh->marketing_consent);
    }

    public function test_patch_settings_checkout_updates_three_fields(): void
    {
        $user = User::factory()->create([
            'default_contact_method' => 'phone',
            'default_delivery_note' => null,
            'express_checkout' => false,
        ]);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/mobile/settings/checkout', [
                'default_contact_method' => 'whatsapp',
                'default_delivery_note' => 'Ring twice',
                'express_checkout' => true,
            ])
            ->assertOk();

        $fresh = $user->fresh();
        $this->assertSame('whatsapp', $fresh->default_contact_method);
        $this->assertSame('Ring twice', $fresh->default_delivery_note);
        $this->assertTrue((bool) $fresh->express_checkout);
    }

    public function test_patch_settings_accessibility_updates_three_fields(): void
    {
        $user = User::factory()->create([
            'font_size_preference' => 'default',
            'reduced_motion' => false,
            'high_contrast_mode' => false,
        ]);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/mobile/settings/accessibility', [
                'font_size_preference' => 'xl',
                'reduced_motion' => true,
                'high_contrast_mode' => true,
            ])
            ->assertOk();

        $fresh = $user->fresh();
        $this->assertSame('xl', $fresh->font_size_preference);
        $this->assertTrue((bool) $fresh->reduced_motion);
        $this->assertTrue((bool) $fresh->high_contrast_mode);
    }

    public function test_patch_settings_full_updates_all_fields(): void
    {
        $user = User::factory()->create();

        $payload = [
            'theme_preference' => 'dark',
            'locale_preference' => 'ar',
            'notify_order_updates' => true,
            'notify_promotions' => false,
            'notify_stock_alerts' => true,
            'login_alerts' => true,
            'session_timeout' => '60',
            'email_notifications' => true,
            'sms_notifications' => false,
            'whatsapp_notifications' => true,
            'marketing_consent' => true,
            'currency_preference' => 'USD',
            'timezone_preference' => 'UTC',
            'date_format_preference' => 'ymd',
            'default_contact_method' => 'email',
            'default_delivery_note' => 'Back gate',
            'express_checkout' => true,
            'font_size_preference' => 'large',
            'reduced_motion' => true,
            'high_contrast_mode' => false,
        ];

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/mobile/settings', $payload)
            ->assertOk();

        $fresh = $user->fresh();
        $this->assertSame('dark', $fresh->theme_preference);
        $this->assertSame('USD', $fresh->currency_preference);
        $this->assertSame('UTC', $fresh->timezone_preference);
        $this->assertSame('ymd', $fresh->date_format_preference);
        $this->assertSame('Back gate', $fresh->default_delivery_note);
    }

    public function test_patch_settings_full_rejects_invalid_currency(): void
    {
        $user = User::factory()->create(['currency_preference' => 'IQD']);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/mobile/settings', [
                'theme_preference' => 'light',
                'locale_preference' => 'en',
                'session_timeout' => '30',
                'currency_preference' => 'EUR',
                'timezone_preference' => 'UTC',
                'date_format_preference' => 'ymd',
                'default_contact_method' => 'email',
                'font_size_preference' => 'default',
            ])
            ->assertStatus(422);

        $this->assertSame('IQD', $user->fresh()->currency_preference);
    }

    public function test_settings_endpoints_require_authentication(): void
    {
        $this->getJson('/api/mobile/settings')->assertStatus(401);
        $this->patchJson('/api/mobile/settings/appearance', ['theme_preference' => 'dark'])->assertStatus(401);
    }
}
