<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'password' => 'password1',
            'password_confirmation' => 'password1',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('verification.notice'));
    }

    public function test_registration_rejects_duplicate_normalized_phone(): void
    {
        User::factory()->create([
            'phone' => '+964 750 123 4567',
        ]);

        $response = $this->post('/register', [
            'name' => 'Second User',
            'email' => 'second@example.com',
            'phone' => '9647501234567',
            'password' => 'password1',
            'password_confirmation' => 'password1',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('phone');
    }
}
