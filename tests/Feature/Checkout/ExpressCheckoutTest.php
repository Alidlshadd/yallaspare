<?php

namespace Tests\Feature\Checkout;

use App\Http\Controllers\ExpressCheckoutController;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Governorate;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Support\PhoneVerificationCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExpressCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.otpiq.api_key' => 'sk_dev_test_key',
            'services.otpiq.base_url' => 'https://api.otpiq.test/api',
            'services.otpiq.provider' => 'sms',
            'services.otpiq.default_country_code' => '964',
            'services.otpiq.verification_ttl' => 10,
        ]);
    }

    public function test_a_guest_orders_without_ever_seeing_a_sign_up_form(): void
    {
        $governorate = Governorate::factory()->create(['shipping_fee' => 7000, 'delivery_days' => 2]);
        $product = $this->productInGuestCart(quantity: 2, price: 12000);

        $code = $this->fakeOtpiq();

        $this->post(route('checkout.express.store'), $this->details($governorate))
            ->assertRedirect(route('checkout.express.verify'));

        $account = User::query()->firstOrFail();
        $this->assertNull($account->email, 'The account has no address to send to yet.');
        $this->assertNull($account->password, 'Nobody has chosen a password for this account.');
        $this->assertNull($account->phone_verified_at);
        $this->assertGuest();

        $this->post(route('checkout.express.verify.store'), ['code' => $code()])
            ->assertRedirect();

        $order = Order::query()->firstOrFail();
        $account->refresh();

        $this->assertAuthenticatedAs($account);
        $this->assertNotNull($account->phone_verified_at);
        $this->assertTrue($account->hasVerifiedAccount(), 'A confirmed phone activates the account.');
        $this->assertSame($account->id, (int) $order->user_id);
        $this->assertSame(24000.0, (float) $order->subtotal_amount);
        $this->assertSame(7000.0, (float) $order->shipping_fee);
        $this->assertSame(31000.0, (float) $order->grand_total);
        $this->assertSame('Karrada, block 9', $order->delivery_address);

        $this->assertDatabaseHas('user_addresses', [
            'user_id' => $account->id,
            'governorate_id' => $governorate->id,
            'city' => 'Baghdad',
            'is_default' => true,
        ]);
        $this->assertDatabaseCount('cart_items', 0);
        $this->assertSame(3, (int) $product->fresh()->stock_quantity);
    }

    public function test_the_order_page_is_reachable_because_the_customer_really_is_signed_in(): void
    {
        $governorate = Governorate::factory()->create();
        $this->productInGuestCart();
        $code = $this->fakeOtpiq();

        $this->post(route('checkout.express.store'), $this->details($governorate));
        $this->post(route('checkout.express.verify.store'), ['code' => $code()]);

        $order = Order::query()->firstOrFail();

        // The gates the account area sits behind — verified account, verified
        // customer phone — all read the same confirmed number.
        $this->get(route('account.orders.show', $order))->assertOk();
    }

    public function test_a_number_that_already_has_a_password_is_sent_to_sign_in_instead(): void
    {
        $governorate = Governorate::factory()->create();
        $this->productInGuestCart();
        Http::fake();

        User::factory()->create([
            'phone' => '0770 448 8315',
            'password' => Hash::make('password-secret-1'),
        ]);

        $this->post(route('checkout.express.store'), $this->details($governorate))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        Http::assertNothingSent();
        $this->assertSame(1, User::query()->count());
        $this->assertGuest();
    }

    public function test_a_returning_guest_on_the_same_number_gets_another_code_rather_than_a_locked_door(): void
    {
        $governorate = Governorate::factory()->create();
        $this->productInGuestCart();
        $code = $this->fakeOtpiq();

        $this->post(route('checkout.express.store'), $this->details($governorate));
        $this->post(route('checkout.express.verify.store'), ['code' => $code()]);

        $account = User::query()->firstOrFail();
        $this->post(route('logout'));

        $this->productInGuestCart();
        $this->post(route('checkout.express.store'), $this->details($governorate))
            ->assertRedirect(route('checkout.express.verify'));

        $this->post(route('checkout.express.verify.store'), ['code' => $code()])
            ->assertRedirect();

        $this->assertSame(1, User::query()->count(), 'The same account is reused, not duplicated.');
        $this->assertSame(2, Order::query()->where('user_id', $account->id)->count());
    }

    public function test_a_wrong_code_places_no_order_and_signs_nobody_in(): void
    {
        $governorate = Governorate::factory()->create();
        $this->productInGuestCart();
        $this->fakeOtpiq();

        $this->post(route('checkout.express.store'), $this->details($governorate));

        $this->post(route('checkout.express.verify.store'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('user_addresses', 0);
    }

    public function test_an_expired_code_places_no_order(): void
    {
        $governorate = Governorate::factory()->create();
        $this->productInGuestCart();
        $code = $this->fakeOtpiq();

        $this->post(route('checkout.express.store'), $this->details($governorate));

        $account = User::query()->firstOrFail();
        PhoneVerificationCode::forgetFor($account);

        $this->post(route('checkout.express.verify.store'), ['code' => $code()])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_a_confirmed_code_is_spent_and_cannot_place_a_second_order(): void
    {
        $governorate = Governorate::factory()->create();
        $this->productInGuestCart(quantity: 1);
        $code = $this->fakeOtpiq();

        $this->post(route('checkout.express.store'), $this->details($governorate));
        $this->post(route('checkout.express.verify.store'), ['code' => $code()])->assertRedirect();

        $this->assertNull(session(ExpressCheckoutController::PENDING_SESSION_KEY));

        // Replaying the same code buys nothing: it was consumed, and the
        // request no longer carries a pending checkout at all.
        $this->post(route('checkout.express.verify.store'), ['code' => $code()])->assertRedirect();

        $this->assertDatabaseCount('orders', 1);
    }

    public function test_an_empty_cart_never_reaches_the_form(): void
    {
        $this->get(route('checkout.express'))
            ->assertRedirect(route('cart.index'));
    }

    public function test_the_cart_page_sends_a_guest_to_express_checkout_not_to_login(): void
    {
        $this->productInGuestCart();

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('href="'.route('checkout.express').'"', false)
            ->assertDontSee(__('Sign in to check out'));
    }

    public function test_the_success_page_offers_the_new_account_a_password(): void
    {
        $governorate = Governorate::factory()->create();
        $this->productInGuestCart();
        $code = $this->fakeOtpiq();

        $this->post(route('checkout.express.store'), $this->details($governorate));
        $this->post(route('checkout.express.verify.store'), ['code' => $code()]);

        $order = Order::query()->firstOrFail();
        $this->get(route('checkout.success', $order))
            ->assertOk()
            ->assertSee(route('account.credentials.store'), false);

        // Still on offer later: somebody who navigated away from the success
        // page has not lost their only chance at a way back in.
        $this->get(route('user.account.personal'))
            ->assertOk()
            ->assertSee(route('account.credentials.store'), false);

        $this->post(route('account.credentials.store'), [
            'email' => 'new-owner@example.test',
            'password' => 'YallaTest!2026',
            'password_confirmation' => 'YallaTest!2026',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $account = User::query()->firstOrFail();
        $this->assertSame('new-owner@example.test', $account->email);
        $this->assertTrue(Hash::check('YallaTest!2026', (string) $account->password));

        // And the offer is gone once it has been taken.
        $this->get(route('checkout.success', $order))
            ->assertOk()
            ->assertDontSee(route('account.credentials.store'), false);

        $this->get(route('user.account.personal'))
            ->assertOk()
            ->assertDontSee(route('account.credentials.store'), false);
    }

    public function test_an_account_that_already_has_credentials_cannot_reset_them_here(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password-secret-1')]);

        $this->actingAs($user)
            ->post(route('account.credentials.store'), [
                'password' => 'YallaTest!2026',
                'password_confirmation' => 'YallaTest!2026',
            ])
            ->assertForbidden();

        $this->assertTrue(Hash::check('password-secret-1', (string) $user->fresh()->password));
    }

    public function test_signing_in_by_phone_never_lands_on_a_stranger_with_no_email(): void
    {
        // Two credential-less accounts exist, so "the user whose email is
        // NULL" is ambiguous — the login lookup must not ask that question.
        $governorate = Governorate::factory()->create();
        $this->productInGuestCart();
        $code = $this->fakeOtpiq();
        $this->post(route('checkout.express.store'), $this->details($governorate));
        $this->post(route('checkout.express.verify.store'), ['code' => $code()]);
        $this->post(route('logout'));

        $guestAccount = User::query()->firstOrFail();

        $realUser = User::factory()->create([
            'email' => null,
            'phone' => '0771 000 0001',
            'password' => Hash::make('password-secret-1'),
        ]);

        $this->post(route('login'), [
            'email' => '0771 000 0001',
            'password' => 'password-secret-1',
        ]);

        $this->assertAuthenticatedAs($realUser);
        $this->assertNotSame($guestAccount->id, auth()->id());
    }

    public function test_the_pending_account_is_forgotten_when_it_gains_credentials_mid_flow(): void
    {
        $governorate = Governorate::factory()->create();
        $this->productInGuestCart();
        $code = $this->fakeOtpiq();

        $this->post(route('checkout.express.store'), $this->details($governorate));

        User::query()->firstOrFail()->forceFill(['password' => Hash::make('password-secret-1')])->save();

        $this->post(route('checkout.express.verify.store'), ['code' => $code()])
            ->assertRedirect(route('checkout.express'));

        $this->assertGuest();
        $this->assertDatabaseCount('orders', 0);
        $this->assertNull(session(ExpressCheckoutController::PENDING_SESSION_KEY));
    }

    /**
     * @return array<string, string|int>
     */
    private function details(Governorate $governorate, array $overrides = []): array
    {
        return array_merge([
            'name' => 'Dana Hassan',
            'country_code' => '+964',
            'phone' => '0770 448 8315',
            'governorate_id' => $governorate->id,
            'city' => 'Baghdad',
            'address_line1' => 'Karrada, block 9',
            'notes' => 'Call on arrival',
        ], $overrides);
    }

    /**
     * Catch the code OTPiQ was asked to deliver.
     *
     * @return \Closure(): string
     */
    private function fakeOtpiq(): \Closure
    {
        $sent = null;

        Http::fake(function (HttpRequest $request) use (&$sent) {
            $sent = (string) $request['verificationCode'];

            return Http::response([
                'message' => 'SMS task created successfully',
                'smsId' => 'sms-1234567890abcdef123456',
                'remainingCredit' => 1000,
                'cost' => 80,
                'canCover' => true,
                'paymentType' => 'prepaid',
            ]);
        });

        return function () use (&$sent): string {
            return (string) $sent;
        };
    }

    private function productInGuestCart(int $quantity = 1, int $price = 10000): Product
    {
        if (! Category::query()->whereKey(1)->exists()) {
            Category::factory()->create(['id' => 1]);
        }

        $product = Product::factory()->create([
            'category_id' => 1,
            'is_active' => true,
            'stock_quantity' => 5,
            'price' => $price,
        ]);

        $this->post(route('cart.add', $product), ['quantity' => $quantity])->assertRedirect();

        $this->assertNotNull(session(CartService::SESSION_KEY));
        $this->assertNotNull(Cart::query()->whereNull('user_id')->first());

        return $product;
    }
}
