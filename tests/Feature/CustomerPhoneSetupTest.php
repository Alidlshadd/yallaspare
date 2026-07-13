<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CustomerPhoneSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_customer_without_phone_is_redirected_to_phone_setup_after_login(): void
    {
        $user = User::factory()->create([
            'phone' => null,
            'two_factor_preference' => 'off',
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('user.phone.setup'));
    }

    public function test_customer_without_phone_cannot_continue_to_protected_customer_routes(): void
    {
        $user = User::factory()->create(['phone' => null]);

        $this->actingAs($user)
            ->get(route('user.account.edit'))
            ->assertRedirect(route('user.phone.setup'));
    }

    public function test_customer_can_add_phone_in_e164_format(): void
    {
        $user = User::factory()->create([
            'phone' => null,
            'two_factor_preference' => 'off',
        ]);

        $this->actingAs($user)
            ->post(route('user.phone.store'), [
                'country_code' => '+964',
                'phone' => '7704488315',
            ])
            ->assertRedirect(route('user.shop.home'));

        $this->assertSame('+9647704488315', $user->refresh()->phone);
        $this->assertNull($user->phone_verified_at);
    }

    public function test_two_factor_customer_can_choose_a_method_after_adding_phone(): void
    {
        Notification::fake();
        $user = User::factory()->create([
            'phone' => null,
            'two_factor_preference' => 'email',
        ]);

        $this->actingAs($user)
            ->post(route('user.phone.store'), [
                'country_code' => '+964',
                'phone' => '+9647704488315',
            ])
            ->assertRedirect(route('user.two-factor.challenge'));

        $this->get(route('user.two-factor.challenge'))
            ->assertOk()
            ->assertSee(__('Use another verification method'))
            ->assertSee(__('SMS'));
    }

    #[DataProvider('privilegedRoles')]
    public function test_privileged_user_without_phone_is_not_locked_out_by_customer_phone_requirement(string $role): void
    {
        config(['security.admin_two_factor.enabled' => false]);
        $admin = User::factory()->create([
            'role' => $role,
            'phone' => null,
        ]);

        $this->post(route('login'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect('/admin/dashboard');

        $this->actingAs($admin)
            ->get(route('profile.edit'))
            ->assertOk();
    }

    public static function privilegedRoles(): array
    {
        return [
            'admin' => [User::ROLE_ADMIN],
            'super admin' => [User::ROLE_SUPER_ADMIN],
        ];
    }
}
