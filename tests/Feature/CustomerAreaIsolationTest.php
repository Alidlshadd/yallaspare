<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAreaIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_session_is_not_rendered_as_customer_account_on_storefront(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin Account',
            'email' => 'admin@example.com',
        ]);
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();

        $response = $this->actingAs($admin)->get(route('user.shop.home'));

        $response->assertOk();
        $response->assertSee('Login');
        $response->assertSee('Register');
        $response->assertDontSee('Admin Account');
        $response->assertDontSee('admin@example.com');
    }

    public function test_admin_session_is_redirected_away_from_customer_account_pages(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();

        $this->actingAs($admin)
            ->get(route('user.account.edit'))
            ->assertRedirect(route('admin.dashboard'));
    }
}
