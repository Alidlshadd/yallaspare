<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\AdminTwoFactorCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminTwoFactorSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_requires_two_factor_when_enabled(): void
    {
        config(['security.admin_two_factor.enabled' => true]);
        Notification::fake();

        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.two-factor.challenge'));

        Notification::assertSentTo($admin, AdminTwoFactorCode::class);
    }

    public function test_admin_route_redirects_until_two_factor_verified(): void
    {
        config(['security.admin_two_factor.enabled' => true]);

        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.two-factor.challenge'));

        $this->withSession(['admin_2fa.verified_user_id' => $admin->id])
            ->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_admin_two_factor_blocks_legacy_profile_and_password_routes_until_verified(): void
    {
        config(['security.admin_two_factor.enabled' => true]);

        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('profile.edit'))
            ->assertRedirect(route('admin.two-factor.challenge'));

        $this->actingAs($admin)
            ->put(route('password.update'), [
                'current_password' => 'password',
                'password' => 'new-password1',
                'password_confirmation' => 'new-password1',
            ])
            ->assertRedirect(route('admin.two-factor.challenge'));

        $this->withSession(['admin_2fa.verified_user_id' => $admin->id])
            ->actingAs($admin)
            ->get(route('profile.edit'))
            ->assertOk();
    }
}
