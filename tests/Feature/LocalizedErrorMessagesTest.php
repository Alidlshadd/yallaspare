<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizedErrorMessagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_checkout_empty_cart_returns_english_message_by_default(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/checkout');

        $response->assertStatus(422);
        $this->assertSame('Cart is empty.', $response->json('message'));
    }

    public function test_mobile_checkout_empty_cart_returns_arabic_message_when_locale_is_ar(): void
    {
        $user = User::factory()->create();
        app()->setLocale('ar');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/checkout');

        $response->assertStatus(422);
        $this->assertSame('السلة فارغة.', $response->json('message'));
    }

    public function test_mobile_vin_decode_post_validation_strip_returns_localized_message(): void
    {
        $user = User::factory()->create();
        app()->setLocale('ar');

        // 8 lowercase chars: passes min:8 validation but preg_replace('/[^A-Z0-9]/')
        // strips them all, leaving an empty VIN that triggers the abort_if.
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/vin/decode', ['vin' => 'abcdefgh']);

        $response->assertStatus(422);
        $this->assertSame('رقم VIN قصير جدًا.', $response->json('message'));
    }

    public function test_errors_lang_file_returns_translated_string_for_each_locale(): void
    {
        app()->setLocale('en');
        $this->assertSame('Cart is empty.', __('errors.cart_empty'));

        app()->setLocale('ar');
        $this->assertSame('السلة فارغة.', __('errors.cart_empty'));

        app()->setLocale('ku');
        $this->assertSame('سەبەتە بەتاڵە.', __('errors.cart_empty'));
    }

    public function test_inventory_error_with_sku_placeholder_interpolates(): void
    {
        app()->setLocale('en');
        $this->assertSame(
            "Product not found for SKU 'XYZ-123'.",
            __('errors.inventory_product_not_found', ['sku' => 'XYZ-123']),
        );
    }
}
