# Mobile Order Invoice Download Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add `GET /api/mobile/orders/{order}/invoice` that returns a PDF invoice byte-identical to the existing web `account.orders.invoice` endpoint, refactoring the shared PDF rendering into a single service.

**Architecture:** Extract the PDF-building logic out of `AccountOrdersController::invoice` into a new `App\Services\InvoiceRenderer` service. Move the private `invoiceLogoPath()` helper into `App\Support\Branding` where the other logo helpers live. Both the web controller and a new `MobileController::orderInvoice` method delegate to the renderer. Web behavior must remain unchanged. Spec: `docs/superpowers/specs/2026-05-30-mobile-order-invoice-design.md`.

**Tech Stack:** Laravel 11, PHP 8.2, `barryvdh/laravel-dompdf`, Laravel Sanctum (existing `auth:sanctum` group), PHPUnit feature tests with `RefreshDatabase`.

---

## File Structure

- **Create**: `app/Services/InvoiceRenderer.php` — single service with `resolveLocale()` and `render()`. Pure delegation target; no HTTP, no auth checks.
- **Create**: `tests/Feature/MobileInvoiceTest.php` — feature tests for the new mobile endpoint.
- **Create**: `tests/Unit/InvoiceRendererLocaleTest.php` — unit tests for `resolveLocale()` precedence.
- **Modify**: `app/Support/Branding.php` — add public static `invoiceLogoPath()` method (moved from `AccountOrdersController`).
- **Modify**: `app/Http/Controllers/Account/AccountOrdersController.php` — refactor `invoice()` to delegate to the service; delete private `invoiceLocale()` and `invoiceLogoPath()` methods.
- **Modify**: `app/Http/Controllers/Api/MobileController.php` — add `orderInvoice()` method.
- **Modify**: `routes/api.php` — register `GET /orders/{order}/invoice` inside the existing `auth:sanctum` group.
- **Modify**: `docs/api-parity.md` — promote row #3 from "Open gaps" to "What's already at parity" and update the suggested order.

---

## Task 1: Move `invoiceLogoPath` helper into `Branding`

The existing private `AccountOrdersController::invoiceLogoPath()` is a static-style filesystem path resolver. It belongs alongside the other helpers in `App\Support\Branding`. Moving it now (before the renderer is built) keeps each later task focused.

**Files:**
- Modify: `app/Support/Branding.php`

- [ ] **Step 1: Add `invoiceLogoPath()` to `Branding`**

Open `app/Support/Branding.php`. Above the closing `}` of the class, add:

```php
    /**
     * Resolve an absolute filesystem path to the site logo for embedding in PDFs.
     * DomPDF needs a path, not a URL. Returns null if no safe logo can be found.
     */
    public static function invoiceLogoPath(): ?string
    {
        $logoValue = (string) \App\Models\Setting::getValue('site_logo', '');
        if ($logoValue === '') {
            return null;
        }

        $storagePath = self::storagePathFromValue($logoValue);
        if ($storagePath && self::isSafeLogoPath($storagePath)) {
            $publicStoragePath = public_path('storage/' . ltrim($storagePath, '/'));
            if (is_file($publicStoragePath)) {
                return str_replace('\\', '/', $publicStoragePath);
            }
        }

        $normalized = str_replace('\\', '/', trim($logoValue));
        if (
            self::isSafeLogoPath($normalized)
            && Str::startsWith($normalized, ['assets/', 'images/', 'storage/', '/assets/', '/images/', '/storage/'])
        ) {
            $publicPath = public_path(ltrim($normalized, '/'));
            if (is_file($publicPath)) {
                return str_replace('\\', '/', $publicPath);
            }
        }

        return null;
    }
```

The body is byte-identical to the private method we'll delete later from `AccountOrdersController`. The only changes: `Setting::getValue(...)` is fully qualified (since `Branding` doesn't import it), and `Branding::isSafeLogoPath()` is now `self::isSafeLogoPath()`.

- [ ] **Step 2: Verify the file parses**

Run: `php -l app/Support/Branding.php`
Expected: `No syntax errors detected in app/Support/Branding.php`

- [ ] **Step 3: Commit**

```bash
git add app/Support/Branding.php
git commit -m "refactor(branding): add static invoiceLogoPath() helper

Moved from AccountOrdersController in preparation for sharing the
invoice renderer between the web and mobile order endpoints."
```

---

## Task 2: Create `InvoiceRenderer` service with `resolveLocale()` (TDD)

`resolveLocale()` has clear precedence logic with several branches — a perfect unit test target. Build it test-first so the precedence is locked in before we wire it up.

**Files:**
- Create: `app/Services/InvoiceRenderer.php`
- Create: `tests/Unit/InvoiceRendererLocaleTest.php`

- [ ] **Step 1: Write the failing unit test file**

Create `tests/Unit/InvoiceRendererLocaleTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\User;
use App\Services\InvoiceRenderer;
use Tests\TestCase;

class InvoiceRendererLocaleTest extends TestCase
{
    private InvoiceRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = new InvoiceRenderer();
    }

    public function test_explicit_lang_wins_over_everything(): void
    {
        $user = new User(['locale_preference' => 'en']);
        $order = new Order();
        $order->setRelation('user', new User(['locale_preference' => 'en']));
        app()->setLocale('ku');

        $this->assertSame('ar', $this->renderer->resolveLocale('ar', $order, $user));
    }

    public function test_unknown_explicit_lang_is_ignored(): void
    {
        $user = new User(['locale_preference' => 'ar']);
        $order = new Order();
        $order->setRelation('user', null);

        $this->assertSame('ar', $this->renderer->resolveLocale('tr', $order, $user));
    }

    public function test_order_owner_preference_beats_app_locale(): void
    {
        $user = new User(['locale_preference' => null]);
        $order = new Order();
        $order->setRelation('user', new User(['locale_preference' => 'ar']));
        app()->setLocale('ku');

        $this->assertSame('ar', $this->renderer->resolveLocale(null, $order, $user));
    }

    public function test_authed_user_preference_when_order_owner_has_none(): void
    {
        $user = new User(['locale_preference' => 'ku']);
        $order = new Order();
        $order->setRelation('user', new User(['locale_preference' => null]));
        app()->setLocale('en');

        $this->assertSame('ku', $this->renderer->resolveLocale(null, $order, $user));
    }

    public function test_app_locale_when_no_user_preferences(): void
    {
        $user = new User(['locale_preference' => null]);
        $order = new Order();
        $order->setRelation('user', new User(['locale_preference' => null]));
        app()->setLocale('ku');

        $this->assertSame('ku', $this->renderer->resolveLocale(null, $order, $user));
    }

    public function test_falls_back_to_english_when_nothing_set(): void
    {
        $user = new User(['locale_preference' => null]);
        $order = new Order();
        $order->setRelation('user', new User(['locale_preference' => null]));
        app()->setLocale('fr');

        $this->assertSame('en', $this->renderer->resolveLocale(null, $order, $user));
    }

    public function test_null_user_is_tolerated(): void
    {
        $order = new Order();
        $order->setRelation('user', new User(['locale_preference' => 'ar']));
        app()->setLocale('en');

        $this->assertSame('ar', $this->renderer->resolveLocale(null, $order, null));
    }
}
```

- [ ] **Step 2: Run the test to confirm it fails (class doesn't exist)**

Run: `php artisan test --filter=InvoiceRendererLocaleTest`
Expected: FAIL — `Class "App\Services\InvoiceRenderer" not found`

- [ ] **Step 3: Create the `InvoiceRenderer` skeleton with `resolveLocale()`**

Create `app/Services/InvoiceRenderer.php`:

```php
<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;

final class InvoiceRenderer
{
    private const ALLOWED_LOCALES = ['en', 'ar', 'ku'];

    /**
     * Resolve the locale to use for an invoice PDF.
     *
     * Precedence (first hit wins):
     *   1. $explicit (?lang= query value), if in the allow-list
     *   2. Order owner's locale_preference
     *   3. Authed user's locale_preference (covers admin/dealer download of someone else's order)
     *   4. app()->getLocale() (Accept-Language middleware on mobile, session on web)
     *   5. 'en'
     */
    public function resolveLocale(?string $explicit, Order $order, ?User $user): string
    {
        $candidates = [
            $explicit,
            $order->user?->locale_preference,
            $user?->locale_preference,
            app()->getLocale(),
        ];

        foreach ($candidates as $candidate) {
            $normalized = strtolower((string) $candidate);
            if (in_array($normalized, self::ALLOWED_LOCALES, true)) {
                return $normalized;
            }
        }

        return 'en';
    }
}
```

- [ ] **Step 4: Run the test to confirm it passes**

Run: `php artisan test --filter=InvoiceRendererLocaleTest`
Expected: PASS — 7 tests, all green.

- [ ] **Step 5: Commit**

```bash
git add app/Services/InvoiceRenderer.php tests/Unit/InvoiceRendererLocaleTest.php
git commit -m "feat(invoice): InvoiceRenderer::resolveLocale() with precedence tests

Service skeleton for the shared invoice PDF generator (U-9 #3).
Locale precedence mirrors the existing web invoiceLocale() exactly:
explicit ?lang > order owner pref > authed user pref > app locale > en."
```

---

## Task 3: Add `render()` method to `InvoiceRenderer`

`render()` performs the existing PDF assembly — eager-load, totals math, locale swap with try/finally, `Pdf::loadView()` call. It returns the `PDF` instance unfiled; the controllers decide whether to `download()` or `stream()`.

**Files:**
- Modify: `app/Services/InvoiceRenderer.php`

- [ ] **Step 1: Add the `render()` method**

Open `app/Services/InvoiceRenderer.php`. Add this method below `resolveLocale()`:

```php
    /**
     * Build the invoice PDF for an order in the given locale.
     * Returns a DomPDF instance — callers choose download() or stream().
     *
     * The provided locale must already be normalized (use resolveLocale()).
     * Temporarily switches app locale for view rendering and restores it after.
     */
    public function render(Order $order, string $locale): \Barryvdh\DomPDF\PDF
    {
        $order->loadMissing([
            'user:id,name,email,phone,locale_preference',
            'items' => fn ($query) => $query
                ->select(['id', 'order_id', 'product_id', 'quantity', 'unit_price', 'subtotal'])
                ->with(['product:id,name_en,name_ar,name_ku,sku,brand']),
        ]);

        $subtotal = (float) ($order->subtotal_amount ?: $order->items->sum('subtotal'));
        $shipping = (float) $order->shipping_fee;
        $discount = (float) $order->discount_amount;
        $grandTotal = (float) ($order->grand_total ?: ($subtotal + $shipping - $discount));
        $year = optional($order->created_at)->format('Y') ?: now()->format('Y');

        $previousLocale = app()->getLocale();
        app()->setLocale($locale);

        try {
            return \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.orders.invoice', [
                'order' => $order,
                'invoiceNumber' => 'INV-' . $year . '-' . str_pad((string) $order->id, 5, '0', STR_PAD_LEFT),
                'currency' => 'IQD',
                'logoPath' => \App\Support\Branding::invoiceLogoPath(),
                'subtotal' => $subtotal,
                'shipping' => $shipping,
                'discount' => $discount,
                'grandTotal' => $grandTotal,
                'locale' => $locale,
                'isRtl' => in_array($locale, ['ar', 'ku'], true),
            ])->setPaper('a4');
        } finally {
            app()->setLocale($previousLocale);
        }
    }
```

This is the same logic that was in `AccountOrdersController::invoice` lines 79–103, with the only change being `Branding::invoiceLogoPath()` instead of `$this->invoiceLogoPath()`. Eager-loading uses `loadMissing` so the controller can pre-load if it wants (the web controller will pass an already-loaded order).

- [ ] **Step 2: Verify the file parses**

Run: `php -l app/Services/InvoiceRenderer.php`
Expected: `No syntax errors detected in app/Services/InvoiceRenderer.php`

- [ ] **Step 3: Commit**

```bash
git add app/Services/InvoiceRenderer.php
git commit -m "feat(invoice): InvoiceRenderer::render() builds the PDF

Lifts the DomPDF assembly out of AccountOrdersController so the
mobile endpoint can call the same code path."
```

---

## Task 4: Refactor `AccountOrdersController::invoice` to use the service

This task does not change web behavior. After the refactor the web invoice endpoint must produce a byte-identical PDF for any given order + locale.

**Files:**
- Modify: `app/Http/Controllers/Account/AccountOrdersController.php`

- [ ] **Step 1: Replace the `invoice()` method**

Open `app/Http/Controllers/Account/AccountOrdersController.php`.

Locate the `use Barryvdh\DomPDF\Facade\Pdf;` import near line 12 and delete it (it's only used inside `invoice()` and the service now handles PDF creation).

Locate the `use App\Support\Branding;` import. Keep it — we still need it (and the service uses it via FQN anyway). If it isn't there yet because we never imported it directly in this file, leave it alone; the controller doesn't reference `Branding` after this refactor.

Locate `use App\Models\Setting;` — keep it only if used elsewhere in the controller. Run a quick `grep -n "Setting" app/Http/Controllers/Account/AccountOrdersController.php` first. If `Setting` appears only inside `invoiceLogoPath()`, remove the import along with that method.

Add this import near the other `App\Services\...` or `App\Support\...` imports:

```php
use App\Services\InvoiceRenderer;
```

Replace the entire `invoice()` method (lines 66–106 in the current file) with:

```php
    public function invoice(Request $request, Order $order, InvoiceRenderer $renderer): Response
    {
        $order = auth()->user()
            ->orders()
            ->whereKey($order->id)
            ->with([
                'user:id,name,email,phone,locale_preference',
                'items' => fn ($query) => $query
                    ->select(['id', 'order_id', 'product_id', 'quantity', 'unit_price', 'subtotal'])
                    ->with(['product:id,name_en,name_ar,name_ku,sku,brand']),
            ])
            ->firstOrFail();

        $explicit = (string) $request->query('lang', $request->query('locale', ''));
        $locale = $renderer->resolveLocale($explicit !== '' ? $explicit : null, $order, auth()->user());

        return $renderer->render($order, $locale)
            ->download('invoice-' . $order->id . '-' . $locale . '.pdf');
    }
```

Note: we keep the eager-load on the controller side to preserve the existing `firstOrFail()` ownership check. `$renderer->render()` calls `loadMissing()`, which is a no-op for already-loaded relations.

- [ ] **Step 2: Delete the obsolete private methods**

In the same file, delete the entire `invoiceLocale()` method (currently lines 108–118) and the entire `invoiceLogoPath()` method (currently lines 246–273). Also remove the `use Illuminate\Support\Str;` import if `Str` is no longer referenced (`grep -n "Str::" app/Http/Controllers/Account/AccountOrdersController.php`).

- [ ] **Step 3: Verify the file parses and no obsolete imports remain**

Run: `php -l app/Http/Controllers/Account/AccountOrdersController.php`
Expected: `No syntax errors detected`

Run: `grep -nE "invoiceLocale|invoiceLogoPath|Barryvdh|Branding|Setting" app/Http/Controllers/Account/AccountOrdersController.php`
Expected: no matches (or only matches inside comments — there shouldn't be any).

- [ ] **Step 4: Smoke-check the web invoice route**

Run: `php artisan route:list --path=orders | grep invoice`
Expected: a `GET account/orders/{order}/invoice` route is still listed, pointing at `AccountOrdersController@invoice`.

- [ ] **Step 5: Run the full existing test suite — nothing should break**

Run: `php artisan test`
Expected: all previously-passing tests still pass.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Account/AccountOrdersController.php
git commit -m "refactor(account): invoice() delegates to InvoiceRenderer

Web invoice endpoint now uses the shared service. Output is
byte-identical for any given order + locale. invoiceLocale() and
invoiceLogoPath() private helpers removed; the latter was moved to
App\\Support\\Branding in an earlier commit."
```

---

## Task 5: Add the mobile invoice route + controller method

**Files:**
- Modify: `app/Http/Controllers/Api/MobileController.php`
- Modify: `routes/api.php`

- [ ] **Step 1: Add the `InvoiceRenderer` import in `MobileController`**

Open `app/Http/Controllers/Api/MobileController.php`. Find the `use App\Services\CouponService;` import (around line 22) and add immediately after it:

```php
use App\Services\InvoiceRenderer;
```

- [ ] **Step 2: Add the `orderInvoice()` method**

Find the existing `order()` method (around line 851 — `public function order(Request $request, Order $order)`). Immediately after the closing `}` of that method, add:

```php
    public function orderInvoice(Request $request, Order $order, InvoiceRenderer $renderer)
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        $explicit = (string) $request->query('lang', '');
        $locale = $renderer->resolveLocale(
            $explicit !== '' ? $explicit : null,
            $order,
            $request->user(),
        );

        return $renderer->render($order, $locale)
            ->download('invoice-' . $order->id . '-' . $locale . '.pdf');
    }
```

- [ ] **Step 3: Register the route**

Open `routes/api.php`. Find line 82 (`Route::get('/orders/{order}', [MobileController::class, 'order']);`). Add a new line immediately after it:

```php
        Route::get('/orders/{order}/invoice', [MobileController::class, 'orderInvoice']);
```

(Matches the indentation of the surrounding lines inside the `auth:sanctum` group.)

- [ ] **Step 4: Verify the route is registered**

Run: `php artisan route:list --path=api/mobile/orders | grep invoice`
Expected: a row like `GET api/mobile/orders/{order}/invoice ... MobileController@orderInvoice`

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Api/MobileController.php routes/api.php
git commit -m "feat(api): GET /api/mobile/orders/{order}/invoice (U-9 #3)

Mobile parity endpoint for the web account invoice download. Reuses
the shared InvoiceRenderer; same PDF, same filename pattern, same
locale precedence. Auth via auth:sanctum + owner check."
```

---

## Task 6: Feature tests for the mobile invoice endpoint

**Files:**
- Create: `tests/Feature/MobileInvoiceTest.php`

- [ ] **Step 1: Create the test file with all nine cases**

Create `tests/Feature/MobileInvoiceTest.php`:

```php
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
        $user = User::factory()->create(['locale_preference' => null]);
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
        $user = User::factory()->create(['locale_preference' => null]);
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
        $user = User::factory()->create(['locale_preference' => null]);
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
```

The `makeOrderFor()` helper uses `forceCreate()` because `Order::$guarded` blocks mass-assignment for `grand_total`, `total_amount`, `status`, `payment_status`. It does not insert order items — the invoice Blade view tolerates an empty items collection (it's already exercised by the web flow for edge cases). If a test reveals the view requires items, expand the helper to insert one row into `order_items` with a minimal product fixture.

- [ ] **Step 2: Run the test file**

Run: `php artisan test --filter=MobileInvoiceTest`
Expected: All 9 tests pass.

- [ ] **Step 3: If the PDF render fails because items/products are required, add a minimal item**

If you see a render error mentioning `name_en` or `subtotal` on a null product, extend `makeOrderFor()` to also insert an item:

```php
        $order->items()->forceCreate([
            'product_id' => null,
            'quantity' => 1,
            'unit_price' => 25000,
            'subtotal' => 25000,
        ]);
        return $order->fresh();
```

Then re-run: `php artisan test --filter=MobileInvoiceTest` — Expected: all green. Skip this step if the previous run was already green.

- [ ] **Step 4: Run the entire test suite — full regression check**

Run: `php artisan test`
Expected: no previously-passing tests fail.

- [ ] **Step 5: Commit**

```bash
git add tests/Feature/MobileInvoiceTest.php
git commit -m "test(api): MobileInvoiceTest — 9 cases for invoice endpoint

Covers auth, owner-guard, 404, PDF Content-Type / Disposition,
and all four locale-precedence paths (?lang override, user pref
beats Accept-Language, Accept-Language fallback, en fallback,
unknown lang ignored)."
```

---

## Task 7: Update the parity audit doc

**Files:**
- Modify: `docs/api-parity.md`

- [ ] **Step 1: Move row #3 to "What's already at parity"**

Open `docs/api-parity.md`. Find the row for gap #3 in the High priority table (around line 46):

```markdown
| 3 | **Order invoice download** | `account.orders.invoice` | ❌ | Returns PDF/HTML invoice. Add `GET /orders/{x}/invoice` returning a download URL or signed link. |
```

Delete that row from the High-priority gaps table.

In the "What's already at parity ✅" table, add a new row right after the row for "Order cancellation / return request":

```markdown
| **Order invoice download (added this commit)** | `account.orders.invoice` | **`GET /orders/{order}/invoice`** |
```

- [ ] **Step 2: Update the "Suggested order" section**

Find the suggested-order list near the bottom (around line 70). Strike #3 like the closed items above it, and promote #1+#2 to "next". Replace the existing list with:

```markdown
1. ~~**#6 Settings sub-pages**~~ — **closed in 50662ac.**
2. ~~**#4 Legal pages content** + **#5 Contact form**~~ — **closed in 361254a.**
3. ~~**#3 Order invoice**~~ — **closed this commit.**
4. **#1 + #2 Buy-Now + Checkout review** — single PR; behavior-equivalent to the web flow.
5. **#8 Wishlist payload** — requires API version bump; do after #1-2 are settled.
6. **#7 Account activity**, **#9 Profile field expansion**, **#10 Account actions** — UX polish, schedule when product priorities allow.
```

- [ ] **Step 3: Update the status snapshot date at the top of the file**

Change line 3:

From:
```markdown
> Status snapshot as of 2026-05-30. Updated after every parity commit.
```

To (no change needed if you ship the same day; otherwise update to today's date). Leave as-is if today is 2026-05-30.

- [ ] **Step 4: Commit**

```bash
git add docs/api-parity.md
git commit -m "docs(api): mark mobile order invoice (#3) as closed

Promote row #3 from open gaps to parity. Suggested order now
points at #1+#2 (Buy-Now + Checkout review) as next priority."
```

---

## Self-Review Notes

- **Spec coverage**: every spec section maps to a task — Branding helper (Task 1), service skeleton + resolveLocale (Task 2), render method (Task 3), web refactor (Task 4), mobile endpoint (Task 5), feature tests (Task 6), audit doc update (Task 7).
- **Behavior preservation**: Task 4 keeps the web controller's eager-load + ownership check inline, then delegates. The web `?locale=` alias is preserved by `$request->query('lang', $request->query('locale', ''))` in the controller.
- **Type consistency**: `resolveLocale(?string, Order, ?User): string` and `render(Order, string): \Barryvdh\DomPDF\PDF` are used identically in both controllers.
- **Test data**: `makeOrderFor()` uses `forceCreate()` because Order's `$guarded` blocks `grand_total`/`status` mass-assignment; this mirrors how the production `OrderService` populates orders.
- **No new dependencies**: everything wires through existing classes (`Barryvdh\DomPDF\Facade\Pdf`, `App\Models\Setting`, `App\Support\Branding`).
