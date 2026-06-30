# Admin Analytics Module — Design

**Date:** 2026-06-30
**Status:** Approved (user-approved via brainstorming, ready for implementation plan)
**Owner:** YallaSpare admin
**Target branch:** to be created off `upgrade/laravel-12-security`

---

## 1. Goal

Add a privacy-respecting, performant Analytics module to the admin panel so the operator can see:

- How many people are visiting the site (today / week / month)
- Which products are viewed and clicked the most
- Which search keywords customers are typing
- Top performers for cart adds and wishlist clicks

The module must not change any existing flow (dashboard, products, orders, cart, checkout) and must not slow page rendering.

---

## 2. Non-goals

- No funnel analytics, no per-user journey, no cohort analysis (YAGNI).
- No third-party (GA / Mixpanel / Plausible) integration in this iteration.
- No realtime websockets — the admin page reloads on visit; 5–10 min cache is enough.
- No multi-tenant scoping (single-store project).

---

## 3. Architecture

```
HTTP request
   │
   ▼
web middleware group
   │   (existing: cookies, session, CSRF, locale, ...)
   ▼
RecordAnalyticsEvent  ◄── new terminable middleware
   │
   ├─ Bot UA? → skip
   ├─ Asset URL (/.js /.css /.png /.woff …)? → skip
   ├─ HTTP method != GET? → skip the page_view path; explicit dispatches still run
   │
   └─ in terminate(): AnalyticsRecorder::record('page_view', …)
                                 │
                                 ├─ INSERT into analytics_events
                                 └─ (no counter update for page_view)

Explicit dispatches from controllers (synchronous, after main work succeeds):
- ProductController@show         → AnalyticsRecorder::record('product_view',     ['product_id'=>...])
                                    └─ also increments product_analytics.views_count + last_viewed_at
- CartController@store           → AnalyticsRecorder::record('add_to_cart',      ['product_id'=>...,'qty'=>...])
                                    └─ also increments product_analytics.add_to_cart_count
- WishlistController@store       → AnalyticsRecorder::record('wishlist_click',   ['product_id'=>...])
                                    └─ also increments product_analytics.wishlist_count
- CheckoutController@start*      → AnalyticsRecorder::record('checkout_started', ['cart_total'=>...])
- Order::created (observer)      → AnalyticsRecorder::record('order_completed',  ['order_id'=>...,'total'=>...])
- ShopController search          → AnalyticsRecorder::recordSearch($rawKeyword)
                                    └─ normalizes, inserts event, upserts search_analytics counter
```

`AnalyticsRecorder` is the single seam — controllers do not touch DB directly. Every insert uses `DB::table()` (no Eloquent boot cost) and every counter update uses `->increment()` (atomic, no race condition).

**Why terminable middleware**: Laravel sends the response to the user inside `Kernel::handle()` and then calls `terminate()` for any terminable middleware. The DB write happens after the user already has bytes — page is not blocked.

---

## 4. Database

Three new tables. Migrations use Laravel 12 syntax and FK to existing `products` / `users` tables.

### 4.1 `analytics_events` (raw event log)

| Column            | Type             | Notes                                          |
| ----------------- | ---------------- | ---------------------------------------------- |
| id                | bigIncrements    |                                                |
| event_type        | string(40)       | indexed; values listed in §6.1                 |
| product_id        | bigInt nullable  | indexed; FK products(id) nullOnDelete          |
| user_id           | bigInt nullable  | indexed; FK users(id) nullOnDelete             |
| session_id        | char(40) nullable| indexed; hashed Laravel session id (sha256 trimmed to 40) |
| ip_hash           | char(64) nullable| sha256(ip . app_key); raw IP never stored      |
| user_agent_hash   | char(64) nullable| sha256(ua . app_key)                           |
| url               | string(2048) nullable | normalized path + query (no fragments)    |
| referrer          | string(2048) nullable | from `$request->headers->get('referer')`  |
| metadata          | json nullable    | event-specific payload (see §6.1)              |
| created_at        | timestamp        | indexed                                        |

**Composite indexes:**

- `(event_type, created_at)` — dashboard time-range queries
- `(product_id, event_type, created_at)` — per-product breakdowns

**Retention:** 365 days (user-selected). `analytics:prune` command runs daily at 03:00.

### 4.2 `product_analytics` (denormalized counters, never deleted)

| Column              | Type              | Notes                                |
| ------------------- | ----------------- | ------------------------------------ |
| id                  | bigIncrements     |                                      |
| product_id          | bigInt unique     | FK products(id) cascadeOnDelete      |
| views_count         | unsignedBigInteger default 0 |                          |
| add_to_cart_count   | unsignedBigInteger default 0 |                          |
| wishlist_count      | unsignedBigInteger default 0 |                          |
| last_viewed_at      | timestamp nullable|                                      |
| timestamps          | created_at/updated_at |                                  |

A row is created on first event for a product via `updateOrInsert`. Counters update via `where(product_id, $id)->increment(...)`.

### 4.3 `search_analytics` (per normalized keyword)

| Column              | Type             | Notes                                                      |
| ------------------- | ---------------- | ---------------------------------------------------------- |
| id                  | bigIncrements    |                                                            |
| keyword             | string(80) unique| normalized (lowercase, trimmed, collapsed whitespace)      |
| search_count        | unsignedInteger default 1 |                                                  |
| last_searched_at    | timestamp        |                                                            |
| timestamps          | created_at/updated_at |                                                       |

**Indexes:** `(search_count)` for top-N ordering.

---

## 5. Privacy & security

- Raw IP **never** stored. Always `hash('sha256', $ip . config('app.key'))`.
- User-Agent stored only as hash (used to deduplicate guests, never displayed).
- Session id stored as a 40-char hash of `session()->getId()` so a leaked DB cannot resume sessions.
- Bot filter (`App\Support\BotDetector`): case-insensitive regex against UA covering `bot`, `crawl`, `spider`, `slurp`, `bingpreview`, `facebookexternalhit`, `headlesschrome`, `lighthouse`. Excluded events do not insert.
- Search keywords passed through `SearchKeywordNormalizer`:
  - `mb_strtolower($input)`
  - `trim` + collapse runs of whitespace to one space (`preg_replace('/\s+/u', ' ', …)`)
  - strip ASCII control chars (0x00–0x1F, 0x7F)
  - reject if length < 2 or > 80 after normalization
  - reject if regex matches `^[\W\d_]+$` (only symbols/digits) — stops `???`, `123456789`
- Admin view renders keywords with Blade `{{ }}` auto-escape (XSS safe by default).
- Routes mounted inside the same group as `admin.dashboard` — picks up `admin` + `admin.2fa` middleware automatically. Verified by `AnalyticsAdminAccessTest`.
- Recorder swallows DB exceptions and logs to `analytics` channel — analytics failures must never break the user request.

---

## 6. Event contract

### 6.1 Event types and metadata

| event_type       | Trigger                          | Required metadata                       |
| ---------------- | -------------------------------- | --------------------------------------- |
| `page_view`      | middleware, every GET HTML       | `{}` (url + referrer at top level)      |
| `product_view`   | ProductController@show           | `{}` (product_id at top level)          |
| `add_to_cart`    | CartController@store success     | `{ "qty": int }` (product_id at top)    |
| `wishlist_click` | WishlistController@store success | `{}` (product_id at top)                |
| `checkout_started` | CheckoutController start path  | `{ "cart_total": "12.50" }`             |
| `order_completed`| Order::created observer          | `{ "order_id": int, "total": "12.50" }` |
| `search`         | Shop search with non-empty query | `{ "keyword": "brake pad", "results_count": 12 }` |

### 6.2 Recorder API

```php
namespace App\Services\Analytics;

class AnalyticsRecorder
{
    public function record(string $type, array $payload = []): void;   // generic event
    public function recordSearch(string $rawKeyword, int $resultsCount): void; // normalizes + upserts counter
}
```

Both methods are `void` — fire-and-forget semantics. Internal failures are caught and logged.

---

## 7. Admin UI

Layout: **Option 1** (chosen by user) — see `public/admin-analytics-preview.html` for the visual reference.

### 7.1 Route

```php
// inside the existing admin route group (already has admin + admin.2fa middleware):
Route::get('/admin/analytics', [Admin\AnalyticsController::class, 'index'])
    ->name('admin.analytics.index');
```

### 7.2 Controller responsibilities

`Admin\AnalyticsController@index($request)`:

1. Resolve range from `?range=7d|30d|90d|1y` (default `30d`, whitelist enforced).
2. Compute via cached service methods:
   - Visitor cards: today / week / month (distinct visitor ids in window)
   - KPI cards: total views, total add-to-cart, total wishlist, top product (by views in range)
   - Top performers: top product (views), top product (cart), top product (wishlist), top search
   - Top-10 viewed products, top-10 cart adds, top-10 searches
   - Two 7-day series for visitors and product views (for Chart.js)
3. Pass everything to `admin.analytics.index` view as an array.

A single `AnalyticsQueryService` class owns these queries. Each result is cached 5–10 minutes (cache tag: `analytics`, key includes range).

### 7.3 View pieces

`resources/views/admin/analytics/index.blade.php` extends `x-app-layout` (same as `admin/dashboard.blade.php`). Sections in order, matching the preview:

1. Time-range chip bar (reused pattern from dashboard).
2. 3 navy hero cards (Today / Week / Month) with SVG sparklines (inline, dashboard-style).
3. 4 KPI cards with colored side-strips (Page Views / Add to Cart / Wishlist / Top Product).
4. Mission Control dark card with 4 conic-gauge cards (Top viewed / Top cart / Top wishlist / Top search).
5. 3 top-N tables in a 3-column grid.
6. 2 Chart.js charts at the bottom (visitors line, product views bar).

### 7.4 Sidebar

`resources/views/layouts/app.blade.php` already renders the admin sidebar inline. Add one `<a class="admin-nav-link">…` immediately after the existing Dashboard link, with icon `fas fa-chart-pie`, label `__('Analytics')`, active when `request()->routeIs('admin.analytics.*')`. Also add `'admin.analytics.*' => __('Site Analytics')` to the `$adminPageTitlePatterns` map for the topbar title.

### 7.5 Charts

Add Chart.js via npm:

```
npm install chart.js
```

Import once in `resources/js/app.js`:

```js
import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);
window.Chart = Chart;
```

The view passes data as `@json($series)` into two `<canvas>` elements, initialized inside a small inline `<script>` (no Vue/Alpine — just direct `new Chart(...)`). One line chart, one bar chart, both with built-in tooltip + zoom on hover.

---

## 8. Aggregation strategy

- **Counters** (`product_analytics`, `search_analytics`): incremented at write time → reads are O(1) lookups.
- **Visitor counts**: `SELECT COUNT(DISTINCT COALESCE(user_id::text, session_id, ip_hash)) FROM analytics_events WHERE event_type='page_view' AND created_at >= ?`. Cache 5 min.
- **7-day series**: `SELECT DATE(created_at) d, COUNT(*) c FROM analytics_events WHERE … GROUP BY d`. Cache 10 min.
- **Top-N from counters**: `SELECT … ORDER BY views_count DESC LIMIT 10`. Cache 5 min. Joins `products` for name/slug.

No materialized daily aggregate table. We can add one later if 365 days × moderate traffic causes slow group-bys, but it is YAGNI for now and would complicate writes.

---

## 9. Pruning

```
php artisan analytics:prune --days=365
```

- Deletes from `analytics_events` where `created_at < now() - days`.
- Does **not** touch `product_analytics` / `search_analytics` (counters are denormalized history).
- Scheduled in `app/Console/Kernel.php` at `03:00` daily, `withoutOverlapping()`, `onOneServer()`.
- Logs deleted count to `analytics` channel.

---

## 10. Files plan

### Create (15 files)

```
app/Http/Middleware/RecordAnalyticsEvent.php
app/Http/Controllers/Admin/AnalyticsController.php
app/Models/AnalyticsEvent.php
app/Models/ProductAnalytic.php
app/Models/SearchAnalytic.php
app/Services/Analytics/AnalyticsRecorder.php
app/Services/Analytics/AnalyticsQueryService.php
app/Support/BotDetector.php
app/Support/SearchKeywordNormalizer.php
app/Console/Commands/PruneAnalyticsEvents.php
database/migrations/2026_06_30_000001_create_analytics_events_table.php
database/migrations/2026_06_30_000002_create_product_analytics_table.php
database/migrations/2026_06_30_000003_create_search_analytics_table.php
resources/views/admin/analytics/index.blade.php
tests/Feature/Analytics/ (5 test files — see §11)
```

### Edit (≤8 files)

```
app/Http/Kernel.php                                  push RecordAnalyticsEvent to web group
app/Console/Kernel.php                               schedule analytics:prune daily 03:00
routes/web.php                                       add admin.analytics.index inside admin group
app/Http/Controllers/ProductController.php (or User\ShopController where show() lives) → dispatch product_view
app/Http/Controllers/CartController.php              dispatch add_to_cart on success
app/Http/Controllers/User/WishlistController.php     dispatch wishlist_click on success
app/Http/Controllers/CheckoutController.php          dispatch checkout_started at start path
app/Models/Order.php (or new OrderObserver)          dispatch order_completed in created event
app/Http/Controllers/ShopController.php OR User\ShopController.php  dispatch search when query present
resources/views/layouts/app.blade.php                add sidebar nav link + page title pattern
resources/js/app.js                                  import Chart.js
package.json                                         add chart.js dep
```

Confirm the exact controller paths during the implementation step — the file plan above lists candidates discovered during brainstorming.

---

## 11. Tests

All under `tests/Feature/Analytics/`. PHPUnit (project standard).

1. **ProductViewTrackingTest** — GET `/products/{slug}` (or however product detail is routed) increments `product_analytics.views_count` by 1, writes one `analytics_events` row with `event_type='product_view'`, `product_id` set, no raw IP stored (`ip_hash` is a 64-char hex string).
2. **AddToCartTrackingTest** — successful cart add writes `event_type='add_to_cart'`, increments `product_analytics.add_to_cart_count`.
3. **SearchTrackingTest** — searching `BraKe  pad ` upserts a single row with `keyword='brake pad'`, `search_count=1`; second identical query → `search_count=2`, `last_searched_at` updated.
4. **AnalyticsAdminAccessTest** — guest → 302 to login; non-admin user → 403; admin (verified 2FA) → 200, view renders.
5. **AnalyticsPruneCommandTest** — seed events at `-366 days` and `-1 day`, run `php artisan analytics:prune --days=365`, assert only old row deleted, counters untouched.

Each test uses `RefreshDatabase` and fakes the clock where time arithmetic matters (`Carbon::setTestNow`).

---

## 12. Performance budget

- Tracking middleware adds **0 ms** to TTFB (runs in `terminate()` after response is sent).
- Each event = 1 INSERT + (for typed events) 1 UPDATE-by-PK. Both indexed, both sub-millisecond on a healthy DB.
- Admin analytics page = 10–14 cached aggregate queries on first uncached load (~30–80 ms total), <5 ms once cached.
- `analytics_events` growth: ~50 bytes per row average. 1M events ≈ 50 MB; with `(event_type, created_at)` and `(product_id, event_type, created_at)` indexes, total ~110 MB per million events. 365-day retention keeps the table predictable.

If raw event volume becomes excessive in production, drop to 180-day retention or introduce a daily aggregate table in a follow-up — no code change to the read path needed (`AnalyticsQueryService` would just point at the new source).

---

## 13. Decisions log (from brainstorming)

| Question | Decision |
| --- | --- |
| Tracking mechanism | Server-side terminable middleware |
| Retention | 365 days |
| Bot filter | Yes — simple User-Agent regex |
| Charts | Chart.js (npm dep added) |
| Layout | Option 1: Hero + KPI strip + Mission Control + Tables |

---

## 14. Out of scope (for clarity)

- Geographic / country breakdown
- Device / browser breakdown
- Per-user activity timeline
- Real-time websocket updates
- CSV export of analytics data
- Referrer source grouping (Direct / Google / Social)

Any of these can be added later without touching the schema — only new query methods + UI sections.
