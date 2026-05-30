<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcceptLanguageApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_accept_language_arabic_yields_arabic_error_message(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['Accept-Language' => 'ar'])
            ->postJson('/api/mobile/checkout');

        $response->assertStatus(422);
        $this->assertSame('السلة فارغة.', $response->json('message'));
    }

    public function test_accept_language_with_quality_values_picks_preferred_locale(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['Accept-Language' => 'fr;q=0.9, ar;q=0.8, en;q=0.5'])
            ->postJson('/api/mobile/checkout');

        $response->assertStatus(422);
        $this->assertSame('السلة فارغة.', $response->json('message'));
    }

    public function test_no_accept_language_header_defaults_to_english(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/checkout');

        $response->assertStatus(422);
        $this->assertSame('Cart is empty.', $response->json('message'));
    }

    public function test_unsupported_accept_language_falls_back_to_english(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['Accept-Language' => 'fr-FR'])
            ->postJson('/api/mobile/checkout');

        $response->assertStatus(422);
        $this->assertSame('Cart is empty.', $response->json('message'));
    }

    public function test_kurdish_ckb_language_tag_is_recognized(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['Accept-Language' => 'ckb'])
            ->postJson('/api/mobile/checkout');

        $response->assertStatus(422);
        $this->assertSame('سەبەتە بەتاڵە.', $response->json('message'));
    }
}
