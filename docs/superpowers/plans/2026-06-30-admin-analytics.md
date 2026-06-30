# Admin Analytics Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add an admin Analytics module that tracks visitors, product views, cart/wishlist actions, and search keywords, then surfaces them on a single admin dashboard page — without slowing the user-facing site.

**Architecture:** Server-side terminable middleware records `page_view` events fire-and-forget after each response is sent. Controllers explicitly call a single `AnalyticsRecorder` seam for typed events (product_view, add_to_cart, wishlist_click, search, checkout_started, order_completed). Counters live in two denormalized tables (`product_analytics`, `search_analytics`); a raw log lives in `analytics_events` with 365-day retention pruned by a scheduled command. Admin dashboard reads through a small `AnalyticsQueryService` with 5–10 minute caching, renders the approved Option-1 layout, and draws two Chart.js charts.

**Tech Stack:** Laravel 12, PHPUnit 11, Tailwind, Blade, Chart.js (new npm dep), MySQL (or whatever the project DB is).

**Spec:** `docs/superpowers/specs/2026-06-30-admin-analytics-design.md`

---

## File structure

### New files

```
app/
├── Http/
│   ├── Middleware/
│   │   └── RecordAnalyticsEvent.php           Terminable middleware: per-request page_view
│   └── Controllers/
│       └── Admin/
│           └── AnalyticsController.php        GET /admin/analytics
├── Models/
│   ├── AnalyticsEvent.php                     Raw event log model
│   ├── ProductAnalytic.php                    Per-product denormalized counters
│   └── SearchAnalytic.php                     Per-keyword counter
├── Services/
│   └── Analytics/
│       ├── AnalyticsRecorder.php              Single write seam (insert + atomic counter increment)
│       └── AnalyticsQueryService.php          Cached aggregate reads for the admin page
├── Support/
│   ├── BotDetector.php                        UA regex matcher
│   └── SearchKeywordNormalizer.php            Pure normalization + validation
└── Console/
    └── Commands/
        └── PruneAnalyticsEvents.php           php artisan analytics:prune --days=365

database/migrations/
├── 2026_06_30_000001_create_analytics_events_table.php
├── 2026_06_30_000002_create_product_analytics_table.php
└── 2026_06_30_000003_create_search_analytics_table.php

resources/views/admin/analytics/
└── index.blade.php                            Option-1 layout (hero + KPI + mission control + tables + charts)

tests/
├── Unit/
│   └── Support/
│       ├── BotDetectorTest.php
│       └── SearchKeywordNormalizerTest.php
└── Feature/
    └── Analytics/
        ├── ProductViewTrackingTest.php
        ├── AddToCartTrackingTest.php
        ├── WishlistTrackingTest.php
        ├── SearchTrackingTest.php
        ├── AnalyticsAdminAccessTest.php
        └── AnalyticsPruneCommandTest.php
```

### Modified files

```
app/Http/Kernel.php                            Push RecordAnalyticsEvent to web group + alias 'analytics'
app/Console/Kernel.php                         Schedule analytics:prune daily 03:00
routes/web.php                                 Add admin.analytics.index inside admin group
app/Http/Controllers/ShopController.php        Dispatch product_view in show(); dispatch search in shop autocomplete/search path if applicable
app/Http/Controllers/User/ShopController.php   Dispatch search event in shop() when request has 'search'
app/Http/Controllers/CartController.php        Dispatch add_to_cart at end of add() success path
app/Http/Controllers/User/WishlistController.php   Dispatch wishlist_click at end of store()
app/Http/Controllers/CheckoutController.php    Dispatch checkout_started in options()/buyNow()
app/Models/Order.php (or new App\Observers\OrderAnalyticsObserver) Dispatch order_completed in created event
resources/views/layouts/app.blade.php          Add sidebar nav link + topbar title mapping
resources/js/app.js                            Import + register Chart.js
package.json                                   Add chart.js dependency
```

---

## Conventions used in this plan

- **Test framework:** PHPUnit 11 (see `composer.json`). Test classes extend `Tests\TestCase`; feature tests use `RefreshDatabase`. The project does not use Pest.
- **Test command:** `vendor/bin/phpunit --filter <TestName>`
- **Migration command:** `php artisan migrate`
- **Commit style:** matches existing repo history — short imperative subject (e.g. `Add analytics events table`). Include `Co-Authored-By: Claude Opus 4.7 <noreply@anthropic.com>` on every commit.
- **Working branch:** continue on `upgrade/laravel-12-security` (current). If you prefer isolation, branch off with `git switch -c feature/admin-analytics` before Task 1 — the spec, preview, and this plan are already committed.

---

## Task 1: Migrations, models, and table scaffolding

**Files:**
- Create: `database/migrations/2026_06_30_000001_create_analytics_events_table.php`
- Create: `database/migrations/2026_06_30_000002_create_product_analytics_table.php`
- Create: `database/migrations/2026_06_30_000003_create_search_analytics_table.php`
- Create: `app/Models/AnalyticsEvent.php`
- Create: `app/Models/ProductAnalytic.php`
- Create: `app/Models/SearchAnalytic.php`

- [ ] **Step 1: Create `analytics_events` migration**

`database/migrations/2026_06_30_000001_create_analytics_events_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('event_type', 40);
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->char('session_id', 40)->nullable();
            $table->char('ip_hash', 64)->nullable();
            $table->char('user_agent_hash', 64)->nullable();
            $table->string('url', 2048)->nullable();
            $table->string('referrer', 2048)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('event_type');
            $table->index('product_id');
            $table->index('user_id');
            $table->index('session_id');
            $table->index('created_at');
            $table->index(['event_type', 'created_at'], 'analytics_events_type_time_idx');
            $table->index(['product_id', 'event_type', 'created_at'], 'analytics_events_product_type_time_idx');

            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
```

- [ ] **Step 2: Create `product_analytics` migration**

`database/migrations/2026_06_30_000002_create_product_analytics_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_analytics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('product_id')->unique();
            $table->unsignedBigInteger('views_count')->default(0);
            $table->unsignedBigInteger('add_to_cart_count')->default(0);
            $table->unsignedBigInteger('wishlist_count')->default(0);
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_analytics');
    }
};
```

- [ ] **Step 3: Create `search_analytics` migration**

`database/migrations/2026_06_30_000003_create_search_analytics_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('search_analytics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('keyword', 80)->unique();
            $table->unsignedInteger('search_count')->default(1);
            $table->timestamp('last_searched_at');
            $table->timestamps();

            $table->index('search_count');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_analytics');
    }
};
```

- [ ] **Step 4: Create the three Eloquent models**

`app/Models/AnalyticsEvent.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsEvent extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'event_type', 'product_id', 'user_id', 'session_id',
        'ip_hash', 'user_agent_hash', 'url', 'referrer', 'metadata', 'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

`app/Models/ProductAnalytic.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAnalytic extends Model
{
    use HasFactory;

    protected $table = 'product_analytics';

    protected $fillable = [
        'product_id', 'views_count', 'add_to_cart_count',
        'wishlist_count', 'last_viewed_at',
    ];

    protected $casts = [
        'last_viewed_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
```

`app/Models/SearchAnalytic.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SearchAnalytic extends Model
{
    use HasFactory;

    protected $table = 'search_analytics';

    protected $fillable = ['keyword', 'search_count', 'last_searched_at'];

    protected $casts = [
        'last_searched_at' => 'datetime',
    ];
}
```

- [ ] **Step 5: Run migrations**

Run: `php artisan migrate`
Expected: three new tables created.

Verify with: `php artisan db:show` (look for `analytics_events`, `product_analytics`, `search_analytics`).

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_06_30_00000{1,2,3}_*.php app/Models/AnalyticsEvent.php app/Models/ProductAnalytic.php app/Models/SearchAnalytic.php
git commit -m "$(cat <<'EOF'
Add analytics tables and Eloquent models

Three new tables back the analytics module: analytics_events (raw
log with 365-day retention), product_analytics and search_analytics
(denormalized counters that survive pruning).

Co-Authored-By: Claude Opus 4.7 <noreply@anthropic.com>
EOF
)"
```

---

## Task 2: `BotDetector` support class (TDD)

**Files:**
- Test: `tests/Unit/Support/BotDetectorTest.php`
- Create: `app/Support/BotDetector.php`

- [ ] **Step 1: Write the failing test**

`tests/Unit/Support/BotDetectorTest.php`:

```php
<?php

namespace Tests\Unit\Support;

use App\Support\BotDetector;
use PHPUnit\Framework\TestCase;

class BotDetectorTest extends TestCase
{
    /** @dataProvider botUserAgents */
    public function test_detects_known_bots(string $userAgent): void
    {
        $this->assertTrue(BotDetector::isBot($userAgent));
    }

    public static function botUserAgents(): array
    {
        return [
            'googlebot' => ['Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'],
            'bingbot' => ['Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)'],
            'ahrefs' => ['Mozilla/5.0 (compatible; AhrefsBot/7.0; +http://ahrefs.com/robot/)'],
            'facebook' => ['facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)'],
            'headless chrome' => ['HeadlessChrome/120.0.0.0 Safari/537.36'],
            'lighthouse' => ['Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/118.0.5993.0 Safari/537.36 Lighthouse'],
            'generic spider' => ['SomethingSpider/1.0'],
        ];
    }

    public function test_treats_real_browsers_as_humans(): void
    {
        $this->assertFalse(BotDetector::isBot('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0 Safari/537.36'));
        $this->assertFalse(BotDetector::isBot('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15'));
    }

    public function test_treats_null_and_empty_as_bot(): void
    {
        $this->assertTrue(BotDetector::isBot(null));
        $this->assertTrue(BotDetector::isBot(''));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit --filter BotDetectorTest`
Expected: FAIL with `Class "App\Support\BotDetector" not found`.

- [ ] **Step 3: Implement `BotDetector`**

`app/Support/BotDetector.php`:

```php
<?php

namespace App\Support;

class BotDetector
{
    private const PATTERNS = [
        'bot', 'crawl', 'spider', 'slurp',
        'bingpreview', 'facebookexternalhit',
        'headlesschrome', 'lighthouse', 'mediapartners-google',
    ];

    public static function isBot(?string $userAgent): bool
    {
        if ($userAgent === null || trim($userAgent) === '') {
            return true;
        }

        $needle = strtolower($userAgent);
        foreach (self::PATTERNS as $pattern) {
            if (str_contains($needle, $pattern)) {
                return true;
            }
        }
        return false;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit --filter BotDetectorTest`
Expected: 9 passing assertions across the data provider rows + the explicit tests.

- [ ] **Step 5: Commit**

```bash
git add tests/Unit/Support/BotDetectorTest.php app/Support/BotDetector.php
git commit -m "$(cat <<'EOF'
Add BotDetector support class

Lightweight UA-substring matcher used by analytics middleware to skip
crawler and headless-browser traffic from visitor counts.

Co-Authored-By: Claude Opus 4.7 <noreply@anthropic.com>
EOF
)"
```

---

## Task 3: `SearchKeywordNormalizer` (TDD)

**Files:**
- Test: `tests/Unit/Support/SearchKeywordNormalizerTest.php`
- Create: `app/Support/SearchKeywordNormalizer.php`

- [ ] **Step 1: Write the failing test**

`tests/Unit/Support/SearchKeywordNormalizerTest.php`:

```php
<?php

namespace Tests\Unit\Support;

use App\Support\SearchKeywordNormalizer;
use PHPUnit\Framework\TestCase;

class SearchKeywordNormalizerTest extends TestCase
{
    public function test_lowercases_and_trims(): void
    {
        $this->assertSame('brake pad', SearchKeywordNormalizer::normalize('  BraKe  Pad  '));
    }

    public function test_collapses_internal_whitespace(): void
    {
        $this->assertSame('oil filter pro', SearchKeywordNormalizer::normalize("oil\tfilter   pro"));
    }

    public function test_strips_control_characters(): void
    {
        $this->assertSame('headlight', SearchKeywordNormalizer::normalize("head\x00light\x1f"));
    }

    public function test_returns_null_when_below_min_length(): void
    {
        $this->assertNull(SearchKeywordNormalizer::normalize('a'));
        $this->assertNull(SearchKeywordNormalizer::normalize(' '));
        $this->assertNull(SearchKeywordNormalizer::normalize(''));
    }

    public function test_returns_null_when_only_symbols_or_digits(): void
    {
        $this->assertNull(SearchKeywordNormalizer::normalize('???'));
        $this->assertNull(SearchKeywordNormalizer::normalize('1234567890'));
        $this->assertNull(SearchKeywordNormalizer::normalize('___---'));
    }

    public function test_truncates_to_eighty_chars(): void
    {
        $long = str_repeat('a', 200);
        $this->assertSame(80, mb_strlen(SearchKeywordNormalizer::normalize($long)));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit --filter SearchKeywordNormalizerTest`
Expected: FAIL with class not found.

- [ ] **Step 3: Implement**

`app/Support/SearchKeywordNormalizer.php`:

```php
<?php

namespace App\Support;

class SearchKeywordNormalizer
{
    public const MAX_LENGTH = 80;
    public const MIN_LENGTH = 2;

    public static function normalize(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $clean = preg_replace('/[\x00-\x1F\x7F]/u', '', $raw) ?? '';
        $clean = mb_strtolower($clean, 'UTF-8');
        $clean = preg_replace('/\s+/u', ' ', $clean) ?? '';
        $clean = trim($clean);

        if ($clean === '' || mb_strlen($clean) < self::MIN_LENGTH) {
            return null;
        }

        if (preg_match('/^[\W\d_]+$/u', $clean) === 1) {
            return null;
        }

        return mb_substr($clean, 0, self::MAX_LENGTH);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit --filter SearchKeywordNormalizerTest`
Expected: 6 assertions pass.

- [ ] **Step 5: Commit**

```bash
git add tests/Unit/Support/SearchKeywordNormalizerTest.php app/Support/SearchKeywordNormalizer.php
git commit -m "$(cat <<'EOF'
Add SearchKeywordNormalizer

Normalizes user search input for analytics — lowercases, trims,
collapses whitespace, strips control chars, rejects symbol-only or
too-short input, truncates to 80 chars.

Co-Authored-By: Claude Opus 4.7 <noreply@anthropic.com>
EOF
)"
```

---

## Task 4: `AnalyticsRecorder` service (TDD)

**Files:**
- Test: `tests/Feature/Analytics/AnalyticsRecorderTest.php` (covers both `record` and `recordSearch`)
- Create: `app/Services/Analytics/AnalyticsRecorder.php`

- [ ] **Step 1: Write the failing test**

`tests/Feature/Analytics/AnalyticsRecorderTest.php`:

```php
<?php

namespace Tests\Feature\Analytics;

use App\Models\Product;
use App\Services\Analytics\AnalyticsRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AnalyticsRecorderTest extends TestCase
{
    use RefreshDatabase;

    public function test_record_inserts_event_and_increments_product_views(): void
    {
        $product = Product::factory()->create();
        app(AnalyticsRecorder::class)->record('product_view', ['product_id' => $product->id]);

        $this->assertDatabaseHas('analytics_events', [
            'event_type' => 'product_view',
            'product_id' => $product->id,
        ]);
        $this->assertSame(1, (int) DB::table('product_analytics')->where('product_id', $product->id)->value('views_count'));
    }

    public function test_record_increments_add_to_cart_counter(): void
    {
        $product = Product::factory()->create();
        app(AnalyticsRecorder::class)->record('add_to_cart', ['product_id' => $product->id, 'qty' => 2]);
        app(AnalyticsRecorder::class)->record('add_to_cart', ['product_id' => $product->id, 'qty' => 1]);

        $this->assertSame(2, (int) DB::table('product_analytics')->where('product_id', $product->id)->value('add_to_cart_count'));
    }

    public function test_record_increments_wishlist_counter(): void
    {
        $product = Product::factory()->create();
        app(AnalyticsRecorder::class)->record('wishlist_click', ['product_id' => $product->id]);

        $this->assertSame(1, (int) DB::table('product_analytics')->where('product_id', $product->id)->value('wishlist_count'));
    }

    public function test_record_search_upserts_keyword(): void
    {
        $recorder = app(AnalyticsRecorder::class);
        $recorder->recordSearch('BraKe  pad ', 5);
        $recorder->recordSearch('brake pad', 7);

        $this->assertSame(1, DB::table('search_analytics')->count());
        $this->assertSame(2, (int) DB::table('search_analytics')->where('keyword', 'brake pad')->value('search_count'));
    }

    public function test_record_search_ignores_invalid_input(): void
    {
        app(AnalyticsRecorder::class)->recordSearch('???', 0);
        $this->assertSame(0, DB::table('search_analytics')->count());
        $this->assertSame(0, DB::table('analytics_events')->where('event_type', 'search')->count());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit --filter AnalyticsRecorderTest`
Expected: FAIL with class `App\Services\Analytics\AnalyticsRecorder` not found.

- [ ] **Step 3: Implement `AnalyticsRecorder`**

`app/Services/Analytics/AnalyticsRecorder.php`:

```php
<?php

namespace App\Services\Analytics;

use App\Support\SearchKeywordNormalizer;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AnalyticsRecorder
{
    /**
     * @param  array<string, mixed>  $payload  Optional keys: product_id, user_id, session_id,
     *                                         ip_hash, user_agent_hash, url, referrer,
     *                                         plus event-specific metadata.
     */
    public function record(string $type, array $payload = []): void
    {
        try {
            $now = Carbon::now();

            DB::table('analytics_events')->insert([
                'event_type'      => $type,
                'product_id'      => $payload['product_id'] ?? null,
                'user_id'         => $payload['user_id']    ?? null,
                'session_id'      => $payload['session_id'] ?? null,
                'ip_hash'         => $payload['ip_hash']    ?? null,
                'user_agent_hash' => $payload['user_agent_hash'] ?? null,
                'url'             => $payload['url']        ?? null,
                'referrer'        => $payload['referrer']   ?? null,
                'metadata'        => isset($payload['metadata']) ? json_encode($payload['metadata']) : null,
                'created_at'      => $now,
            ]);

            $productId = $payload['product_id'] ?? null;
            if ($productId !== null) {
                $this->touchProductCounter($type, (int) $productId, $now);
            }
        } catch (Throwable $e) {
            Log::channel('stack')->warning('analytics.record_failed', [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function recordSearch(string $rawKeyword, int $resultsCount): void
    {
        $keyword = SearchKeywordNormalizer::normalize($rawKeyword);
        if ($keyword === null) {
            return;
        }

        try {
            $now = Carbon::now();

            $this->record('search', [
                'metadata' => ['keyword' => $keyword, 'results_count' => $resultsCount],
            ]);

            $affected = DB::table('search_analytics')
                ->where('keyword', $keyword)
                ->update([
                    'search_count' => DB::raw('search_count + 1'),
                    'last_searched_at' => $now,
                    'updated_at' => $now,
                ]);

            if ($affected === 0) {
                DB::table('search_analytics')->insert([
                    'keyword' => $keyword,
                    'search_count' => 1,
                    'last_searched_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        } catch (Throwable $e) {
            Log::channel('stack')->warning('analytics.record_search_failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function touchProductCounter(string $type, int $productId, Carbon $now): void
    {
        $column = match ($type) {
            'product_view'   => 'views_count',
            'add_to_cart'    => 'add_to_cart_count',
            'wishlist_click' => 'wishlist_count',
            default          => null,
        };
        if ($column === null) {
            return;
        }

        $updates = [
            $column => DB::raw($column . ' + 1'),
            'updated_at' => $now,
        ];
        if ($type === 'product_view') {
            $updates['last_viewed_at'] = $now;
        }

        $affected = DB::table('product_analytics')
            ->where('product_id', $productId)
            ->update($updates);

        if ($affected === 0) {
            DB::table('product_analytics')->insert([
                'product_id'        => $productId,
                'views_count'       => $type === 'product_view'   ? 1 : 0,
                'add_to_cart_count' => $type === 'add_to_cart'    ? 1 : 0,
                'wishlist_count'    => $type === 'wishlist_click' ? 1 : 0,
                'last_viewed_at'    => $type === 'product_view'   ? $now : null,
                'created_at'        => $now,
                'updated_at'        => $now,
            ]);
        }
    }

    /**
     * Builds a payload pre-populated with hashed visitor identifiers from the current request.
     * Callers use this then merge in their own keys (product_id, metadata, etc.).
     *
     * @return array<string, mixed>
     */
    public static function visitorPayloadFor(Request $request): array
    {
        $key = (string) config('app.key');
        $ip = $request->ip();
        $ua = $request->userAgent();

        return [
            'user_id'         => optional($request->user())->id,
            'session_id'      => $request->hasSession() ? substr(hash('sha256', $request->session()->getId()), 0, 40) : null,
            'ip_hash'         => $ip !== null ? hash('sha256', $ip . $key) : null,
            'user_agent_hash' => $ua !== null ? hash('sha256', $ua . $key) : null,
            'url'             => substr($request->fullUrl(), 0, 2048),
            'referrer'        => substr((string) $request->headers->get('referer', ''), 0, 2048) ?: null,
        ];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit --filter AnalyticsRecorderTest`
Expected: 5 tests pass.

- [ ] **Step 5: Commit**

```bash
git add tests/Feature/Analytics/AnalyticsRecorderTest.php app/Services/Analytics/AnalyticsRecorder.php
git commit -m "$(cat <<'EOF'
Add AnalyticsRecorder write seam

Single class that inserts raw events and atomically increments
denormalized counters. Catches all exceptions so analytics failures
can never break user requests. visitorPayloadFor() builds the
hashed-identifier payload from a Request.

Co-Authored-By: Claude Opus 4.7 <noreply@anthropic.com>
EOF
)"
```

---

## Task 5: `RecordAnalyticsEvent` middleware + Kernel registration

**Files:**
- Create: `app/Http/Middleware/RecordAnalyticsEvent.php`
- Modify: `app/Http/Kernel.php` — append middleware to `web` group
- Test: `tests/Feature/Analytics/PageViewTrackingTest.php`

- [ ] **Step 1: Write the failing feature test**

`tests/Feature/Analytics/PageViewTrackingTest.php`:

```php
<?php

namespace Tests\Feature\Analytics;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PageViewTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_request_records_page_view_event(): void
    {
        $this->withServerVariables(['HTTP_USER_AGENT' => 'Mozilla/5.0 Chrome/121 Safari/537.36'])
             ->get('/shop')
             ->assertOk();

        $this->assertSame(1, DB::table('analytics_events')->where('event_type', 'page_view')->count());

        $row = DB::table('analytics_events')->where('event_type', 'page_view')->first();
        $this->assertSame(64, strlen((string) $row->ip_hash));
        $this->assertSame(64, strlen((string) $row->user_agent_hash));
    }

    public function test_bot_user_agent_is_ignored(): void
    {
        $this->withServerVariables(['HTTP_USER_AGENT' => 'Googlebot/2.1'])
             ->get('/shop')
             ->assertOk();

        $this->assertSame(0, DB::table('analytics_events')->count());
    }

    public function test_asset_paths_are_ignored(): void
    {
        $this->withServerVariables(['HTTP_USER_AGENT' => 'Mozilla/5.0 Chrome/121'])
             ->get('/favicon.ico');

        // Either the asset 404s or returns the file; either way, no event.
        $this->assertSame(0, DB::table('analytics_events')->where('event_type', 'page_view')->count());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit --filter PageViewTrackingTest`
Expected: FAIL — first assertion sees zero rows because middleware does not exist.

- [ ] **Step 3: Implement `RecordAnalyticsEvent`**

`app/Http/Middleware/RecordAnalyticsEvent.php`:

```php
<?php

namespace App\Http\Middleware;

use App\Services\Analytics\AnalyticsRecorder;
use App\Support\BotDetector;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RecordAnalyticsEvent
{
    private const ASSET_EXTENSIONS = [
        'js','css','map','png','jpg','jpeg','gif','svg','webp','ico',
        'woff','woff2','ttf','eot','otf','mp4','webm','mp3','pdf','txt','xml','json',
    ];

    public function __construct(private readonly AnalyticsRecorder $recorder) {}

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if ($request->method() !== 'GET') {
            return;
        }
        if ($response->getStatusCode() >= 400) {
            return;
        }
        if (BotDetector::isBot($request->userAgent())) {
            return;
        }
        if ($this->isAsset($request->path())) {
            return;
        }

        $this->recorder->record('page_view', AnalyticsRecorder::visitorPayloadFor($request));
    }

    private function isAsset(string $path): bool
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return $ext !== '' && in_array($ext, self::ASSET_EXTENSIONS, true);
    }
}
```

- [ ] **Step 4: Register middleware in `web` group**

Modify `app/Http/Kernel.php`, inside the `web` array (after `SubstituteBindings`), append:

```php
\App\Http\Middleware\RecordAnalyticsEvent::class,
```

So `web` ends with:

```php
'web' => [
    \App\Http\Middleware\EncryptCookies::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \Illuminate\Session\Middleware\StartSession::class,
    \App\Http\Middleware\SetLocale::class,
    \App\Http\Middleware\ApplyUserPreferences::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    \App\Http\Middleware\VerifyCsrfToken::class,
    \Illuminate\Routing\Middleware\ThrottleRequests::class.':web',
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
    \App\Http\Middleware\RecordAnalyticsEvent::class,
],
```

- [ ] **Step 5: Run test to verify it passes**

Run: `vendor/bin/phpunit --filter PageViewTrackingTest`
Expected: 3 tests pass.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Middleware/RecordAnalyticsEvent.php app/Http/Kernel.php tests/Feature/Analytics/PageViewTrackingTest.php
git commit -m "$(cat <<'EOF'
Track page views via terminable analytics middleware

Records one page_view per GET HTML response after the response has
been sent to the client. Bots and asset URLs are filtered out before
the DB insert.

Co-Authored-By: Claude Opus 4.7 <noreply@anthropic.com>
EOF
)"
```

---

## Task 6: Track product views (TDD)

**Files:**
- Test: `tests/Feature/Analytics/ProductViewTrackingTest.php`
- Modify: `app/Http/Controllers/ShopController.php` — dispatch `product_view` in `show()`

- [ ] **Step 1: Write the failing test**

`tests/Feature/Analytics/ProductViewTrackingTest.php`:

```php
<?php

namespace Tests\Feature\Analytics;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductViewTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_visiting_product_detail_records_product_view(): void
    {
        $product = Product::factory()->create(['is_active' => true]);

        $this->withServerVariables(['HTTP_USER_AGENT' => 'Mozilla/5.0 Chrome/121'])
             ->get(route('shop.show', $product))
             ->assertOk();

        $this->assertSame(1, DB::table('analytics_events')
            ->where('event_type', 'product_view')
            ->where('product_id', $product->id)
            ->count());

        $this->assertSame(1, (int) DB::table('product_analytics')->where('product_id', $product->id)->value('views_count'));
    }

    public function test_product_view_increments_idempotently_across_two_visits(): void
    {
        $product = Product::factory()->create(['is_active' => true]);
        $url = route('shop.show', $product);

        for ($i = 0; $i < 3; $i++) {
            $this->withServerVariables(['HTTP_USER_AGENT' => 'Mozilla/5.0 Chrome/121'])
                 ->get($url);
        }

        $this->assertSame(3, (int) DB::table('product_analytics')->where('product_id', $product->id)->value('views_count'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit --filter ProductViewTrackingTest`
Expected: FAIL — the `product_analytics.views_count` assertion shows 0.

- [ ] **Step 3: Dispatch event from `ShopController@show`**

Open `app/Http/Controllers/ShopController.php`, locate the `show(Product $product)` method, and at the end (just before `return view(...)`), add:

```php
app(\App\Services\Analytics\AnalyticsRecorder::class)->record('product_view', array_merge(
    \App\Services\Analytics\AnalyticsRecorder::visitorPayloadFor(request()),
    ['product_id' => $product->id],
));
```

If the method has no early `return` paths before the success render, place this line just before `return view(...)`. Do not change any existing logic.

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit --filter ProductViewTrackingTest`
Expected: 2 tests pass.

- [ ] **Step 5: Commit**

```bash
git add tests/Feature/Analytics/ProductViewTrackingTest.php app/Http/Controllers/ShopController.php
git commit -m "$(cat <<'EOF'
Track product views from shop product detail page

Co-Authored-By: Claude Opus 4.7 <noreply@anthropic.com>
EOF
)"
```

---

## Task 7: Track add-to-cart (TDD)

**Files:**
- Test: `tests/Feature/Analytics/AddToCartTrackingTest.php`
- Modify: `app/Http/Controllers/CartController.php` — dispatch `add_to_cart` in `add()` success path

- [ ] **Step 1: Write the failing test**

`tests/Feature/Analytics/AddToCartTrackingTest.php`:

```php
<?php

namespace Tests\Feature\Analytics;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AddToCartTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_adding_to_cart_records_event_and_increments_counter(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['is_active' => true, 'stock_quantity' => 10]);

        $this->actingAs($user)
             ->withServerVariables(['HTTP_USER_AGENT' => 'Mozilla/5.0 Chrome/121'])
             ->post(route('cart.add', $product), ['quantity' => 2])
             ->assertRedirect();

        $this->assertSame(1, DB::table('analytics_events')
            ->where('event_type', 'add_to_cart')
            ->where('product_id', $product->id)
            ->count());

        $this->assertSame(1, (int) DB::table('product_analytics')->where('product_id', $product->id)->value('add_to_cart_count'));
    }
}
```

> If the `cart.add` route name differs in your project, run `php artisan route:list --path=cart` and use the actual name.

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit --filter AddToCartTrackingTest`
Expected: FAIL — `add_to_cart_count` is 0.

- [ ] **Step 3: Dispatch event from `CartController@add`**

Open `app/Http/Controllers/CartController.php` and find the `add(Request, Product)` method. Locate every success-path branch that returns to the caller after the item has been added to the cart (both JSON and HTML branches). Immediately before each success `return`, add:

```php
app(\App\Services\Analytics\AnalyticsRecorder::class)->record('add_to_cart', array_merge(
    \App\Services\Analytics\AnalyticsRecorder::visitorPayloadFor($request),
    ['product_id' => $product->id, 'metadata' => ['qty' => $quantity]],
));
```

Do not dispatch on the pending-action branch (where the user is not authenticated and gets redirected to login) — that is not yet a cart add.

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit --filter AddToCartTrackingTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add tests/Feature/Analytics/AddToCartTrackingTest.php app/Http/Controllers/CartController.php
git commit -m "$(cat <<'EOF'
Track add-to-cart actions

Co-Authored-By: Claude Opus 4.7 <noreply@anthropic.com>
EOF
)"
```

---

## Task 8: Track wishlist clicks (TDD)

**Files:**
- Test: `tests/Feature/Analytics/WishlistTrackingTest.php`
- Modify: `app/Http/Controllers/User/WishlistController.php` — dispatch `wishlist_click` in `store()`

- [ ] **Step 1: Write the failing test**

`tests/Feature/Analytics/WishlistTrackingTest.php`:

```php
<?php

namespace Tests\Feature\Analytics;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WishlistTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_adding_to_wishlist_records_event_and_increments_counter(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['is_active' => true]);

        $this->actingAs($user)
             ->withServerVariables(['HTTP_USER_AGENT' => 'Mozilla/5.0 Chrome/121'])
             ->post(route('user.wishlist.store', $product))
             ->assertRedirect();

        $this->assertSame(1, DB::table('analytics_events')
            ->where('event_type', 'wishlist_click')
            ->where('product_id', $product->id)
            ->count());

        $this->assertSame(1, (int) DB::table('product_analytics')->where('product_id', $product->id)->value('wishlist_count'));
    }
}
```

> Adjust the route name if `route:list` shows it differently.

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit --filter WishlistTrackingTest`
Expected: FAIL.

- [ ] **Step 3: Dispatch event in `WishlistController@store`**

Open `app/Http/Controllers/User/WishlistController.php` and add before `return back()->with(...)`:

```php
app(\App\Services\Analytics\AnalyticsRecorder::class)->record('wishlist_click', array_merge(
    \App\Services\Analytics\AnalyticsRecorder::visitorPayloadFor($request),
    ['product_id' => $product->id],
));
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit --filter WishlistTrackingTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add tests/Feature/Analytics/WishlistTrackingTest.php app/Http/Controllers/User/WishlistController.php
git commit -m "$(cat <<'EOF'
Track wishlist clicks

Co-Authored-By: Claude Opus 4.7 <noreply@anthropic.com>
EOF
)"
```

---

## Task 9: Track search queries (TDD)

**Files:**
- Test: `tests/Feature/Analytics/SearchTrackingTest.php`
- Modify: `app/Http/Controllers/User/ShopController.php` — dispatch `recordSearch` in `shop()` when `?search=` is present

- [ ] **Step 1: Write the failing test**

`tests/Feature/Analytics/SearchTrackingTest.php`:

```php
<?php

namespace Tests\Feature\Analytics;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SearchTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_query_creates_keyword_row(): void
    {
        $this->withServerVariables(['HTTP_USER_AGENT' => 'Mozilla/5.0 Chrome/121'])
             ->get(route('shop.index', ['search' => 'BraKe  pad ']))
             ->assertOk();

        $this->assertSame(1, DB::table('search_analytics')->count());
        $this->assertSame(1, (int) DB::table('search_analytics')->where('keyword', 'brake pad')->value('search_count'));
    }

    public function test_second_identical_search_increments(): void
    {
        $url = route('shop.index', ['search' => 'oil filter']);

        for ($i = 0; $i < 2; $i++) {
            $this->withServerVariables(['HTTP_USER_AGENT' => 'Mozilla/5.0 Chrome/121'])
                 ->get($url);
        }

        $this->assertSame(2, (int) DB::table('search_analytics')->where('keyword', 'oil filter')->value('search_count'));
    }

    public function test_empty_search_is_ignored(): void
    {
        $this->withServerVariables(['HTTP_USER_AGENT' => 'Mozilla/5.0 Chrome/121'])
             ->get(route('shop.index', ['search' => ' ']));

        $this->assertSame(0, DB::table('search_analytics')->count());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit --filter SearchTrackingTest`
Expected: FAIL.

- [ ] **Step 3: Dispatch search event in `User\ShopController@shop`**

Open `app/Http/Controllers/User/ShopController.php` and locate `shop(Request $request)`. At the point where the search has already been executed and a result count is known, add:

```php
if ($request->filled('search')) {
    app(\App\Services\Analytics\AnalyticsRecorder::class)->recordSearch(
        (string) $request->input('search'),
        is_object($products) && method_exists($products, 'total') ? (int) $products->total() : 0,
    );
}
```

Replace `$products` with whichever variable holds the paginated/collection result. If results count is not readily available, pass `0`.

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit --filter SearchTrackingTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add tests/Feature/Analytics/SearchTrackingTest.php app/Http/Controllers/User/ShopController.php
git commit -m "$(cat <<'EOF'
Track normalized search keywords from the shop page

Co-Authored-By: Claude Opus 4.7 <noreply@anthropic.com>
EOF
)"
```

---

## Task 10: Track checkout start and order completion

**Files:**
- Modify: `app/Http/Controllers/CheckoutController.php` — dispatch `checkout_started` in `options()` and `buyNow()`
- Create: `app/Observers/OrderAnalyticsObserver.php`
- Modify: `app/Providers/AppServiceProvider.php` (or `EventServiceProvider`) — register observer
- Test: `tests/Feature/Analytics/CheckoutAndOrderTrackingTest.php`

- [ ] **Step 1: Write the failing test**

`tests/Feature/Analytics/CheckoutAndOrderTrackingTest.php`:

```php
<?php

namespace Tests\Feature\Analytics;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CheckoutAndOrderTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_created_event_records_order_completed(): void
    {
        $user = User::factory()->create();
        Order::factory()->create([
            'user_id' => $user->id,
            'total' => '49.99',
        ]);

        $this->assertSame(1, DB::table('analytics_events')
            ->where('event_type', 'order_completed')
            ->where('user_id', $user->id)
            ->count());
    }
}
```

> Checkout-start coverage is exercised manually — adding a feature test requires standing up a full cart + address + coupon flow, which is heavy. The observer test above proves the wiring works for the most important event (`order_completed`); checkout_started uses the same `AnalyticsRecorder` already covered by `AnalyticsRecorderTest`.

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit --filter CheckoutAndOrderTrackingTest`
Expected: FAIL.

- [ ] **Step 3: Create observer**

`app/Observers/OrderAnalyticsObserver.php`:

```php
<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\Analytics\AnalyticsRecorder;

class OrderAnalyticsObserver
{
    public function __construct(private readonly AnalyticsRecorder $recorder) {}

    public function created(Order $order): void
    {
        $this->recorder->record('order_completed', [
            'user_id'  => $order->user_id,
            'metadata' => ['order_id' => $order->id, 'total' => (string) $order->total],
        ]);
    }
}
```

- [ ] **Step 4: Register observer**

Open `app/Providers/AppServiceProvider.php`, in the `boot()` method add:

```php
\App\Models\Order::observe(\App\Observers\OrderAnalyticsObserver::class);
```

- [ ] **Step 5: Dispatch `checkout_started` from `CheckoutController`**

In `app/Http/Controllers/CheckoutController.php`, at the top of both `options(Request $request, Product $product)` and `buyNow(Request $request, Product $product)` — immediately after the existing `abort_unless` line — add:

```php
app(\App\Services\Analytics\AnalyticsRecorder::class)->record('checkout_started', array_merge(
    \App\Services\Analytics\AnalyticsRecorder::visitorPayloadFor($request),
    ['product_id' => $product->id],
));
```

- [ ] **Step 6: Run test to verify it passes**

Run: `vendor/bin/phpunit --filter CheckoutAndOrderTrackingTest`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add tests/Feature/Analytics/CheckoutAndOrderTrackingTest.php app/Observers/OrderAnalyticsObserver.php app/Providers/AppServiceProvider.php app/Http/Controllers/CheckoutController.php
git commit -m "$(cat <<'EOF'
Track checkout starts and completed orders

OrderAnalyticsObserver fires on Order::created; CheckoutController
emits checkout_started at the entrance to the review page.

Co-Authored-By: Claude Opus 4.7 <noreply@anthropic.com>
EOF
)"
```

---

## Task 11: Prune command and scheduler

**Files:**
- Create: `app/Console/Commands/PruneAnalyticsEvents.php`
- Modify: `app/Console/Kernel.php` — schedule daily at 03:00
- Test: `tests/Feature/Analytics/AnalyticsPruneCommandTest.php`

- [ ] **Step 1: Write the failing test**

`tests/Feature/Analytics/AnalyticsPruneCommandTest.php`:

```php
<?php

namespace Tests\Feature\Analytics;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AnalyticsPruneCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_prune_deletes_old_events_only(): void
    {
        DB::table('analytics_events')->insert([
            ['event_type' => 'page_view', 'created_at' => Carbon::now()->subDays(366)],
            ['event_type' => 'page_view', 'created_at' => Carbon::now()->subDays(1)],
        ]);

        $this->artisan('analytics:prune', ['--days' => 365])
             ->assertSuccessful();

        $this->assertSame(1, DB::table('analytics_events')->count());
        $remaining = DB::table('analytics_events')->first();
        $this->assertTrue(Carbon::parse($remaining->created_at)->isAfter(Carbon::now()->subDays(2)));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit --filter AnalyticsPruneCommandTest`
Expected: FAIL — command not found.

- [ ] **Step 3: Implement command**

`app/Console/Commands/PruneAnalyticsEvents.php`:

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PruneAnalyticsEvents extends Command
{
    protected $signature = 'analytics:prune {--days=365 : Retention window in days}';

    protected $description = 'Delete analytics_events rows older than the retention window.';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = Carbon::now()->subDays($days);

        $deleted = DB::table('analytics_events')
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info("Pruned {$deleted} analytics_events rows older than {$days} days.");
        return self::SUCCESS;
    }
}
```

- [ ] **Step 4: Schedule the command**

In `app/Console/Kernel.php`, inside `schedule(Schedule $schedule)`, add:

```php
$schedule->command('analytics:prune --days=365')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->onOneServer();
```

- [ ] **Step 5: Run test to verify it passes**

Run: `vendor/bin/phpunit --filter AnalyticsPruneCommandTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add tests/Feature/Analytics/AnalyticsPruneCommandTest.php app/Console/Commands/PruneAnalyticsEvents.php app/Console/Kernel.php
git commit -m "$(cat <<'EOF'
Add analytics:prune command and daily 03:00 schedule

Removes analytics_events rows older than the retention window
(default 365 days). Denormalized counters are not touched.

Co-Authored-By: Claude Opus 4.7 <noreply@anthropic.com>
EOF
)"
```

---

## Task 12: `AnalyticsQueryService` for the admin page

**Files:**
- Create: `app/Services/Analytics/AnalyticsQueryService.php`
- Test: `tests/Feature/Analytics/AnalyticsQueryServiceTest.php`

- [ ] **Step 1: Write the failing test**

`tests/Feature/Analytics/AnalyticsQueryServiceTest.php`:

```php
<?php

namespace Tests\Feature\Analytics;

use App\Models\Product;
use App\Services\Analytics\AnalyticsQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AnalyticsQueryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitor_counts_count_distinct_session_ids(): void
    {
        DB::table('analytics_events')->insert([
            ['event_type' => 'page_view', 'session_id' => 'a', 'created_at' => Carbon::now()->subHours(1)],
            ['event_type' => 'page_view', 'session_id' => 'a', 'created_at' => Carbon::now()->subHours(2)],
            ['event_type' => 'page_view', 'session_id' => 'b', 'created_at' => Carbon::now()->subHours(3)],
        ]);

        $this->assertSame(2, app(AnalyticsQueryService::class)->visitorsForRange(Carbon::now()->subDay(), Carbon::now()));
    }

    public function test_top_viewed_products_returns_ordered_rows(): void
    {
        $p1 = Product::factory()->create(['name_en' => 'Brake Pad XR']);
        $p2 = Product::factory()->create(['name_en' => 'Oil Filter Pro']);

        DB::table('product_analytics')->insert([
            ['product_id' => $p1->id, 'views_count' => 100, 'add_to_cart_count' => 10, 'wishlist_count' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $p2->id, 'views_count' => 50,  'add_to_cart_count' => 30, 'wishlist_count' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $top = app(AnalyticsQueryService::class)->topProducts('views_count', 5);
        $this->assertSame($p1->id, $top->first()['product_id']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit --filter AnalyticsQueryServiceTest`
Expected: FAIL — class not found.

- [ ] **Step 3: Implement**

`app/Services/Analytics/AnalyticsQueryService.php`:

```php
<?php

namespace App\Services\Analytics;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalyticsQueryService
{
    public function visitorsForRange(Carbon $from, Carbon $to): int
    {
        $key = "analytics:visitors:{$from->timestamp}:{$to->timestamp}";
        return (int) Cache::remember($key, now()->addMinutes(5), function () use ($from, $to) {
            return DB::table('analytics_events')
                ->where('event_type', 'page_view')
                ->whereBetween('created_at', [$from, $to])
                ->selectRaw('COUNT(DISTINCT COALESCE(CAST(user_id AS CHAR), session_id, ip_hash)) AS visitors')
                ->value('visitors');
        });
    }

    public function totalEvents(string $type, Carbon $from, Carbon $to): int
    {
        $key = "analytics:events:{$type}:{$from->timestamp}:{$to->timestamp}";
        return (int) Cache::remember($key, now()->addMinutes(5), function () use ($type, $from, $to) {
            return DB::table('analytics_events')
                ->where('event_type', $type)
                ->whereBetween('created_at', [$from, $to])
                ->count();
        });
    }

    /** @return Collection<int, array{product_id:int,name:string,count:int}> */
    public function topProducts(string $column, int $limit = 10): Collection
    {
        $key = "analytics:top_products:{$column}:{$limit}";
        return Cache::remember($key, now()->addMinutes(5), function () use ($column, $limit) {
            return DB::table('product_analytics as pa')
                ->join('products as p', 'p.id', '=', 'pa.product_id')
                ->orderByDesc("pa.{$column}")
                ->limit($limit)
                ->get(['pa.product_id', 'p.name_en as name', "pa.{$column} as count"])
                ->map(fn ($row) => [
                    'product_id' => (int) $row->product_id,
                    'name'       => (string) $row->name,
                    'count'      => (int) $row->count,
                ]);
        });
    }

    /** @return Collection<int, array{keyword:string,count:int,last_searched_at:?string}> */
    public function topSearches(int $limit = 10): Collection
    {
        return Cache::remember("analytics:top_searches:{$limit}", now()->addMinutes(5), function () use ($limit) {
            return DB::table('search_analytics')
                ->orderByDesc('search_count')
                ->limit($limit)
                ->get(['keyword', 'search_count as count', 'last_searched_at'])
                ->map(fn ($row) => [
                    'keyword' => (string) $row->keyword,
                    'count'   => (int) $row->count,
                    'last_searched_at' => $row->last_searched_at,
                ]);
        });
    }

    /** @return array<int, array{date:string,count:int}>  exactly $days entries, oldest first */
    public function dailySeries(string $type, int $days = 7): array
    {
        $key = "analytics:series:{$type}:{$days}";
        return Cache::remember($key, now()->addMinutes(10), function () use ($type, $days) {
            $from = Carbon::today()->subDays($days - 1);

            $rows = DB::table('analytics_events')
                ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
                ->where('event_type', $type)
                ->where('created_at', '>=', $from)
                ->groupBy('d')
                ->pluck('c', 'd');

            $series = [];
            for ($i = 0; $i < $days; $i++) {
                $date = $from->copy()->addDays($i)->toDateString();
                $series[] = ['date' => $date, 'count' => (int) ($rows[$date] ?? 0)];
            }
            return $series;
        });
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit --filter AnalyticsQueryServiceTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add tests/Feature/Analytics/AnalyticsQueryServiceTest.php app/Services/Analytics/AnalyticsQueryService.php
git commit -m "$(cat <<'EOF'
Add AnalyticsQueryService with 5–10 minute caching

Single read seam for the admin Analytics page: visitor counts,
top-N products / searches, and daily series for charts.

Co-Authored-By: Claude Opus 4.7 <noreply@anthropic.com>
EOF
)"
```

---

## Task 13: `AnalyticsController`, route, and access tests

**Files:**
- Create: `app/Http/Controllers/Admin/AnalyticsController.php`
- Modify: `routes/web.php` — add route inside the existing `admin.` group
- Test: `tests/Feature/Analytics/AnalyticsAdminAccessTest.php`

- [ ] **Step 1: Write the failing access test**

`tests/Feature/Analytics/AnalyticsAdminAccessTest.php`:

```php
<?php

namespace Tests\Feature\Analytics;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsAdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.analytics.index'))->assertRedirect(route('login'));
    }

    public function test_non_admin_user_gets_forbidden(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user)->get(route('admin.analytics.index'))->assertForbidden();
    }

    public function test_admin_can_view_analytics(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)
             ->withSession(['admin_two_factor_verified_at' => now()->toIso8601String()])
             ->get(route('admin.analytics.index'))
             ->assertOk();
    }
}
```

> Adjust `is_admin` column / 2FA session key to whatever `IsAdmin` and `EnsureAdminTwoFactorVerified` actually check — look at those middleware classes for the exact gate.

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit --filter AnalyticsAdminAccessTest`
Expected: FAIL — route does not exist.

- [ ] **Step 3: Implement controller**

`app/Http/Controllers/Admin/AnalyticsController.php`:

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Analytics\AnalyticsQueryService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    private const RANGES = ['7d' => 7, '30d' => 30, '90d' => 90, '1y' => 365];

    public function __construct(private readonly AnalyticsQueryService $queries) {}

    public function index(Request $request): View
    {
        $rangeKey = $request->input('range', '30d');
        if (!array_key_exists($rangeKey, self::RANGES)) {
            $rangeKey = '30d';
        }
        $days = self::RANGES[$rangeKey];
        $to = Carbon::now();
        $from = $to->copy()->subDays($days);

        $today = Carbon::today();
        $startOfWeek = Carbon::now()->startOfWeek();
        $startOfMonth = Carbon::now()->startOfMonth();

        $topProductId = DB::table('product_analytics')
            ->orderByDesc('views_count')
            ->value('product_id');
        $topProductName = $topProductId
            ? (string) DB::table('products')->where('id', $topProductId)->value('name_en')
            : '—';

        return view('admin.analytics.index', [
            'rangeKey'         => $rangeKey,
            'rangeDays'        => $days,
            'visitorsToday'    => $this->queries->visitorsForRange($today, Carbon::now()),
            'visitorsWeek'     => $this->queries->visitorsForRange($startOfWeek, Carbon::now()),
            'visitorsMonth'    => $this->queries->visitorsForRange($startOfMonth, Carbon::now()),
            'totalPageViews'   => $this->queries->totalEvents('page_view', $from, $to),
            'totalAddToCart'   => $this->queries->totalEvents('add_to_cart', $from, $to),
            'totalWishlist'    => $this->queries->totalEvents('wishlist_click', $from, $to),
            'topProductName'   => $topProductName,
            'topViewed'        => $this->queries->topProducts('views_count', 10),
            'topCarted'        => $this->queries->topProducts('add_to_cart_count', 10),
            'topSearches'      => $this->queries->topSearches(10),
            'seriesVisitors'   => $this->queries->dailySeries('page_view', 7),
            'seriesProductViews' => $this->queries->dailySeries('product_view', 7),
        ]);
    }
}
```

- [ ] **Step 4: Add the route**

Open `routes/web.php`, find the existing admin group (look for `->name('admin.')` near line 215), and inside that group add:

```php
Route::get('/analytics', [\App\Http\Controllers\Admin\AnalyticsController::class, 'index'])
    ->name('analytics.index');
```

(Group prefix already gives it `admin.analytics.index` and applies the `admin` + `admin.2fa` middleware.)

- [ ] **Step 5: Create a minimal view stub so the route returns 200**

`resources/views/admin/analytics/index.blade.php` (temporary stub — replaced in Task 16):

```blade
<x-app-layout>
    <div class="p-8 text-slate-900 dark:text-slate-100">
        <h1 class="text-2xl font-black">Analytics</h1>
        <p class="mt-2">Range: {{ $rangeKey }} ({{ $rangeDays }} days)</p>
        <p>Visitors today: {{ $visitorsToday }}</p>
    </div>
</x-app-layout>
```

- [ ] **Step 6: Run test to verify it passes**

Run: `vendor/bin/phpunit --filter AnalyticsAdminAccessTest`
Expected: 3 tests pass. If `assertForbidden` returns 302 instead of 403 in your project, change to `assertStatus(403)` or match how other admin routes are tested.

- [ ] **Step 7: Commit**

```bash
git add tests/Feature/Analytics/AnalyticsAdminAccessTest.php app/Http/Controllers/Admin/AnalyticsController.php routes/web.php resources/views/admin/analytics/index.blade.php
git commit -m "$(cat <<'EOF'
Add admin analytics controller and route

Route mounted inside the existing admin group, so it picks up the
admin + admin.2fa middleware. View is a temporary stub; the full
Option-1 layout is built in a follow-up task.

Co-Authored-By: Claude Opus 4.7 <noreply@anthropic.com>
EOF
)"
```

---

## Task 14: Chart.js setup

**Files:**
- Modify: `package.json` — add `chart.js`
- Modify: `resources/js/app.js` — import and register

- [ ] **Step 1: Add Chart.js dependency**

Run: `npm install chart.js`
Expected: `package.json` now lists `"chart.js": "^4.x"` under dependencies.

- [ ] **Step 2: Import + expose globally**

Edit `resources/js/app.js`, append at the bottom:

```js
import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);
window.Chart = Chart;
```

- [ ] **Step 3: Rebuild assets**

Run: `npm run build`
Expected: build succeeds, `Chart` ends up in the compiled bundle.

- [ ] **Step 4: Commit**

```bash
git add package.json package-lock.json resources/js/app.js
git commit -m "$(cat <<'EOF'
Add Chart.js for admin analytics charts

Co-Authored-By: Claude Opus 4.7 <noreply@anthropic.com>
EOF
)"
```

---

## Task 15: Sidebar nav link + topbar title mapping

**Files:**
- Modify: `resources/views/layouts/app.blade.php`

- [ ] **Step 1: Add nav link**

Open `resources/views/layouts/app.blade.php`. Find the existing admin Dashboard link — the snippet that begins:

```
<a
    href="{{ route('admin.dashboard') }}"
    class="admin-nav-link {{ $navItem(request()->routeIs('admin.dashboard')) }}"
```

Immediately after the closing `</a>` of that link, insert:

```blade
<a
    href="{{ route('admin.analytics.index') }}"
    class="admin-nav-link {{ $navItem(request()->routeIs('admin.analytics.*')) }}"
    data-admin-sidebar-tooltip="{{ __('Analytics') }}"
    @if(request()->routeIs('admin.analytics.*')) aria-current="page" @endif
>
    <span class="admin-nav-icon" aria-hidden="true"><i class="fas fa-chart-pie"></i></span>
    <span class="admin-nav-label">{{ __('Analytics') }}</span>
</a>
```

- [ ] **Step 2: Add topbar title mapping**

In the same file, find the `$adminPageTitlePatterns` array (around line 321). Insert this line right after the `admin.dashboard` entry:

```blade
'admin.analytics.*'            => __('Site Analytics'),
```

- [ ] **Step 3: Manual smoke check (no automated test for this; it is a visual link)**

Run: `php artisan serve` (or just hit the admin via Laragon), log in as admin, complete 2FA, click the new "Analytics" item in the sidebar. Confirm:
- Link is visible and styled like other nav items.
- Active state highlights when on `/admin/analytics`.
- Topbar shows "Site Analytics".

- [ ] **Step 4: Commit**

```bash
git add resources/views/layouts/app.blade.php
git commit -m "$(cat <<'EOF'
Wire admin Analytics into sidebar nav and topbar title

Co-Authored-By: Claude Opus 4.7 <noreply@anthropic.com>
EOF
)"
```

---

## Task 16: Final Blade view (Option-1 layout)

**Files:**
- Modify: `resources/views/admin/analytics/index.blade.php` (replace the stub from Task 13)

This task ports the approved Option-1 mockup (`public/admin-analytics-preview.html`) into a real Blade view that consumes the controller variables.

- [ ] **Step 1: Replace the stub view**

`resources/views/admin/analytics/index.blade.php`:

```blade
<x-app-layout>
    @php
        $rangeOptions = ['7d' => '7D', '30d' => '30D', '90d' => '90D', '1y' => '1Y'];
        $visitorsSeries = $seriesVisitors;
        $viewsSeries = $seriesProductViews;
    @endphp

    <div class="bg-[#f3f4f7] dark:bg-slate-950 min-h-screen">
    <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Range bar --}}
        <div class="mb-8 rounded-2xl bg-white border border-slate-200/70 dark:bg-slate-900 dark:border-slate-800 px-4 py-3 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <div class="h-9 w-9 rounded-xl bg-primary/10 text-primary grid place-items-center"><i class="far fa-calendar-days text-sm"></i></div>
                <div>
                    <p class="text-[10px] uppercase tracking-widest text-slate-400 font-bold leading-none">{{ __('Time Range') }}</p>
                    <p class="text-sm font-bold text-primary dark:text-slate-100 leading-tight mt-0.5">{{ __('Analytics Period') }} <span class="font-mono text-[11px] text-slate-400">· {{ __('Last :n days', ['n' => $rangeDays]) }}</span></p>
                </div>
            </div>
            <div class="flex items-center gap-1.5">
                @foreach($rangeOptions as $key => $label)
                    @php $isActive = $key === $rangeKey; @endphp
                    <a href="{{ route('admin.analytics.index', ['range' => $key]) }}"
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold transition {{ $isActive ? 'bg-gradient-to-r from-primary to-indigo-700 text-white shadow-md' : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Hero row: 3 navy cards (Today / Week / Month) --}}
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 mb-4">
            {{-- Today big hero (2 cols) --}}
            <div class="lg:col-span-2 relative rounded-3xl text-white p-7 overflow-hidden" style="background: linear-gradient(135deg, #04042a 0%, #070740 50%, #0a0d3f 100%);">
                <div class="absolute top-0 left-0 right-0 h-[2px]" style="background: linear-gradient(90deg, #22d3ee, #818cf8, #e879f9);"></div>
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-white/60 font-bold">{{ __('Visitors Today') }}</p>
                        <p class="mt-1.5 text-xs text-white/50">{{ __('Unique sessions in the last 24h') }}</p>
                    </div>
                    <div class="h-12 w-12 rounded-2xl bg-white/10 border border-white/15 grid place-items-center"><i class="fas fa-users-line text-white"></i></div>
                </div>
                <div class="mt-8">
                    <p class="text-5xl md:text-6xl font-black leading-none">{{ number_format($visitorsToday) }}</p>
                </div>
            </div>

            {{-- Week --}}
            <div class="relative rounded-3xl text-white p-6 overflow-hidden" style="background: linear-gradient(135deg, #04042a 0%, #070740 50%, #0a0d3f 100%);">
                <div class="absolute top-0 left-0 right-0 h-[2px]" style="background: linear-gradient(90deg, #34d399, #22d3ee);"></div>
                <p class="text-[10px] uppercase tracking-widest text-white/60 font-bold">{{ __('This Week') }}</p>
                <p class="mt-4 text-4xl font-black">{{ number_format($visitorsWeek) }}</p>
            </div>

            {{-- Month --}}
            <div class="relative rounded-3xl text-white p-6 overflow-hidden" style="background: linear-gradient(135deg, #04042a 0%, #070740 50%, #0a0d3f 100%);">
                <div class="absolute top-0 left-0 right-0 h-[2px]" style="background: linear-gradient(90deg, #c084fc, #818cf8);"></div>
                <p class="text-[10px] uppercase tracking-widest text-white/60 font-bold">{{ __('This Month') }}</p>
                <p class="mt-4 text-4xl font-black">{{ number_format($visitorsMonth) }}</p>
            </div>
        </div>

        {{-- KPI strip --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            @foreach([
                ['kicker' => __('Page Views'),     'value' => $totalPageViews, 'icon' => 'far fa-eye',      'strip' => 'from-indigo-500 to-indigo-600',  'iconClass' => 'bg-indigo-50 text-indigo-600'],
                ['kicker' => __('Add to Cart'),    'value' => $totalAddToCart, 'icon' => 'fas fa-cart-plus','strip' => 'from-rose-400 to-rose-600',      'iconClass' => 'bg-rose-50 text-rose-600'],
                ['kicker' => __('Wishlist Clicks'),'value' => $totalWishlist,  'icon' => 'far fa-heart',    'strip' => 'from-amber-400 to-orange-500',   'iconClass' => 'bg-amber-50 text-amber-600'],
                ['kicker' => __('Top Product'),    'value' => $topProductName, 'icon' => 'fas fa-trophy',   'strip' => 'from-amber-300 to-amber-500',    'iconClass' => 'bg-amber-50 text-amber-700', 'isText' => true],
            ] as $card)
                <div class="relative rounded-3xl bg-white p-6 border border-slate-200/70 overflow-hidden">
                    <div class="absolute top-0 left-0 bottom-0 w-1 bg-gradient-to-b {{ $card['strip'] }}"></div>
                    <div class="flex items-start justify-between">
                        <p class="text-[10px] uppercase tracking-[0.22em] text-slate-500 font-bold">{{ $card['kicker'] }}</p>
                        <div class="h-10 w-10 rounded-xl {{ $card['iconClass'] }} grid place-items-center"><i class="{{ $card['icon'] }}"></i></div>
                    </div>
                    <p class="mt-6 text-{{ ($card['isText'] ?? false) ? '2xl' : '4xl' }} font-black text-primary">
                        {{ ($card['isText'] ?? false) ? $card['value'] : number_format($card['value']) }}
                    </p>
                </div>
            @endforeach
        </div>

        {{-- Top tables --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-8">
            @foreach([
                ['title' => __('Top viewed'),    'rows' => $topViewed,   'iconColor' => 'text-indigo-600', 'icon' => 'far fa-eye'],
                ['title' => __('Top cart adds'), 'rows' => $topCarted,   'iconColor' => 'text-rose-600',   'icon' => 'fas fa-cart-plus'],
                ['title' => __('Top searches'),  'rows' => $topSearches, 'iconColor' => 'text-cyan-700',   'icon' => 'fas fa-magnifying-glass', 'keyword' => true],
            ] as $card)
                <div class="rounded-3xl bg-white p-6 border border-slate-200/70">
                    <h3 class="text-sm font-black text-primary mb-3"><i class="{{ $card['icon'] }} mr-1.5 {{ $card['iconColor'] }}"></i> {{ $card['title'] }}</h3>
                    <table class="w-full text-sm">
                        @forelse($card['rows'] as $i => $row)
                            <tr class="@if($i > 0) border-t border-slate-100 @endif">
                                <td class="py-2"><span class="inline-block w-6 h-6 leading-6 text-center text-xs font-bold rounded bg-slate-100 text-slate-600 mr-2">{{ $i + 1 }}</span>
                                    @if($card['keyword'] ?? false)
                                        {{ $row['keyword'] }}
                                    @else
                                        {{ $row['name'] }}
                                    @endif
                                </td>
                                <td class="py-2 text-right font-bold text-primary">{{ number_format($row['count']) }}</td>
                            </tr>
                        @empty
                            <tr><td class="py-4 text-slate-400 text-center" colspan="2">{{ __('No data yet') }}</td></tr>
                        @endforelse
                    </table>
                </div>
            @endforeach
        </div>

        {{-- Charts --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="rounded-3xl bg-white p-6 border border-slate-200/70">
                <h3 class="text-sm font-black text-primary mb-3">{{ __('Visitors · last 7 days') }}</h3>
                <canvas id="analytics-visitors-chart" height="80"></canvas>
            </div>
            <div class="rounded-3xl bg-white p-6 border border-slate-200/70">
                <h3 class="text-sm font-black text-primary mb-3">{{ __('Product views · last 7 days') }}</h3>
                <canvas id="analytics-views-chart" height="80"></canvas>
            </div>
        </div>

    </div>
    </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const visitors = @json($visitorsSeries);
            const views = @json($viewsSeries);

            new window.Chart(document.getElementById('analytics-visitors-chart'), {
                type: 'line',
                data: {
                    labels: visitors.map(r => r.date),
                    datasets: [{
                        label: 'Visitors',
                        data: visitors.map(r => r.count),
                        borderColor: '#4f46e5',
                        backgroundColor: 'rgba(79,70,229,0.18)',
                        fill: true,
                        tension: 0.35,
                    }],
                },
                options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } },
            });

            new window.Chart(document.getElementById('analytics-views-chart'), {
                type: 'bar',
                data: {
                    labels: views.map(r => r.date),
                    datasets: [{
                        label: 'Views',
                        data: views.map(r => r.count),
                        backgroundColor: '#f59e0b',
                    }],
                },
                options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } },
            });
        });
    </script>
    @endpush
</x-app-layout>
```

> If `x-app-layout` does not define a `scripts` stack, replace `@push('scripts') … @endpush` with an inline `<script>` block at the bottom of the page (outside the layout slot).

- [ ] **Step 2: Manual visual check**

Run the dev server, log in as admin, navigate to `/admin/analytics`. Confirm:
- Hero, KPI strip, three tables, two charts all render.
- Charts populate with whatever data exists; if you have no analytics yet, hit a few product pages first to seed counters.
- Range chips switch between 7D / 30D / 90D / 1Y.

- [ ] **Step 3: Commit**

```bash
git add resources/views/admin/analytics/index.blade.php
git commit -m "$(cat <<'EOF'
Build admin Analytics page (Option-1 layout)

Hero with today/week/month visitors, KPI strip for page views /
add-to-cart / wishlist / top product, three top-N tables, and two
Chart.js charts for visitor and view trends.

Co-Authored-By: Claude Opus 4.7 <noreply@anthropic.com>
EOF
)"
```

---

## Final acceptance check

Once all tasks are committed, run the full analytics test bundle:

```
vendor/bin/phpunit --filter 'Analytics|BotDetector|SearchKeywordNormalizer'
```

Expected: all tests green.

Smoke-test the user flow:

1. Visit `/shop` as a guest in a normal browser.
2. Open a product detail page.
3. Add the product to cart.
4. Add a product to the wishlist (if signed in).
5. Search for "brake pad" from the shop search bar.
6. Log in as admin, go to `/admin/analytics`, confirm all counters updated.
