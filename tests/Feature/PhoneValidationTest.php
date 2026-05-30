<?php

namespace Tests\Feature;

use App\Models\User;
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
