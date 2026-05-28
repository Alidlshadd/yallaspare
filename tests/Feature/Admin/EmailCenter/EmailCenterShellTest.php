<?php

namespace Tests\Feature\Admin\EmailCenter;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailCenterShellTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_sees_all_four_tabs(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $response = $this->actingAs($admin)->get('/admin/email');

        $response->assertOk();
        $response->assertSee('Email Center');
        $response->assertSee('Settings', false);
        $response->assertSee('Broadcast', false);
        $response->assertSee('History', false);
        $response->assertSee('Template Preview', false);
    }

    public function test_settings_manager_without_email_broadcast_sees_only_settings_and_preview(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_SETTINGS_MANAGER]);

        $response = $this->actingAs($user)->get('/admin/email');

        $response->assertOk();
        $response->assertSee('Settings', false);
        $response->assertSee('Template Preview', false);
        $response->assertDontSee('Broadcast');
        $response->assertDontSee('History');
    }

    public function test_admin_role_can_broadcast(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->assertTrue($admin->hasPermission(User::PERMISSION_EMAIL_BROADCAST));
    }

    public function test_settings_manager_cannot_broadcast(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_SETTINGS_MANAGER]);

        $this->assertFalse($user->hasPermission(User::PERMISSION_EMAIL_BROADCAST));
    }
}
