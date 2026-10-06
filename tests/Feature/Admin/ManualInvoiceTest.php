<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\ManualInvoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Invoices\ManualInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ManualInvoiceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);
    }

    private function customer(array $overrides = []): Customer
    {
        return Customer::query()->create(array_merge([
            'name' => 'Karwan Garage',
            'phone' => '+9647701234567',
            'city' => 'Erbil',
            'address' => '60 Meter Street',
        ], $overrides));
    }

    private function product(array $overrides = []): Product
    {
        return Product::factory()->create(array_merge([
            'category_id' => Category::factory()->create()->id,
            'name_en' => 'Front Brake Pad',
            'sku' => 'BRK-1001',
            'price' => 25000,
            'stock_quantity' => 10,
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(Customer $customer, array $items, array $overrides = []): array
    {
        return array_merge([
            'customer_id' => $customer->id,
            'invoice_date' => '2026-10-07',
            'payment_status' => 'unpaid',
            'discount_amount' => 0,
            'delivery_fee' => 0,
            'items' => $items,
            'action' => 'draft',
        ], $overrides);
    }

    private function draft(Customer $customer, Product $product, int $quantity = 2): ManualInvoice
    {
        $this->actingAs($this->admin)->post(route('admin.manual-invoices.store'), $this->payload($customer, [
            ['product_id' => $product->id, 'description' => 'Front Brake Pad', 'sku' => 'BRK-1001', 'quantity' => $quantity, 'unit_price' => 25000],
        ]))->assertSessionHasNoErrors();

        return ManualInvoice::query()->latest('id')->firstOrFail();
    }

    // ── Customers ──────────────────────────────────────────────────

    public function test_a_customer_is_saved_without_an_account_or_a_message(): void
    {
        Mail::fake();
        Notification::fake();
        $usersBefore = User::query()->count();

        $this->actingAs($this->admin)->post(route('admin.customers.store'), [
            'name' => 'Karwan Garage',
            'phone' => '0770 123 4567',
            'whatsapp' => '07509998877',
            'city' => 'Erbil',
            'address' => '60 Meter Street',
            'notes' => 'Pays on delivery',
        ])->assertRedirect(route('admin.customers.index'));

        $this->assertDatabaseHas('customers', [
            'name' => 'Karwan Garage',
            'phone' => '+9647701234567',
            'whatsapp' => '+9647509998877',
            'city' => 'Erbil',
        ]);
        $this->assertSame($usersBefore, User::query()->count());
        Mail::assertNothingOutgoing();
        Notification::assertNothingSent();
    }

    public function test_name_and_a_valid_iraqi_phone_are_required(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.customers.store'), ['name' => '', 'phone' => '12345'])
            ->assertSessionHasErrors(['name', 'phone']);

        $this->assertSame(0, Customer::query()->count());
    }

    public function test_local_and_international_spellings_of_a_number_are_one_customer(): void
    {
        $existing = $this->customer();

        foreach (['07701234567', '+964 770 123 4567', '7701234567', '009647701234567'] as $spelling) {
            $this->actingAs($this->admin)
                ->post(route('admin.customers.store'), ['name' => 'Someone Else', 'phone' => $spelling])
                ->assertRedirect(route('admin.customers.edit', $existing))
                ->assertSessionHas('warning');
        }

        $this->assertSame(1, Customer::query()->count());
        $this->assertSame('Karwan Garage', $existing->fresh()->name);
    }

    public function test_the_invoice_form_is_handed_the_existing_customer_for_a_known_number(): void
    {
        $existing = $this->customer();

        $this->actingAs($this->admin)
            ->postJson(route('admin.customers.store'), ['name' => 'Duplicate', 'phone' => '07701234567'])
            ->assertOk()
            ->assertJsonPath('existing', true)
            ->assertJsonPath('data.id', $existing->id);

        $this->actingAs($this->admin)
            ->postJson(route('admin.customers.store'), ['name' => 'New Person', 'phone' => '07801112233'])
            ->assertCreated()
            ->assertJsonPath('existing', false)
            ->assertJsonPath('data.phone', '+9647801112233');

        $this->assertSame(2, Customer::query()->count());
    }

    public function test_a_customer_cannot_be_given_another_customers_number(): void
    {
        $this->customer();
        $other = $this->customer(['name' => 'Second Shop', 'phone' => '+9647809990000']);

        $this->actingAs($this->admin)
            ->put(route('admin.customers.update', $other), ['name' => 'Second Shop', 'phone' => '0770 123 4567'])
            ->assertSessionHasErrors('phone');

        $this->assertSame('+9647809990000', $other->fresh()->phone);
    }

    public function test_customers_are_found_by_name_or_by_any_spelling_of_the_phone(): void
    {
        $this->customer();
        $this->customer(['name' => 'Unrelated', 'phone' => '+9647500000000']);

        foreach (['karwan', '0770123', '+964770', '7701234567'] as $query) {
            $this->actingAs($this->admin)
                ->getJson(route('admin.customers.search', ['q' => $query]))
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.name', 'Karwan Garage');
        }
    }

    // ── Calculation ────────────────────────────────────────────────

    public function test_totals_are_calculated_on_the_server_and_submitted_totals_are_ignored(): void
    {
        $customer = $this->customer();
        $product = $this->product();

        $this->actingAs($this->admin)->post(route('admin.manual-invoices.store'), $this->payload($customer, [
            ['product_id' => $product->id, 'description' => '', 'quantity' => 3, 'unit_price' => 25000, 'line_total' => 1],
            ['description' => 'Brake service', 'quantity' => 1, 'unit_price' => 15000, 'line_total' => 1],
        ], [
            'discount_amount' => 5000,
            'delivery_fee' => 3000,
            'subtotal' => 1,
            'total' => 1,
        ]))->assertSessionHasNoErrors();

        $invoice = ManualInvoice::query()->with('items')->firstOrFail();

        $this->assertMatchesRegularExpression('/^MINV-\d{4}-\d{5}$/', $invoice->number);
        $this->assertSame(90000.0, $invoice->subtotal);
        $this->assertSame(5000.0, $invoice->discount_amount);
        $this->assertSame(3000.0, $invoice->delivery_fee);
        $this->assertSame(88000.0, $invoice->total);

        // The catalogue line took its name and code from the product; the
        // manual one has no product behind it.
        $this->assertSame('Front Brake Pad', $invoice->items[0]->description);
        $this->assertSame('BRK-1001', $invoice->items[0]->sku);
        $this->assertSame(75000.0, $invoice->items[0]->line_total);
        $this->assertNull($invoice->items[1]->product_id);
        $this->assertSame(15000.0, $invoice->items[1]->line_total);
    }

    public function test_an_invoice_needs_a_customer_a_line_and_a_sane_discount(): void
    {
        $customer = $this->customer();

        $this->actingAs($this->admin)
            ->post(route('admin.manual-invoices.store'), ['invoice_date' => '2026-10-07', 'payment_status' => 'unpaid'])
            ->assertSessionHasErrors(['customer_id', 'items']);

        $this->actingAs($this->admin)
            ->post(route('admin.manual-invoices.store'), $this->payload($customer, [
                ['description' => 'Service', 'quantity' => 0, 'unit_price' => -5],
            ]))
            ->assertSessionHasErrors(['items.0.quantity', 'items.0.unit_price']);

        $this->actingAs($this->admin)
            ->post(route('admin.manual-invoices.store'), $this->payload($customer, [
                ['description' => 'Service', 'quantity' => 1, 'unit_price' => 1000],
            ], ['discount_amount' => 5000]))
            ->assertSessionHasErrors('discount_amount');

        $this->assertSame(0, ManualInvoice::query()->count());
    }

    public function test_invoice_numbers_are_unique(): void
    {
        $customer = $this->customer();
        $product = $this->product();

        $first = $this->draft($customer, $product);
        $second = $this->draft($customer, $product);

        $this->assertNotSame($first->number, $second->number);
    }

    // ── Draft, finalize, stock ─────────────────────────────────────

    public function test_a_draft_touches_neither_stock_nor_revenue(): void
    {
        $product = $this->product();
        $movementsBefore = InventoryMovement::query()->count();

        $invoice = $this->draft($this->customer(), $product, 4);

        $this->assertTrue($invoice->isDraft());
        $this->assertSame(10, (int) $product->fresh()->stock_quantity);
        $this->assertSame($movementsBefore, InventoryMovement::query()->count());
        $this->assertSame(0, Order::query()->count());
    }

    public function test_finalizing_deducts_stock_once_however_often_it_is_pressed(): void
    {
        $product = $this->product();
        $invoice = $this->draft($this->customer(), $product, 4);

        for ($press = 0; $press < 3; $press++) {
            $this->actingAs($this->admin)
                ->post(route('admin.manual-invoices.finalize', $invoice))
                ->assertRedirect(route('admin.manual-invoices.show', $invoice));
        }

        $this->assertTrue($invoice->fresh()->isFinalized());
        $this->assertSame(6, (int) $product->fresh()->stock_quantity);

        $movements = InventoryMovement::query()->where('reference', $invoice->number)->get();
        $this->assertCount(1, $movements);
        $this->assertSame(InventoryMovement::TYPE_OUT, $movements[0]->type);
        $this->assertSame(4, (int) $movements[0]->quantity);
        $this->assertSame($this->admin->id, $movements[0]->user_id);
    }

    public function test_a_manual_line_moves_no_stock(): void
    {
        $customer = $this->customer();

        $this->actingAs($this->admin)->post(route('admin.manual-invoices.store'), $this->payload($customer, [
            ['description' => 'Oil change service', 'quantity' => 1, 'unit_price' => 20000],
        ], ['action' => 'finalize']))->assertSessionHasNoErrors();

        $this->assertTrue(ManualInvoice::query()->firstOrFail()->isFinalized());
        $this->assertSame(0, InventoryMovement::query()->count());
    }

    public function test_short_stock_leaves_the_invoice_a_draft_and_deducts_nothing(): void
    {
        $customer = $this->customer();
        $plenty = $this->product(['sku' => 'OK-1', 'name_en' => 'Plenty', 'stock_quantity' => 50]);
        $short = $this->product(['sku' => 'LOW-1', 'name_en' => 'Scarce Part', 'stock_quantity' => 1]);

        $this->actingAs($this->admin)->post(route('admin.manual-invoices.store'), $this->payload($customer, [
            ['product_id' => $plenty->id, 'description' => 'Plenty', 'quantity' => 5, 'unit_price' => 1000],
            ['product_id' => $short->id, 'description' => 'Scarce Part', 'quantity' => 3, 'unit_price' => 1000],
        ], ['action' => 'finalize']))->assertSessionHasErrors('items');

        // Saved once as a draft — not lost, and not written twice on retry.
        $this->assertSame(1, ManualInvoice::query()->count());
        $this->assertTrue(ManualInvoice::query()->firstOrFail()->isDraft());
        $this->assertSame(50, (int) $plenty->fresh()->stock_quantity);
        $this->assertSame(1, (int) $short->fresh()->stock_quantity);
        $this->assertSame(0, InventoryMovement::query()->count());
    }

    public function test_a_finalized_invoice_keeps_what_it_said_when_catalogue_and_customer_change(): void
    {
        $customer = $this->customer();
        $product = $this->product();
        $invoice = $this->draft($customer, $product);
        $this->actingAs($this->admin)->post(route('admin.manual-invoices.finalize', $invoice));

        $product->update(['name_en' => 'Renamed Pad', 'sku' => 'NEW-SKU', 'price' => 99999]);
        $customer->update(['name' => 'New Owner', 'phone' => '+9647800000001', 'city' => 'Duhok']);

        $invoice = $invoice->fresh('items');
        $this->assertSame('Karwan Garage', $invoice->customer_name);
        $this->assertSame('+9647701234567', $invoice->customer_phone);
        $this->assertSame('Erbil', $invoice->customer_city);
        $this->assertSame('Front Brake Pad', $invoice->items[0]->description);
        $this->assertSame('BRK-1001', $invoice->items[0]->sku);
        $this->assertSame(25000.0, $invoice->items[0]->unit_price);
        $this->assertSame(50000.0, $invoice->total);

        $this->actingAs($this->admin)
            ->get(route('admin.manual-invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Karwan Garage')
            ->assertDontSee('New Owner');
    }

    public function test_a_finalized_invoice_cannot_be_edited_or_deleted(): void
    {
        $customer = $this->customer();
        $product = $this->product();
        $invoice = $this->draft($customer, $product);
        $this->actingAs($this->admin)->post(route('admin.manual-invoices.finalize', $invoice));

        $this->actingAs($this->admin)
            ->put(route('admin.manual-invoices.update', $invoice), $this->payload($customer, [
                ['description' => 'Tampered', 'quantity' => 1, 'unit_price' => 1],
            ]))
            ->assertSessionHasErrors('invoice');

        $this->actingAs($this->admin)
            ->delete(route('admin.manual-invoices.destroy', $invoice))
            ->assertSessionHasErrors('invoice');

        $this->actingAs($this->admin)
            ->get(route('admin.manual-invoices.edit', $invoice))
            ->assertRedirect(route('admin.manual-invoices.show', $invoice));

        $this->assertSame(50000.0, $invoice->fresh()->total);
        $this->assertSame(8, (int) $product->fresh()->stock_quantity);
    }

    public function test_a_draft_can_be_edited_and_deleted_and_payment_status_changed(): void
    {
        $customer = $this->customer();
        $product = $this->product();
        $invoice = $this->draft($customer, $product);

        $this->actingAs($this->admin)
            ->put(route('admin.manual-invoices.update', $invoice), $this->payload($customer, [
                ['description' => 'Wheel alignment', 'quantity' => 1, 'unit_price' => 30000],
            ], ['payment_status' => 'partial']))
            ->assertRedirect(route('admin.manual-invoices.show', $invoice));

        $invoice = $invoice->fresh('items');
        $this->assertCount(1, $invoice->items);
        $this->assertSame(30000.0, $invoice->total);

        $this->actingAs($this->admin)
            ->patch(route('admin.manual-invoices.update-payment', $invoice), ['payment_status' => 'paid'])
            ->assertSessionHasNoErrors();
        $this->assertSame('paid', $invoice->fresh()->payment_status);

        $this->actingAs($this->admin)
            ->delete(route('admin.manual-invoices.destroy', $invoice))
            ->assertRedirect(route('admin.manual-invoices.index'));
        $this->assertDatabaseMissing('manual_invoices', ['id' => $invoice->id]);
    }

    // ── Listing ────────────────────────────────────────────────────

    public function test_the_list_is_searchable_by_customer_phone_number_date_and_payment(): void
    {
        $product = $this->product(['stock_quantity' => 100]);
        $first = $this->draft($this->customer(), $product);
        $second = $this->draft($this->customer(['name' => 'Soran Parts', 'phone' => '+9647501112233']), $product);
        $second->update(['payment_status' => 'paid', 'invoice_date' => '2026-09-01']);

        $see = fn (array $query) => $this->actingAs($this->admin)->get(route('admin.manual-invoices.index', $query))->assertOk();

        $see(['q' => 'karwan'])->assertSee($first->number)->assertDontSee($second->number);
        $see(['q' => '0750111'])->assertSee($second->number)->assertDontSee($first->number);
        $see(['q' => $second->number])->assertSee('Soran Parts')->assertDontSee('Karwan Garage');
        $see(['payment_status' => 'paid'])->assertSee($second->number)->assertDontSee($first->number);
        $see(['date_from' => '2026-10-01'])->assertSee($first->number)->assertDontSee($second->number);
        $see(['date_to' => '2026-09-30'])->assertSee($second->number)->assertDontSee($first->number);
    }

    // ── Authorization ──────────────────────────────────────────────

    public function test_only_staff_who_manage_orders_reach_any_of_it(): void
    {
        $invoice = $this->draft($this->customer(), $this->product());

        $routes = [
            ['get', route('admin.manual-invoices.index')],
            ['get', route('admin.manual-invoices.create')],
            ['get', route('admin.manual-invoices.show', $invoice)],
            ['get', route('admin.manual-invoices.pdf', $invoice)],
            ['post', route('admin.manual-invoices.finalize', $invoice)],
            ['post', route('admin.manual-invoices.share', $invoice)],
            ['get', route('admin.customers.index')],
            ['get', route('admin.customers.search')],
            ['post', route('admin.customers.store')],
        ];

        $outsiders = [
            User::factory()->create(['role' => User::ROLE_USER, 'email_verified_at' => now()]),
            User::factory()->create(['role' => User::ROLE_DEALER, 'email_verified_at' => now()]),
            User::factory()->create(['role' => User::ROLE_PRODUCT_MANAGER, 'email_verified_at' => now()]),
        ];

        foreach ($outsiders as $user) {
            foreach ($routes as [$method, $url]) {
                $this->assertContains(
                    $this->actingAs($user)->{$method}($url)->status(),
                    [302, 403],
                    "{$user->role} reached {$method} {$url}"
                );
            }
        }

        auth()->logout();
        $this->get(route('admin.manual-invoices.index'))->assertRedirect();

        $this->assertTrue($invoice->fresh()->isDraft());
        $this->assertNull($invoice->fresh()->share_token_hash);

        $orderManager = User::factory()->create(['role' => User::ROLE_ORDER_MANAGER, 'email_verified_at' => now()]);
        $this->actingAs($orderManager)->get(route('admin.manual-invoices.index'))->assertOk();
    }

    // ── PDF ────────────────────────────────────────────────────────

    public function test_the_pdf_renders_in_all_three_languages(): void
    {
        $invoice = $this->draft($this->customer(['name' => 'گەراجی کاروان']), $this->product());
        $this->actingAs($this->admin)->post(route('admin.manual-invoices.finalize', $invoice));

        foreach (['en', 'ar', 'ku'] as $locale) {
            $response = $this->actingAs($this->admin)
                ->get(route('admin.manual-invoices.pdf', ['manual_invoice' => $invoice, 'doc_lang' => $locale]));

            $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
            $this->assertStringStartsWith('%PDF-', $response->getContent());
            $this->assertStringContainsString('attachment', (string) $response->headers->get('Content-Disposition'));
            $this->assertStringContainsString('-'.$locale.'.pdf', (string) $response->headers->get('Content-Disposition'));
        }

        // Printing in another language leaves the panel in the admin's own.
        $this->assertNotSame('ku', session('locale'));

        $this->actingAs($this->admin)
            ->get(route('admin.manual-invoices.pdf', ['manual_invoice' => $invoice, 'inline' => 1]))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'inline; filename="invoice-'.$invoice->number.'-en.pdf"');
    }

    public function test_the_printed_invoice_carries_the_business_the_customer_and_the_totals(): void
    {
        $invoice = $this->draft($this->customer(), $this->product());

        foreach (['en' => 'INVOICE', 'ar' => 'حالة الدفع', 'ku' => 'دۆخی پارەدان'] as $locale => $expected) {
            app()->setLocale($locale);

            $html = view('admin.manual-invoices.pdf', [
                'invoice' => $invoice->load('items'),
                'currency' => 'IQD',
                'logoPath' => null,
                'locale' => $locale,
                'isRtl' => $locale !== 'en',
            ])->render();

            $this->assertStringContainsString($expected, $html);
            $this->assertStringContainsString($invoice->number, $html);
            $this->assertStringContainsString('Karwan Garage', $html);
            $this->assertStringContainsString('Front Brake Pad', $html);
            $this->assertStringContainsString('BRK-1001', $html);
            $this->assertStringContainsString('50,000 IQD', $html);
            $this->assertStringContainsString($locale === 'en' ? 'dir="ltr"' : 'dir="rtl"', $html);
        }
    }

    // ── Sharing ────────────────────────────────────────────────────

    public function test_a_draft_cannot_be_shared(): void
    {
        $invoice = $this->draft($this->customer(), $this->product());

        $this->actingAs($this->admin)
            ->post(route('admin.manual-invoices.share', $invoice))
            ->assertSessionHasErrors('share');

        $this->assertNull($invoice->fresh()->share_token_hash);
    }

    public function test_a_share_link_opens_that_invoice_only_and_needs_no_sign_in(): void
    {
        $product = $this->product(['stock_quantity' => 100]);
        $invoice = $this->draft($this->customer(), $product);
        $other = $this->draft($this->customer(['name' => 'Private Other Customer', 'phone' => '+9647502223344']), $product);
        $this->actingAs($this->admin)->post(route('admin.manual-invoices.finalize', $invoice));
        $this->actingAs($this->admin)->post(route('admin.manual-invoices.finalize', $other));

        $this->actingAs($this->admin)->post(route('admin.manual-invoices.share', $invoice))->assertSessionHasNoErrors();
        $invoice = $invoice->fresh();
        $url = $invoice->shareUrl();

        $this->assertMatchesRegularExpression('#/i/[a-f0-9]{48}$#', $url);
        // The raw token is not what is stored for lookup.
        $this->assertNotSame($invoice->share_token, $invoice->share_token_hash);
        $this->assertDatabaseMissing('manual_invoices', ['share_token' => $invoice->share_token]);

        auth()->logout();

        $page = $this->get($url);
        $page->assertOk()
            ->assertSee($invoice->number)
            ->assertSee('Karwan Garage')
            ->assertSee('Front Brake Pad')
            ->assertDontSee($other->number)
            ->assertDontSee('Private Other Customer')
            ->assertDontSee('/admin/', false)
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $this->assertStringContainsString('no-store', (string) $page->headers->get('Cache-Control'));

        $pdf = $this->get($url.'/pdf');
        $pdf->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());

        // Asking again keeps the link a customer may already have.
        $this->actingAs($this->admin)->post(route('admin.manual-invoices.share', $invoice));
        $this->assertSame($url, $invoice->fresh()->shareUrl());
    }

    public function test_wrong_guessed_and_revoked_links_all_look_like_nothing(): void
    {
        $invoice = $this->draft($this->customer(), $this->product());
        $this->actingAs($this->admin)->post(route('admin.manual-invoices.finalize', $invoice));
        $this->actingAs($this->admin)->post(route('admin.manual-invoices.share', $invoice));
        $url = $invoice->fresh()->shareUrl();

        auth()->logout();

        $this->get('/i/'.str_repeat('a', 48))->assertNotFound();
        $this->get('/i/'.$invoice->id)->assertNotFound();
        $this->get('/i/'.$invoice->number)->assertNotFound();
        $this->get($url)->assertOk();

        $this->actingAs($this->admin)
            ->delete(route('admin.manual-invoices.share.revoke', $invoice))
            ->assertSessionHasNoErrors();
        auth()->logout();

        $this->get($url)->assertNotFound();
        $this->get($url.'/pdf')->assertNotFound();

        // A fresh link is a different link; the old one stays dead.
        $this->actingAs($this->admin)->post(route('admin.manual-invoices.share', $invoice));
        $this->assertNotSame($url, $invoice->fresh()->shareUrl());
        auth()->logout();
        $this->get($url)->assertNotFound();
    }

    public function test_the_whatsapp_button_opens_a_chat_with_a_ready_message_and_the_public_link(): void
    {
        $invoice = $this->draft($this->customer(['whatsapp' => '+9647509998877']), $this->product());
        $this->actingAs($this->admin)->post(route('admin.manual-invoices.finalize', $invoice));

        $service = app(ManualInvoiceService::class);
        $this->assertNull($service->whatsappUrl($invoice->fresh()), 'No link, no message to send.');

        $this->actingAs($this->admin)->post(route('admin.manual-invoices.share', $invoice));
        $invoice = $invoice->fresh();
        $url = $service->whatsappUrl($invoice);

        // The customer's WhatsApp number, digits only, as wa.me expects.
        $this->assertStringStartsWith('https://wa.me/9647509998877?text=', $url);

        $message = rawurldecode((string) parse_url($url, PHP_URL_QUERY));
        $this->assertStringContainsString($service->businessName(), $message);
        $this->assertStringContainsString($invoice->number, $message);
        $this->assertStringContainsString('50,000 IQD', $message);
        $this->assertStringContainsString($invoice->shareUrl(), $message);
        $this->assertStringNotContainsString('/admin/', $message);

        $this->actingAs($this->admin)
            ->get(route('admin.manual-invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Send via WhatsApp')
            ->assertSee('https://wa.me/9647509998877?text=', false)
            ->assertSee('Download PDF');
    }

    public function test_every_screen_renders(): void
    {
        $customer = $this->customer();
        $invoice = $this->draft($customer, $this->product());

        foreach ([
            route('admin.customers.index'),
            route('admin.customers.create'),
            route('admin.customers.edit', $customer),
            route('admin.manual-invoices.index'),
            route('admin.manual-invoices.create'),
            route('admin.manual-invoices.create', ['customer_id' => $customer->id]),
            route('admin.manual-invoices.edit', $invoice),
            route('admin.manual-invoices.show', $invoice),
        ] as $url) {
            $this->actingAs($this->admin)->get($url)->assertOk();
        }

        $this->actingAs($this->admin)
            ->get(route('admin.manual-invoices.index'))
            ->assertSee('Manual Invoices')
            ->assertSee('Customer Directory')
            ->assertSee('https://wa.me/9647701234567', false);

        $this->actingAs($this->admin)
            ->getJson(route('admin.manual-invoices.products.search', ['q' => 'BRK']))
            ->assertOk()
            ->assertJsonPath('data.0.sku', 'BRK-1001')
            ->assertJsonPath('data.0.price', 25000);
    }
}
