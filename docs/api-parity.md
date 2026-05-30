# Mobile API Parity Audit (U-9)

> Status snapshot as of 2026-05-31. Updated after every parity commit.
> Web routes from `routes/web.php`; mobile routes from `routes/api.php` (prefix `/api/mobile`).

## What's already at parity

| Capability | Web route | Mobile route |
|---|---|---|
| Login / register / forgot-password | `auth.*` | `POST /login` / `register` / `forgot-password` |
| Current user | - | `GET /me` |
| Logout, refresh token | `logout` | `POST /logout`, `POST /token/refresh` |
| Profile update | `PATCH /profile` | `PATCH /profile` |
| Profile password | - | `PATCH /profile/password` |
| **Profile delete (added 78dcd3b)** | `DELETE /profile` | `DELETE /profile` |
| Catalog browse | `shop.index`, `shop.show` | `GET /products`, `GET /products/{x}` |
| Categories list | `categories.index` | `GET /categories` |
| Brands list | (inline filter) | `GET /brands` |
| Vehicle fitments | (catalog filter) | `GET /vehicle-fitments` |
| VIN decode | - | `POST /vin/decode` |
| Coupon preview | (inline at checkout) | `POST /coupons/preview` |
| Cart CRUD | `cart.*` | `GET /cart`, `POST/PATCH/DELETE /cart/items[/{id}]` |
| Wishlist | `user.wishlist.*` | `GET /wishlist` (payload diff; see gap #8) |
| Product reviews | `shop.reviews.store` | `GET/POST /products/{x}/reviews` |
| Addresses CRUD | `account.addresses.*` | `GET/POST/PATCH/DELETE /addresses` |
| Set default address | `addresses.default` | `PATCH /addresses/{x}/default` |
| Standard checkout | `checkout.store` | `POST /checkout` |
| Orders list / detail | `account.orders.*` | `GET /orders`, `GET /orders/{x}` |
| Cancellation / return request | `orders.cancellation-request` / `orders.return-request` | `POST /orders/{x}/cancellation-request`, `return-request` |
| **Order invoice download (added this commit)** | `account.orders.invoice` | **`GET /orders/{order}/invoice`** |
| **Buy-Now checkout flow (added this commit)** | `checkout.options` / `checkout.buy-now` / `checkout.buy-now.place` | **`POST /products/{x}/buy-now/preview` + `POST /products/{x}/buy-now/place`** |
| **Checkout review (added this commit)** | `checkout.review` | **`POST /checkout/review`** |
| Notifications | - | `GET /notifications` |
| Localized errors (Accept-Language) | session-based | **header-based (added 78dcd3b)** |
| **User settings (all 7 slices + full)** | `user.settings.*` (8 routes: edit/update + appearance/language/notifications/security/communication/checkout/accessibility) | **`GET /settings` + 8 PATCH endpoints (added 50662ac)** |
| **Legal content pages (7)** | `legal.*` (8 routes incl. contact) | **`GET /legal` + `GET /legal/{slug}` (added this commit; 7 content pages)** |
| **Contact form** | `POST legal.contact.send` | **`POST /legal/contact` (added this commit)** |
| Dealer dashboard / products / orders / stock | (admin-only on web) | `GET /dealer/*`, `PATCH .../stock` |
| Admin module surface | full admin/ | `GET /admin/dashboard`, `/admin/{section}` + 5 patch endpoints |

## Open gaps (prioritized)

### High priority - business-critical missing flows

| # | Capability | Web | Mobile | Notes |
|---|---|---|---|---|
| - | No open high-priority gaps after current buy-now + checkout review work. | - | - | - |

### Medium priority - UX surface

| # | Capability | Web | Mobile | Notes |
|---|---|---|---|---|
| 7 | **Account activity feed** | `user.account.activity` | Missing | Returns user's recent orders, address changes, security events. Add `GET /account/activity?limit=N`. |
| 8 | **Wishlist payload shape mismatch** | `WishlistController` returns full product view models | `GET /mobile/wishlist` returns array of product_ids only | Mobile client has to make N follow-up calls for product details. Fix mobile to return paginated products consistent with `GET /products`. Schema break: bump API version or add `GET /wishlist?expand=products`. |

### Low priority - admin / dealer extras

| # | Capability | Web | Mobile | Notes |
|---|---|---|---|---|
| 9 | **Profile update field coverage** | `UpdateProfileRequest` accepts `first_name`, `last_name`, DOB, country, city, address_line1, notes | `MobileController::updateProfile` only accepts `name`, `email`, `phone` | Mobile profile screen can't write address/DOB. Decide whether mobile keeps a slim shape or grows to match. |
| 10 | **Account actions page** | `user.account.actions` | Missing | Suspected to be account-level destructive actions (deactivate, deletion request, export). Inspect controller before scoping. |
| 11 | **Per-locale routing alignment** | Path/session-driven; sitemap now emits `?lang=` | Header-driven (Accept-Language); no `?lang=` param respected | Acceptable; document the divergence in the OpenAPI spec when it is written. |
| 12 | **Mobile cart-checkout divergence** | `CheckoutController::store` does DB transaction + `lockForUpdate` + `InventoryMovement` + `UserCommunication` notification + status history + coupon usage tracking + free-shipping coupon handling | `MobileController::checkout` and new `buyNowPlace` skip all of the above | Bigger correctness gap: both mobile place endpoints can oversell stock under concurrency, leave no audit trail, and diverge on coupon handling. Fix by extracting an `OrderPlacement` service used by web + mobile. Scoped for a follow-up PR. |

### Infrastructure follow-ups (not strictly parity)

- **OpenAPI spec** - `docs/openapi.yaml` covering every `/api/mobile/*` endpoint. Will catch future drift via CI lint.
- **API versioning** - `/api/mobile/*` to `/api/v1/mobile/*` with the current path kept as alias until clients migrate. Required before any breaking change like gap #8.
- **Field-level audit** - many list endpoints return different field shapes than the web view models (e.g. `productPayload` vs `Product::toArray`). Worth aligning while OpenAPI is written.
- **Web `CheckoutController` totals math refactor** - extract the duplicated subtotal/shipping/coupon/grand-total math into `App\Services\CheckoutTotals`, already used by mobile review and buy-now endpoints. Keep it behavior-equivalent in a separate commit so the web change stays easy to review.

## Suggested order

1. ~~**#6 Settings sub-pages**~~ - **closed in 50662ac.**
2. ~~**#4 Legal pages content** + **#5 Contact form**~~ - **closed in 361254a.**
3. ~~**#3 Order invoice**~~ - **closed this commit.**
4. ~~**#1 + #2 Buy-Now + Checkout review**~~ - **closed this commit.**
5. **#8 Wishlist payload** - requires API version bump; do after the version scheme is decided.
6. **#12 Mobile cart-checkout divergence** - extract shared `OrderPlacement` service used by web + mobile; medium-priority correctness fix.
7. **#7 Account activity**, **#9 Profile field expansion**, **#10 Account actions** - UX polish, schedule when product priorities allow.

## How to extend this doc

Every parity-closing commit should:

1. Move its row from "Open gaps" to "What's already at parity" with the commit SHA appended.
2. Update the "Suggested order" list if scope changes.
3. Land alongside a feature test in `tests/Feature/Mobile*Test.php` so the parity claim is enforced by CI.
