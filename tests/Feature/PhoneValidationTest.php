<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddress;
use App\Rules\PhoneNumber;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PhoneValidationTest extends TestCase
{
    use RefreshDatabase;

    private function validatePhone(mixed $value): ValidatorContract
    {
        return Validator::make(
            ['phone' => $value],
            ['phone' => ['nullable', new PhoneNumber()]],
        );
    }

    public function test_passes_when_value_is_null(): void
    {
        $this->assertTrue($this->validatePhone(null)->passes());
    }

    public function test_passes_when_value_is_empty_string(): void
    {
        $this->assertTrue($this->validatePhone('')->passes());
    }

    public function test_passes_valid_international_format(): void
    {
        $this->assertTrue($this->validatePhone('+964 770 123 4567')->passes());
    }

    public function test_passes_local_format_with_dashes(): void
    {
        $this->assertTrue($this->validatePhone('0770-123-4567')->passes());
    }

    public function test_passes_arabic_indic_digits(): void
    {
        $this->assertTrue($this->validatePhone('٠٧٧٠١٢٣٤٥٦٧')->passes());
    }

    public function test_passes_persian_digits(): void
    {
        $this->assertTrue($this->validatePhone('۰۷۷۰۱۲۳۴۵۶۷')->passes());
    }

    public function test_passes_minimum_length_eight_digits(): void
    {
        $this->assertTrue($this->validatePhone('12345678')->passes());
    }

    public function test_passes_maximum_length_fifteen_digits(): void
    {
        $this->assertTrue($this->validatePhone('123456789012345')->passes());
    }

    public function test_fails_when_too_short_seven_digits(): void
    {
        $v = $this->validatePhone('1234567');
        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('phone', $v->errors()->toArray());
    }

    public function test_fails_when_too_long_sixteen_digits(): void
    {
        $this->assertTrue($this->validatePhone('1234567890123456')->fails());
    }

    public function test_fails_letters_only(): void
    {
        $this->assertTrue($this->validatePhone('abcdefgh')->fails());
    }

    public function test_fails_letters_with_few_digits(): void
    {
        $this->assertTrue($this->validatePhone('abc12')->fails());
    }

    public function test_fails_invalid_symbols_even_with_valid_digit_count(): void
    {
        $this->assertTrue($this->validatePhone('0770#1234567')->fails());
    }

    public function test_duplicate_phone_fails_when_uniqueness_is_required(): void
    {
        $existing = User::factory()->create(['phone' => '+964 750 123 4567']);

        $validator = Validator::make(
            ['phone' => '9647501234567'],
            ['phone' => ['nullable', new PhoneNumber(), User::uniquePhoneRule()]],
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('phone', $validator->errors()->toArray());

        $ignoredValidator = Validator::make(
            ['phone' => '9647501234567'],
            ['phone' => ['nullable', new PhoneNumber(), User::uniquePhoneRule($existing->id)]],
        );

        $this->assertTrue($ignoredValidator->passes());
    }

    public function test_mobile_register_uses_phone_number_rule(): void
    {
        $response = $this->postJson('/api/mobile/register', [
            'name' => 'Mobile User',
            'email' => 'mobile@example.com',
            'phone' => '0770#1234567',
            'password' => 'password1',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('phone');
    }

    public function test_mobile_address_uses_phone_number_rule(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/addresses', [
                'label' => 'Home',
                'city' => 'Baghdad',
                'line1' => 'Street 10',
                'phone' => '0770#1234567',
            ]);

        $response->assertStatus(422)->assertJsonValidationErrors('phone');
    }

    public function test_mobile_checkout_uses_phone_number_rule_for_saved_address(): void
    {
        if (! Category::query()->whereKey(1)->exists()) {
            Category::factory()->create(['id' => 1]);
        }

        $user = User::factory()->create();
        UserAddress::query()->forceCreate([
            'user_id' => $user->id,
            'label' => 'Home',
            'country' => 'IQ',
            'city' => 'Baghdad',
            'address_line1' => 'Street 10',
            'phone' => '0770#1234567',
            'is_default' => true,
        ]);

        $product = Product::factory()->create([
            'price' => 10000,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);
        $cart = Cart::query()->forceCreate(['user_id' => $user->id]);
        CartItem::query()->forceCreate([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/checkout/review')
            ->assertStatus(422)
            ->assertJsonPath('message', __('validation.phone', ['attribute' => 'delivery phone']));
    }

    public function test_user_mutator_normalizes_western_phone(): void
    {
        $user = User::factory()->create(['phone' => '+964 (770) 123-4567']);
        $this->assertSame('9647701234567', $user->phone_normalized);
    }

    public function test_user_mutator_normalizes_arabic_indic_phone(): void
    {
        $user = User::factory()->create(['phone' => '٠٧٧٠١٢٣٤٥٦٧']);
        $this->assertSame('07701234567', $user->phone_normalized);
    }
}
