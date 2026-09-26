<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
