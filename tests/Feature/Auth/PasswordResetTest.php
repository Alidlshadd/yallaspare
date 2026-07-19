<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\ImmediateResetPassword;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response
            ->assertStatus(200)
            ->assertSee('Email or phone')
            ->assertSee('name="login"', false);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ImmediateResetPassword::class);
        $this->assertFalse(new ImmediateResetPassword('token') instanceof ShouldQueue);
    }

    public function test_reset_password_link_request_keeps_missing_users_private(): void
    {
        Notification::fake();

        $this->post('/forgot-password', ['email' => 'missing@example.com'])
            ->assertSessionHas('status', 'If an account matches these details, we sent a reset link to its registered email.')
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }

    public function test_reset_password_link_can_be_requested_with_an_iraqi_phone_number(): void
    {
        Notification::fake();

        $user = User::factory()->create(['phone' => '+964 750 448 8315']);

        $this->post('/forgot-password', ['login' => '0750 448 8315'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ImmediateResetPassword::class);
    }

    public function test_reset_password_link_phone_request_keeps_missing_users_private(): void
    {
        Notification::fake();

        $this->post('/forgot-password', ['login' => '0750 000 0099'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        Notification::assertNothingSent();
    }

    public function test_phone_reset_prefers_the_customer_when_staff_share_its_number(): void
    {
        Notification::fake();

        $customer = User::factory()->create([
            'email' => 'shared-phone-customer@example.com',
            'phone' => '+964 750 448 8315',
        ]);
        $admin = User::factory()->create([
            'email' => 'shared-phone-admin@example.com',
            'phone' => '+964 750 448 8315',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->post('/forgot-password', ['login' => '0750 448 8315'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        Notification::assertSentTo($customer, ImmediateResetPassword::class);
        Notification::assertNotSentTo($admin, ImmediateResetPassword::class);
    }

    public function test_phone_reset_requires_email_when_multiple_staff_accounts_share_the_number(): void
    {
        Notification::fake();

        foreach ([User::ROLE_ADMIN, User::ROLE_SETTINGS_MANAGER] as $index => $role) {
            User::factory()->create([
                'email' => "shared-phone-staff-{$index}@example.com",
                'phone' => '+964 750 448 8315',
                'role' => $role,
            ]);
        }

        $this->post('/forgot-password', ['login' => '0750 448 8315'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        Notification::assertNothingSent();
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ImmediateResetPassword::class, function ($notification) {
            $response = $this->get('/reset-password/'.$notification->token);

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ImmediateResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'YallaTest!2026',
                'password_confirmation' => 'YallaTest!2026',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });
    }
}
