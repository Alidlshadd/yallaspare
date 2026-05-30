# Mobile API Parity Audit (U-9)

> Status snapshot as of 2026-05-30. Updated after every parity commit.
> Web routes from `routes/web.php`; mobile routes from `routes/api.php` (prefix `/api/mobile`).

## What's already at parity ✅

| Capability | Web route | Mobile route |
|---|---|---|
| Login / register / forgot-password | `auth.*` | `POST /login` / `register` / `forgot-password` |
| Current user | — | `GET /me` |
| Logout, refresh token | `logout` | `POST /logout`, `POST /token/refresh` |
| Profile update | `PATCH /profile` | `PATCH /profile` |
| Profile password | — | `PATCH /profile/password` |
| **Profile delete (added 78dcd3b)** | `DELETE /profile` | `DELETE /profile` |
| Catalog browse | `shop.index`, `shop.show` | `GET /products`, `GET /products/{x}` |
| Categories list | `categories.index` | `GET /categories` |
| Brands list | (inline filter) | `GET /brands` |
| Vehicle fitments | (catalog filter) | `GET /vehicle-fitments` |
| VIN decode | — | `POST /vin/decode` |
| Coupon preview | (inline at checkout) | `POST /coupons/preview` |
| Cart CRUD | `cart.*` | `GET /cart`, `POST/PATCH/DELETE /cart/items[/{id}]` |
| Wishlist | `user.wishlist.*` | `GET /wishlist` (⚠ payload diff — see gap #8) |
| Product reviews | `shop.reviews.store` | `GET/POST /products/{x}/reviews` |
| Addresses CRUD | `account.addresses.*` | `GET/POST/PATCH/DELETE /addresses` |
| Set default address | `addresses.default` | `PATCH /addresses/{x}/default` |
| Standard checkout | `checkout.store` | `POST /checkout` |
| Orders list / detail | `account.orders.*` | `GET /orders`, `GET /orders/{x}` |
| Cancellation / return request | `orders.cancellation-request` / `orders.return-request` | `POST /orders/{x}/cancellation-request`, `return-request` |
| Notifications | — | `GET /notifications` |
| Localized errors (Accept-Language) | session-based | **header-based (added 78dcd3b)** |
| Dealer dashboard / products / orders / stock | (admin-only on web) | `GET /dealer/*`, `PATCH .../stock` |
| Admin module surface | full admin/ | `GET /admin/dashboard`, `/admin/{section}` + 5 patch endpoints |

## Open gaps ❌ (prioritized)

### High priority — business-critical missing flows

| # | Capability | Web | Mobile | Notes |
|---|---|---|---|---|
| 1 | **Buy-Now checkout flow** | `GET checkout.options/{product}`, `MATCH checkout.buy-now/{product}`, `POST checkout.buy-now.place` | ❌ | Single-product fast purchase. Common on PDP. Add as `POST /products/{x}/buy-now/preview` + `POST /products/{x}/buy-now/place`. |
| 2 | **Checkout review (GET form)** | `MATCH checkout.review` | ❌ | Server-side computed totals before placing. Mobile recomputes locally; review endpoint would prevent client/server mismatch. Add `POST /checkout/review` returning subtotal + shipping + discount + grand total. |
| 3 | **Order invoice download** | `account.orders.invoice` | ❌ | Returns PDF/HTML invoice. Add `GET /orders/{x}/invoice` returning a download URL or signed link. |
| 4 | **Legal pages content** | 8 routes under `legal.*` (privacy, terms, support, about, contact, return, shipping, distance-sales) | ❌ | Mobile app currently has to ship copies of these texts. Add `GET /legal/{slug}` returning `{title, html_body, updated_at}` per locale. |
| 5 | **Contact form submission** | `POST legal.contact.send` | ❌ | Add `POST /legal/contact` with same throttle. |

### Medium priority — UX surface

| # | Capability | Web | Mobile | Notes |
|---|---|---|---|---|
| 6 | **User settings sub-pages** | `user.settings.{notifications,communication,security,appearance,language,checkout,accessibility}` + their `PATCH` siblings | only `notify_order_updates` / `notify_promotions` / `notify_stock_alerts` writable via PATCH /profile? Verify. | Group as `GET /settings` returning all flags, `PATCH /settings/notifications`, `/security`, `/communication`, `/checkout`, `/accessibility` (each owns one slice). |
| 7 | **Account activity feed** | `user.account.activity` | ❌ | Returns user's recent orders, address changes, security events. Add `GET /account/activity?limit=N`. |
| 8 | **Wishlist payload shape mismatch** | `WishlistController` returns full product view models | `GET /mobile/wishlist` returns array of product_ids only | Mobile client has to make N follow-up calls for product details. Fix mobile to return paginated products consistent with `GET /products`. Schema break — bump API version or add `GET /wishlist?expand=products`. |

### Low priority — admin / dealer extras

| # | Capability | Web | Mobile | Notes |
|---|---|---|---|---|
| 9 | **Profile update field coverage** | `UpdateProfileRequest` accepts `first_name`, `last_name`, DOB, country, city, address_line1, notes | `MobileController::updateProfile` only accepts `name`, `email`, `phone` | Mobile profile screen can't write address/DOB. Decide whether mobile keeps a slim shape or grows to match. |
| 10 | **Account actions page** | `user.account.actions` | ❌ | Suspected to be account-level destructive actions (deactivate, deletion request, export). Inspect controller before scoping. |
| 11 | **Per-locale routing alignment** | Path/session-driven; sitemap now emits `?lang=` | Header-driven (Accept-Language); no `?lang=` param respected | Acceptable; document the divergence in the OpenAPI spec when it's written. |

### Infrastructure follow-ups (not strictly parity)

- **OpenAPI spec** — `docs/openapi.yaml` covering every `/api/mobile/*` endpoint. Will catch future drift via CI lint.
- **API versioning** — `/api/mobile/*` → `/api/v1/mobile/*` with the current path kept as alias until clients migrate. Required before any breaking change like gap #8.
- **Field-level audit** — many list endpoints return different field shapes than the web view models (e.g. `productPayload` vs `Product::toArray`). Worth aligning while OpenAPI is written.

## Suggested order

1. **#6 Settings sub-pages** — narrow scope, mostly a CRUD pattern repeated; high value for in-app preference management.
2. **#4 Legal pages content** + **#5 Contact form** — single small commit; closes the legal-compliance gap for the mobile app.
3. **#3 Order invoice** — backend reuses existing PDF/HTML renderer; mobile gets a download URL.
4. **#1 + #2 Buy-Now + Checkout review** — single PR; behavior-equivalent to the web flow.
5. **#8 Wishlist payload** — requires API version bump; do after #1-5 are settled.
6. **#7 Account activity**, **#9 Profile field expansion**, **#10 Account actions** — UX polish, schedule when product priorities allow.

## How to extend this doc

Every parity-closing commit should:
1. Move its row from "Open gaps" → "What's already at parity" with the commit SHA appended.
2. Update the "Suggested order" list if scope changes.
3. Land alongside a feature test in `tests/Feature/Mobile*Test.php` so the parity claim is enforced by CI.
