<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserVerificationTest extends TestCase
{
    use RefreshDatabase;

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
