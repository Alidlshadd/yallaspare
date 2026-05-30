<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MobileProfileDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_delete_profile_with_correct_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('correct-password')]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/mobile/profile', ['password' => 'correct-password']);

        $response->assertOk();
        $this->assertNull(User::find($user->id));
    }

    public function test_delete_profile_rejects_wrong_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('correct-password')]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/mobile/profile', ['password' => 'wrong-password']);

        $response->assertStatus(422);
        $this->assertNotNull(User::find($user->id));
    }

    public function test_delete_profile_revokes_tokens(): void
    {
        $user = User::factory()->create(['password' => Hash::make('correct-password')]);
        $user->createToken('mobile')->plainTextToken;
        $user->createToken('mobile')->plainTextToken;
        $this->assertSame(2, $user->tokens()->count());

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/mobile/profile', ['password' => 'correct-password'])
            ->assertOk();

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_delete_profile_requires_authentication(): void
    {
        $response = $this->deleteJson('/api/mobile/profile', ['password' => 'anything']);

        $response->assertStatus(401);
    }

    public function test_delete_profile_requires_password_field(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/mobile/profile', []);

        $response->assertStatus(422);
        $this->assertNotNull(User::find($user->id));
    }
}
