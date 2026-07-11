<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserPrivilegeHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_manager_cannot_grant_themselves_finance_manage(): void
    {
        $admin = $this->userManager();

        $this->actingAs($admin)
            ->patch(route('admin.users.update-details', $admin), $this->userDetailsPayload($admin, [
                User::PERMISSION_USERS_MANAGE,
                User::PERMISSION_FINANCE_MANAGE,
            ]))
            ->assertSessionHas('error');

        $this->assertFalse($admin->fresh()->hasPermission(User::PERMISSION_FINANCE_MANAGE));
    }

    public function test_users_manager_cannot_grant_themselves_settings_manage(): void
    {
        $admin = $this->userManager();

        $this->actingAs($admin)
            ->patch(route('admin.users.update-details', $admin), $this->userDetailsPayload($admin, [
                User::PERMISSION_USERS_MANAGE,
                User::PERMISSION_SETTINGS_MANAGE,
            ]))
            ->assertSessionHas('error');

        $this->assertFalse($admin->fresh()->hasPermission(User::PERMISSION_SETTINGS_MANAGE));
    }

    public function test_users_manager_cannot_grant_themselves_stock_manage(): void
    {
        $admin = $this->userManager();

        $this->actingAs($admin)
            ->patch(route('admin.users.update-details', $admin), $this->userDetailsPayload($admin, [
                User::PERMISSION_USERS_MANAGE,
                User::PERMISSION_STOCK_MANAGE,
            ]))
            ->assertSessionHas('error');

        $this->assertFalse($admin->fresh()->hasPermission(User::PERMISSION_STOCK_MANAGE));
    }

    public function test_users_manager_cannot_assign_users_manage_permission(): void
    {
        $admin = $this->userManager();
        $target = User::factory()->create(['role' => User::ROLE_USER]);

        $this->actingAs($admin)
            ->patch(route('admin.users.update-details', $target), $this->userDetailsPayload($target, [
                User::PERMISSION_USERS_MANAGE,
            ]))
            ->assertSessionHas('error');

        $this->assertFalse($target->fresh()->hasPermission(User::PERMISSION_USERS_MANAGE));
    }

    public function test_users_manager_cannot_promote_themselves_to_admin(): void
    {
        $admin = $this->userManager();

        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->patch(route('admin.users.update-role', $admin), [
                'role' => User::ROLE_ADMIN,
            ])
            ->assertSessionHas('error');

        $this->assertSame(User::ROLE_ADMIN, $admin->fresh()->role);
        $this->assertSame([User::PERMISSION_USERS_MANAGE], $admin->fresh()->permissions);
    }

    public function test_users_manager_cannot_promote_another_user_to_admin(): void
    {
        $admin = $this->userManager();
        $target = User::factory()->create(['role' => User::ROLE_USER]);

        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->patch(route('admin.users.update-role', $target), [
                'role' => User::ROLE_ADMIN,
            ])
            ->assertSessionHas('error');

        $this->assertSame(User::ROLE_USER, $target->fresh()->role);
    }

    public function test_users_manager_cannot_assign_finance_manager_role(): void
    {
        $admin = $this->userManager();
        $target = User::factory()->create(['role' => User::ROLE_USER]);

        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->patch(route('admin.users.update-role', $target), [
                'role' => User::ROLE_FINANCE_MANAGER,
            ])
            ->assertSessionHas('error');

        $this->assertSame(User::ROLE_USER, $target->fresh()->role);
    }

    public function test_super_admin_can_still_assign_allowed_roles_and_permissions(): void
    {
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
        $target = User::factory()->create(['role' => User::ROLE_USER]);

        $this->actingAs($superAdmin)
            ->patch(route('admin.users.update-details', $target), $this->userDetailsPayload($target, [
                User::PERMISSION_FINANCE_VIEW,
                User::PERMISSION_FINANCE_MANAGE,
            ], User::ROLE_FINANCE_MANAGER))
            ->assertSessionHas('success');

        $target->refresh();
        $this->assertSame(User::ROLE_FINANCE_MANAGER, $target->role);
        $this->assertTrue($target->hasPermission(User::PERMISSION_FINANCE_MANAGE));
    }

    public function test_users_manager_cannot_reset_admin_password(): void
    {
        $admin = $this->userManager();
        $target = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'password' => Hash::make('OriginalPass123!'),
        ]);

        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->patch(route('admin.users.update-password', $target), $this->passwordPayload())
            ->assertSessionHas('error');

        $this->assertFalse(Hash::check('NewSecurePass123!', (string) $target->fresh()->password));
    }

    public function test_users_manager_cannot_reset_super_admin_password(): void
    {
        $admin = $this->userManager();
        $target = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'password' => Hash::make('OriginalPass123!'),
        ]);

        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->patch(route('admin.users.update-password', $target), $this->passwordPayload())
            ->assertSessionHas('error');

        $this->assertFalse(Hash::check('NewSecurePass123!', (string) $target->fresh()->password));
    }

    public function test_users_manager_cannot_delete_admin(): void
    {
        $admin = $this->userManager();
        $target = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->delete(route('admin.users.destroy', $target))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_users_manager_cannot_delete_super_admin(): void
    {
        $admin = $this->userManager();
        $target = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->delete(route('admin.users.destroy', $target))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_super_admin_can_reset_password_and_revokes_sessions_and_tokens(): void
    {
        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $target = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'password' => Hash::make('OriginalPass123!'),
        ]);
        $target->createToken('mobile');
        DB::table('sessions')->insert([
            'id' => 'target-session',
            'user_id' => $target->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'payload' => 'test',
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($superAdmin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->patch(route('admin.users.update-password', $target), $this->passwordPayload())
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('NewSecurePass123!', (string) $target->fresh()->password));
        $this->assertSame(0, $target->tokens()->count());
        $this->assertDatabaseMissing('sessions', ['id' => 'target-session']);
        $this->assertDatabaseHas('admin_activity_logs', [
            'action' => 'security.password_reset_completed',
            'subject_id' => $target->id,
        ]);
    }

    public function test_super_admin_can_delete_privileged_account(): void
    {
        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $target = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($superAdmin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->delete(route('admin.users.destroy', $target))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_mobile_users_manager_cannot_promote_themselves_to_admin(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_USER,
            'permissions' => [User::PERMISSION_USERS_MANAGE],
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($admin, ['admin:mobile']);

        $this->patchJson("/api/mobile/admin/users/{$admin->id}/role", [
            'role' => User::ROLE_ADMIN,
        ])->assertForbidden();

        $this->assertSame(User::ROLE_USER, $admin->fresh()->role);
    }

    public function test_mobile_users_manager_cannot_promote_another_user_to_admin(): void
    {
        $admin = $this->userManager();
        $target = User::factory()->create(['role' => User::ROLE_USER]);

        Sanctum::actingAs($admin, ['admin:mobile']);

        $this->patchJson("/api/mobile/admin/users/{$target->id}/role", [
            'role' => User::ROLE_ADMIN,
        ])->assertForbidden();

        $this->assertSame(User::ROLE_USER, $target->fresh()->role);
    }

    public function test_mobile_users_manager_cannot_assign_finance_manager_role(): void
    {
        $admin = $this->userManager();
        $target = User::factory()->create(['role' => User::ROLE_USER]);

        Sanctum::actingAs($admin, ['admin:mobile']);

        $this->patchJson("/api/mobile/admin/users/{$target->id}/role", [
            'role' => User::ROLE_FINANCE_MANAGER,
        ])->assertForbidden();

        $this->assertSame(User::ROLE_USER, $target->fresh()->role);
    }

    private function userManager(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'permissions' => [User::PERMISSION_USERS_MANAGE],
            'email_verified_at' => now(),
        ]);
    }

    private function userDetailsPayload(User $user, array $permissions, ?string $role = null): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => null,
            'date_of_birth' => null,
            'role' => $role ?? $user->role,
            'permissions' => $permissions,
            'dealer_status' => User::DEALER_STATUS_INACTIVE,
            'dealer_discount' => 0,
        ];
    }

    private function passwordPayload(): array
    {
        return [
            'password' => 'NewSecurePass123!',
            'password_confirmation' => 'NewSecurePass123!',
        ];
    }
}
