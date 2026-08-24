<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Registration takes one proof of ownership: the code goes to email by
 * default, SMS and WhatsApp are the alternatives. The login gate has always
 * honoured either, but the admin panel asked only about email_verified_at —
 * so a customer who confirmed by SMS was let into the site and listed as
 * unverified in the same breath.
 */
class AdminVerifiedByPhoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_phone_verified_account_counts_as_verified(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'phone_verified_at' => now(),
        ]);

        $this->assertTrue($user->hasVerifiedAccount());
        $this->assertSame('phone', $user->verifiedVia());
    }

    public function test_an_account_with_neither_timestamp_is_unverified(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'phone_verified_at' => null,
        ]);

        $this->assertFalse($user->hasVerifiedAccount());
        $this->assertNull($user->verifiedVia());
    }

    public function test_the_verified_filter_returns_accounts_proved_by_either_channel(): void
    {
        $byEmail = User::factory()->create(['email_verified_at' => now(), 'phone_verified_at' => null]);
        $byPhone = User::factory()->create(['email_verified_at' => null, 'phone_verified_at' => now()]);
        $neither = User::factory()->create(['email_verified_at' => null, 'phone_verified_at' => null]);

        $verified = User::query()->verifiedAccount()->pluck('id');
        $unverified = User::query()->unverifiedAccount()->pluck('id');

        $this->assertContains($byEmail->id, $verified);
        $this->assertContains($byPhone->id, $verified);
        $this->assertNotContains($neither->id, $verified);

        $this->assertContains($neither->id, $unverified);
        $this->assertNotContains($byPhone->id, $unverified);
    }

    public function test_the_admin_user_list_marks_a_phone_verified_account_as_verified(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);

        $customer = User::factory()->create([
            'name' => 'Phone Verified Customer',
            'role' => User::ROLE_USER,
            'email_verified_at' => null,
            'phone_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.index', ['filter' => 'verified']));

        $response->assertOk();
        $response->assertSee($customer->name);
    }

    public function test_the_admin_detail_page_names_the_channel_that_proved_the_account(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);

        $customer = User::factory()->create([
            'role' => User::ROLE_USER,
            'email_verified_at' => null,
            'phone_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.show', $customer));

        $response->assertOk();
        // The header badge names the channel, while the snapshot keeps a row
        // per channel — email genuinely is unverified here and should say so.
        $response->assertSee('Verified by phone');
        $response->assertSee('Phone Verified');
    }
}
