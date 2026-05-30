# Mobile Order Invoice Download — Design

> Closes gap **#3** from `docs/api-parity.md` (Mobile API Parity Audit U-9).
> Adds `GET /api/mobile/orders/{order}/invoice` returning the same PDF the web account dashboard already renders.

## Goal

A mobile-authenticated user can download a PDF invoice for one of their own orders. The PDF content, layout, and locale behavior match the existing web endpoint (`AccountOrdersController::invoice`). No new rendering, branding, or schema changes — just a parity endpoint.

## Endpoint

**Route**: `GET /api/mobile/orders/{order}/invoice`
**Auth**: `auth:sanctum` (existing middleware group in `routes/api.php`)
**Owner check**: `abort_unless($order->user_id === $request->user()->id, 403)` (consistent with `MobileController::order`).

### Responses

| Status | Body | Notes |
|---|---|---|
| 200 | PDF binary | `Content-Type: application/pdf`; `Content-Disposition: attachment; filename="invoice-{id}-{locale}.pdf"` |
| 401 | (auth middleware default) | No bearer token |
| 403 | `{ "message": ... }` | Order belongs to another user |
| 404 | (route-model binding default) | Order ID does not exist |

### Locale precedence (`?lang=` overrides `Accept-Language`)

Resolution order — first hit wins. This matches the existing web `invoiceLocale()` exactly, so the web path's behavior does not change after the refactor.

1. `?lang=` query parameter, if in whitelist `{en, ar, ku}` (web also accepts `?locale=` as alias)
2. Order owner's `users.locale_preference`, if in whitelist
3. Authed user's `users.locale_preference`, if in whitelist (covers admin/dealer downloading another user's order — not currently a mobile feature, but parity with web's existing fallback)
4. `app()->getLocale()` (set by Accept-Language middleware on mobile, by session on web), if in whitelist
5. Default `en`

Why `Accept-Language` ranks below `locale_preference`: a user with `locale_preference=ar` who happens to be browsing the app in English should still get an Arabic invoice unless they explicitly ask for English via `?lang=en`. This is the web behavior today; preserving it avoids a regression for any web user with the two preferences mismatched.

Filename always reflects the resolved locale: `invoice-{id}-{locale}.pdf`.

## Architecture

The web endpoint inlines ~40 lines of PDF assembly + a private `invoiceLogoPath()` helper. We extract this to a small service so the web and mobile controllers can share a single implementation. The web behavior must remain byte-identical after the refactor.

### New service: `App\Services\InvoiceRenderer`

```php
final class InvoiceRenderer
{
    public function resolveLocale(?string $explicit, Order $order, ?User $user): string;
    public function render(Order $order, string $locale): \Barryvdh\DomPDF\PDF;
}
```

- `resolveLocale($explicit, $order, $user)` — implements the precedence list above. `$explicit` is the raw `?lang=` value (controller passes `$request->query('lang')`); `$user` is the authenticated user (may be null only if invoked outside an authed flow, but both call sites pass it).
- `render($order, $locale)` — eager-loads `user` + `items.product`, computes `subtotal/shipping/discount/grandTotal`, sets `app()->setLocale($locale)` inside a try/finally that restores the previous locale, calls `Pdf::loadView('admin.orders.invoice', [...])->setPaper('a4')`, and returns the `PDF` instance. **Does not** call `download()`/`stream()` — that's the controller's choice.
- The `invoiceNumber` format (`INV-{Y}-{padded-id}`) and `currency = 'IQD'` constants stay where they are now: inside the service.

### Helper move: `Branding::invoiceLogoPath()`

The current `AccountOrdersController::invoiceLogoPath()` is a static-style resolver that returns an absolute filesystem path (DomPDF needs a path, not a URL). It belongs in `App\Support\Branding` alongside `logoUrlFromValue()`. The mobile controller does not call it directly — only the service does — so the public method serves the service.

### Controller wiring

**`MobileController::orderInvoice`** (new, ~8 lines):
```php
public function orderInvoice(Request $request, Order $order, InvoiceRenderer $renderer)
{
    abort_unless($order->user_id === $request->user()->id, 403);
    $locale = $renderer->resolveLocale(
        $request->query('lang'),
        $order,
        $request->user(),
    );
    return $renderer->render($order, $locale)
        ->download("invoice-{$order->id}-{$locale}.pdf");
}
```

**`AccountOrdersController::invoice`** (refactored, ~12 lines):
```php
public function invoice(Request $request, Order $order, InvoiceRenderer $renderer): Response
{
    $order = auth()->user()->orders()->whereKey($order->id)->firstOrFail();
    $locale = $renderer->resolveLocale($request->query('lang') ?? $request->query('locale'), $order, auth()->user());
    return $renderer->render($order, $locale)
        ->download("invoice-{$order->id}-{$locale}.pdf");
}
```
- Web preserves the existing `?locale=` alias by passing it as fallback to `?lang=` (web has historically accepted both — see lines 110–111 of the original).
- Private `invoiceLogoPath()` and `invoiceLocale()` methods are deleted from `AccountOrdersController`.

## Data Flow

```
Mobile client
  → GET /api/mobile/orders/42/invoice?lang=ar  (Authorization: Bearer ...)
  → Accept-Language middleware sets app locale (irrelevant here because ?lang wins)
  → auth:sanctum resolves user
  → MobileController::orderInvoice
    → owner check
    → InvoiceRenderer::resolveLocale('ar', $order, $user) → 'ar'
    → InvoiceRenderer::render($order, 'ar')
      → eager-load relations
      → setLocale('ar') (try)
      → Pdf::loadView('admin.orders.invoice', [...])->setPaper('a4')
      → setLocale($previous) (finally)
    → ->download('invoice-42-ar.pdf')
  → Symfony BinaryFileResponse → client
```

## Error Handling

| Case | Behavior |
|---|---|
| No token / invalid token | Sanctum middleware → 401 |
| Order ID does not exist | Route-model binding → 404 |
| Order belongs to another user | `abort(403)` |
| Order has no items (defensive) | Renderer proceeds; totals default to `0`. The Blade view already tolerates an empty `items` collection (it does today via web path). |
| DomPDF / Blade exception | Bubbles up as 500 (Laravel exception handler). Not caught — same as web today. |
| `?lang=` set to a non-whitelisted value (e.g. `tr`) | Silently ignored; falls through to the next precedence step. Mirrors web's current behavior. |

## Testing — `tests/Feature/MobileInvoiceTest.php`

Uses `RefreshDatabase` and follows the established pattern from `MobileLegalAndContactTest`. Sanctum auth via `Sanctum::actingAs($user)`.

| # | Test | Assertion |
|---|---|---|
| 1 | `test_invoice_requires_authentication` | No token → 401 |
| 2 | `test_invoice_rejects_non_owner` | Token user ≠ order owner → 403 |
| 3 | `test_invoice_returns_404_for_missing_order` | Nonexistent ID → 404 |
| 4 | `test_invoice_returns_pdf_with_attachment_header` | 200, `Content-Type: application/pdf`, `Content-Disposition` contains `attachment` and `filename=invoice-{id}-en.pdf` |
| 5 | `test_invoice_lang_query_overrides_everything` | `?lang=ar` + `Accept-Language: ku` + user `locale_preference=en` → filename `invoice-{id}-ar.pdf` |
| 6 | `test_invoice_user_locale_preference_beats_accept_language` | No `?lang`, `Accept-Language: ku`, user `locale_preference=ar` → filename ends `-ar.pdf` (preserves web behavior) |
| 7 | `test_invoice_uses_accept_language_when_no_user_preference` | No `?lang`, `Accept-Language: ku`, user `locale_preference` null → filename ends `-ku.pdf` |
| 8 | `test_invoice_falls_back_to_english_when_nothing_set` | No header, no query, no user preference → filename ends `-en.pdf` |
| 9 | `test_invoice_ignores_unknown_lang_value` | `?lang=tr` → behaves like the next precedence step |

For PDF assertions: check headers only (`Content-Type`, `Content-Disposition`); do not parse the PDF body. The body is DomPDF output and is already exercised by the existing web flow.

## Audit doc update

`docs/api-parity.md`:
1. Move row #3 from "High priority — business-critical missing flows" → "What's already at parity ✅" with the commit SHA.
2. Strike #3 in "Suggested order"; promote #1+#2 to next.

## Out of scope

- Signed URLs / shareable invoice links (not requested; YAGNI).
- Caching the rendered PDF (DomPDF is fast enough at this volume; revisit only if perf becomes an issue).
- HTML-only fallback or alternative invoice formats.
- Admin-side invoice download endpoint (separate parity item, not in U-9 scope).
- API versioning (`/api/v1/...`) — separate infrastructure follow-up in the audit doc.

## Implementation order (preview for writing-plans)

1. Add `Branding::invoiceLogoPath()` static method (move + adapt the private helper).
2. Add `App\Services\InvoiceRenderer` with `resolveLocale()` and `render()`.
3. Refactor `AccountOrdersController::invoice` to delegate to the service; delete private helpers. Run existing web invoice manually or via any existing test to confirm parity.
4. Add `MobileController::orderInvoice` + route in `routes/api.php`.
5. Write `tests/Feature/MobileInvoiceTest.php`.
6. Update `docs/api-parity.md` (move row, strike entry).
7. Commit as `feat(api): mobile order invoice download (U-9 #3)`.
