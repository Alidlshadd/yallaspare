<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee('password-input-toggle', false);
        $response->assertSee('x-data="passwordInput(', false);
        $response->assertSee('Show password');
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'country_code' => '+964',
            'phone' => '07704488315',
            'password' => 'YallaTest!2026',
            'password_confirmation' => 'YallaTest!2026',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('phone.verify'));
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'phone' => '+9647704488315',
        ]);
    }

    public function test_phone_is_required_during_registration(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'missing-phone@example.com',
            'country_code' => '+964',
            'password' => 'YallaTest!2026',
            'password_confirmation' => 'YallaTest!2026',
        ])->assertSessionHasErrors('phone');

        $this->assertGuest();
    }

    #[DataProvider('iraqiPhoneFormats')]
    public function test_iraqi_phone_formats_are_stored_as_e164(string $input, string $email): void
    {
        $this->post('/register', [
            'name' => 'Formatted Phone',
            'email' => $email,
            'country_code' => '+964',
            'phone' => $input,
            'password' => 'YallaTest!2026',
            'password_confirmation' => 'YallaTest!2026',
        ])->assertRedirect(route('phone.verify'));

        $this->assertDatabaseHas('users', [
            'email' => $email,
            'phone' => '+9647704488315',
        ]);

        auth()->logout();
    }

    public static function iraqiPhoneFormats(): array
    {
        return [
            'local with zero' => ['07704488315', 'local-zero@example.com'],
            'local without zero' => ['7704488315', 'local@example.com'],
            'e164' => ['+9647704488315', 'e164@example.com'],
        ];
    }

    public function test_invalid_iraqi_phone_is_rejected(): void
    {
        $this->post('/register', [
            'name' => 'Invalid Phone',
            'email' => 'invalid-phone@example.com',
            'country_code' => '+964',
            'phone' => '+905551234567',
            'password' => 'YallaTest!2026',
            'password_confirmation' => 'YallaTest!2026',
        ])->assertSessionHasErrors('phone');

        $this->assertGuest();
    }

    public function test_registration_rejects_duplicate_normalized_phone(): void
    {
        User::factory()->create([
            'phone' => '+964 750 123 4567',
        ]);

        $response = $this->post('/register', [
            'name' => 'Second User',
            'email' => 'second@example.com',
            'country_code' => '+964',
            'phone' => '9647501234567',
            'password' => 'YallaTest!2026',
            'password_confirmation' => 'YallaTest!2026',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('phone');
    }

    public function test_registration_rejects_a_legacy_local_duplicate_phone(): void
    {
        $existing = User::factory()->create();
        User::query()->whereKey($existing->id)->update([
            'phone' => '07704488315',
            'phone_normalized' => '07704488315',
        ]);

        $this->post('/register', [
            'name' => 'Duplicate Legacy Phone',
            'email' => 'legacy-duplicate@example.com',
            'country_code' => '+964',
            'phone' => '+9647704488315',
            'password' => 'YallaTest!2026',
            'password_confirmation' => 'YallaTest!2026',
        ])->assertSessionHasErrors('phone');

        $this->assertGuest();
    }
}
