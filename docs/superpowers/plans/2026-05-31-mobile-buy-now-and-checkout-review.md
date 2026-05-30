# Mobile Buy-Now + Checkout Review Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add three new mobile API endpoints (`checkout/review`, `buy-now/preview`, `buy-now/place`) so mobile clients can server-side preview totals and place single-product orders, closing parity gaps #1 and #2.

**Architecture:** A new pure-function `App\Services\CheckoutTotals` service centralizes the subtotal/shipping/coupon/grand_total math so the three new mobile endpoints don't duplicate it. Existing `MobileController::checkout` is touched only to extract a 3-line address-resolution helper (behavior-neutral). The new buy-now place endpoint deliberately matches the existing simpler `MobileController::checkout` shape (no transaction, no inventory movement, no notification) — fixing that divergence is tracked as a separate audit gap. Spec: `docs/superpowers/specs/2026-05-31-mobile-buy-now-and-checkout-review-design.md`.

**Tech Stack:** Laravel 11, PHP 8.1, Laravel Sanctum (`auth:sanctum`), existing `App\Services\CouponService`, existing `App\Models\Setting`, PHPUnit feature tests with `RefreshDatabase`.

---

## File Structure

- **Create**: `app/Services/CheckoutTotals.php` — single pure method `compute(items, shipping_fee, ?couponPreview)`; no DB writes, no HTTP, no validation.
- **Create**: `tests/Unit/CheckoutTotalsTest.php` — 7 unit cases for the math.
- **Create**: `tests/Feature/MobileCheckoutReviewTest.php` — feature tests for `POST /api/mobile/checkout/review`.
- **Create**: `tests/Feature/MobileBuyNowTest.php` — feature tests for both buy-now endpoints (preview + place).
- **Modify**: `lang/en/errors.php`, `lang/ar/errors.php`, `lang/ku/errors.php` — add `product_unavailable` and `stock_insufficient` keys (3 files in lockstep).
- **Modify**: `app/Http/Controllers/Api/MobileController.php` — add `resolveOrderAddress()` private helper, refactor existing `checkout()` to use it (behavior-neutral), add three new methods (`checkoutReview`, `buyNowPreview`, `buyNowPlace`).
- **Modify**: `routes/api.php` — register three new routes inside the existing `auth:sanctum` group.
- **Modify**: `docs/api-parity.md` — promote rows #1 and #2 to "at parity", add new gap row for mobile cart-checkout divergence.

---

## Task 1: Add error translation keys

The new endpoints raise two new error messages — keep all three locale files in sync.

**Files:**
- Modify: `lang/en/errors.php`
- Modify: `lang/ar/errors.php`
- Modify: `lang/ku/errors.php`

- [ ] **Step 1: Add the keys to all three locale files**

In `lang/en/errors.php`, add these two entries inside the returned array (alongside the existing `cart_empty` etc.):

```php
    'product_unavailable' => 'This product is not available right now.',
    'stock_insufficient' => 'Insufficient stock for this product.',
```

In `lang/ar/errors.php` add (alongside the existing Arabic entries):

```php
    'product_unavailable' => 'هذا المنتج غير متاح حالياً.',
    'stock_insufficient' => 'لا يوجد مخزون كافٍ لهذا المنتج.',
```

In `lang/ku/errors.php` add (alongside the existing Kurdish entries):

```php
    'product_unavailable' => 'ئەم بەرهەمە لە ئێستادا بەردەست نییە.',
    'stock_insufficient' => 'کۆگای پێویست بۆ ئەم بەرهەمە نییە.',
```

- [ ] **Step 2: Verify all three files parse**

Run:
```
/c/laragon/bin/php/php-8.1.10-Win32-vs16-x64/php.exe -l lang/en/errors.php
/c/laragon/bin/php/php-8.1.10-Win32-vs16-x64/php.exe -l lang/ar/errors.php
/c/laragon/bin/php/php-8.1.10-Win32-vs16-x64/php.exe -l lang/ku/errors.php
```

Expected: `No syntax errors detected` for each.

- [ ] **Step 3: Commit**

```bash
git add lang/en/errors.php lang/ar/errors.php lang/ku/errors.php
git commit -m "i18n(errors): add product_unavailable + stock_insufficient

Translations for the upcoming mobile buy-now + checkout review
endpoints (U-9 #1 + #2)."
```

---

## Task 2: Build `CheckoutTotals` service with unit tests (TDD)

**Files:**
- Create: `app/Services/CheckoutTotals.php`
- Create: `tests/Unit/CheckoutTotalsTest.php`

- [ ] **Step 1: Write the failing unit tests**

Create `tests/Unit/CheckoutTotalsTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Services\CheckoutTotals;
use Tests\TestCase;

class CheckoutTotalsTest extends TestCase
{
    private CheckoutTotals $totals;

    protected function setUp(): void
    {
        parent::setUp();
        $this->totals = new CheckoutTotals();
    }

    public function test_empty_items_returns_zero_subtotal_with_shipping_only(): void
    {
        $result = $this->totals->compute([], 5000.0, null);

        $this->assertSame(0.0, $result['subtotal']);
        $this->assertSame(5000.0, $result['shipping_fee']);
        $this->assertSame(0.0, $result['discount_amount']);
        $this->assertSame(5000.0, $result['grand_total']);
    }

    public function test_items_only_no_coupon(): void
    {
        $result = $this->totals->compute(
            [['quantity' => 2, 'unit_price' => 12500]],
            5000.0,
            null,
        );

        $this->assertSame(25000.0, $result['subtotal']);
        $this->assertSame(0.0, $result['discount_amount']);
        $this->assertSame(30000.0, $result['grand_total']);
    }

    public function test_coupon_discount_applied(): void
    {
        $coupon = ['valid' => true, 'discount' => 2000.0, 'free_shipping' => false];
        $result = $this->totals->compute(
            [['quantity' => 1, 'unit_price' => 10000]],
            5000.0,
            $coupon,
        );

        $this->assertSame(2000.0, $result['discount_amount']);
        $this->assertSame(13000.0, $result['grand_total']);
    }

    public function test_coupon_free_shipping_zeroes_shipping_line(): void
    {
        $coupon = ['valid' => true, 'discount' => 0.0, 'free_shipping' => true];
        $result = $this->totals->compute(
            [['quantity' => 1, 'unit_price' => 10000]],
            5000.0,
            $coupon,
        );

        $this->assertSame(5000.0, $result['discount_amount']);
        $this->assertSame(10000.0, $result['grand_total']);
    }

    public function test_coupon_discount_plus_free_shipping_combine(): void
    {
        $coupon = ['valid' => true, 'discount' => 2000.0, 'free_shipping' => true];
        $result = $this->totals->compute(
            [['quantity' => 1, 'unit_price' => 10000]],
            5000.0,
            $coupon,
        );

        $this->assertSame(7000.0, $result['discount_amount']);
        $this->assertSame(8000.0, $result['grand_total']);
    }

    public function test_grand_total_clamped_to_zero_when_discount_exceeds_total(): void
    {
        $coupon = ['valid' => true, 'discount' => 100000.0, 'free_shipping' => false];
        $result = $this->totals->compute(
            [['quantity' => 1, 'unit_price' => 1000]],
            5000.0,
            $coupon,
        );

        $this->assertSame(0.0, $result['grand_total']);
    }

    public function test_invalid_coupon_ignored(): void
    {
        $coupon = ['valid' => false, 'discount' => 9999.0, 'free_shipping' => true];
        $result = $this->totals->compute(
            [['quantity' => 1, 'unit_price' => 10000]],
            5000.0,
            $coupon,
        );

        $this->assertSame(0.0, $result['discount_amount']);
        $this->assertSame(15000.0, $result['grand_total']);
    }
}
```

- [ ] **Step 2: Run the test to confirm it fails**

Run: `/c/laragon/bin/php/php-8.1.10-Win32-vs16-x64/php.exe artisan test --filter=CheckoutTotalsTest`
Expected: FAIL — `Class "App\Services\CheckoutTotals" not found` (or autoload error).

- [ ] **Step 3: Implement the service**

Create `app/Services/CheckoutTotals.php`:

```php
<?php

namespace App\Services;

final class CheckoutTotals
{
    /**
     * Compute order totals from raw inputs.
     *
     * @param  iterable<array{quantity:int|float, unit_price:int|float}>  $items
     * @param  float  $shippingFee  Resolved shipping fee (caller decides source).
     * @param  array{valid:bool, discount:float, free_shipping:bool}|null  $couponPreview
     *         Output of CouponService::preview(), or null when no code submitted.
     * @return array{subtotal:float, shipping_fee:float, discount_amount:float, grand_total:float}
     */
    public function compute(iterable $items, float $shippingFee, ?array $couponPreview): array
    {
        $subtotal = 0.0;
        foreach ($items as $item) {
            $subtotal += (float) $item['quantity'] * (float) $item['unit_price'];
        }
        $subtotal = round($subtotal, 2);

        $couponValid = (bool) ($couponPreview['valid'] ?? false);
        $couponDiscount = $couponValid ? (float) ($couponPreview['discount'] ?? 0) : 0.0;
        $freeShipping = $couponValid && (bool) ($couponPreview['free_shipping'] ?? false);
        $freeShippingDiscount = $freeShipping ? $shippingFee : 0.0;

        $discountAmount = round($couponDiscount + $freeShippingDiscount, 2);
        $grandTotal = round(max(0.0, $subtotal + $shippingFee - $discountAmount), 2);

        return [
            'subtotal' => $subtotal,
            'shipping_fee' => $shippingFee,
            'discount_amount' => $discountAmount,
            'grand_total' => $grandTotal,
        ];
    }
}
```

- [ ] **Step 4: Run the test to confirm it passes**

Run: `/c/laragon/bin/php/php-8.1.10-Win32-vs16-x64/php.exe artisan test --filter=CheckoutTotalsTest`
Expected: `Tests: 7 passed`

- [ ] **Step 5: Commit**

```bash
git add app/Services/CheckoutTotals.php tests/Unit/CheckoutTotalsTest.php
git commit -m "feat(checkout): CheckoutTotals service for shared totals math

Pure function: subtotal + shipping + coupon (discount + free_shipping)
+ clamp-to-zero grand_total. Used by the upcoming mobile review and
buy-now endpoints (U-9 #1 + #2)."
```

---

## Task 3: Extract `resolveOrderAddress` helper into `MobileController`

Behavior-neutral refactor: the address-fallback chain (`address_id` → user default → first) is currently inlined in `MobileController::checkout()` and will be used by all three new methods. Extracting it now keeps later tasks focused.

**Files:**
- Modify: `app/Http/Controllers/Api/MobileController.php`

- [ ] **Step 1: Add the helper next to the other private helpers**

Open `app/Http/Controllers/Api/MobileController.php`. Find the private `addressPayload()` method (around line 1488). Immediately above `addressPayload`, add:

```php
    private function resolveOrderAddress(User $user, ?int $addressId): ?UserAddress
    {
        return $user->addresses()->whereKey($addressId)->first()
            ?: $user->addresses()->where('is_default', true)->first()
            ?: $user->addresses()->first();
    }
```

- [ ] **Step 2: Refactor `checkout()` to use the helper**

In the same file, find the address resolution block inside `checkout()` (around lines 779–781):

```php
        $address = $request->user()->addresses()->whereKey($data['address_id'] ?? null)->first()
            ?: $request->user()->addresses()->where('is_default', true)->first()
            ?: $request->user()->addresses()->first();
```

Replace those three lines with:

```php
        $address = $this->resolveOrderAddress($request->user(), $data['address_id'] ?? null);
```

- [ ] **Step 3: Verify the file parses**

Run: `/c/laragon/bin/php/php-8.1.10-Win32-vs16-x64/php.exe -l app/Http/Controllers/Api/MobileController.php`
Expected: `No syntax errors detected`

- [ ] **Step 4: Run the full test suite — no regressions allowed**

Run: `/c/laragon/bin/php/php-8.1.10-Win32-vs16-x64/php.exe artisan test`
Expected: same number of tests passing as before this commit. Existing checkout tests (in any `Mobile*Test.php`) must still pass.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Api/MobileController.php
git commit -m "refactor(mobile): extract resolveOrderAddress helper

Will be reused by upcoming /checkout/review + /products/{x}/buy-now/*
endpoints. checkout() now calls the helper; behavior identical."
```

---

## Task 4: Implement `checkoutReview` endpoint (TDD)

**Files:**
- Create: `tests/Feature/MobileCheckoutReviewTest.php`
- Modify: `app/Http/Controllers/Api/MobileController.php`
- Modify: `routes/api.php`

- [ ] **Step 1: Write the failing feature tests**

Create `tests/Feature/MobileCheckoutReviewTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileCheckoutReviewTest extends TestCase
{
    use RefreshDatabase;

    private function userWithAddress(array $userOverrides = [], array $addressOverrides = []): User
    {
        $user = User::factory()->create($userOverrides);
        UserAddress::query()->forceCreate(array_merge([
            'user_id' => $user->id,
            'label' => 'Home',
            'country' => 'IQ',
            'city' => 'Erbil',
            'address_line1' => '100 Test Street',
            'phone' => '+964 770 000 0000',
            'is_default' => true,
        ], $addressOverrides));
        return $user->fresh();
    }

    private function fillCart(User $user, Product $product, int $qty = 2): void
    {
        $cart = Cart::query()->forceCreate(['user_id' => $user->id]);
        CartItem::query()->forceCreate([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => $qty,
        ]);
    }

    public function test_review_requires_auth(): void
    {
        $this->postJson('/api/mobile/checkout/review', [])->assertStatus(401);
    }

    public function test_review_returns_422_for_empty_cart(): void
    {
        $user = $this->userWithAddress();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/checkout/review', []);

        $response->assertStatus(422);
        $this->assertStringContainsString('cart', strtolower((string) $response->json('message')));
    }

    public function test_review_returns_422_when_no_address(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 10, 'is_active' => true]);
        $this->fillCart($user, $product);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/checkout/review', [])
            ->assertStatus(422);
    }

    public function test_review_returns_totals_for_default_address(): void
    {
        $user = $this->userWithAddress();
        $product = Product::factory()->create(['price' => 12500, 'stock_quantity' => 10, 'is_active' => true]);
        $this->fillCart($user, $product, 2);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/checkout/review', []);

        $response->assertOk();
        $totals = $response->json('data.totals');
        $this->assertSame(25000.0, (float) $totals['subtotal']);
        $this->assertSame(5000.0, (float) $totals['shipping_fee']);
        $this->assertSame(0.0, (float) $totals['discount_amount']);
        $this->assertSame(30000.0, (float) $totals['grand_total']);
        $this->assertNotEmpty($response->json('data.address.id'));
        $this->assertCount(1, $response->json('data.items'));
    }

    public function test_review_explicit_address_id_used(): void
    {
        $user = $this->userWithAddress();
        $other = UserAddress::query()->forceCreate([
            'user_id' => $user->id,
            'label' => 'Office',
            'country' => 'IQ',
            'city' => 'Sulaymaniyah',
            'address_line1' => '200 Office Lane',
            'phone' => '+964 770 111 1111',
            'is_default' => false,
        ]);
        $product = Product::factory()->create(['price' => 10000, 'stock_quantity' => 5, 'is_active' => true]);
        $this->fillCart($user, $product, 1);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/checkout/review', ['address_id' => $other->id]);

        $response->assertOk();
        $this->assertSame($other->id, $response->json('data.address.id'));
    }

    public function test_review_invalid_coupon_returns_200_with_failure_envelope(): void
    {
        $user = $this->userWithAddress();
        $product = Product::factory()->create(['price' => 10000, 'stock_quantity' => 5, 'is_active' => true]);
        $this->fillCart($user, $product, 1);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/checkout/review', ['coupon_code' => 'NOPE-BAD-CODE']);

        $response->assertOk();
        $this->assertFalse($response->json('data.coupon_summary.valid'));
        $this->assertSame(0.0, (float) $response->json('data.totals.discount_amount'));
    }

    public function test_review_uses_setting_for_shipping_fee(): void
    {
        Setting::setValue('shipping_fee', 7777);
        $user = $this->userWithAddress();
        $product = Product::factory()->create(['price' => 10000, 'stock_quantity' => 5, 'is_active' => true]);
        $this->fillCart($user, $product, 1);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/checkout/review', []);

        $response->assertOk();
        $this->assertSame(7777.0, (float) $response->json('data.totals.shipping_fee'));
    }

    public function test_review_echoes_notes(): void
    {
        $user = $this->userWithAddress();
        $product = Product::factory()->create(['price' => 10000, 'stock_quantity' => 5, 'is_active' => true]);
        $this->fillCart($user, $product, 1);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/checkout/review', ['notes' => 'ring before delivery']);

        $response->assertOk();
        $this->assertSame('ring before delivery', $response->json('data.notes'));
    }
}
```

If the `Product::factory()` doesn't have a `price` attribute slot, inspect `database/factories/ProductFactory.php` and adjust the field name (likely `price` already exists; if it doesn't, the test setup needs the price column whatever it's called — see `priceFor()` in the Product model). The tests assume the default `priceFor($user)` is the base `price` attribute, which holds for non-dealer users.

- [ ] **Step 2: Run the tests to confirm they fail (route doesn't exist)**

Run: `/c/laragon/bin/php/php-8.1.10-Win32-vs16-x64/php.exe artisan test --filter=MobileCheckoutReviewTest`
Expected: most cases fail with 404 (route not registered).

- [ ] **Step 3: Add the controller method**

Open `app/Http/Controllers/Api/MobileController.php`. Find the import block (top of file). After the existing `use App\Services\CouponService;` and `use App\Services\InvoiceRenderer;`, add:

```php
use App\Services\CheckoutTotals;
```

Find the existing `checkout()` method (around line 769). Immediately after the closing `}` of `checkout()`, add the new method:

```php
    public function checkoutReview(Request $request, CouponService $coupons, CheckoutTotals $totals)
    {
        $data = $request->validate([
            'address_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'coupon_code' => ['nullable', 'string', 'max:80'],
        ]);

        $user = $request->user();
        $cart = $this->cartFor($user)->load('items.product');
        abort_if($cart->items->isEmpty(), 422, __('errors.cart_empty'));

        $address = $this->resolveOrderAddress($user, $data['address_id'] ?? null);
        abort_if(! $address, 422, __('errors.delivery_address_required'));
        $normalizedDeliveryPhone = User::normalizePhone((string) $address->phone);
        abort_if(
            $normalizedDeliveryPhone === null
                || strlen($normalizedDeliveryPhone) < PhoneNumber::MIN_DIGITS
                || strlen($normalizedDeliveryPhone) > PhoneNumber::MAX_DIGITS,
            422,
            __('validation.phone', ['attribute' => 'delivery phone']),
        );

        $lineItems = [];
        $responseItems = [];
        foreach ($cart->items as $item) {
            if (! $item->product) {
                continue;
            }
            $unitPrice = (float) $item->product->priceFor($user);
            $lineItems[] = ['quantity' => (int) $item->quantity, 'unit_price' => $unitPrice];
            $responseItems[] = [
                'product' => $this->productPayload($item->product, $user),
                'quantity' => (int) $item->quantity,
                'unit_price' => $unitPrice,
                'subtotal' => round($unitPrice * $item->quantity, 2),
            ];
        }

        $shippingFee = (float) Setting::getValue('shipping_fee', 5000);
        $code = $coupons->normalizeCode((string) ($data['coupon_code'] ?? ''));
        $subtotalForCoupon = array_sum(array_map(fn ($i) => $i['quantity'] * $i['unit_price'], $lineItems));
        $couponPreview = $code !== '' ? $coupons->preview($code, round($subtotalForCoupon, 2), $user) : null;

        $computed = $totals->compute($lineItems, $shippingFee, $couponPreview);

        return response()->json(['data' => [
            'address' => $this->addressPayload($address),
            'items' => $responseItems,
            'notes' => (string) ($data['notes'] ?? ''),
            'totals' => $computed,
            'coupon_summary' => $this->couponSummaryFor($couponPreview),
        ]]);
    }

    private function couponSummaryFor(?array $couponPreview): array
    {
        if ($couponPreview === null) {
            return [
                'valid' => false,
                'code' => '',
                'discount' => 0.0,
                'free_shipping' => false,
                'message' => null,
            ];
        }
        return [
            'valid' => (bool) ($couponPreview['valid'] ?? false),
            'code' => (string) ($couponPreview['code'] ?? ''),
            'discount' => (float) ($couponPreview['discount'] ?? 0),
            'free_shipping' => (bool) ($couponPreview['free_shipping'] ?? false),
            'message' => $couponPreview['message'] ?? null,
        ];
    }
```

Also add `use App\Models\Setting;` to the import block if it isn't already imported (check with `grep -n "use App\\\\Models\\\\Setting" app/Http/Controllers/Api/MobileController.php`; add it next to the other model imports if absent).

- [ ] **Step 4: Register the route**

Open `routes/api.php`. Find line 80 (`Route::post('/checkout', [MobileController::class, 'checkout']);`). Add immediately after it:

```php
        Route::post('/checkout/review', [MobileController::class, 'checkoutReview']);
```

- [ ] **Step 5: Verify and run the tests**

Run:
```
/c/laragon/bin/php/php-8.1.10-Win32-vs16-x64/php.exe -l app/Http/Controllers/Api/MobileController.php
/c/laragon/bin/php/php-8.1.10-Win32-vs16-x64/php.exe artisan route:list --path=api/mobile/checkout 2>&1 | grep review
/c/laragon/bin/php/php-8.1.10-Win32-vs16-x64/php.exe artisan test --filter=MobileCheckoutReviewTest
```
Expected: file parses, route appears (`POST api/mobile/checkout/review`), all 8 tests pass.

- [ ] **Step 6: Run full suite — no regressions**

Run: `/c/laragon/bin/php/php-8.1.10-Win32-vs16-x64/php.exe artisan test`
Expected: same number passing as before plus the 8 new ones.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Api/MobileController.php routes/api.php tests/Feature/MobileCheckoutReviewTest.php
git commit -m "feat(api): POST /api/mobile/checkout/review (U-9 #2)

Server-side cart totals preview. Uses shared CheckoutTotals service
so mobile clients can trust subtotal/shipping/discount/grand_total
match what the server will charge on /checkout."
```

---

## Task 5: Implement `buyNowPreview` endpoint (TDD)

**Files:**
- Create: `tests/Feature/MobileBuyNowTest.php`
- Modify: `app/Http/Controllers/Api/MobileController.php`
- Modify: `routes/api.php`

- [ ] **Step 1: Write the failing feature tests for preview**

Create `tests/Feature/MobileBuyNowTest.php` (place-endpoint tests will be added in Task 6):

```php
<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileBuyNowTest extends TestCase
{
    use RefreshDatabase;

    private function userWithAddress(array $userOverrides = []): User
    {
        $user = User::factory()->create($userOverrides);
        UserAddress::query()->forceCreate([
            'user_id' => $user->id,
            'label' => 'Home',
            'country' => 'IQ',
            'city' => 'Erbil',
            'address_line1' => '100 Test Street',
            'phone' => '+964 770 000 0000',
            'is_default' => true,
        ]);
        return $user->fresh();
    }

    public function test_preview_requires_auth(): void
    {
        Product::factory()->create(['stock_quantity' => 10, 'is_active' => true]);
        $this->postJson('/api/mobile/products/999/buy-now/preview', [])->assertStatus(401);
    }

    public function test_preview_returns_404_for_unknown_product(): void
    {
        $user = $this->userWithAddress();
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/products/this-slug-does-not-exist/buy-now/preview', [])
            ->assertStatus(404);
    }

    public function test_preview_returns_422_for_inactive_product(): void
    {
        $user = $this->userWithAddress();
        $product = Product::factory()->create(['stock_quantity' => 10, 'is_active' => false]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/products/' . $product->id . '/buy-now/preview', [])
            ->assertStatus(422);
    }

    public function test_preview_returns_422_when_out_of_stock(): void
    {
        $user = $this->userWithAddress();
        $product = Product::factory()->create(['stock_quantity' => 0, 'is_active' => true]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/products/' . $product->id . '/buy-now/preview', [])
            ->assertStatus(422);
    }

    public function test_preview_clamps_quantity_to_stock(): void
    {
        $user = $this->userWithAddress();
        $product = Product::factory()->create(['price' => 10000, 'stock_quantity' => 3, 'is_active' => true]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/products/' . $product->id . '/buy-now/preview', ['quantity' => 5]);

        $response->assertOk();
        $this->assertSame(3, (int) $response->json('data.quantity'));
        $this->assertSame(5, (int) $response->json('data.quantity_requested'));
    }

    public function test_preview_totals(): void
    {
        $user = $this->userWithAddress();
        $product = Product::factory()->create(['price' => 12500, 'stock_quantity' => 10, 'is_active' => true]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/products/' . $product->id . '/buy-now/preview', ['quantity' => 2]);

        $response->assertOk();
        $totals = $response->json('data.totals');
        $this->assertSame(25000.0, (float) $totals['subtotal']);
        $this->assertSame(5000.0, (float) $totals['shipping_fee']);
        $this->assertSame(30000.0, (float) $totals['grand_total']);
        $this->assertSame($product->id, (int) $response->json('data.product.id'));
    }

    public function test_preview_invalid_coupon_returns_200_with_failure_envelope(): void
    {
        $user = $this->userWithAddress();
        $product = Product::factory()->create(['price' => 10000, 'stock_quantity' => 5, 'is_active' => true]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/products/' . $product->id . '/buy-now/preview', [
                'quantity' => 1,
                'coupon_code' => 'NO-SUCH-CODE',
            ]);

        $response->assertOk();
        $this->assertFalse($response->json('data.coupon_summary.valid'));
        $this->assertSame(0.0, (float) $response->json('data.totals.discount_amount'));
    }
}
```

- [ ] **Step 2: Run the tests to confirm they fail**

Run: `/c/laragon/bin/php/php-8.1.10-Win32-vs16-x64/php.exe artisan test --filter=MobileBuyNowTest`
Expected: tests fail with 404 (route not registered).

- [ ] **Step 3: Add the controller method**

Open `app/Http/Controllers/Api/MobileController.php`. After the `checkoutReview()` method you added in Task 4, add:

```php
    public function buyNowPreview(Request $request, string $idOrSlug, CouponService $coupons, CheckoutTotals $totals)
    {
        $data = $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'address_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'coupon_code' => ['nullable', 'string', 'max:80'],
        ]);

        $user = $request->user();
        $product = $this->findProduct($idOrSlug);
        abort_unless($product->is_active, 422, __('errors.product_unavailable'));
        abort_if((int) $product->stock_quantity < 1, 422, __('errors.stock_insufficient'));

        $requested = (int) ($data['quantity'] ?? 1);
        $quantity = min(99, max(1, min($requested, (int) $product->stock_quantity)));

        $address = $this->resolveOrderAddress($user, $data['address_id'] ?? null);
        abort_if(! $address, 422, __('errors.delivery_address_required'));
        $normalizedDeliveryPhone = User::normalizePhone((string) $address->phone);
        abort_if(
            $normalizedDeliveryPhone === null
                || strlen($normalizedDeliveryPhone) < PhoneNumber::MIN_DIGITS
                || strlen($normalizedDeliveryPhone) > PhoneNumber::MAX_DIGITS,
            422,
            __('validation.phone', ['attribute' => 'delivery phone']),
        );

        $unitPrice = (float) $product->priceFor($user);
        $shippingFee = (float) Setting::getValue('shipping_fee', 5000);
        $code = $coupons->normalizeCode((string) ($data['coupon_code'] ?? ''));
        $subtotalForCoupon = round($unitPrice * $quantity, 2);
        $couponPreview = $code !== '' ? $coupons->preview($code, $subtotalForCoupon, $user) : null;

        $computed = $totals->compute(
            [['quantity' => $quantity, 'unit_price' => $unitPrice]],
            $shippingFee,
            $couponPreview,
        );

        $payload = [
            'product' => $this->productPayload($product, $user),
            'quantity' => $quantity,
            'address' => $this->addressPayload($address),
            'notes' => (string) ($data['notes'] ?? ''),
            'totals' => $computed,
            'coupon_summary' => $this->couponSummaryFor($couponPreview),
        ];
        if ($quantity !== $requested) {
            $payload['quantity_requested'] = $requested;
        }

        return response()->json(['data' => $payload]);
    }
```

- [ ] **Step 4: Register the route**

Open `routes/api.php`. Find the existing reviews routes (around lines 71–72: `/products/{idOrSlug}/reviews`). Immediately after the `POST .../reviews` line, add:

```php
        Route::post('/products/{idOrSlug}/buy-now/preview', [MobileController::class, 'buyNowPreview']);
```

- [ ] **Step 5: Verify and run the tests**

Run:
```
/c/laragon/bin/php/php-8.1.10-Win32-vs16-x64/php.exe -l app/Http/Controllers/Api/MobileController.php
/c/laragon/bin/php/php-8.1.10-Win32-vs16-x64/php.exe artisan route:list --path=api/mobile/products 2>&1 | grep buy-now
/c/laragon/bin/php/php-8.1.10-Win32-vs16-x64/php.exe artisan test --filter=MobileBuyNowTest
```
Expected: file parses, the preview route appears, all 7 preview tests pass.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Api/MobileController.php routes/api.php tests/Feature/MobileBuyNowTest.php
git commit -m "feat(api): POST /api/mobile/products/{x}/buy-now/preview (U-9 #1)

Single-product buy-now totals preview. Mirrors the cart review
behavior for one product + quantity, clamping requested quantity
to available stock and reporting the original requested value
when clamping changed it."
```

---

## Task 6: Implement `buyNowPlace` endpoint (TDD)

**Files:**
- Modify: `tests/Feature/MobileBuyNowTest.php` (add place cases to the existing file)
- Modify: `app/Http/Controllers/Api/MobileController.php`
- Modify: `routes/api.php`

- [ ] **Step 1: Add the failing place tests to the existing test file**

Open `tests/Feature/MobileBuyNowTest.php`. Add these methods inside the existing `MobileBuyNowTest` class (after the preview tests, before the closing `}` of the class):

```php
    public function test_place_requires_auth(): void
    {
        $this->postJson('/api/mobile/products/999/buy-now/place', ['quantity' => 1])->assertStatus(401);
    }

    public function test_place_requires_quantity(): void
    {
        $user = $this->userWithAddress();
        $product = Product::factory()->create(['price' => 10000, 'stock_quantity' => 5, 'is_active' => true]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/products/' . $product->id . '/buy-now/place', [])
            ->assertStatus(422);
    }

    public function test_place_creates_order_with_correct_totals(): void
    {
        $user = $this->userWithAddress();
        $product = Product::factory()->create(['price' => 12500, 'stock_quantity' => 10, 'is_active' => true]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/products/' . $product->id . '/buy-now/place', ['quantity' => 2]);

        $response->assertOk();
        $orderId = $response->json('order.id');
        $this->assertNotNull($orderId);
        $order = \App\Models\Order::find($orderId);
        $this->assertSame(25000.0, (float) $order->subtotal_amount);
        $this->assertSame(5000.0, (float) $order->shipping_fee);
        $this->assertSame(0.0, (float) $order->discount_amount);
        $this->assertSame(30000.0, (float) $order->grand_total);
        $this->assertSame($user->id, $order->user_id);
        $this->assertCount(1, $order->items);
        $this->assertSame(2, (int) $order->items->first()->quantity);
        $this->assertSame($product->id, (int) $order->items->first()->product_id);
    }

    public function test_place_decrements_stock(): void
    {
        $user = $this->userWithAddress();
        $product = Product::factory()->create(['price' => 10000, 'stock_quantity' => 5, 'is_active' => true]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/products/' . $product->id . '/buy-now/place', ['quantity' => 2])
            ->assertOk();

        $this->assertSame(3, (int) $product->fresh()->stock_quantity);
    }

    public function test_place_hard_fails_on_insufficient_stock(): void
    {
        $user = $this->userWithAddress();
        $product = Product::factory()->create(['price' => 10000, 'stock_quantity' => 1, 'is_active' => true]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/products/' . $product->id . '/buy-now/place', ['quantity' => 5])
            ->assertStatus(422);

        $this->assertSame(0, \App\Models\Order::where('user_id', $user->id)->count());
    }

    public function test_place_hard_fails_on_inactive_product(): void
    {
        $user = $this->userWithAddress();
        $product = Product::factory()->create(['price' => 10000, 'stock_quantity' => 5, 'is_active' => false]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/products/' . $product->id . '/buy-now/place', ['quantity' => 1])
            ->assertStatus(422);

        $this->assertSame(0, \App\Models\Order::where('user_id', $user->id)->count());
    }

    public function test_place_hard_fails_on_invalid_coupon(): void
    {
        $user = $this->userWithAddress();
        $product = Product::factory()->create(['price' => 10000, 'stock_quantity' => 5, 'is_active' => true]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/products/' . $product->id . '/buy-now/place', [
                'quantity' => 1,
                'coupon_code' => 'NO-SUCH',
            ])
            ->assertStatus(422);

        $this->assertSame(0, \App\Models\Order::where('user_id', $user->id)->count());
    }

    public function test_place_respects_address_id(): void
    {
        $user = $this->userWithAddress();
        $other = UserAddress::query()->forceCreate([
            'user_id' => $user->id,
            'label' => 'Office',
            'country' => 'IQ',
            'city' => 'Sulaymaniyah',
            'address_line1' => '200 Office Lane',
            'phone' => '+964 770 111 1111',
            'is_default' => false,
        ]);
        $product = Product::factory()->create(['price' => 10000, 'stock_quantity' => 5, 'is_active' => true]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/mobile/products/' . $product->id . '/buy-now/place', [
                'quantity' => 1,
                'address_id' => $other->id,
            ])
            ->assertOk();

        $order = \App\Models\Order::where('user_id', $user->id)->first();
        $this->assertSame('Sulaymaniyah', $order->delivery_city);
    }
```

- [ ] **Step 2: Run the tests to confirm they fail**

Run: `/c/laragon/bin/php/php-8.1.10-Win32-vs16-x64/php.exe artisan test --filter=MobileBuyNowTest`
Expected: the new place tests fail with 404 (route not registered).

- [ ] **Step 3: Add the controller method**

Open `app/Http/Controllers/Api/MobileController.php`. After the `buyNowPreview()` method, add:

```php
    public function buyNowPlace(Request $request, string $idOrSlug, CouponService $coupons, CheckoutTotals $totals)
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'address_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'coupon_code' => ['nullable', 'string', 'max:80'],
            'payment_method' => ['nullable', Rule::in(['cash_on_delivery', 'zaincash', 'fastpay', 'bank_transfer'])],
        ]);

        $user = $request->user();
        $product = $this->findProduct($idOrSlug);
        abort_unless($product->is_active, 422, __('errors.product_unavailable'));
        $quantity = (int) $data['quantity'];
        abort_if((int) $product->stock_quantity < $quantity, 422, __('errors.stock_insufficient'));

        $address = $this->resolveOrderAddress($user, $data['address_id'] ?? null);
        abort_if(! $address, 422, __('errors.delivery_address_required'));
        $normalizedDeliveryPhone = User::normalizePhone((string) $address->phone);
        abort_if(
            $normalizedDeliveryPhone === null
                || strlen($normalizedDeliveryPhone) < PhoneNumber::MIN_DIGITS
                || strlen($normalizedDeliveryPhone) > PhoneNumber::MAX_DIGITS,
            422,
            __('validation.phone', ['attribute' => 'delivery phone']),
        );

        $unitPrice = (float) $product->priceFor($user);
        $shippingFee = (float) Setting::getValue('shipping_fee', 5000);
        $code = $coupons->normalizeCode((string) ($data['coupon_code'] ?? ''));
        $subtotalForCoupon = round($unitPrice * $quantity, 2);
        $couponPreview = null;
        if ($code !== '') {
            $couponPreview = $coupons->preview($code, $subtotalForCoupon, $user);
            abort_if(
                ! ($couponPreview['valid'] ?? false),
                422,
                (string) ($couponPreview['message'] ?? __('Coupon could not be applied.')),
            );
        }

        $computed = $totals->compute(
            [['quantity' => $quantity, 'unit_price' => $unitPrice]],
            $shippingFee,
            $couponPreview,
        );

        $order = new Order();
        $order->forceFill([
            'user_id' => $user->id,
            'order_number' => 'ORD-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5)),
            'subtotal_amount' => $computed['subtotal'],
            'shipping_fee' => $computed['shipping_fee'],
            'discount_amount' => $computed['discount_amount'],
            'coupon_code' => ($couponPreview['valid'] ?? false) ? (string) ($couponPreview['code'] ?? '') : null,
            'grand_total' => $computed['grand_total'],
            'total_amount' => $computed['grand_total'],
            'status' => Order::STATUS_PENDING,
            'payment_method' => $data['payment_method'] ?? 'cash_on_delivery',
            'payment_status' => Order::PAYMENT_PENDING,
            'delivery_address' => $address->address_line1,
            'delivery_city' => $address->city,
            'delivery_phone' => $address->phone,
            'notes' => ($data['notes'] ?? '') !== '' ? (string) $data['notes'] : null,
        ])->save();

        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => round($unitPrice * $quantity, 2),
        ]);

        $product->update(['stock_quantity' => (int) $product->stock_quantity - $quantity]);

        return response()->json([
            'order' => $this->orderPayload($order->fresh('items.product')),
        ]);
    }
```

- [ ] **Step 4: Register the route**

Open `routes/api.php`. Find the line you added in Task 5 (`POST /products/{idOrSlug}/buy-now/preview`). Immediately after it, add:

```php
        Route::post('/products/{idOrSlug}/buy-now/place', [MobileController::class, 'buyNowPlace']);
```

- [ ] **Step 5: Verify and run all `MobileBuyNowTest` cases**

Run:
```
/c/laragon/bin/php/php-8.1.10-Win32-vs16-x64/php.exe -l app/Http/Controllers/Api/MobileController.php
/c/laragon/bin/php/php-8.1.10-Win32-vs16-x64/php.exe artisan route:list --path=api/mobile/products 2>&1 | grep buy-now
/c/laragon/bin/php/php-8.1.10-Win32-vs16-x64/php.exe artisan test --filter=MobileBuyNowTest
```
Expected: file parses, both `buy-now/preview` and `buy-now/place` routes appear, all preview+place tests pass (15 total).

- [ ] **Step 6: Run the full test suite — final regression check**

Run: `/c/laragon/bin/php/php-8.1.10-Win32-vs16-x64/php.exe artisan test`
Expected: no previously-passing tests fail.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Api/MobileController.php routes/api.php tests/Feature/MobileBuyNowTest.php
git commit -m "feat(api): POST /api/mobile/products/{x}/buy-now/place (U-9 #1)

Creates a single-product order. Matches the existing simpler
MobileController::checkout pattern intentionally: no transaction,
no inventory movement record, no order-placed notification — those
gaps are tracked separately as audit item 'mobile cart-checkout
divergence' and will be fixed in a dedicated PR."
```

---

## Task 7: Update the parity audit doc

**Files:**
- Modify: `docs/api-parity.md`

- [ ] **Step 1: Promote rows #1 and #2 to "at parity"**

Open `docs/api-parity.md`. In the High-priority gaps table, find and delete these two rows:

```markdown
| 1 | **Buy-Now checkout flow** | `GET checkout.options/{product}`, `MATCH checkout.buy-now/{product}`, `POST checkout.buy-now.place` | ❌ | Single-product fast purchase. Common on PDP. Add as `POST /products/{x}/buy-now/preview` + `POST /products/{x}/buy-now/place`. |
| 2 | **Checkout review (GET form)** | `MATCH checkout.review` | ❌ | Server-side computed totals before placing. Mobile recomputes locally; review endpoint would prevent client/server mismatch. Add `POST /checkout/review` returning subtotal + shipping + discount + grand total. |
```

In the "What's already at parity ✅" table, add these two rows right after the existing "Order invoice download" row:

```markdown
| **Buy-Now checkout flow (added this commit)** | `checkout.options` / `checkout.buy-now` / `checkout.buy-now.place` | **`POST /products/{x}/buy-now/preview` + `POST /products/{x}/buy-now/place`** |
| **Checkout review (added this commit)** | `checkout.review` | **`POST /checkout/review`** |
```

- [ ] **Step 2: Add a new gap row for mobile cart-checkout divergence**

In the "Low priority — admin / dealer extras" table, add a new numbered row at the bottom of that table (use the next available number — currently #11 is "Per-locale routing alignment", so this becomes #12):

```markdown
| 12 | **Mobile cart-checkout divergence** | `CheckoutController::store` does DB transaction + lockForUpdate + InventoryMovement + UserCommunication notification + status history + coupon usage tracking + free_shipping coupon | `MobileController::checkout` (and new `buyNowPlace`) skip all of the above | Bigger correctness gap — both mobile place endpoints can oversell stock under concurrency, leave no audit trail, and silently swallow free-shipping coupons. Fix means extracting an OrderPlacement service used by web + mobile. Scoped for a follow-up PR. |
```

- [ ] **Step 3: Update the "Suggested order" section**

Find the suggested-order numbered list near the bottom. Replace the entire list with:

```markdown
1. ~~**#6 Settings sub-pages**~~ — **closed in 50662ac.**
2. ~~**#4 Legal pages content** + **#5 Contact form**~~ — **closed in 361254a.**
3. ~~**#3 Order invoice**~~ — **closed in f7e43a0.**
4. ~~**#1 + #2 Buy-Now + Checkout review**~~ — **closed this commit.**
5. **#8 Wishlist payload** — requires API version bump; do after the version scheme is decided.
6. **#12 Mobile cart-checkout divergence** — extract shared OrderPlacement service used by web + mobile; medium-priority correctness fix.
7. **#7 Account activity**, **#9 Profile field expansion**, **#10 Account actions** — UX polish, schedule when product priorities allow.
```

- [ ] **Step 4: Add a "Refactor candidates" note at the bottom of the Infrastructure section**

Find the section `## Infrastructure follow-ups (not strictly parity)` and inside it, add one bullet:

```markdown
- **Web `CheckoutController` totals math refactor** — extract the (currently duplicated 4-way) subtotal/shipping/coupon/grand_total math into `App\Services\CheckoutTotals` (already used by mobile review + buy-now endpoints). Behavior-equivalent; do in a separate commit so the web change is byte-identical and easy to review.
```

- [ ] **Step 5: Verify the doc structure**

Run:
```
grep -nE "^\| 1 \| \*\*Buy-Now|^\| 2 \| \*\*Checkout review" docs/api-parity.md
```
Expected: no matches (rows removed).

```
grep -n "Buy-Now checkout flow (added this commit)" docs/api-parity.md
grep -n "Checkout review (added this commit)" docs/api-parity.md
grep -n "Mobile cart-checkout divergence" docs/api-parity.md
grep -n "closed this commit" docs/api-parity.md
```
Expected: one match for each of the first three; one match for "closed this commit" (in the suggested-order list, item 4).

- [ ] **Step 6: Commit**

```bash
git add docs/api-parity.md
git commit -m "docs(api): mark mobile buy-now + checkout review (#1 + #2) as closed

Promote rows #1 and #2 from open gaps to parity. Add new gap #12
tracking mobile cart-checkout divergence (no transaction, no
inventory movement, no notification, no free_shipping coupon
support). Suggested order points at #8 (Wishlist) and #12 as next."
```

---

## Self-Review Notes

- **Spec coverage**: all three endpoints map to tasks (Task 4 = review, Task 5 = buy-now preview, Task 6 = buy-now place). `CheckoutTotals` service in Task 2. Address helper in Task 3. Translation keys in Task 1. Audit doc + new divergence gap in Task 7.
- **Behavior preservation**: Task 3 refactors `checkout()` to use the new address helper but the helper is a line-for-line equivalent of the inlined version — full test suite must remain green after Task 3.
- **Type consistency**: `CheckoutTotals::compute(iterable $items, float $shippingFee, ?array $couponPreview): array` signature used identically in Tasks 2, 4, 5, 6. `resolveOrderAddress(User $user, ?int $addressId): ?UserAddress` used identically in Tasks 3, 4, 5, 6.
- **No new dependencies**: everything wires through existing classes (`CouponService`, `Setting`, `User::normalizePhone`, `PhoneNumber`, `Order`, `Product`).
- **Decision documented**: Task 6 explicitly preserves the simpler-mobile pattern (no transaction, no inventory movement, no notification) and Task 7 logs that as a separate audit gap rather than silently fixing it in this PR.
