<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUsersIndexTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
    }

    public function test_super_admin_can_view_users_index(): void
    {
        $admin = $this->superAdmin();
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'name' => 'Plain Customer Account',
        ]);

        $this->withSession(['auth.password_confirmed_at' => time()])
            ->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Plain Customer Account')
            ->assertSee($user->email);
    }

    public function test_role_filter_limits_results(): void
    {
        $admin = $this->superAdmin();
        User::factory()->create([
            'role' => User::ROLE_DEALER,
            'name' => 'Dealer Only Account',
        ]);
        User::factory()->create([
            'role' => User::ROLE_USER,
            'name' => 'Regular Only Account',
        ]);

        $this->withSession(['auth.password_confirmed_at' => time()])
            ->actingAs($admin)
            ->get(route('admin.users.index', ['filter' => 'dealer']))
            ->assertOk()
            ->assertSee('Dealer Only Account')
            ->assertDontSee('Regular Only Account');
    }

    public function test_unverified_filter_limits_results(): void
    {
        $admin = $this->superAdmin();
        User::factory()->unverified()->create([
            'role' => User::ROLE_USER,
            'name' => 'Unverified Email Account',
        ]);
        User::factory()->create([
            'role' => User::ROLE_USER,
            'name' => 'Verified Email Account',
            'email_verified_at' => now(),
        ]);

        $this->withSession(['auth.password_confirmed_at' => time()])
            ->actingAs($admin)
            ->get(route('admin.users.index', ['filter' => 'unverified']))
            ->assertOk()
            ->assertSee('Unverified Email Account')
            ->assertDontSee('Verified Email Account');
    }

    public function test_invalid_filter_falls_back_to_all(): void
    {
        $admin = $this->superAdmin();
        User::factory()->create([
            'role' => User::ROLE_USER,
            'name' => 'Fallback Visible Account',
        ]);

        $this->withSession(['auth.password_confirmed_at' => time()])
            ->actingAs($admin)
            ->get(route('admin.users.index', ['filter' => 'nonsense']))
            ->assertOk()
            ->assertSee('Fallback Visible Account');
    }

    public function test_ban_status_filters_limit_results(): void
    {
        $admin = $this->superAdmin();
        User::factory()->create(['name' => 'Active Filter Account']);
        User::factory()->create([
            'name' => 'Temporary Ban Filter Account',
            'banned_at' => now(),
            'banned_until' => now()->addDays(7),
            'ban_reason' => 'Temporary test',
        ]);
        User::factory()->create([
            'name' => 'Permanent Ban Filter Account',
            'banned_at' => now(),
            'banned_until' => null,
            'ban_reason' => 'Permanent test',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['filter' => 'banned']))
            ->assertOk()
            ->assertSee('Temporary Ban Filter Account')
            ->assertSee('Permanent Ban Filter Account')
            ->assertDontSee('Active Filter Account');

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['filter' => 'temporarily_banned']))
            ->assertOk()
            ->assertSee('Temporary Ban Filter Account')
            ->assertDontSee('Permanent Ban Filter Account');

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['filter' => 'permanently_banned']))
            ->assertOk()
            ->assertSee('Permanent Ban Filter Account')
            ->assertDontSee('Temporary Ban Filter Account');

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['filter' => 'active']))
            ->assertOk()
            ->assertSee('Active Filter Account')
            ->assertDontSee('Temporary Ban Filter Account')
            ->assertDontSee('Permanent Ban Filter Account');
    }

    public function test_users_sidebar_shows_access_status_counts(): void
    {
        $admin = $this->superAdmin();
        User::factory()->create([
            'banned_at' => now(),
            'banned_until' => now()->addDay(),
            'ban_reason' => 'Temporary count',
        ]);
        User::factory()->create([
            'banned_at' => now(),
            'banned_until' => null,
            'ban_reason' => 'Permanent count',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Access Status')
            ->assertSee('Active accounts')
            ->assertSee('All banned')
            ->assertSee('Temporary Ban')
            ->assertSee('Permanent Ban');
    }
}
