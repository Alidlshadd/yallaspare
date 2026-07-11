<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserBanTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_temporarily_ban_a_user_and_revoke_tokens(): void
    {
        $admin = $this->superAdmin();
        $user = User::factory()->create();
        $user->createToken('customer-device');

        $this->actingAs($admin)
            ->patch(route('admin.users.update-ban', $user), [
                'ban_type' => 'temporary',
                'duration_days' => 7,
                'ban_reason' => 'Repeated payment abuse',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $fresh = $user->fresh();
        $this->assertTrue($fresh->isBanned());
        $this->assertFalse($fresh->isPermanentlyBanned());
        $this->assertSame('Repeated payment abuse', $fresh->ban_reason);
        $this->assertNotNull($fresh->banned_until);
        $this->assertTrue($fresh->banned_until->between(now()->addDays(6), now()->addDays(8)));
        $this->assertSame(0, $fresh->tokens()->count());
    }

    public function test_super_admin_can_permanently_ban_and_unban_a_user(): void
    {
        $admin = $this->superAdmin();
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->patch(route('admin.users.update-ban', $user), [
                'ban_type' => 'permanent',
                'ban_reason' => 'Confirmed account fraud',
            ])
            ->assertSessionHas('success');

        $fresh = $user->fresh();
        $this->assertTrue($fresh->isBanned());
        $this->assertTrue($fresh->isPermanentlyBanned());
        $this->assertNull($fresh->banned_until);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy-ban', $user))
            ->assertSessionHas('success');

        $fresh = $user->fresh();
        $this->assertFalse($fresh->isBanned());
        $this->assertNull($fresh->banned_at);
        $this->assertNull($fresh->banned_until);
        $this->assertNull($fresh->ban_reason);
    }

    public function test_admin_cannot_ban_their_own_account(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->patch(route('admin.users.update-ban', $admin), [
                'ban_type' => 'permanent',
                'ban_reason' => 'Should be rejected',
            ])
            ->assertSessionHas('error');

        $this->assertFalse($admin->fresh()->isBanned());
    }

    public function test_non_super_user_manager_cannot_ban_a_privileged_account(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);
        $manager->forceFill(['permissions' => [User::PERMISSION_USERS_MANAGE]])->save();

        $target = User::factory()->create([
            'role' => User::ROLE_FINANCE_MANAGER,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($manager)
            ->patch(route('admin.users.update-ban', $target), [
                'ban_type' => 'permanent',
                'ban_reason' => 'Unauthorized action',
            ])
            ->assertSessionHas('error');

        $this->assertFalse($target->fresh()->isBanned());
    }

    public function test_admin_can_ban_a_regular_user(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);
        $admin->forceFill(['permissions' => [User::PERMISSION_USERS_MANAGE]])->save();
        $target = User::factory()->create(['role' => User::ROLE_USER]);

        $this->actingAs($admin)
            ->patch(route('admin.users.update-ban', $target), [
                'ban_type' => 'temporary',
                'duration_days' => 7,
                'ban_reason' => 'Account review',
            ])
            ->assertSessionHas('success');

        $this->assertTrue($target->fresh()->isBanned());
    }

    public function test_manager_with_users_permission_cannot_ban_a_regular_user_or_see_controls(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_PRODUCT_MANAGER,
            'email_verified_at' => now(),
        ]);
        $manager->forceFill(['permissions' => [User::PERMISSION_USERS_MANAGE]])->save();
        $target = User::factory()->create(['role' => User::ROLE_USER]);

        $this->actingAs($manager)
            ->patch(route('admin.users.update-ban', $target), [
                'ban_type' => 'permanent',
                'ban_reason' => 'Unauthorized manager action',
            ])
            ->assertSessionHas('error');

        $this->assertFalse($target->fresh()->isBanned());

        $this->actingAs($manager)
            ->get(route('admin.users.show', $target))
            ->assertOk()
            ->assertDontSee('Apply Ban')
            ->assertDontSee(route('admin.users.update-ban', $target), false);
    }

    public function test_user_pages_render_ban_controls_and_status_badges(): void
    {
        $admin = $this->superAdmin();
        $activeUser = User::factory()->create();
        $user = User::factory()->create([
            'banned_at' => now(),
            'banned_until' => null,
            'ban_reason' => 'Permanent policy violation',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee('Ban Management')
            ->assertSee('Permanent Ban')
            ->assertSee('Remove Ban');

        $this->actingAs($admin)
            ->get(route('admin.users.show', $activeUser))
            ->assertOk()
            ->assertSee('Apply Temporary Ban')
            ->assertSee('Apply Permanent Ban')
            ->assertSee('name="ban_type"', false)
            ->assertSee('value="temporary"', false)
            ->assertSee('value="permanent"', false);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Permanent Ban');
    }

    private function superAdmin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
    }
}
