<?php

namespace Tests\Feature\Auth;

use App\Mail\WelcomeMail;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class WelcomeEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_unverified_account_gets_no_welcome_until_it_is_verified_then_exactly_one(): void
    {
        Mail::fake();
        $user = User::factory()->unverified()->create(['phone_verified_at' => null, 'welcome_email_sent_at' => null]);

        Mail::assertNothingQueued();

        $user->forceFill(['email_verified_at' => now()])->save();
        $user->forceFill(['name' => 'Renamed'])->save();
        $user->forceFill(['phone_verified_at' => now()])->save();

        Mail::assertQueued(WelcomeMail::class, 1);
        Mail::assertQueued(WelcomeMail::class, fn (WelcomeMail $mail) => $mail->hasTo($user->email));
        $this->assertNotNull($user->fresh()->welcome_email_sent_at);
    }

    public function test_verifying_by_phone_also_welcomes_an_account_that_has_an_email(): void
    {
        Mail::fake();
        $user = User::factory()->unverified()->create(['phone_verified_at' => null, 'welcome_email_sent_at' => null]);

        $user->forceFill(['phone_verified_at' => now()])->save();

        Mail::assertQueued(WelcomeMail::class, 1);
    }

    public function test_an_account_created_already_verified_is_welcomed(): void
    {
        // Social sign-up creates the account with a provider-verified email.
        Mail::fake();

        User::factory()->create(['welcome_email_sent_at' => null]);

        Mail::assertQueued(WelcomeMail::class, 1);
    }

    public function test_a_guest_checkout_account_without_an_email_is_not_mailed(): void
    {
        Mail::fake();
        $guest = new User;
        $guest->name = 'Guest';
        $guest->phone = '+9647701234567';
        $guest->save();

        $guest->forceFill(['phone_verified_at' => now()])->save();

        Mail::assertNothingQueued();
        $this->assertNull($guest->fresh()->welcome_email_sent_at);
    }

    public function test_staff_accounts_are_not_welcomed_like_customers(): void
    {
        Mail::fake();

        User::factory()->create(['role' => User::ROLE_SUPER_ADMIN, 'welcome_email_sent_at' => null]);

        Mail::assertNothingQueued();
    }

    public function test_the_welcome_uses_the_saved_template(): void
    {
        EmailTemplate::query()->create([
            'template_key' => 'welcome',
            'locale' => 'en',
            'subject' => 'Glad you are here, {name}',
            'body_html' => '<p>Start with the parts finder at <a href="{url}">your shop</a>.</p>',
        ]);
        $user = User::factory()->make(['name' => 'Sara']);

        $mail = (new WelcomeMail($user))->build();

        $this->assertSame('Glad you are here, Sara', $mail->subject);
        $this->assertStringContainsString('Start with the parts finder', $mail->render());
    }
}
