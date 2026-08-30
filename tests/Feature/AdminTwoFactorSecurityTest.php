<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\AdminTwoFactorCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\RawMessage;
use Tests\TestCase;

class AdminTwoFactorSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_requires_two_factor_when_enabled(): void
    {
        config(['security.admin_two_factor.enabled' => true]);
        Notification::fake();

        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.two-factor.challenge'));

        Notification::assertSentTo($admin, AdminTwoFactorCode::class);
    }

    public function test_admin_route_redirects_until_two_factor_verified(): void
    {
        config(['security.admin_two_factor.enabled' => true]);

        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.two-factor.challenge'));

        $this->withSession(['admin_2fa.verified_user_id' => $admin->id])
            ->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_admin_two_factor_blocks_legacy_profile_and_password_routes_until_verified(): void
    {
        config(['security.admin_two_factor.enabled' => true]);

        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('profile.edit'))
            ->assertRedirect(route('admin.two-factor.challenge'));

        $this->actingAs($admin)
            ->put(route('password.update'), [
                'current_password' => 'password',
                'password' => 'New-Password1!',
                'password_confirmation' => 'New-Password1!',
            ])
            ->assertRedirect(route('admin.two-factor.challenge'));

        $this->withSession(['admin_2fa.verified_user_id' => $admin->id])
            ->actingAs($admin)
            ->get(route('profile.edit'))
            ->assertOk();
    }

    public function test_a_resend_that_cannot_be_delivered_leaves_the_working_code_alone(): void
    {
        // The administrator has a code in their inbox and the mailer has since
        // stopped working. Asking for another one must fail without also
        // destroying the one they can still type in — otherwise a mail outage
        // shuts them out of the panel rather than merely refusing them a new
        // code.
        config(['security.admin_two_factor.enabled' => true]);
        $this->everyEmailFails();

        $admin = $this->adminUser();
        $challenge = [
            'hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10)->timestamp,
        ];

        $this->actingAs($admin)
            ->withSession(['admin_2fa.challenge' => $challenge])
            ->post(route('admin.two-factor.resend'))
            ->assertSessionHasErrors('code')
            ->assertSessionHas('admin_2fa.challenge', $challenge);

        // And the code they already hold still gets them in.
        $this->actingAs($admin)
            ->withSession(['admin_2fa.challenge' => $challenge])
            ->post(route('admin.two-factor.verify'), ['code' => '123456'])
            ->assertSessionHasNoErrors();
    }

    public function test_a_delivered_resend_does_replace_the_code(): void
    {
        config(['security.admin_two_factor.enabled' => true]);
        Notification::fake();

        $admin = $this->adminUser();
        $challenge = [
            'hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10)->timestamp,
        ];

        $this->actingAs($admin)
            ->withSession(['admin_2fa.challenge' => $challenge])
            ->post(route('admin.two-factor.resend'))
            ->assertSessionHasNoErrors();

        $this->assertNotSame($challenge, session('admin_2fa.challenge'));
        Notification::assertSentTo($admin, AdminTwoFactorCode::class);
    }

    public function test_a_failed_send_reports_itself_on_the_challenge_screen(): void
    {
        config(['security.admin_two_factor.enabled' => true]);
        $this->everyEmailFails();

        $this->actingAs($this->adminUser())
            ->get(route('admin.two-factor.challenge'))
            ->assertOk()
            ->assertSee('We could not send a verification email', false);
    }

    private function adminUser(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * A mailer that refuses everything, the way a revoked SMTP password does.
     */
    private function everyEmailFails(): void
    {
        Mail::extend('always-failing', fn (): TransportInterface => new class implements TransportInterface
        {
            public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
            {
                throw new TransportException('Failed to authenticate on SMTP server.');
            }

            public function __toString(): string
            {
                return 'always-failing';
            }
        });

        config([
            'mail.default' => 'always-failing',
            'mail.mailers.always-failing' => ['transport' => 'always-failing'],
        ]);
    }
}
