<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_assign_a_privileged_role_with_a_shared_phone(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
        User::factory()->create(['phone' => '+964 750 123 4567']);
        $target = User::factory()->create([
            'phone' => '+964 770 987 6543',
            'role' => User::ROLE_USER,
        ]);

        $this->withSession(['auth.password_confirmed_at' => time()])
            ->actingAs($admin)
            ->patch(route('admin.users.update-details', $target), [
                'name' => $target->name,
                'email' => $target->email,
                'phone' => '0750 123 4567',
                'role' => User::ROLE_PRODUCT_MANAGER,
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $target->refresh();
        $this->assertSame(User::ROLE_PRODUCT_MANAGER, $target->role);
        $this->assertSame('9647501234567', $target->phone_normalized);
    }

    public function test_super_admin_can_mark_user_email_as_verified(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
        $user = User::factory()->unverified()->create([
            'role' => User::ROLE_USER,
        ]);

        $this->withSession(['auth.password_confirmed_at' => time()])
            ->actingAs($admin)
            ->patch(route('admin.users.update-details', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'role' => User::ROLE_USER,
                'email_verified' => '1',
            ])
            ->assertSessionHas('success');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_super_admin_can_mark_user_email_as_unverified(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'email_verified_at' => now(),
        ]);

        $this->withSession(['auth.password_confirmed_at' => time()])
            ->actingAs($admin)
            ->patch(route('admin.users.update-details', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'role' => User::ROLE_USER,
            ])
            ->assertSessionHas('success');

        $this->assertNull($user->refresh()->email_verified_at);
    }
}
