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
use Illuminate\Support\Facades\DB;
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

    public function test_name_and_a_valid_phone_are_required(): void
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

    public function test_numbers_are_read_by_their_own_country_and_never_given_an_iraqi_prefix(): void
    {
        $cases = [
            // country, as typed, stored
            ['IQ', '0770 123 4567', '+9647701234567'],
            ['IR', '0912 345 6789', '+989123456789'],
            ['IR', '۰۹۱۲۳۴۵۶۷۸۰', '+989123456780'],
            ['TR', '0532 123 45 67', '+905321234567'],
            ['TR', '532 123 45 68', '+905321234568'],
            // A number written in full says where it is from, whatever is selected.
            ['IQ', '+90 532 123 45 69', '+905321234569'],
            ['IQ', '0098 912 345 6781', '+989123456781'],
            ['DE', '0151 23456789', '+4915123456789'],
        ];

        foreach ($cases as $index => [$country, $typed, $stored]) {
            $this->actingAs($this->admin)->post(route('admin.customers.store'), [
                'name' => 'Customer '.$index,
                'phone' => $typed,
                'phone_country' => $country,
            ])->assertSessionHasNoErrors();

            $this->assertDatabaseHas('customers', ['name' => 'Customer '.$index, 'phone' => $stored]);
        }

        $this->assertSame(0, Customer::query()->where('phone', 'like', '+9649%')->orWhere('phone', 'like', '+9645%')->count());
    }

    public function test_a_number_that_is_wrong_for_its_country_is_refused_by_name(): void
    {
        foreach ([
            ['IR', '0912 345', 'Iran'],
            ['TR', '12345', 'Türkiye'],
            ['TR', '0770 123 4567', 'Türkiye'],   // an Iraqi mobile is not a Turkish number
            ['IQ', '0770 12', 'Iraq'],
        ] as [$country, $typed, $named]) {
            $this->actingAs($this->admin)
                ->post(route('admin.customers.store'), ['name' => 'Nobody', 'phone' => $typed, 'phone_country' => $country])
                ->assertSessionHasErrors('phone');

            $this->assertStringContainsString($named, session('errors')->first('phone'));
        }

        $this->actingAs($this->admin)
            ->post(route('admin.customers.store'), ['name' => 'Nobody', 'phone' => '0912 345 6789', 'phone_country' => 'XX'])
            ->assertSessionHasErrors('phone_country');

        $this->assertSame(0, Customer::query()->count());
    }

    public function test_whatsapp_and_address_country_are_kept_separately_from_the_phone(): void
    {
        // Lives in Türkiye, keeps an Iranian line, uses WhatsApp on a Turkish one.
        $this->actingAs($this->admin)->post(route('admin.customers.store'), [
            'name' => 'Cross Border Trading',
            'phone' => '0912 345 6789',
            'phone_country' => 'IR',
            'whatsapp' => '0532 123 45 67',
            'whatsapp_country' => 'TR',
            'country' => 'TR',
            'city' => 'Van',
        ])->assertSessionHasNoErrors();

        $customer = Customer::query()->firstOrFail();
        $this->assertSame('+989123456789', $customer->phone);
        $this->assertSame('+905321234567', $customer->whatsapp);
        $this->assertSame('TR', $customer->country);

        // The edit form shows each number under its own country, not Iraq's.
        $this->actingAs($this->admin)
            ->get(route('admin.customers.edit', $customer))
            ->assertOk()
            ->assertSee('value="9123456789"', false)
            ->assertSee('value="5321234567"', false)
            ->assertSee('<option value="IR" selected>', false)
            ->assertSee('<option value="TR" selected>', false);

        // Saving it back unchanged leaves both numbers exactly as they were.
        $this->actingAs($this->admin)->put(route('admin.customers.update', $customer), [
            'name' => 'Cross Border Trading',
            'phone' => '9123456789', 'phone_country' => 'IR',
            'whatsapp' => '5321234567', 'whatsapp_country' => 'TR',
            'country' => 'TR', 'city' => 'Van',
        ])->assertSessionHasNoErrors();

        $this->assertSame('+989123456789', $customer->fresh()->phone);
        $this->assertSame('+905321234567', $customer->fresh()->whatsapp);

        // The invoice carries the country and WhatsApp opens the Turkish number.
        $invoice = $this->draft($customer, $this->product());
        $this->actingAs($this->admin)->post(route('admin.manual-invoices.finalize', $invoice));
        $this->actingAs($this->admin)->post(route('admin.manual-invoices.share', $invoice));
        $invoice = $invoice->fresh();

        $this->assertSame('TR', $invoice->customer_country);
        $this->assertStringStartsWith('https://wa.me/905321234567?text=', app(ManualInvoiceService::class)->whatsappUrl($invoice));

        $this->actingAs($this->admin)->get(route('admin.manual-invoices.show', $invoice))->assertSee('Türkiye');
        auth()->logout();
        $this->get($invoice->shareUrl())->assertOk()->assertSee('Türkiye')->assertSee('+989123456789');
    }

    public function test_customers_saved_before_countries_existed_are_untouched_and_iraqi(): void
    {
        // What the first migration produced: no country given at all.
        DB::table('customers')->insert([
            'name' => 'Old Customer', 'phone' => '+9647701234567', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $customer = Customer::query()->firstOrFail();
        $this->assertSame('IQ', $customer->country);
        $this->assertSame('+9647701234567', $customer->phone);

        $this->actingAs($this->admin)
            ->get(route('admin.customers.edit', $customer))
            ->assertOk()
            ->assertSee('value="7701234567"', false)
            ->assertSee('<option value="IQ" selected>', false);

        // Still the one customer for that number, in any spelling.
        $this->actingAs($this->admin)
            ->post(route('admin.customers.store'), ['name' => 'Again', 'phone' => '0770 123 4567', 'phone_country' => 'IQ'])
            ->assertRedirect(route('admin.customers.edit', $customer));
        $this->assertSame(1, Customer::query()->count());
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

    public function test_a_finalized_invoice_cannot_be_deleted(): void
    {
        $customer = $this->customer();
        $product = $this->product();
        $invoice = $this->draft($customer, $product);
        $this->actingAs($this->admin)->post(route('admin.manual-invoices.finalize', $invoice));

        $this->actingAs($this->admin)
            ->delete(route('admin.manual-invoices.destroy', $invoice))
            ->assertSessionHasErrors('invoice');

        $this->assertSame(50000.0, $invoice->fresh()->total);
        $this->assertSame(8, (int) $product->fresh()->stock_quantity);
    }

    // ── Amending a finalized invoice ───────────────────────────────

    private function finalizedFor(Customer $customer, Product $product, int $quantity = 2): ManualInvoice
    {
        $invoice = $this->draft($customer, $product, $quantity);
        $this->actingAs($this->admin)->post(route('admin.manual-invoices.finalize', $invoice));

        return $invoice->fresh();
    }

    private function line(Product $product, int $quantity, float $unitPrice): array
    {
        return ['product_id' => $product->id, 'description' => $product->name_en, 'sku' => $product->sku, 'quantity' => $quantity, 'unit_price' => $unitPrice];
    }

    public function test_a_finalized_invoice_opens_for_editing(): void
    {
        $invoice = $this->finalizedFor($this->customer(), $this->product());

        $this->actingAs($this->admin)
            ->get(route('admin.manual-invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Edit invoice');

        $this->actingAs($this->admin)
            ->get(route('admin.manual-invoices.edit', $invoice))
            ->assertOk()
            ->assertSee('This invoice is already finalized.')
            ->assertSee('Save changes')
            ->assertDontSee('Save as draft');
    }

    public function test_adding_a_product_later_deducts_only_that_product(): void
    {
        $customer = $this->customer();
        $pads = $this->product();
        $filter = $this->product(['name_en' => 'Oil Filter', 'sku' => 'OIL-2002', 'price' => 8000, 'stock_quantity' => 5]);
        $invoice = $this->finalizedFor($customer, $pads);
        $movementsBefore = InventoryMovement::query()->count();

        $this->actingAs($this->admin)
            ->put(route('admin.manual-invoices.update', $invoice), $this->payload($customer, [
                $this->line($pads, 2, 25000),
                $this->line($filter, 3, 8000),
            ]))
            ->assertRedirect(route('admin.manual-invoices.show', $invoice))
            ->assertSessionHasNoErrors();

        $invoice = $invoice->fresh('items');

        $this->assertTrue($invoice->isFinalized());
        $this->assertCount(2, $invoice->items);
        $this->assertSame(74000.0, $invoice->total);
        $this->assertSame(8, (int) $pads->fresh()->stock_quantity);
        $this->assertSame(2, (int) $filter->fresh()->stock_quantity);

        // One movement, for the product that changed, under the invoice number.
        $this->assertSame($movementsBefore + 1, InventoryMovement::query()->count());
        $movement = InventoryMovement::query()->latest('id')->firstOrFail();
        $this->assertSame($filter->id, (int) $movement->product_id);
        $this->assertSame(InventoryMovement::TYPE_OUT, $movement->type);
        $this->assertSame(3, (int) $movement->quantity);
        $this->assertSame($invoice->number, $movement->reference);
    }

    public function test_removing_a_line_or_lowering_a_quantity_returns_the_stock(): void
    {
        $customer = $this->customer();
        $pads = $this->product();
        $filter = $this->product(['name_en' => 'Oil Filter', 'sku' => 'OIL-2002', 'price' => 8000, 'stock_quantity' => 5]);
        $invoice = $this->finalizedFor($customer, $pads, 4);

        $this->actingAs($this->admin)
            ->put(route('admin.manual-invoices.update', $invoice), $this->payload($customer, [
                $this->line($pads, 4, 25000),
                $this->line($filter, 2, 8000),
            ]))->assertSessionHasNoErrors();

        $this->assertSame(6, (int) $pads->fresh()->stock_quantity);
        $this->assertSame(3, (int) $filter->fresh()->stock_quantity);

        // Fewer pads, and the filter taken off altogether.
        $this->actingAs($this->admin)
            ->put(route('admin.manual-invoices.update', $invoice), $this->payload($customer, [
                $this->line($pads, 1, 25000),
            ]))->assertSessionHasNoErrors();

        $this->assertSame(9, (int) $pads->fresh()->stock_quantity);
        $this->assertSame(5, (int) $filter->fresh()->stock_quantity);
        $this->assertSame(25000.0, $invoice->fresh()->total);
    }

    public function test_a_price_change_or_a_discount_moves_no_stock_and_updates_the_balance(): void
    {
        $customer = $this->customer();
        $pads = $this->product();
        $invoice = $this->finalizedFor($customer, $pads);

        $this->actingAs($this->admin)->post(route('admin.manual-invoices.payments.store', $invoice), [
            'kind' => 'payment', 'amount' => 50000, 'paid_on' => now()->toDateString(),
        ])->assertSessionHasNoErrors();
        $this->assertSame(ManualInvoice::PAYMENT_PAID, $invoice->fresh()->payment_status);

        $movementsBefore = InventoryMovement::query()->count();

        // The customer comes back for a dearer fitting: the price goes up.
        $this->actingAs($this->admin)
            ->put(route('admin.manual-invoices.update', $invoice), $this->payload($customer, [
                $this->line($pads, 2, 30000),
            ], ['discount_amount' => 4000]))
            ->assertSessionHasNoErrors();

        $invoice = $invoice->fresh();

        $this->assertSame(60000.0, $invoice->subtotal);
        $this->assertSame(4000.0, $invoice->discount_amount);
        $this->assertSame(56000.0, $invoice->total);
        $this->assertSame(50000.0, $invoice->paid_amount);
        $this->assertSame(6000.0, $invoice->balance());
        $this->assertSame(ManualInvoice::PAYMENT_PARTIAL, $invoice->payment_status);
        $this->assertSame($movementsBefore, InventoryMovement::query()->count());
        $this->assertSame(8, (int) $pads->fresh()->stock_quantity);
    }

    public function test_the_total_cannot_be_cut_below_what_was_already_paid(): void
    {
        $customer = $this->customer();
        $pads = $this->product();
        $invoice = $this->finalizedFor($customer, $pads);

        $this->actingAs($this->admin)->post(route('admin.manual-invoices.payments.store', $invoice), [
            'kind' => 'payment', 'amount' => 40000, 'paid_on' => now()->toDateString(),
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.manual-invoices.update', $invoice), $this->payload($customer, [
                $this->line($pads, 1, 25000),
            ]))
            ->assertSessionHasErrors('items');

        // Nothing moved: not the total, not the lines, not the shelf.
        $this->assertSame(50000.0, $invoice->fresh()->total);
        $this->assertSame(2, (int) $invoice->items()->first()->quantity);
        $this->assertSame(8, (int) $pads->fresh()->stock_quantity);
    }

    public function test_an_increase_the_shelf_cannot_cover_changes_nothing(): void
    {
        $customer = $this->customer();
        $pads = $this->product();
        $filter = $this->product(['name_en' => 'Oil Filter', 'sku' => 'OIL-2002', 'price' => 8000, 'stock_quantity' => 1]);
        $invoice = $this->finalizedFor($customer, $pads);

        $this->actingAs($this->admin)
            ->put(route('admin.manual-invoices.update', $invoice), $this->payload($customer, [
                $this->line($pads, 5, 25000),
                $this->line($filter, 2, 8000),
            ]))
            ->assertSessionHasErrors('items');

        $this->assertSame(50000.0, $invoice->fresh()->total);
        $this->assertCount(1, $invoice->fresh()->items);
        $this->assertSame(8, (int) $pads->fresh()->stock_quantity);
        $this->assertSame(1, (int) $filter->fresh()->stock_quantity);
    }

    public function test_a_line_keeps_the_cost_it_was_sold_at_when_the_invoice_is_amended(): void
    {
        $customer = $this->customer();
        $pads = $this->product(['cost_price' => 15000]);
        $filter = $this->product(['name_en' => 'Oil Filter', 'sku' => 'OIL-2002', 'price' => 8000, 'stock_quantity' => 5, 'cost_price' => 5000]);
        $invoice = $this->finalizedFor($customer, $pads);

        $pads->update(['cost_price' => 19000]);

        $this->actingAs($this->admin)
            ->put(route('admin.manual-invoices.update', $invoice), $this->payload($customer, [
                $this->line($pads, 2, 25000),
                $this->line($filter, 1, 8000),
            ]))->assertSessionHasNoErrors();

        $items = $invoice->fresh('items')->items->keyBy('product_id');

        $this->assertSame(15000.0, (float) $items[$pads->id]->unit_cost);
        $this->assertSame(5000.0, (float) $items[$filter->id]->unit_cost);
    }

    public function test_a_void_invoice_cannot_be_amended(): void
    {
        $customer = $this->customer();
        $pads = $this->product();
        $invoice = $this->finalizedFor($customer, $pads);

        $this->actingAs($this->admin)
            ->post(route('admin.manual-invoices.void', $invoice), ['void_reason' => 'Entered twice'])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->admin)
            ->get(route('admin.manual-invoices.edit', $invoice))
            ->assertRedirect(route('admin.manual-invoices.show', $invoice));

        $this->actingAs($this->admin)
            ->put(route('admin.manual-invoices.update', $invoice), $this->payload($customer, [
                $this->line($pads, 1, 25000),
            ]))
            ->assertSessionHasErrors('invoice');

        $this->assertTrue($invoice->fresh()->isVoid());
        $this->assertSame(10, (int) $pads->fresh()->stock_quantity);
    }

    public function test_a_draft_can_be_edited_and_deleted(): void
    {
        $customer = $this->customer();
        $product = $this->product();
        $invoice = $this->draft($customer, $product);

        $this->actingAs($this->admin)
            ->put(route('admin.manual-invoices.update', $invoice), $this->payload($customer, [
                ['description' => 'Wheel alignment', 'quantity' => 1, 'unit_price' => 30000],
            ]))
            ->assertRedirect(route('admin.manual-invoices.show', $invoice));

        $invoice = $invoice->fresh('items');
        $this->assertCount(1, $invoice->items);
        $this->assertSame(30000.0, $invoice->total);

        $this->actingAs($this->admin)
            ->delete(route('admin.manual-invoices.destroy', $invoice))
            ->assertRedirect(route('admin.manual-invoices.index'));
        $this->assertDatabaseMissing('manual_invoices', ['id' => $invoice->id]);
    }

    // ── Payments ───────────────────────────────────────────────────

    private function finalized(int $quantity = 2): ManualInvoice
    {
        $invoice = $this->draft($this->customer(), $this->product(), $quantity);
        $this->actingAs($this->admin)->post(route('admin.manual-invoices.finalize', $invoice));

        return $invoice->fresh();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function pay(ManualInvoice $invoice, float $amount, string $kind = 'payment', array $overrides = [])
    {
        return $this->actingAs($this->admin)->post(route('admin.manual-invoices.payments.store', $invoice), array_merge([
            'kind' => $kind,
            'amount' => $amount,
            'paid_on' => now()->toDateString(),
            'method' => 'cash',
        ], $overrides));
    }

    public function test_the_payment_status_follows_the_money_recorded(): void
    {
        $invoice = $this->finalized();   // total 50,000
        $this->assertSame('unpaid', $invoice->payment_status);

        $this->pay($invoice, 20000)->assertSessionHasNoErrors();
        $invoice->refresh();
        $this->assertSame(20000.0, $invoice->paid_amount);
        $this->assertSame(30000.0, $invoice->balance());
        $this->assertSame('partial', $invoice->payment_status);

        $this->pay($invoice, 30000, 'payment', ['method' => 'bank_transfer', 'note' => 'Second half'])->assertSessionHasNoErrors();
        $invoice->refresh();
        $this->assertSame(50000.0, $invoice->paid_amount);
        $this->assertSame(0.0, $invoice->balance());
        $this->assertSame('paid', $invoice->payment_status);

        $this->assertSame(2, $invoice->payments()->count());
        $this->assertSame($this->admin->id, $invoice->payments()->first()->created_by);

        $this->actingAs($this->admin)
            ->get(route('admin.manual-invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Second half')
            ->assertSee('Balance due');
    }

    public function test_a_payment_cannot_exceed_the_balance_nor_a_refund_what_was_paid(): void
    {
        $invoice = $this->finalized();

        $this->pay($invoice, 50001)->assertSessionHasErrors('amount');
        $this->pay($invoice, 1, 'refund')->assertSessionHasErrors('amount');
        $this->pay($invoice, 0)->assertSessionHasErrors('amount');
        $this->pay($invoice, -5)->assertSessionHasErrors('amount');
        $this->pay($invoice, 1000, 'payment', ['paid_on' => now()->addDay()->toDateString()])->assertSessionHasErrors('paid_on');
        $this->pay($invoice, 1000, 'payment', ['method' => 'crypto'])->assertSessionHasErrors('method');

        $this->assertSame(0.0, $invoice->fresh()->paid_amount);
        $this->assertSame(0, $invoice->payments()->count());

        $this->pay($invoice, 30000)->assertSessionHasNoErrors();
        $this->pay($invoice, 30000)->assertSessionHasErrors('amount');
        $this->pay($invoice, 30001, 'refund')->assertSessionHasErrors('amount');
        $this->assertSame(30000.0, $invoice->fresh()->paid_amount);
    }

    public function test_a_refund_is_kept_as_its_own_line_and_lowers_what_was_paid(): void
    {
        $invoice = $this->finalized();
        $this->pay($invoice, 50000);
        $this->pay($invoice, 20000, 'refund', ['note' => 'Returned one pad'])->assertSessionHasNoErrors();

        $invoice->refresh();
        $this->assertSame(30000.0, $invoice->paid_amount);
        $this->assertSame('partial', $invoice->payment_status);
        $this->assertSame([50000.0, -20000.0], $invoice->payments()->pluck('amount')->map(fn ($a) => (float) $a)->all());
    }

    public function test_a_draft_takes_no_payments(): void
    {
        $invoice = $this->draft($this->customer(), $this->product());

        $this->pay($invoice, 1000)->assertSessionHasErrors('amount');
        $this->assertSame(0, $invoice->payments()->count());
    }

    public function test_the_printed_invoice_shows_what_was_paid_and_what_is_left(): void
    {
        $invoice = $this->finalized();
        $this->pay($invoice, 20000);
        $invoice = $invoice->fresh('items');

        $html = view('admin.manual-invoices.pdf', [
            'invoice' => $invoice, 'currency' => 'IQD', 'logoPath' => null, 'locale' => 'en', 'isRtl' => false,
        ])->render();

        $this->assertStringContainsString('Balance Due', $html);
        $this->assertStringContainsString('20,000 IQD', $html);
        $this->assertStringContainsString('30,000 IQD', $html);
        $this->assertStringContainsString('Partially paid', $html);
    }

    // ── Voiding ────────────────────────────────────────────────────

    public function test_voiding_returns_the_stock_once_and_keeps_the_invoice(): void
    {
        $invoice = $this->finalized(4);
        $product = Product::query()->where('sku', 'BRK-1001')->firstOrFail();
        $this->assertSame(6, (int) $product->fresh()->stock_quantity);

        for ($press = 0; $press < 3; $press++) {
            $this->actingAs($this->admin)
                ->post(route('admin.manual-invoices.void', $invoice), ['void_reason' => 'Customer cancelled'])
                ->assertRedirect(route('admin.manual-invoices.show', $invoice));
        }

        $invoice->refresh();
        $this->assertTrue($invoice->isVoid());
        $this->assertSame('Customer cancelled', $invoice->void_reason);
        $this->assertSame($this->admin->id, $invoice->voided_by);
        $this->assertSame(10, (int) $product->fresh()->stock_quantity);

        // One movement out when it was finalized, one back in now, no more.
        $movements = InventoryMovement::query()->where('reference', $invoice->number)->orderBy('id')->get();
        $this->assertSame([InventoryMovement::TYPE_OUT, InventoryMovement::TYPE_IN], $movements->pluck('type')->all());
        $this->assertSame([4, 4], $movements->pluck('quantity')->map(fn ($q) => (int) $q)->all());

        // Kept, with everything it said, and no longer editable or finalizable.
        $this->assertSame(100000.0, $invoice->total);
        $this->assertCount(1, $invoice->items);
        $this->actingAs($this->admin)->post(route('admin.manual-invoices.finalize', $invoice));
        $this->assertTrue($invoice->fresh()->isVoid());
        $this->assertSame(10, (int) $product->fresh()->stock_quantity);
        $this->actingAs($this->admin)->delete(route('admin.manual-invoices.destroy', $invoice))->assertSessionHasErrors('invoice');
        $this->assertDatabaseHas('manual_invoices', ['id' => $invoice->id]);
    }

    public function test_an_invoice_with_money_on_it_must_be_refunded_before_it_is_voided(): void
    {
        $invoice = $this->finalized();
        $this->pay($invoice, 50000);

        $this->actingAs($this->admin)
            ->post(route('admin.manual-invoices.void', $invoice), ['void_reason' => 'Returned'])
            ->assertSessionHasErrors('void_reason');
        $this->assertTrue($invoice->fresh()->isFinalized());
        $this->assertSame(8, (int) Product::query()->where('sku', 'BRK-1001')->value('stock_quantity'));

        $this->pay($invoice, 50000, 'refund')->assertSessionHasNoErrors();
        $this->actingAs($this->admin)
            ->post(route('admin.manual-invoices.void', $invoice), ['void_reason' => 'Returned'])
            ->assertSessionHasNoErrors();

        $this->assertTrue($invoice->fresh()->isVoid());
        $this->assertSame(10, (int) Product::query()->where('sku', 'BRK-1001')->value('stock_quantity'));
        // Both the payment and the refund are still on file.
        $this->assertSame(2, $invoice->payments()->count());
    }

    public function test_voiding_needs_a_reason_and_only_applies_to_finalized_invoices(): void
    {
        $draft = $this->draft($this->customer(), $this->product());

        $this->actingAs($this->admin)
            ->post(route('admin.manual-invoices.void', $draft), ['void_reason' => 'Mistake'])
            ->assertSessionHasErrors('void_reason');
        $this->assertTrue($draft->fresh()->isDraft());

        $this->actingAs($this->admin)->post(route('admin.manual-invoices.finalize', $draft));
        $this->actingAs($this->admin)
            ->post(route('admin.manual-invoices.void', $draft), ['void_reason' => ''])
            ->assertSessionHasErrors('void_reason');
        $this->assertTrue($draft->fresh()->isFinalized());
    }

    public function test_voiding_withdraws_the_share_link_and_takes_no_more_payments(): void
    {
        $invoice = $this->finalized();
        $this->actingAs($this->admin)->post(route('admin.manual-invoices.share', $invoice));
        $url = $invoice->fresh()->shareUrl();

        $this->actingAs($this->admin)->post(route('admin.manual-invoices.void', $invoice), ['void_reason' => 'Entered twice']);

        $this->assertNull($invoice->fresh()->share_token_hash);
        $this->actingAs($this->admin)->post(route('admin.manual-invoices.share', $invoice))->assertSessionHasErrors('share');
        $this->pay($invoice, 1000)->assertSessionHasErrors('amount');

        auth()->logout();
        $this->get($url)->assertNotFound();
        $this->get($url.'/pdf')->assertNotFound();
    }

    public function test_sales_figures_count_money_received_and_leave_out_drafts_and_void_invoices(): void
    {
        $product = $this->product(['stock_quantity' => 100]);
        $customer = $this->customer();

        $this->draft($customer, $product);                                   // draft: 50,000, not a sale
        $partly = $this->draft($customer, $product);                         // 50,000, 20,000 paid
        $voided = $this->draft($customer, $product);                         // 50,000, voided
        foreach ([$partly, $voided] as $invoice) {
            $this->actingAs($this->admin)->post(route('admin.manual-invoices.finalize', $invoice));
        }
        $this->pay($partly->fresh(), 20000);
        $this->actingAs($this->admin)->post(route('admin.manual-invoices.void', $voided), ['void_reason' => 'Cancelled']);
        $partly->update(['invoice_date' => now()->toDateString()]);

        $this->actingAs($this->admin)
            ->get(route('admin.manual-invoices.index'))
            ->assertOk()
            ->assertSeeInOrder(['Finalized sales', '50,000 IQD', 'Paid', '20,000 IQD', 'Outstanding', '30,000 IQD']);

        $this->actingAs($this->admin)
            ->get(route('admin.manual-invoices.index', ['status' => 'void']))
            ->assertSee($voided->number)
            ->assertDontSee($partly->number);

        // The revenue page breaks the same sales out, and counts what was
        // invoiced in its revenue.
        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN, 'email_verified_at' => now()]);
        $this->actingAs($superAdmin)
            ->get(route('admin.revenue.index'))
            ->assertOk()
            ->assertSeeInOrder([
                'included in the revenue above',
                'Invoiced in range', '50,000', '1 INVOICES',
                'Collected in range', '20,000',
                'Outstanding', '30,000',
            ]);

        // No site order exists, so every dinar of revenue here is manual:
        // the finalized invoice counts, the draft and the void one do not.
        $this->assertSame(0, Order::query()->count());
        $this->assertSame(50000.0, ManualInvoice::invoicedBetween());
    }

    public function test_payments_and_voiding_are_closed_to_staff_without_order_access(): void
    {
        $invoice = $this->finalized();
        $outsider = User::factory()->create(['role' => User::ROLE_PRODUCT_MANAGER, 'email_verified_at' => now()]);

        $this->assertContains($this->actingAs($outsider)->post(route('admin.manual-invoices.payments.store', $invoice), [
            'kind' => 'payment', 'amount' => 1000, 'paid_on' => now()->toDateString(),
        ])->status(), [302, 403]);
        $this->assertContains($this->actingAs($outsider)->post(route('admin.manual-invoices.void', $invoice), [
            'void_reason' => 'No',
        ])->status(), [302, 403]);

        $this->assertSame(0.0, $invoice->fresh()->paid_amount);
        $this->assertTrue($invoice->fresh()->isFinalized());
    }

    // ── Listing ────────────────────────────────────────────────────

    public function test_the_list_is_searchable_by_customer_phone_number_date_and_payment(): void
    {
        $product = $this->product(['stock_quantity' => 100]);
        $first = $this->draft($this->customer(), $product);
        $second = $this->draft($this->customer(['name' => 'Soran Parts', 'phone' => '+9647501112233']), $product);
        $second->update(['payment_status' => 'paid', 'paid_amount' => $second->total, 'invoice_date' => '2026-09-01']);

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
            // The line is named in the document's language, not the one the
            // admin's screen was in when the product was picked.
            $this->assertStringContainsString(e($invoice->items->first()->descriptionFor($locale)), $html);
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

    public function test_a_share_link_is_never_written_into_the_traffic_log(): void
    {
        $invoice = $this->draft($this->customer(), $this->product());
        $this->actingAs($this->admin)->post(route('admin.manual-invoices.finalize', $invoice));
        $this->actingAs($this->admin)->post(route('admin.manual-invoices.share', $invoice));
        $url = $invoice->fresh()->shareUrl();
        auth()->logout();

        $browser = ['HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/126.0 Safari/537.36'];

        // An ordinary page is recorded, so the check below is not passing
        // merely because nothing is being recorded at all.
        $this->get(route('legal.contact'), $browser)->assertOk();
        $this->assertTrue(DB::table('analytics_events')->where('url', 'like', '%/contact%')->exists());

        $this->get($url, $browser)->assertOk();
        $this->get($url.'/pdf', $browser)->assertOk();

        $this->assertFalse(
            DB::table('analytics_events')->where('url', 'like', '%/i/%')->exists(),
            'The share token was copied into analytics_events.'
        );
    }

    public function test_a_total_too_large_for_the_ledger_is_refused_not_clipped(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.manual-invoices.store'), $this->payload($this->customer(), [
                ['description' => 'Absurd', 'quantity' => 100000, 'unit_price' => 9999999999],
            ]))
            ->assertSessionHasErrors('items');

        $this->assertSame(0, ManualInvoice::query()->count());
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
