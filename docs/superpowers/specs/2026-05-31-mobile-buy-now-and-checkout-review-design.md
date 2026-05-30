# Mobile Buy-Now + Checkout Review — Design

> Closes gaps **#1** (Buy-Now checkout flow) and **#2** (Checkout review) from `docs/api-parity.md` (Mobile API Parity Audit U-9).
> Adds three new endpoints to the mobile API and one shared totals service.

## Goal

A mobile-authenticated user can:

1. **Preview cart totals** before placing an order — server-computed `subtotal + shipping_fee + discount_amount + grand_total` so the client never disagrees with the server about price (gap #2).
2. **Preview a single-product buy-now order** without going through the cart — same totals shape, scoped to one product + quantity (gap #1 part 1).
3. **Place a single-product buy-now order** — actually creates the Order (gap #1 part 2).

The math (subtotal, coupon discount, free_shipping handling, grand_total clamp) is the same in all three paths and currently lives duplicated across four web controller methods. We extract it into one service so the new mobile endpoints don't add a fifth copy.

## Endpoints

All three live inside the existing `auth:sanctum` middleware group in `routes/api.php`.

### `POST /api/mobile/checkout/review`

Cart-based review. Does **not** create or modify an order.

**Body:**
| Field | Type | Required | Notes |
|---|---|---|---|
| `address_id` | int | no | Nullable; falls back to user's default address, then first address |
| `notes` | string ≤1000 | no | Echoed in response for client display |
| `coupon_code` | string ≤80 | no | Normalized via `CouponService::normalizeCode()`; invalid codes return graceful `coupon_summary.valid=false` (200), not an error |

**Response 200:**
```json
{
  "data": {
    "address": { /* addressPayload shape */ },
    "items": [ { "product": {...}, "quantity": 2, "unit_price": 12500, "subtotal": 25000 } ],
    "notes": "ring before delivery",
    "totals": {
      "subtotal": 25000,
      "shipping_fee": 5000,
      "discount_amount": 0,
      "grand_total": 30000
    },
    "coupon_summary": {
      "valid": false,
      "code": "",
      "discount": 0,
      "free_shipping": false,
      "message": null
    }
  }
}
```

**Errors:**
- 401 — no/invalid Sanctum token
- 422 — `errors.cart_empty` if the user's cart has no items
- 422 — `errors.delivery_address_required` if `address_id` is invalid AND no fallback address exists
- 422 — `validation.phone` if resolved address phone fails normalization (same check as existing `MobileController::checkout()`)

### `POST /api/mobile/products/{idOrSlug}/buy-now/preview`

Single-product preview. Does **not** create or modify an order.

**Path param:** `{idOrSlug}` — numeric id or slug; resolved via existing `MobileController::findProduct()`.

**Body:**
| Field | Type | Required | Notes |
|---|---|---|---|
| `quantity` | int 1..99 | no | Default 1; silently clamped to `min(99, max(1, product.stock_quantity))` (matches web `clampQuantityToStock`) |
| `address_id` | int | no | Same fallback chain as review |
| `notes` | string ≤1000 | no | Echoed |
| `coupon_code` | string ≤80 | no | Same handling as review |

**Response 200:** same shape as review with two added fields:
```json
{
  "data": {
    "product": { /* mobile productPayload shape */ },
    "quantity": 3,           // the clamped value actually used
    "quantity_requested": 5, // present only when clamping occurred; absent otherwise
    "address": {...},
    "notes": "",
    "totals": {...},
    "coupon_summary": {...}
  }
}
```

**Errors:** same as review, plus:
- 404 — product id/slug not found (route-model-binding behavior via `findProduct`)
- 422 — product `is_active = false` (use a distinct message like `errors.product_unavailable`)
- 422 — product `stock_quantity = 0` (cannot buy now)

### `POST /api/mobile/products/{idOrSlug}/buy-now/place`

Single-product order placement. Creates an Order and decrements stock.

**Body:**
| Field | Type | Required | Notes |
|---|---|---|---|
| `quantity` | int 1..99 | **yes** | Hard-fails if `quantity > stock_quantity` (does NOT silently clamp here) |
| `address_id` | int | no | Falls back to default → first (same chain) |
| `notes` | string ≤1000 | no | Saved on the order |
| `coupon_code` | string ≤80 | no | Hard-fails if invalid (422) |
| `payment_method` | enum | no | Default `cash_on_delivery`; same allowlist as `MobileController::checkout()`: `cash_on_delivery, zaincash, fastpay, bank_transfer` |

**Response 200:**
```json
{
  "order": { /* mobile orderPayload shape */ }
}
```

(Matches the existing `MobileController::checkout()` response envelope, minus the `cart` key since buy-now does not touch the cart.)

**Errors:**
- 401 — no/invalid token
- 404 — product not found
- 422 — product inactive, stock insufficient, address missing/invalid, phone invalid, coupon invalid

**Behavior parity decision — intentional mobile pattern:**

The place endpoint follows the **existing simpler `MobileController::checkout()` pattern**, NOT the web `placeBuyNow()` pattern. Specifically:

- ❌ No DB transaction / `lockForUpdate`
- ❌ No status history row created
- ❌ No `InventoryMovement::TYPE_OUT` record
- ❌ No `UserCommunication::sendOrderPlaced()` notification dispatch
- ❌ No coupon usage / discount rule usage tracking
- ✅ Stock check before save (best-effort, no lock)
- ✅ Stock decrement after save
- ✅ Order created with correct shipping_fee + free_shipping coupon math

This intentional divergence is documented as **a new audit gap** (`#11` or next available number) so it gets prioritized as a follow-up. Fixing it for buy-now alone would create a one-off where buy-now and cart-checkout behave differently. Better to fix both at once in a dedicated PR.

## Shared service: `App\Services\CheckoutTotals`

Pure computation; no DB writes, no transactions, no validation. Inputs flow in, totals flow out.

```php
namespace App\Services;

final class CheckoutTotals
{
    /**
     * @param iterable<array{quantity:int, unit_price:float}> $items
     * @param array|null $couponPreview  Output of CouponService::preview(), or null
     * @return array{subtotal:float, shipping_fee:float, discount_amount:float, grand_total:float}
     */
    public function compute(iterable $items, float $shippingFee, ?array $couponPreview): array;
}
```

**Math (matches web exactly):**
1. `subtotal = round(sum(quantity * unit_price), 2)`
2. `couponDiscount = couponPreview['valid'] ? (float)couponPreview['discount'] : 0.0`
3. `freeShippingDiscount = (couponPreview['valid'] && couponPreview['free_shipping']) ? shippingFee : 0.0`
4. `discount_amount = round(couponDiscount + freeShippingDiscount, 2)`
5. `grand_total = round(max(0, subtotal + shippingFee - discount_amount), 2)`

**Returned `coupon_summary` shape in the controller response** is built from the same `$couponPreview` (or a neutral default when null) — the service does not own the response shape; controllers do.

The web `CheckoutController` is **NOT refactored** in this PR. Its math is byte-identical to what `CheckoutTotals::compute()` produces, so a future refactor commit can swap in the service without behavior change. Noted in the audit doc as a follow-up.

## Architecture

### File map

| File | Action | Purpose |
|---|---|---|
| `app/Services/CheckoutTotals.php` | new | Pure totals computation |
| `app/Http/Controllers/Api/MobileController.php` | modify | Add `checkoutReview`, `buyNowPreview`, `buyNowPlace` methods; reuse `cartFor`, `findProduct`, `addressPayload`, `orderPayload`, `cartPayload` helpers |
| `routes/api.php` | modify | Register 3 new routes inside the `auth:sanctum` group |
| `tests/Unit/CheckoutTotalsTest.php` | new | Unit tests for totals math (6 cases) |
| `tests/Feature/MobileCheckoutReviewTest.php` | new | Feature tests for review endpoint |
| `tests/Feature/MobileBuyNowTest.php` | new | Feature tests for both buy-now endpoints |
| `docs/api-parity.md` | modify | Promote #1 + #2 to "at parity"; add new gap for mobile checkout divergence |
| `lang/errors.php` | modify | Add 2 keys if missing: `product_unavailable`, `stock_insufficient` |

### Data flow — `checkoutReview`

```
Client POST /api/mobile/checkout/review
  → auth:sanctum (Sanctum)
  → Accept-Language middleware sets app locale
  → MobileController::checkoutReview(Request, CouponService, CheckoutTotals)
    → validate(address_id?, notes?, coupon_code?)
    → cart = $this->cartFor($user)->load('items.product')
    → abort_if($cart->items->isEmpty(), 422, 'errors.cart_empty')
    → address = resolveAddress($user, $data['address_id'])  // helper to be extracted
    → abort_if(no address, 422, 'errors.delivery_address_required')
    → assert phone normalization (existing pattern from checkout())
    → items = cart.items.map(item => {quantity, unit_price: priceFor(user)})
    → shipping = Setting::getValue('shipping_fee', 5000)
    → couponPreview = code ? CouponService::preview(code, subtotal, user) : null
    → totals = CheckoutTotals::compute(items, shipping, couponPreview)
    → return JSON { data: { address, items, notes, totals, coupon_summary } }
```

### Data flow — `buyNowPreview`

Same as review except:
- `findProduct($idOrSlug)` instead of `cartFor()`
- `abort_unless($product->is_active, 422, 'errors.product_unavailable')`
- `quantity = clampQuantityToStock($requested, $product)` — sets `quantity_requested` in response only when clamping changed the value
- `abort_if($product->stock_quantity < 1, 422, 'errors.stock_insufficient')`
- Single synthetic line item: `[{quantity, unit_price: $product->priceFor($user)}]`

### Data flow — `buyNowPlace`

```
Client POST /api/mobile/products/{idOrSlug}/buy-now/place
  → auth:sanctum
  → validate(quantity:required, address_id?, notes?, coupon_code?, payment_method?)
  → product = findProduct($idOrSlug)
  → abort_unless($product->is_active, 422, 'errors.product_unavailable')
  → abort_if($product->stock_quantity < $quantity, 422, 'errors.stock_insufficient')
  → address resolution + phone check (same as review)
  → couponPreview = code ? CouponService::preview(...) : null
  → if coupon code given AND !valid: abort(422, $couponPreview['message'])
  → totals = CheckoutTotals::compute([{quantity, unit_price}], shipping, couponPreview)
  → $order = Order forceFill (same shape as MobileController::checkout)
  → $order->items()->create([product_id, quantity, unit_price, subtotal])
  → $product->update(['stock_quantity' => $stock - $quantity])
  → return JSON { order: orderPayload($order->fresh('items.product')) }
```

No DB transaction, no `lockForUpdate`, no inventory movement, no notification — same intentional simplicity as existing `MobileController::checkout()`.

## Shared address-resolution helper

Both review and buy-now (and existing `MobileController::checkout`) need the same `(int|null) $addressId → UserAddress` fallback chain. Currently each call site inlines it. We extract a small private helper:

```php
private function resolveOrderAddress(User $user, ?int $addressId): ?UserAddress
{
    return $user->addresses()->whereKey($addressId)->first()
        ?: $user->addresses()->where('is_default', true)->first()
        ?: $user->addresses()->first();
}
```

Existing `MobileController::checkout()` is refactored to use this helper (3-line change, behavior identical). New endpoints use it too.

## Error model — summary

| Trigger | Status | Body |
|---|---|---|
| No / invalid token | 401 | sanctum default |
| Product not found | 404 | sanctum/laravel default |
| Product inactive | 422 | `{ message: __('errors.product_unavailable') }` |
| Stock insufficient (place or preview-with-zero) | 422 | `{ message: __('errors.stock_insufficient') }` |
| Cart empty (review) | 422 | `{ message: __('errors.cart_empty') }` (existing key) |
| Address resolvable to nothing | 422 | `{ message: __('errors.delivery_address_required') }` (existing key) |
| Address phone fails normalization | 422 | `{ message: __('validation.phone') }` (existing key) |
| Invalid coupon (review/preview) | 200 | `coupon_summary.valid=false` with message |
| Invalid coupon (place) | 422 | `{ message: $couponPreview['message'] }` |
| Validation failure (bad field types) | 422 | Laravel validation envelope |

## Testing

### Unit — `tests/Unit/CheckoutTotalsTest.php` (6 cases)

| # | Test | Setup | Expected |
|---|---|---|---|
| 1 | `test_empty_items_returns_zero_subtotal_with_shipping_only` | items=[], shipping=5000, coupon=null | subtotal=0, discount=0, grand=5000 |
| 2 | `test_items_only_no_coupon` | items=[{2, 12500}], shipping=5000, coupon=null | subtotal=25000, discount=0, grand=30000 |
| 3 | `test_coupon_discount_applied` | items=[{1, 10000}], shipping=5000, coupon valid discount=2000 free_shipping=false | discount=2000, grand=13000 |
| 4 | `test_coupon_free_shipping_zeroes_shipping_line` | items=[{1, 10000}], shipping=5000, coupon valid discount=0 free_shipping=true | discount=5000, grand=10000 |
| 5 | `test_coupon_discount_plus_free_shipping_combine` | items=[{1, 10000}], shipping=5000, coupon valid discount=2000 free_shipping=true | discount=7000, grand=8000 |
| 6 | `test_grand_total_clamped_to_zero_when_discount_exceeds_subtotal_plus_shipping` | items=[{1, 1000}], shipping=5000, coupon valid discount=100000 | grand=0 (not negative) |
| 7 | `test_invalid_coupon_ignored` | items=[{1, 10000}], shipping=5000, coupon valid=false | discount=0, grand=15000 |

### Feature — `tests/Feature/MobileCheckoutReviewTest.php`

| Test | Assertion |
|---|---|
| `test_review_requires_auth` | 401 without token |
| `test_review_returns_422_for_empty_cart` | empty cart → 422 with `errors.cart_empty` |
| `test_review_returns_422_when_no_address` | user has no addresses → 422 |
| `test_review_returns_totals_for_default_address` | items + default address → 200 + totals match `subtotal + 5000` (default shipping) |
| `test_review_explicit_address_id_used` | passes `address_id` of non-default → response uses that address |
| `test_review_valid_coupon_applies_discount` | code → discount_amount > 0, coupon_summary.valid=true |
| `test_review_invalid_coupon_returns_200_with_failure_envelope` | bad code → 200 + coupon_summary.valid=false (NOT 422) |
| `test_review_free_shipping_coupon_zeroes_shipping_from_total` | free-shipping coupon → grand_total = subtotal (or 0 if discount also applies) |
| `test_review_uses_setting_for_shipping_fee` | overrides `shipping_fee` setting → response reflects new value |

### Feature — `tests/Feature/MobileBuyNowTest.php`

| Test | Assertion |
|---|---|
| `test_buy_now_preview_requires_auth` | 401 |
| `test_buy_now_preview_returns_404_for_unknown_product` | bad id → 404 |
| `test_buy_now_preview_returns_422_for_inactive_product` | `is_active=false` → 422 |
| `test_buy_now_preview_clamps_quantity_to_stock` | request 5, stock 3 → quantity=3, `quantity_requested=5` in response |
| `test_buy_now_preview_returns_422_when_out_of_stock` | stock=0 → 422 |
| `test_buy_now_preview_totals` | quantity=2 unit=12500 stock=10 → subtotal=25000, grand=30000 |
| `test_buy_now_preview_applies_coupon` | valid code → discount applied |
| `test_buy_now_place_requires_auth` | 401 |
| `test_buy_now_place_creates_order_with_correct_totals` | full flow → Order row inserted with subtotal/shipping/grand_total matching service |
| `test_buy_now_place_decrements_stock` | before: stock=5, after place(qty=2): stock=3 |
| `test_buy_now_place_hard_fails_on_insufficient_stock` | qty>stock → 422 + no Order created |
| `test_buy_now_place_hard_fails_on_invalid_coupon` | bad code → 422 + no Order created |
| `test_buy_now_place_creates_order_item_row` | order.items has one row with correct product_id/quantity/unit_price |
| `test_buy_now_place_respects_address_id` | uses specified address; delivery_address fields match |

## Audit doc update

`docs/api-parity.md`:
1. Move row #1 and row #2 from "Open gaps" → "What's already at parity ✅" with the commit SHA.
2. Strike #1+#2 in "Suggested order"; promote #8 (Wishlist payload) to next.
3. **Add a new gap row** (next available number) for *Mobile checkout (cart) lacks inventory movements, status history, notifications, lockForUpdate, and free_shipping coupon support — diverges from web `CheckoutController::store`*. Low-priority but tracked.
4. Add note that web `CheckoutController` math is a refactor candidate (extract to `CheckoutTotals` service).

## Out of scope (explicitly)

- Mobile cart-based `POST /api/mobile/checkout` divergence fix (separate audit gap, follow-up PR).
- Web `CheckoutController` refactor to use `CheckoutTotals` (separate refactor, byte-identical preserve).
- Idempotency keys (web doesn't have them; YAGNI).
- Payment gateway integration (`zaincash`, `fastpay`, `bank_transfer` are accepted as values but treated as `cash_on_delivery` at the data layer, same as existing mobile checkout).
- Buy-now mid-flight quantity update / cart-merge flow.
- API versioning (`/api/v1/...`).
