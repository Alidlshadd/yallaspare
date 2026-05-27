<?php

namespace Tests\Feature\Admin;

use App\Mail\OperationalNotificationMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminEmailPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_manager_can_open_email_center(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SETTINGS_MANAGER,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.email.index'))
            ->assertOk()
            ->assertSee('Email Center')
            ->assertSee('Send Test Email')
            ->assertSee('Readiness Checks');
    }

    public function test_email_center_does_not_use_confirm_password_route(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SETTINGS_MANAGER,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.email.index'))
            ->assertOk()
            ->assertSee('Email Center');
    }

    public function test_settings_manager_can_send_test_email(): void
    {
        config([
            'mail.default' => 'array',
            'mail.from.address' => 'support@yallaspare.com',
            'mail.from.name' => 'YallaSpare',
        ]);
        Mail::fake();

        $admin = User::factory()->create([
            'role' => User::ROLE_SETTINGS_MANAGER,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.email.test'), [
                'recipient' => 'owner@example.com',
                'subject' => 'Admin mail test',
                'mailer' => 'array',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        Mail::assertQueued(OperationalNotificationMail::class, function (OperationalNotificationMail $mail): bool {
            return $mail->subjectLine === 'Admin mail test'
                && ($mail->context['type'] ?? null) === 'mail_test';
        });
    }
}
