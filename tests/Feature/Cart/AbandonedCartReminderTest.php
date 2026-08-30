<?php

namespace Tests\Feature\Cart;

use App\Mail\OperationalNotificationMail;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\Cart\AbandonedCartReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AbandonedCartReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        config([
            'cart_reminders.enabled' => true,
            'cart_reminders.stages' => [4, 24],
            'cart_reminders.max_age_days' => 7,
            'cart_reminders.max_per_run' => 200,
        ]);
    }

    public function test_a_consenting_customer_is_reminded_once_the_first_delay_has_passed(): void
    {
        $cart = $this->abandonedCart(hoursAgo: 5);

        $this->assertSame(1, $this->runReminder());

        Mail::assertQueued(OperationalNotificationMail::class, 1);
        $this->assertSame(1, (int) $cart->fresh()->reminder_stage);
    }

    public function test_nothing_goes_out_before_the_delay_has_passed(): void
    {
        $cart = $this->abandonedCart(hoursAgo: 1);

        $this->assertSame(0, $this->runReminder());

        Mail::assertNothingQueued();
        $this->assertSame(0, (int) $cart->fresh()->reminder_stage);
        $this->assertNull($cart->fresh()->reminded_at);
    }

    public function test_a_second_reminder_follows_a_day_later_and_a_third_never_does(): void
    {
        $cart = $this->abandonedCart(hoursAgo: 5);
        $this->assertSame(1, $this->runReminder());

        // Still the same cart, now a day untouched.
        $this->ageCart($cart, hoursAgo: 25);
        $this->assertSame(1, $this->runReminder());
        $this->assertSame(2, (int) $cart->fresh()->reminder_stage);

        // A week of silence buys no third message.
        $this->ageCart($cart, hoursAgo: 100);
        $this->assertSame(0, $this->runReminder());

        Mail::assertQueued(OperationalNotificationMail::class, 2);
    }

    public function test_the_same_reminder_is_not_sent_twice_by_a_second_run(): void
    {
        $this->abandonedCart(hoursAgo: 5);

        $this->assertSame(1, $this->runReminder());
        $this->assertSame(0, $this->runReminder());

        Mail::assertQueued(OperationalNotificationMail::class, 1);
    }

    public function test_touching_the_cart_starts_the_cycle_over(): void
    {
        $cart = $this->abandonedCart(hoursAgo: 5);
        $this->assertSame(1, $this->runReminder());

        // Six hours on from that reminder the customer comes back, adds
        // another part, and leaves again — so the cart was last touched an
        // hour after we last wrote to them.
        $this->ageReminder($cart, hoursAgo: 6);
        CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_id' => $this->product()->id,
            'quantity' => 1,
        ]);
        $this->ageCart($cart, hoursAgo: 5);

        $this->assertSame(1, $this->runReminder());

        // Back to the first reminder, not the second: this is a fresh cart.
        $this->assertSame(1, (int) $cart->fresh()->reminder_stage);
        Mail::assertQueued(OperationalNotificationMail::class, 2);
    }

    public function test_a_customer_who_did_not_allow_marketing_is_left_alone(): void
    {
        $this->abandonedCart(hoursAgo: 5, userAttributes: ['marketing_consent' => false]);

        $this->assertSame(0, $this->runReminder());
        Mail::assertNothingQueued();
    }

    public function test_a_customer_who_never_verified_their_email_is_left_alone(): void
    {
        $this->abandonedCart(hoursAgo: 5, userAttributes: ['email_verified_at' => null]);

        $this->assertSame(0, $this->runReminder());
        Mail::assertNothingQueued();
    }

    public function test_a_guest_cart_has_nobody_to_write_to(): void
    {
        $cart = Cart::query()->create(['session_token' => 'guest-token-1']);
        CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_id' => $this->product()->id,
            'quantity' => 1,
        ]);
        $this->ageCart($cart, hoursAgo: 5);

        $this->assertSame(0, $this->runReminder());
        Mail::assertNothingQueued();
    }

    public function test_an_emptied_cart_is_not_reminded_about(): void
    {
        $cart = $this->abandonedCart(hoursAgo: 5);
        // What checkout does once the order exists.
        $cart->items()->delete();

        $this->assertSame(0, $this->runReminder());
        Mail::assertNothingQueued();
    }

    public function test_a_cart_older_than_the_window_is_left_alone(): void
    {
        $this->abandonedCart(hoursAgo: 24 * 10);

        $this->assertSame(0, $this->runReminder());
        Mail::assertNothingQueued();
    }

    public function test_a_run_stops_at_the_configured_ceiling(): void
    {
        config(['cart_reminders.max_per_run' => 1]);

        $this->abandonedCart(hoursAgo: 5);
        $this->abandonedCart(hoursAgo: 6);

        $this->assertSame(1, $this->runReminder());
        Mail::assertQueued(OperationalNotificationMail::class, 1);

        // The one left behind goes out on the next pass.
        $this->assertSame(1, $this->runReminder());
    }

    public function test_the_whole_thing_can_be_switched_off(): void
    {
        config(['cart_reminders.enabled' => false]);
        $this->abandonedCart(hoursAgo: 5);

        $this->assertSame(0, $this->runReminder());
        Mail::assertNothingQueued();
    }

    public function test_the_message_carries_what_the_cart_holds(): void
    {
        $product = $this->product(['name_en' => 'Brake Pad Set', 'price' => 12000]);
        $this->abandonedCart(hoursAgo: 5, product: $product, quantity: 2);

        $this->runReminder();

        Mail::assertQueued(OperationalNotificationMail::class, function (OperationalNotificationMail $mail) use ($product): bool {
            $rows = (array) ($mail->context['cart_rows'] ?? []);

            return ($mail->context['type'] ?? null) === 'cart_reminder'
                && count($rows) === 1
                && $rows[0]['name'] === $product->name_en
                && $rows[0]['quantity'] === 2
                && str_contains((string) ($mail->context['cart_total'] ?? ''), '24,000');
        });
    }

    public function test_the_message_renders_as_an_email(): void
    {
        $product = $this->product(['name_en' => 'Brake Pad Set', 'price' => 12000]);
        $this->abandonedCart(hoursAgo: 5, product: $product, quantity: 2);

        $this->runReminder();

        // Mail::fake() never renders, so nothing above would notice a broken
        // template. This is the test that opens the envelope.
        Mail::assertQueued(OperationalNotificationMail::class, function (OperationalNotificationMail $mail): bool {
            $html = $mail->render();

            $this->assertStringContainsString('Brake Pad Set', $html);
            $this->assertStringContainsString('24,000', $html);
            $this->assertStringContainsString(route('cart.index'), $html);

            return true;
        });
    }

    public function test_the_command_runs_the_reminder(): void
    {
        $this->abandonedCart(hoursAgo: 5);

        $this->artisan('carts:remind-abandoned')
            ->expectsOutputToContain('Reminded 1 customer(s)')
            ->assertSuccessful();
    }

    private function runReminder(): int
    {
        return app(AbandonedCartReminder::class)->run();
    }

    private function abandonedCart(
        float $hoursAgo,
        array $userAttributes = [],
        ?Product $product = null,
        int $quantity = 1,
    ): Cart {
        $user = User::factory()->create(array_merge([
            'email_verified_at' => now(),
            'marketing_consent' => true,
            'email_notifications' => true,
        ], $userAttributes));

        $cart = Cart::query()->create(['user_id' => $user->id]);

        CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_id' => ($product ?? $this->product())->id,
            'quantity' => $quantity,
        ]);

        $this->ageCart($cart, $hoursAgo);

        return $cart;
    }

    /**
     * Push the cart's last activity into the past.
     *
     * Written straight to the column rather than through the model: the point
     * is a timestamp Eloquent would otherwise insist on refreshing.
     */
    private function ageCart(Cart $cart, float $hoursAgo): void
    {
        CartItem::query()
            ->where('cart_id', $cart->getKey())
            ->update(['updated_at' => Carbon::now()->subMinutes((int) round($hoursAgo * 60))]);
    }

    /**
     * Push the last reminder we sent into the past, so a later cart activity
     * can land after it the way it would in life.
     */
    private function ageReminder(Cart $cart, float $hoursAgo): void
    {
        Cart::query()
            ->whereKey($cart->getKey())
            ->update(['reminded_at' => Carbon::now()->subMinutes((int) round($hoursAgo * 60))]);
    }

    private function product(array $attributes = []): Product
    {
        if (! Category::query()->whereKey(1)->exists()) {
            Category::factory()->create(['id' => 1]);
        }

        return Product::factory()->create(array_merge([
            'category_id' => 1,
            'is_active' => true,
            'stock_quantity' => 10,
        ], $attributes));
    }
}
