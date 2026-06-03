<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\UserTwoFactorCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserSecurityActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_enable_email_two_factor_from_security_settings(): void
    {
        $user = User::factory()->create(['two_factor_preference' => 'off']);

        $this->actingAs($user)
            ->patch(route('user.settings.security.update'), [
                'two_factor_preference' => 'email',
                'login_alerts' => true,
                'session_timeout' => '60',
            ])
            ->assertRedirect(route('user.settings.security'))
            ->assertSessionHas('success');

        $this->assertSame('email', (string) $user->fresh()->two_factor_preference);
        $this->assertSame($user->id, session('user_2fa.verified_user_id'));
    }

    public function test_email_two_factor_is_required_on_next_login_and_can_be_verified(): void
    {
        Notification::fake();
        $user = User::factory()->create(['two_factor_preference' => 'email']);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('user.two-factor.challenge'));

        $this->get(route('user.settings.security'))
            ->assertRedirect(route('user.two-factor.challenge'));

        $code = null;
        Notification::assertSentTo($user, UserTwoFactorCode::class, function (UserTwoFactorCode $notification) use (&$code): bool {
            $code = $notification->code;

            return strlen($code) === 6;
        });

        $this->post(route('user.two-factor.verify'), ['code' => $code])
            ->assertRedirect(route('user.shop.home'));

        $this->assertSame($user->id, session('user_2fa.verified_user_id'));

        $this->get(route('user.settings.security'))->assertOk();
    }

    public function test_email_two_factor_blocks_legacy_profile_and_password_routes_until_verified(): void
    {
        $user = User::factory()->create(['two_factor_preference' => 'email']);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertRedirect(route('user.two-factor.challenge'));

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'Bypass Attempt',
                'email' => 'bypass@example.com',
            ])
            ->assertRedirect(route('user.two-factor.challenge'));

        $this->actingAs($user)
            ->put(route('password.update'), [
                'current_password' => 'password',
                'password' => 'new-password1',
                'password_confirmation' => 'new-password1',
            ])
            ->assertRedirect(route('user.two-factor.challenge'));

        $this->withSession(['user_2fa.verified_user_id' => $user->id])
            ->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk();
    }

    public function test_global_signout_removes_other_sessions_and_mobile_tokens(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $user->createToken('mobile');

        DB::table('sessions')->insert([
            [
                'id' => 'other-session',
                'user_id' => $user->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Test',
                'payload' => 'payload',
                'last_activity' => now()->timestamp,
            ],
            [
                'id' => 'other-user-session',
                'user_id' => $otherUser->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Test',
                'payload' => 'payload',
                'last_activity' => now()->timestamp,
            ],
        ]);

        $this->actingAs($user)
            ->post(route('user.settings.security.global-signout'), [
                'current_password' => 'password',
            ])
            ->assertRedirect(route('user.settings.security'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('sessions', [
            'id' => 'other-session',
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('sessions', [
            'id' => 'other-user-session',
            'user_id' => $otherUser->id,
        ]);
        $this->assertSame(0, $user->tokens()->count());
    }
}
