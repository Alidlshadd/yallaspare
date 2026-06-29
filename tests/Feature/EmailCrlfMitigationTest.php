<?php

namespace Tests\Feature;

use App\Mail\SupportContactRequestMail;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\EmailBroadcast;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddress;
use App\Notifications\ImmediateResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmailCrlfMitigationTest extends TestCase
{
    use RefreshDatabase;

    private string $crlfEmail = "attacker@example.test\r\nBcc: victim@example.test";

    public function test_web_registration_rejects_crlf_email_before_user_creation(): void
    {
        $this->post('/register', [
            'name' => 'Attacker',
            'email' => $this->crlfEmail,
            'phone' => null,
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ])->assertStatus(422);

        $this->assertDatabaseMissing('users', ['name' => 'Attacker']);
    }

    public function test_web_login_rejects_crlf_email_before_authentication(): void
    {
        User::factory()->create([
            'email' => 'attacker@example.test',
            'password' => Hash::make('password'),
        ]);

        $this->post('/login', [
            'email' => $this->crlfEmail,
            'password' => 'password',
        ])->assertStatus(422);

        $this->assertGuest();
    }

    public function test_password_reset_rejects_crlf_email_before_notification(): void
    {
        Notification::fake();
        User::factory()->create(['email' => 'attacker@example.test']);

        $this->post(route('password.email'), [
            'email' => $this->crlfEmail,
        ])->assertStatus(422);

        Notification::assertNothingSent();
    }

    public function test_checkout_rejects_unexpected_crlf_email_field_before_order_creation(): void
    {
        [$user, $address] = $this->checkoutContext();

        $this->actingAs($user)
            ->post(route('checkout.store'), [
                'address_id' => $address->id,
                'payment_method' => 'cash_on_delivery',
                'email' => $this->crlfEmail,
            ])->assertStatus(422);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_web_contact_rejects_crlf_email_before_mail_queueing(): void
    {
        Mail::fake();

        $this->post(route('legal.contact.send'), [
            'name' => 'Attacker',
            'email' => $this->crlfEmail,
            'phone' => null,
            'topic' => 'support',
            'subject' => 'Question',
            'message' => 'Normal message body.',
        ])->assertStatus(422);

        Mail::assertNotQueued(SupportContactRequestMail::class);
    }

    public function test_admin_user_update_rejects_crlf_email_before_save(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'permissions' => [User::PERMISSION_USERS_MANAGE],
            'email_verified_at' => now(),
        ]);
        $target = User::factory()->create([
            'email' => 'customer@example.test',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.users.update-details', $target), [
                'name' => $target->name,
                'email' => $this->crlfEmail,
                'phone' => null,
                'role' => $target->role,
                'account_status' => (string) $target->account_status,
            ])->assertStatus(422);

        $this->assertSame('customer@example.test', (string) $target->fresh()->email);
    }

    public function test_admin_email_test_rejects_crlf_recipient_before_mail_queueing(): void
    {
        Mail::fake();
        $admin = User::factory()->create([
            'role' => User::ROLE_SETTINGS_MANAGER,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.email.test'), [
                'recipient' => $this->crlfEmail,
                'subject' => 'Admin mail test',
                'mailer' => 'array',
            ])->assertStatus(422);

        Mail::assertNothingQueued();
    }

    public function test_admin_email_broadcast_rejects_crlf_recipient_before_broadcast_creation(): void
    {
        Mail::fake();
        User::factory()->create([
            'email' => 'customer@example.test',
            'email_verified_at' => now(),
            'email_notifications' => true,
            'marketing_consent' => true,
        ]);
        $admin = User::factory()->create([
            'role' => User::ROLE_SETTINGS_MANAGER,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.email.broadcast'), [
                'audience_type' => EmailBroadcast::AUDIENCE_USER,
                'recipient_email' => $this->crlfEmail,
                'purpose' => EmailBroadcast::PURPOSE_PROMOTIONAL,
                'subject' => 'Admin broadcast',
                'message' => 'Normal broadcast body.',
                'action_url' => url('/'),
                'action_text' => 'Open',
            ])->assertStatus(422);

        Mail::assertNothingQueued();
        $this->assertDatabaseCount('email_broadcasts', 0);
    }

    public function test_mobile_registration_rejects_crlf_email_before_user_creation(): void
    {
        $this->postJson('/api/mobile/register', [
            'name' => 'Mobile Attacker',
            'email' => $this->crlfEmail,
            'phone' => null,
            'password' => 'Password123',
        ])->assertStatus(422);

        $this->assertDatabaseMissing('users', ['name' => 'Mobile Attacker']);
    }

    public function test_mobile_login_rejects_crlf_email_before_token_creation(): void
    {
        $user = User::factory()->create([
            'email' => 'attacker@example.test',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        $this->postJson('/api/mobile/login', [
            'email' => $this->crlfEmail,
            'password' => 'password',
        ])->assertStatus(422);

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_mobile_password_reset_rejects_crlf_email_before_notification(): void
    {
        Notification::fake();
        User::factory()->create(['email' => 'attacker@example.test']);

        $this->postJson('/api/mobile/forgot-password', [
            'email' => $this->crlfEmail,
        ])->assertStatus(422);

        Notification::assertNothingSent();
    }

    public function test_mobile_profile_update_rejects_crlf_email_before_save(): void
    {
        $user = User::factory()->create([
            'email' => 'mobile@example.test',
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $this->patchJson('/api/mobile/profile', [
            'name' => $user->name,
            'email' => $this->crlfEmail,
            'phone' => null,
        ])->assertStatus(422);

        $this->assertSame('mobile@example.test', (string) $user->fresh()->email);
    }

    public function test_mobile_contact_rejects_crlf_email_before_mail_queueing(): void
    {
        Mail::fake();

        $this->postJson('/api/mobile/legal/contact', [
            'name' => 'Attacker',
            'email' => $this->crlfEmail,
            'phone' => null,
            'topic' => 'support',
            'subject' => 'Question',
            'message' => 'Normal message body.',
        ])->assertStatus(422);

        Mail::assertNotQueued(SupportContactRequestMail::class);
    }

    private function checkoutContext(): array
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 10000,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);
        $address = UserAddress::query()->create([
            'user_id' => $user->id,
            'label' => 'Home',
            'country' => 'IQ',
            'city' => 'Erbil',
            'address_line1' => '100 Test Street',
            'phone' => '+964 770 000 0000',
            'is_default' => true,
        ]);
        $cart = Cart::query()->create(['user_id' => $user->id]);
        CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        return [$user, $address];
    }
}
