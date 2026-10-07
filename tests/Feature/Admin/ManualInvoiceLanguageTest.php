<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Customer;
use App\Models\ManualInvoice;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * An invoice printed in Kurdish or Arabic names its parts in that language,
 * not in whichever language the admin's screen was in when it was written.
 */
class ManualInvoiceLanguageTest extends TestCase
{
    use RefreshDatabase;

    private const EN = 'Front Brake Pad';

    private const AR = 'فحمات فرامل أمامية';

    private const KU = 'پادی برێکی پێشەوە';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);
    }

    private function product(): Product
    {
        return Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
            'name_en' => self::EN,
            'name_ar' => self::AR,
            'name_ku' => self::KU,
            'sku' => 'BRK-1001',
            'price' => 25000,
            'stock_quantity' => 10,
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function draft(array $items): ManualInvoice
    {
        $customer = Customer::query()->create([
            'name' => 'Karwan Garage',
            'phone' => '+9647701234567',
            'city' => 'Erbil',
            'address' => '60 Meter Street',
        ]);

        $this->actingAs($this->admin)->post(route('admin.manual-invoices.store'), [
            'customer_id' => $customer->id,
            'invoice_date' => '2026-10-08',
            'action' => 'draft',
            'items' => $items,
        ])->assertSessionHasNoErrors();

        return ManualInvoice::query()->latest('id')->firstOrFail();
    }

    private function shareUrl(ManualInvoice $invoice): string
    {
        $this->actingAs($this->admin)->post(route('admin.manual-invoices.finalize', $invoice));
        $this->actingAs($this->admin)->post(route('admin.manual-invoices.share', $invoice));

        return (string) $invoice->fresh()->shareUrl();
    }

    public function test_a_catalogue_line_is_named_in_the_language_of_the_document(): void
    {
        $product = $this->product();

        // Picked while the admin's screen was in English.
        $invoice = $this->draft([
            ['product_id' => $product->id, 'description' => self::EN, 'quantity' => 1, 'unit_price' => 25000],
        ]);

        $line = $invoice->items()->firstOrFail();

        $this->assertSame(self::EN, $line->descriptionFor('en'));
        $this->assertSame(self::AR, $line->descriptionFor('ar'));
        $this->assertSame(self::KU, $line->descriptionFor('ku'));

        $url = $this->shareUrl($invoice);

        $this->get($url.'?lang=ku')->assertOk()->assertSee(self::KU)->assertDontSee(self::EN);
        $this->get($url.'?lang=ar')->assertOk()->assertSee(self::AR)->assertDontSee(self::EN);
        $this->get($url.'?lang=en')->assertOk()->assertSee(self::EN)->assertDontSee(self::KU);
    }

    public function test_the_pdf_is_built_in_each_language_with_the_translated_line(): void
    {
        $product = $this->product();
        $invoice = $this->draft([
            ['product_id' => $product->id, 'description' => self::EN, 'quantity' => 1, 'unit_price' => 25000],
        ]);

        foreach (['en', 'ar', 'ku'] as $locale) {
            $html = view('admin.manual-invoices.pdf', [
                'invoice' => $invoice->load('items'),
                'currency' => 'IQD',
                'logoPath' => null,
                'locale' => $locale,
                'isRtl' => $locale !== 'en',
            ])->render();

            $this->assertStringContainsString(['en' => self::EN, 'ar' => self::AR, 'ku' => self::KU][$locale], $html);

            $this->actingAs($this->admin)
                ->get(route('admin.manual-invoices.pdf', ['manual_invoice' => $invoice, 'doc_lang' => $locale]))
                ->assertOk()
                ->assertHeader('content-type', 'application/pdf');
        }
    }

    /**
     * The picture is drawn in the browser, so what can be checked here is the
     * page it is drawn from: the sheet, the button, the right language, and
     * that only staff reach the preview of an unshared invoice.
     */
    public function test_staff_can_open_the_invoice_as_a_page_to_save_as_an_image(): void
    {
        $product = $this->product();
        $invoice = $this->draft([
            ['product_id' => $product->id, 'description' => self::EN, 'quantity' => 1, 'unit_price' => 25000],
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.manual-invoices.image', ['manual_invoice' => $invoice, 'doc_lang' => 'ku', 'auto' => 1]))
            ->assertOk()
            ->assertSee('data-invoice-sheet', false)
            ->assertSee('data-invoice-image', false)
            ->assertSee('data-auto="1"', false)
            ->assertSee('dir="rtl"', false)
            ->assertSee(self::KU)
            ->assertDontSee(self::EN)
            // Staff links, not the customer's token links.
            ->assertSee(route('admin.manual-invoices.pdf', ['manual_invoice' => $invoice, 'doc_lang' => 'ku']), false);

        $this->actingAs($this->admin)
            ->get(route('admin.manual-invoices.show', $invoice))
            ->assertOk()
            ->assertSee(e(route('admin.manual-invoices.image', ['manual_invoice' => $invoice, 'doc_lang' => 'ku', 'auto' => 1])), false);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.manual-invoices.image', $invoice))
            ->assertForbidden();
    }

    public function test_the_customers_shared_page_offers_the_image_too(): void
    {
        $product = $this->product();
        $invoice = $this->draft([
            ['product_id' => $product->id, 'description' => self::EN, 'quantity' => 1, 'unit_price' => 25000],
        ]);

        $this->get($this->shareUrl($invoice).'?lang=ku')
            ->assertOk()
            ->assertSee('data-invoice-image', false)
            ->assertDontSee('data-auto', false)
            ->assertDontSee('/admin/', false);
    }

    public function test_a_line_picked_in_kurdish_still_prints_in_english(): void
    {
        $product = $this->product();
        $invoice = $this->draft([
            ['product_id' => $product->id, 'description' => self::KU, 'quantity' => 1, 'unit_price' => 25000],
        ]);

        $this->assertSame(self::EN, $invoice->items()->firstOrFail()->descriptionFor('en'));
    }

    public function test_a_description_written_by_hand_reads_as_written_in_every_language(): void
    {
        $product = $this->product();
        $invoice = $this->draft([
            ['product_id' => $product->id, 'description' => 'Brake pads, fitted on site', 'quantity' => 1, 'unit_price' => 30000],
            ['description' => 'Workshop labour', 'quantity' => 1, 'unit_price' => 10000],
        ]);

        foreach ($invoice->items as $line) {
            $this->assertNull($line->description_translations);
            $this->assertSame($line->description, $line->descriptionFor('ku'));
        }
    }

    public function test_a_finalized_invoice_keeps_the_names_it_was_issued_with(): void
    {
        $product = $this->product();
        $invoice = $this->draft([
            ['product_id' => $product->id, 'description' => self::EN, 'quantity' => 1, 'unit_price' => 25000],
        ]);
        $this->actingAs($this->admin)->post(route('admin.manual-invoices.finalize', $invoice));

        $product->update(['name_ku' => 'ناوێکی نوێ', 'name_en' => 'Renamed Pad']);

        $line = $invoice->items()->firstOrFail();
        $this->assertSame(self::KU, $line->descriptionFor('ku'));
        $this->assertSame(self::EN, $line->descriptionFor('en'));
    }

    public function test_lines_saved_before_this_are_filled_in_from_the_catalogue(): void
    {
        $product = $this->product();
        $invoice = $this->draft([
            ['product_id' => $product->id, 'description' => self::EN, 'quantity' => 1, 'unit_price' => 25000],
            ['product_id' => $product->id, 'description' => 'Reworded by hand', 'quantity' => 1, 'unit_price' => 25000],
        ]);

        // As those lines looked before the column existed.
        DB::table('manual_invoice_items')->update(['description_translations' => null]);

        (require database_path('migrations/2026_10_08_100000_add_description_translations_to_manual_invoice_items.php'))->up();

        [$picked, $reworded] = $invoice->items()->orderBy('sort_order')->get()->all();

        $this->assertSame(self::KU, $picked->descriptionFor('ku'));
        $this->assertSame('Reworded by hand', $reworded->descriptionFor('ku'));
    }
}
