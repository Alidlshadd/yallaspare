<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\UserTwoFactorCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

/**
 * The mobile API must hold accounts to the same rules as the web forms.
 */
class MobileAccountSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_changing_email_on_mobile_clears_its_verification(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.test']);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/mobile/profile', [
                'name' => $user->name,
                'email' => 'new-address@example.test',
            ])
            ->assertOk();

        $fresh = $user->fresh();
        $this->assertSame('new-address@example.test', $fresh->email);
        $this->assertNull($fresh->email_verified_at);
    }

    public function test_saving_the_same_email_on_mobile_keeps_its_verification(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.test']);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/mobile/profile', [
                'name' => 'Renamed',
                'email' => 'owner@example.test',
            ])
            ->assertOk();

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_mobile_login_asks_for_the_sign_in_code_the_customer_turned_on(): void
    {
        Notification::fake();
        $user = $this->customerWithEmailCodes();

        $response = $this->postJson('/api/mobile/login', [
            'email' => 'twofa@example.test',
            'password' => 'password',
        ]);

        $response->assertStatus(202)
            ->assertJsonPath('two_factor_required', true)
            ->assertJsonMissingPath('token');
        $this->assertSame(0, $user->tokens()->count());

        $code = $this->sentCode($user);

        $this->postJson('/api/mobile/login/two-factor', [
            'challenge' => $response->json('challenge'),
            'code' => $code,
        ])->assertOk()->assertJsonStructure(['token', 'user']);

        // Single use.
        $this->postJson('/api/mobile/login/two-factor', [
            'challenge' => $response->json('challenge'),
            'code' => $code,
        ])->assertStatus(422);
    }

    public function test_a_wrong_code_issues_no_token_and_the_challenge_dies_after_five_misses(): void
    {
        Notification::fake();
        // The route's own 5/min limiter would stop the sixth request first;
        // this test is about the challenge's attempt budget.
        $this->withoutMiddleware(ThrottleRequests::class);
        $user = $this->customerWithEmailCodes();

        $challenge = $this->postJson('/api/mobile/login', [
            'email' => 'twofa@example.test',
            'password' => 'password',
        ])->json('challenge');
        $code = $this->sentCode($user);
        $wrong = $code === '111111' ? '222222' : '111111';

        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/mobile/login/two-factor', ['challenge' => $challenge, 'code' => $wrong])
                ->assertStatus(422);
        }

        $this->postJson('/api/mobile/login/two-factor', ['challenge' => $challenge, 'code' => $code])
            ->assertStatus(422);
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_a_password_change_between_the_two_steps_voids_the_challenge(): void
    {
        Notification::fake();
        $user = $this->customerWithEmailCodes();

        $challenge = $this->postJson('/api/mobile/login', [
            'email' => 'twofa@example.test',
            'password' => 'password',
        ])->json('challenge');
        $code = $this->sentCode($user);

        $user->forceFill(['password' => 'a-brand-new-password'])->save();

        $this->postJson('/api/mobile/login/two-factor', ['challenge' => $challenge, 'code' => $code])
            ->assertStatus(422);
    }

    public function test_customers_without_sign_in_codes_still_get_a_token_directly(): void
    {
        User::factory()->create(['email' => 'plain@example.test']);

        $this->postJson('/api/mobile/login', [
            'email' => 'plain@example.test',
            'password' => 'password',
        ])->assertOk()->assertJsonStructure(['token', 'user']);
    }

    public function test_changing_password_on_mobile_signs_out_other_devices_but_not_this_one(): void
    {
        $user = User::factory()->create(['password' => 'old-password-123']);
        $other = $user->createToken('mobile')->plainTextToken;
        $current = $user->createToken('mobile')->plainTextToken;

        $this->withToken($current)
            ->patchJson('/api/mobile/profile/password', [
                'current_password' => 'old-password-123',
                'password' => 'N3w-Str0ng-Passw0rd!x',
                'password_confirmation' => 'N3w-Str0ng-Passw0rd!x',
            ])
            ->assertOk();

        $this->assertSame(1, $user->tokens()->count());
        $this->app['auth']->forgetGuards();
        $this->withToken($current)->getJson('/api/mobile/me')->assertOk();
        $this->app['auth']->forgetGuards();
        $this->withToken($other)->getJson('/api/mobile/me')->assertUnauthorized();
    }

    private function customerWithEmailCodes(): User
    {
        $user = User::factory()->create(['email' => 'twofa@example.test']);
        $user->forceFill(['two_factor_preference' => 'email'])->save();

        return $user;
    }

    private function sentCode(User $user): string
    {
        $code = null;
        Notification::assertSentTo($user, UserTwoFactorCode::class, function (UserTwoFactorCode $notification) use (&$code): bool {
            $code = $notification->code;

            return true;
        });

        return (string) $code;
    }
}
