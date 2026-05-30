<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MobileInvoiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrderFor(User $user, array $overrides = []): Order
    {
        return Order::query()->forceCreate(array_merge([
            'user_id' => $user->id,
            'order_number' => 'TEST-' . Str::upper(Str::random(8)),
            'total_amount' => 25000,
            'subtotal_amount' => 25000,
            'shipping_fee' => 5000,
            'discount_amount' => 0,
            'grand_total' => 30000,
            'status' => Order::STATUS_PROCESSING,
            'payment_method' => 'cash_on_delivery',
            'delivery_address' => '123 Test Street',
            'delivery_city' => 'Erbil',
            'delivery_phone' => '+964 770 000 0000',
        ], $overrides));
    }

    public function test_invoice_requires_authentication(): void
    {
        $this->getJson('/api/mobile/orders/1/invoice')->assertStatus(401);
    }

    public function test_invoice_rejects_non_owner(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $order = $this->makeOrderFor($owner);

        $this->actingAs($intruder, 'sanctum')
            ->get('/api/mobile/orders/' . $order->id . '/invoice')
            ->assertStatus(403);
    }

    public function test_invoice_returns_404_for_missing_order(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->get('/api/mobile/orders/999999/invoice')
            ->assertStatus(404);
    }

    public function test_invoice_returns_pdf_with_attachment_header(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrderFor($user);

        $response = $this->actingAs($user, 'sanctum')
            ->get('/api/mobile/orders/' . $order->id . '/invoice');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $disposition = $response->headers->get('Content-Disposition');
        $this->assertNotNull($disposition);
        $this->assertStringContainsString('attachment', $disposition);
        $this->assertStringContainsString('invoice-' . $order->id . '-en.pdf', $disposition);
    }

    public function test_invoice_lang_query_overrides_everything(): void
    {
        $user = User::factory()->create(['locale_preference' => 'en']);
        $order = $this->makeOrderFor($user);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['Accept-Language' => 'ku'])
            ->get('/api/mobile/orders/' . $order->id . '/invoice?lang=ar');

        $response->assertOk();
        $this->assertStringContainsString(
            'invoice-' . $order->id . '-ar.pdf',
            (string) $response->headers->get('Content-Disposition'),
        );
    }

    public function test_invoice_user_locale_preference_beats_accept_language(): void
    {
        $user = User::factory()->create(['locale_preference' => 'ar']);
        $order = $this->makeOrderFor($user);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['Accept-Language' => 'ku'])
            ->get('/api/mobile/orders/' . $order->id . '/invoice');

        $response->assertOk();
        $this->assertStringContainsString(
            'invoice-' . $order->id . '-ar.pdf',
            (string) $response->headers->get('Content-Disposition'),
        );
    }

    public function test_invoice_uses_accept_language_when_no_user_preference(): void
    {
        $user = User::factory()->create(['locale_preference' => '']);
        $order = $this->makeOrderFor($user);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['Accept-Language' => 'ku'])
            ->get('/api/mobile/orders/' . $order->id . '/invoice');

        $response->assertOk();
        $this->assertStringContainsString(
            'invoice-' . $order->id . '-ku.pdf',
            (string) $response->headers->get('Content-Disposition'),
        );
    }

    public function test_invoice_falls_back_to_english_when_nothing_set(): void
    {
        $user = User::factory()->create(['locale_preference' => '']);
        $order = $this->makeOrderFor($user);

        $response = $this->actingAs($user, 'sanctum')
            ->get('/api/mobile/orders/' . $order->id . '/invoice');

        $response->assertOk();
        $this->assertStringContainsString(
            'invoice-' . $order->id . '-en.pdf',
            (string) $response->headers->get('Content-Disposition'),
        );
    }

    public function test_invoice_ignores_unknown_lang_value(): void
    {
        $user = User::factory()->create(['locale_preference' => '']);
        $order = $this->makeOrderFor($user);

        $response = $this->actingAs($user, 'sanctum')
            ->get('/api/mobile/orders/' . $order->id . '/invoice?lang=tr');

        $response->assertOk();
        $this->assertStringContainsString(
            'invoice-' . $order->id . '-en.pdf',
            (string) $response->headers->get('Content-Disposition'),
        );
    }
}
