# Product Change Approvals Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Non-admin roles' (everyone except `admin`/`super_admin`) product create/update actions, and a new "request deletion" action, are staged as pending `ProductChangeRequest` rows instead of touching the live `products` table, until a `super_admin` approves them on a dedicated page.

**Architecture:** Extract the DB-writing tail of `ProductController::store/update/destroy` into a new `ProductWriteService` (`applyCreate`/`applyUpdate`/`applyDelete`) so identical logic runs whether a write happens immediately (admin/super_admin) or after approval (super_admin, via a new `ProductApprovalController`). `store()`/`update()` branch on `auth()->user()->isAdmin()`: true → call the service directly (today's behavior, unchanged); false → persist a `product_change_requests` row and stop.

**Tech Stack:** Laravel 12, Blade, MySQL/SQLite (tests), PHPUnit.

**Spec:** `docs/superpowers/specs/2026-09-16-product-change-approvals-design.md`

---

## Conventions used throughout this plan

- PHP binary (not on PATH): `C:\laragon\bin\php\php-8.3.31-Win32-vs16-x64\php.exe`
- Run a single test file: `& "C:\laragon\bin\php\php-8.3.31-Win32-vs16-x64\php.exe" artisan test tests/Feature/Admin/<File>.php`
- Run one test method: append `--filter=test_method_name`
- Every new/changed PHP file gets `php -l`'d before running tests.
- Commit after every task (not every step) — each task is a coherent, reviewable unit.

---

### Task 1: `product_change_requests` table + `ProductChangeRequest` model

**Files:**
- Create: `database/migrations/2026_09_16_000001_create_product_change_requests_table.php`
- Create: `app/Models/ProductChangeRequest.php`

- [ ] **Step 1: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_change_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 10); // create | update | delete
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->json('payload')->nullable();
            $table->string('status', 10)->default('pending'); // pending | approved | rejected
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('rejection_note', 500)->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_change_requests');
    }
};
```

- [ ] **Step 2: Run the migration**

Run: `& "C:\laragon\bin\php\php-8.3.31-Win32-vs16-x64\php.exe" artisan migrate`
Expected: `Migrating: 2026_09_16_000001_create_product_change_requests_table` then `Migrated:` with no errors.

- [ ] **Step 3: Write the model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductChangeRequest extends Model
{
    use HasFactory;

    public const TYPE_CREATE = 'create';

    public const TYPE_UPDATE = 'update';

    public const TYPE_DELETE = 'delete';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    /**
     * Product fields this feature stages for approval. Shared by
     * ProductController (building the payload) and ProductWriteService
     * (writing only these keys back, even though payload is arbitrary JSON).
     *
     * @var array<int, string>
     */
    public const CORE_FIELDS = [
        'category_id', 'name_en', 'name_ar', 'name_ku',
        'description_en', 'description_ar', 'description_ku',
        'price', 'dealer_price', 'stock_quantity', 'sku',
        'oem_number', 'part_number', 'warranty',
        'product_brand_id', 'brand', 'compatible_models', 'is_active',
    ];

    protected $fillable = [
        'type', 'product_id', 'requested_by', 'payload',
        'status', 'reviewed_by', 'reviewed_at', 'rejection_note',
    ];

    protected $casts = [
        'payload' => 'array',
        'reviewed_at' => 'datetime',
    ];

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<User, $this> */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public static function pendingFor(int $productId): Builder
    {
        return static::query()
            ->where('product_id', $productId)
            ->pending();
    }
}
```

- [ ] **Step 4: Lint the new files**

Run: `& "C:\laragon\bin\php\php-8.3.31-Win32-vs16-x64\php.exe" -l app/Models/ProductChangeRequest.php`
Expected: `No syntax errors detected`

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_09_16_000001_create_product_change_requests_table.php app/Models/ProductChangeRequest.php
git commit -m "Add product_change_requests table and model"
```

---

### Task 2: Extract `ProductWriteService` (behavior-preserving refactor)

This task moves the DB-writing tail of `store()`/`update()`/`destroy()` into a new service, unchanged in behavior — it must produce **zero** test regressions. The critical detail: for `applyUpdate`, the *old* image file must only be deleted from disk inside this method (called only when a write actually applies), never earlier — a staged (pending) update in Task 4 must never touch the live image file before approval.

**Files:**
- Create: `app/Services/Products/ProductWriteService.php`
- Modify: `app/Http/Controllers/Admin/ProductController.php`
- Test: `tests/Feature/Admin/AdminProductsCrudTest.php` (existing — used as the regression check, not modified)

- [ ] **Step 1: Run the existing product tests as a baseline**

Run: `& "C:\laragon\bin\php\php-8.3.31-Win32-vs16-x64\php.exe" artisan test tests/Feature/Admin/AdminProductsCrudTest.php`
Expected: all tests PASS (this is the baseline the refactor must not break).

- [ ] **Step 2: Write `ProductWriteService`**

```php
<?php

namespace App\Services\Products;

use App\Models\Product;
use App\Models\ProductChangeRequest;
use App\Support\AdminLogger;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Owns every write to the `products` table and its images. Called directly
 * for an immediate admin/super_admin write, and again — with the same
 * inputs — from ProductApprovalController when a staged request is
 * approved, so the two paths can never drift apart.
 */
class ProductWriteService
{
    public function applyCreate(array $fields, ?string $imagePath): Product
    {
        $product = Product::create(array_merge(
            Arr::only($fields, ProductChangeRequest::CORE_FIELDS),
            ['image' => $imagePath]
        ));

        if ($imagePath) {
            $product->images()->create([
                'path' => $imagePath,
                'disk' => 'public',
                'alt_text' => $product->name_en,
                'sort_order' => 0,
                'is_primary' => true,
            ]);
        }

        return $product;
    }

    public function applyUpdate(Product $product, array $fields, ?string $newImagePath, bool $removeImage): Product
    {
        $oldImagePath = $product->image;
        $imagePath = $oldImagePath;

        if ($removeImage) {
            if ($oldImagePath) {
                Storage::disk('public')->delete($oldImagePath);
            }
            $imagePath = null;
        }

        if ($newImagePath !== null) {
            if ($oldImagePath && $oldImagePath !== $newImagePath) {
                Storage::disk('public')->delete($oldImagePath);
            }
            $imagePath = $newImagePath;
        }

        $product->update(array_merge(
            Arr::only($fields, ProductChangeRequest::CORE_FIELDS),
            ['image' => $imagePath]
        ));

        if ($removeImage && $oldImagePath) {
            $product->images()->where('path', $oldImagePath)->delete();
        }

        if ($newImagePath !== null) {
            $product->images()->update(['is_primary' => false]);
            $product->images()->create([
                'path' => $newImagePath,
                'disk' => 'public',
                'alt_text' => $product->name_en,
                'sort_order' => 0,
                'is_primary' => true,
            ]);
        }

        $product->load('images');
        if ($imagePath && $product->images->isEmpty()) {
            $product->images()->create([
                'path' => $imagePath,
                'disk' => 'public',
                'alt_text' => $product->name_en,
                'sort_order' => 0,
                'is_primary' => true,
            ]);
            $product->load('images');
        }

        return $product;
    }

    /**
     * Remove a product from the catalogue for good.
     *
     * Only a super admin reaches this directly (destroy()'s own gate) or
     * indirectly (approving a `delete` change request) — see the gates on
     * both entry points.
     *
     * @throws \Illuminate\Database\QueryException if the product is linked
     *         to records the database itself refuses to let go of.
     */
    public function applyDelete(Product $product): void
    {
        $name = (string) $product->name_en;
        $sku = (string) $product->sku;

        // Read before the delete: the image rows go with the product.
        $imagePaths = $this->productImagePaths($product);

        DB::transaction(function () use ($product): void {
            $this->releaseHistoricalReferences($product);
            $product->delete();
        });

        // Files come last, and only once the row is really gone. A failed
        // transaction must not leave a product pointing at pictures that
        // were already thrown away.
        $this->deleteImageFiles($imagePaths);

        AdminLogger::log('product.deleted', null, [
            'product_id' => $product->id,
            'name' => $name,
            'sku' => $sku,
        ]);
    }

    /**
     * Cut the product loose from records that outlive it.
     *
     * Two kinds of row point at a product, and they need opposite treatment.
     * An order line is history — it says what a customer bought and must
     * survive, so its product_id is cleared and its own snapshot carries the
     * name and code. Everything else here belongs to the product itself and
     * has no meaning without it.
     *
     * Rows the schema already cascades — cart items, wishlists, reviews,
     * images, fitments, stock movements, analytics — are left to the
     * database.
     */
    private function releaseHistoricalReferences(Product $product): void
    {
        DB::table('order_items')
            ->where('product_id', $product->id)
            ->update(['product_id' => null]);

        foreach (['price_histories', 'stock_transactions', 'purchase_invoice_items'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'product_id')) {
                DB::table($table)->where('product_id', $product->id)->delete();
            }
        }
    }

    /**
     * Every file this product owns, gallery and main picture alike.
     *
     * @return array<int, string>
     */
    private function productImagePaths(Product $product): array
    {
        $paths = [];

        if ($product->image) {
            $paths[] = (string) $product->image;
        }

        if (Schema::hasTable('product_images')) {
            foreach ($product->images()->get(['path']) as $image) {
                $paths[] = (string) $image->path;
            }
        }

        return array_values(array_unique(array_filter($paths)));
    }

    /**
     * Delete the files, unless another product is still using one.
     *
     * @param  array<int, string>  $paths
     */
    private function deleteImageFiles(array $paths): void
    {
        foreach ($paths as $path) {
            $stillUsed = Product::query()->where('image', $path)->exists()
                || (Schema::hasTable('product_images')
                    && DB::table('product_images')->where('path', $path)->exists());

            if ($stillUsed) {
                continue;
            }

            Storage::disk('public')->delete($path);
        }
    }
}
```

- [ ] **Step 3: Wire the service into `ProductController` and replace the old bodies**

Add a constructor and imports. Modify `app/Http/Controllers/Admin/ProductController.php`:

Replace:
```php
class ProductController extends Controller
{
    public function index(Request $request)
```
With:
```php
class ProductController extends Controller
{
    public function __construct(private readonly ProductWriteService $productWriter) {}

    public function index(Request $request)
```

Add near the top with the other `use` statements:
```php
use App\Services\Products\ProductWriteService;
```

Replace the body of `store()` from `$product = Product::create([` through the closing `]);` and the following `if ($imagePath) { ... }` block (lines 224–254 in the pre-refactor file) with:
```php
        $fields = [
            'category_id' => $request->category_id,
            'name_en' => $request->name_en,
            'name_ar' => $request->name_ar,
            'name_ku' => $request->name_ku,
            'description_en' => $request->description_en,
            'description_ar' => $request->description_ar,
            'description_ku' => $request->description_ku,
            'price' => $basePrice,
            'dealer_price' => $dealerPrice,
            'stock_quantity' => $request->stock_quantity,
            'sku' => $sku,
            'oem_number' => $request->filled('oem_number') ? $request->oem_number : null,
            'part_number' => $request->filled('part_number') ? $request->part_number : null,
            'warranty' => $request->filled('warranty') ? $request->warranty : null,
            'product_brand_id' => $selectedBrand?->id,
            'brand' => $selectedBrand?->name,
            'compatible_models' => $compatibleModels,
            'is_active' => $request->boolean('is_active'),
        ];

        $product = $this->productWriter->applyCreate($fields, $imagePath);
```

(`$this->storeGalleryImages($request, $product, $imagePath ? 1 : 0);` and everything after it stays exactly as it is today — unchanged.)

Replace the body of `update()` from `$product->update([` through the closing `]);` (lines 321–341 in the pre-refactor file) with:
```php
        $fields = [
            'category_id' => $request->category_id,
            'name_en' => $request->name_en,
            'name_ar' => $request->name_ar,
            'name_ku' => $request->name_ku,
            'description_en' => $request->description_en,
            'description_ar' => $request->description_ar,
            'description_ku' => $request->description_ku,
            'price' => $basePrice,
            'dealer_price' => $dealerPrice,
            'stock_quantity' => $request->stock_quantity,
            'sku' => $request->filled('sku') ? $request->sku : $product->sku,
            'oem_number' => $request->filled('oem_number') ? $request->oem_number : null,
            'part_number' => $request->filled('part_number') ? $request->part_number : null,
            'warranty' => $request->filled('warranty') ? $request->warranty : null,
            'product_brand_id' => $brandWasSubmitted ? $selectedBrand?->id : $product->product_brand_id,
            'brand' => $brandWasSubmitted ? $selectedBrand?->name : $product->brand,
            'compatible_models' => $compatibleModels,
            'is_active' => $request->boolean('is_active'),
        ];

        $removeImage = $request->boolean('remove_image');

        $product = $this->productWriter->applyUpdate($product, $fields, $newImagePath, $removeImage);
```

Just above this replacement, the existing lines that resolve `$oldImagePath`/`$imagePath` by directly deleting files must be removed — replace:
```php
        $oldImagePath = $product->image;
        $imagePath = $product->image;
        if ($request->boolean('remove_image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $imagePath = null;
        }
        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $imagePath = SecureImageStorage::store($request->file('image'), 'products');
        }
```
With:
```php
        $newImagePath = $request->hasFile('image')
            ? SecureImageStorage::store($request->file('image'), 'products')
            : null;
```

(`$newImagePath` means "a newly uploaded file, or null" — it is the exact value `applyUpdate` receives as its `$newImagePath` parameter later in this method; there is no separate variable for it. Update the two remaining uses below this point accordingly: the `if ($request->boolean('remove_image') && $oldImagePath)` / gallery-primary-image block that followed the old `$product->update([...])` call is now handled *inside* `applyUpdate` and must be deleted from `update()` — everything from `if ($request->boolean('remove_image') && $oldImagePath) {` through the `if ($imagePath && $product->images->isEmpty()) { ... }` block (today's lines 343–368) is removed; `$product->load('images');` immediately after stays, followed directly by `$this->updateExistingGalleryImages($request, $product);` as today.)

Replace the body of `destroy()`. Today's body from `$name = (string) $product->name_en;` through the `AdminLogger::log(...)` call becomes:
```php
    public function destroy(Request $request, Product $product)
    {
        Gate::authorize('products.delete');

        $returnTo = $this->productsIndexReturnUrl($request);

        try {
            $this->productWriter->applyDelete($product);
        } catch (QueryException $e) {
            Log::error('Product could not be deleted', [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'error' => $e->getMessage(),
            ]);

            return redirect()->to($returnTo)
                ->with('error', __('Product could not be deleted because it is linked to existing records.'));
        }

        return redirect()->to($returnTo)
            ->with('success', __('Product deleted permanently.'));
    }
```

Delete the now-unused private methods `releaseHistoricalReferences`, `productImagePaths`, and `deleteImageFiles` from `ProductController` (they live in `ProductWriteService` now). Leave `storeGalleryImages`, `updateExistingGalleryImages`, and `syncPrimaryImage` in place — still used, unchanged, only ever called from the admin-direct path.

- [ ] **Step 4: Lint**

Run: `& "C:\laragon\bin\php\php-8.3.31-Win32-vs16-x64\php.exe" -l app/Http/Controllers/Admin/ProductController.php`
Run: `& "C:\laragon\bin\php\php-8.3.31-Win32-vs16-x64\php.exe" -l app/Services/Products/ProductWriteService.php`
Expected: `No syntax errors detected` for both.

- [ ] **Step 5: Run the full existing product test suite — must still be all-green**

Run: `& "C:\laragon\bin\php\php-8.3.31-Win32-vs16-x64\php.exe" artisan test tests/Feature/Admin/AdminProductsCrudTest.php`
Expected: same pass count as Step 1. Any failure means the extraction changed behavior — fix before continuing; do not proceed to Task 3 with a red baseline.

- [ ] **Step 6: Commit**

```bash
git add app/Services/Products/ProductWriteService.php app/Http/Controllers/Admin/ProductController.php
git commit -m "Extract ProductWriteService from ProductController (no behavior change)"
```

---

### Task 3: `products.approve` gate

**Files:**
- Modify: `app/Providers/AuthServiceProvider.php:46`

- [ ] **Step 1: Write a failing test**

Add to `tests/Feature/Admin/AdminProductsCrudTest.php` (new test method, anywhere after `test_admin_cannot_delete_product`):

```php
    public function test_only_super_admin_passes_the_products_approve_gate(): void
    {
        $this->assertTrue($this->superAdminUser()->can('products.approve'));
        $this->assertFalse($this->adminUser()->can('products.approve'));
    }
```

Run: `& "C:\laragon\bin\php\php-8.3.31-Win32-vs16-x64\php.exe" artisan test tests/Feature/Admin/AdminProductsCrudTest.php --filter=test_only_super_admin_passes_the_products_approve_gate`
Expected: FAIL — gate `products.approve` is not defined (Laravel returns `false`/denies for an undefined gate, so `assertTrue` fails).

- [ ] **Step 2: Define the gate**

Modify `app/Providers/AuthServiceProvider.php` — add right after the `products.delete` gate:

```php
        // Same owner-only bar as deletion: approving a change is the last
        // word before it reaches the live catalogue.
        Gate::define('products.approve', fn (User $user): bool => $user->isSuperAdmin());
```

- [ ] **Step 3: Run the test again**

Run: `& "C:\laragon\bin\php\php-8.3.31-Win32-vs16-x64\php.exe" artisan test tests/Feature/Admin/AdminProductsCrudTest.php --filter=test_only_super_admin_passes_the_products_approve_gate`
Expected: PASS

- [ ] **Step 4: Commit**

```bash
git add app/Providers/AuthServiceProvider.php tests/Feature/Admin/AdminProductsCrudTest.php
git commit -m "Add products.approve gate (super_admin only)"
```

---

### Task 4: Non-admin create/update is staged, not applied

**Files:**
- Modify: `app/Http/Controllers/Admin/ProductController.php` (`store()`, `update()`)
- Test: Create `tests/Feature/Admin/ProductChangeApprovalTest.php`

- [ ] **Step 1: Write the failing tests**

```php
<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductChangeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductChangeApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function productManager(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_PRODUCT_MANAGER,
            'email_verified_at' => now(),
        ]);
    }

    private function adminUser(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);
    }

    private function superAdminUser(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
    }

    private function createCategory(): Category
    {
        return Category::factory()->create([
            'name_en' => 'Test Category',
            'name_ar' => 'Test Category',
            'name_ku' => 'Test Category',
            'slug' => 'test-category',
        ]);
    }

    private function productPayload(Category $category, array $overrides = []): array
    {
        return array_merge([
            'name_en' => 'Oil Filter',
            'name_ar' => 'Oil Filter',
            'name_ku' => 'Oil Filter',
            'description_en' => 'Test description',
            'price' => 15000,
            'dealer_price' => 12000,
            'stock_quantity' => 20,
            'sku' => 'SKU-PM-CREATE-01',
            'brand' => 'Bosch',
            'category_id' => $category->id,
            'is_active' => true,
        ], $overrides);
    }

    public function test_product_manager_create_is_queued_not_applied(): void
    {
        $category = $this->createCategory();

        $response = $this->actingAs($this->productManager())
            ->post(route('admin.products.store'), $this->productPayload($category));

        $response->assertRedirect(route('admin.products.index'));
        $this->assertDatabaseMissing('products', ['sku' => 'SKU-PM-CREATE-01']);
        $this->assertDatabaseHas('product_change_requests', [
            'type' => ProductChangeRequest::TYPE_CREATE,
            'product_id' => null,
            'status' => ProductChangeRequest::STATUS_PENDING,
        ]);

        $request = ProductChangeRequest::query()->firstOrFail();
        $this->assertSame('Oil Filter', $request->payload['name_en']);
        $this->assertSame('SKU-PM-CREATE-01', $request->payload['sku']);
    }

    public function test_product_manager_update_is_queued_not_applied(): void
    {
        $category = $this->createCategory();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'sku' => 'SKU-PM-UPDATE-01',
            'name_en' => 'Original Name',
            'price' => 10000,
        ]);

        $response = $this->actingAs($this->productManager())
            ->put(route('admin.products.update', $product), $this->productPayload($category, [
                'name_en' => 'Changed Name',
                'sku' => 'SKU-PM-UPDATE-01',
                'price' => 99000,
            ]));

        $response->assertRedirect(route('admin.products.index'));

        $product->refresh();
        $this->assertSame('Original Name', $product->name_en);
        $this->assertEquals(10000, $product->price);

        $this->assertDatabaseHas('product_change_requests', [
            'type' => ProductChangeRequest::TYPE_UPDATE,
            'product_id' => $product->id,
            'status' => ProductChangeRequest::STATUS_PENDING,
        ]);
        $request = ProductChangeRequest::query()->where('product_id', $product->id)->firstOrFail();
        $this->assertSame('Changed Name', $request->payload['name_en']);
    }

    public function test_admin_create_and_update_still_apply_immediately(): void
    {
        $category = $this->createCategory();
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $this->productPayload($category, ['sku' => 'SKU-ADMIN-DIRECT']))
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', ['sku' => 'SKU-ADMIN-DIRECT']);
        $this->assertDatabaseCount('product_change_requests', 0);

        $product = Product::query()->where('sku', 'SKU-ADMIN-DIRECT')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.products.update', $product), $this->productPayload($category, [
                'sku' => 'SKU-ADMIN-DIRECT',
                'name_en' => 'Admin Edited Directly',
            ]))
            ->assertRedirect(route('admin.products.index'));

        $product->refresh();
        $this->assertSame('Admin Edited Directly', $product->name_en);
        $this->assertDatabaseCount('product_change_requests', 0);
    }

    public function test_second_pending_update_for_the_same_product_is_refused(): void
    {
        $category = $this->createCategory();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'sku' => 'SKU-PM-DUPLICATE',
        ]);
        $manager = $this->productManager();

        $this->actingAs($manager)
            ->put(route('admin.products.update', $product), $this->productPayload($category, ['sku' => 'SKU-PM-DUPLICATE']))
            ->assertRedirect(route('admin.products.index'));

        $this->actingAs($manager)
            ->put(route('admin.products.update', $product), $this->productPayload($category, [
                'sku' => 'SKU-PM-DUPLICATE',
                'name_en' => 'Second Attempt',
            ]))
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseCount('product_change_requests', 1);
    }
}
```

Run: `& "C:\laragon\bin\php\php-8.3.31-Win32-vs16-x64\php.exe" artisan test tests/Feature/Admin/ProductChangeApprovalTest.php`
Expected: FAIL — `store()`/`update()` still apply directly for every role, so `test_product_manager_create_is_queued_not_applied` and `test_product_manager_update_is_queued_not_applied` fail (`products` table has the row; `product_change_requests` is empty).

- [ ] **Step 2: Branch `store()` on `isAdmin()`**

Modify `app/Http/Controllers/Admin/ProductController.php` — immediately after the `$fields = [...]` array built in Task 2 Step 3 (and before `$product = $this->productWriter->applyCreate($fields, $imagePath);`), insert:

```php
        if (! auth()->user()->isAdmin()) {
            ProductChangeRequest::create([
                'type' => ProductChangeRequest::TYPE_CREATE,
                'product_id' => null,
                'requested_by' => auth()->id(),
                'payload' => array_merge($fields, ['image' => $imagePath]),
                'status' => ProductChangeRequest::STATUS_PENDING,
            ]);

            return redirect()->to($this->productsIndexReturnUrl($request))
                ->with('success', __('Submitted for review. A super admin will approve or reject this new product.'));
        }

```

Add the import near the top of the file:
```php
use App\Models\ProductChangeRequest;
```

- [ ] **Step 3: Branch `update()` on `isAdmin()`**

Immediately after the `$fields = [...]` array built in Task 2 Step 3 for `update()`, and before the `$removeImage`/`$newImagePath`/`applyUpdate` lines, insert:

```php
        if (! auth()->user()->isAdmin()) {
            if (ProductChangeRequest::pendingFor($product->id)->exists()) {
                return redirect()->to($this->productsIndexReturnUrl($request))
                    ->with('error', __('This product already has a change awaiting approval.'));
            }

            ProductChangeRequest::create([
                'type' => ProductChangeRequest::TYPE_UPDATE,
                'product_id' => $product->id,
                'requested_by' => auth()->id(),
                'payload' => array_merge($fields, [
                    'image' => $newImagePath,
                    'remove_image' => $request->boolean('remove_image'),
                ]),
                'status' => ProductChangeRequest::STATUS_PENDING,
            ]);

            return redirect()->to($this->productsIndexReturnUrl($request))
                ->with('success', __('Submitted for review. A super admin will approve or reject this change.'));
        }

```

(`$newImagePath` is already resolved above this point per Task 2 Step 3 — the same value `applyUpdate` receives on the admin path. `remove_image` is read directly from the request here since the admin-path variable for it, `$removeImage`, is declared just below this inserted block.)

- [ ] **Step 4: Run the new tests**

Run: `& "C:\laragon\bin\php\php-8.3.31-Win32-vs16-x64\php.exe" artisan test tests/Feature/Admin/ProductChangeApprovalTest.php`
Expected: PASS — all 4 tests green.

- [ ] **Step 5: Re-run the full product regression suite**

Run: `& "C:\laragon\bin\php\php-8.3.31-Win32-vs16-x64\php.exe" artisan test tests/Feature/Admin/AdminProductsCrudTest.php`
Expected: PASS — admin/super_admin behavior is untouched.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Admin/ProductController.php tests/Feature/Admin/ProductChangeApprovalTest.php
git commit -m "Queue non-admin product create/update as pending change requests"
```

---

### Task 5: "Request deletion" for non-admin roles

**Files:**
- Modify: `app/Http/Controllers/Admin/ProductController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Admin/ProductChangeApprovalTest.php`

- [ ] **Step 1: Write the failing test**

Add to `tests/Feature/Admin/ProductChangeApprovalTest.php`:

```php
    public function test_product_manager_can_request_deletion_admin_still_deletes_directly(): void
    {
        $category = $this->createCategory();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'sku' => 'SKU-PM-REQUEST-DELETE',
        ]);

        $response = $this->actingAs($this->productManager())
            ->post(route('admin.products.request-destroy', $product));

        $response->assertRedirect(route('admin.products.index'));
        $this->assertDatabaseHas('products', ['id' => $product->id]);
        $this->assertDatabaseHas('product_change_requests', [
            'type' => ProductChangeRequest::TYPE_DELETE,
            'product_id' => $product->id,
            'status' => ProductChangeRequest::STATUS_PENDING,
        ]);

        $superAdmin = $this->superAdminUser();
        $directProduct = Product::factory()->create([
            'category_id' => $category->id,
            'sku' => 'SKU-SUPERADMIN-DIRECT-DELETE',
        ]);
        $this->actingAs($superAdmin)
            ->delete(route('admin.products.destroy', $directProduct))
            ->assertRedirect(route('admin.products.index'));
        $this->assertDatabaseMissing('products', ['id' => $directProduct->id]);
    }

    public function test_admin_cannot_use_the_request_deletion_route(): void
    {
        $category = $this->createCategory();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'sku' => 'SKU-ADMIN-REQUEST-DELETE-DENIED',
        ]);

        $response = $this->actingAs($this->adminUser())
            ->post(route('admin.products.request-destroy', $product));

        $response->assertForbidden();
        $this->assertDatabaseCount('product_change_requests', 0);
    }
```

Run: `& "C:\laragon\bin\php\php-8.3.31-Win32-vs16-x64\php.exe" artisan test tests/Feature/Admin/ProductChangeApprovalTest.php --filter=request_deletion`
Expected: FAIL — route `admin.products.request-destroy` does not exist.

- [ ] **Step 2: Add the route**

Modify `routes/web.php` — right after the existing `products.destroy` route:

```php
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])
            ->middleware(['can:products.delete', 'throttle:admin-write'])
            ->name('products.destroy');
        Route::post('/products/{product}/request-deletion', [ProductController::class, 'requestDestroy'])
            ->middleware(['can:'.User::PERMISSION_PRODUCTS_MANAGE, 'throttle:admin-write'])
            ->name('products.request-destroy');
```

- [ ] **Step 3: Add the controller method**

Modify `app/Http/Controllers/Admin/ProductController.php` — add right after `destroy()`:

```php
    /**
     * Anyone who is not admin/super_admin has no delete button at all
     * (see the products.delete gate) — this is the only way they can ask
     * for a product to go away, and it still needs a super admin's yes.
     */
    public function requestDestroy(Request $request, Product $product): RedirectResponse
    {
        if (auth()->user()->isAdmin()) {
            abort(403);
        }

        $returnTo = $this->productsIndexReturnUrl($request);

        if (ProductChangeRequest::pendingFor($product->id)->exists()) {
            return redirect()->to($returnTo)
                ->with('error', __('This product already has a change awaiting approval.'));
        }

        ProductChangeRequest::create([
            'type' => ProductChangeRequest::TYPE_DELETE,
            'product_id' => $product->id,
            'requested_by' => auth()->id(),
            'payload' => null,
            'status' => ProductChangeRequest::STATUS_PENDING,
        ]);

        return redirect()->to($returnTo)
            ->with('success', __('Deletion requested. A super admin will review it.'));
    }
```

- [ ] **Step 4: Run the tests**

Run: `& "C:\laragon\bin\php\php-8.3.31-Win32-vs16-x64\php.exe" artisan test tests/Feature/Admin/ProductChangeApprovalTest.php`
Expected: PASS — all tests green, including the two new ones.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Admin/ProductController.php routes/web.php tests/Feature/Admin/ProductChangeApprovalTest.php
git commit -m "Add request-deletion route for non-admin product roles"
```

---

### Task 6: `ProductApprovalController` — approve/reject

**Files:**
- Create: `app/Http/Controllers/Admin/ProductApprovalController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Admin/ProductChangeApprovalTest.php`

- [ ] **Step 1: Write the failing tests**

Add to `tests/Feature/Admin/ProductChangeApprovalTest.php`:

```php
    public function test_only_super_admin_can_reach_the_approval_routes(): void
    {
        $category = $this->createCategory();
        $product = Product::factory()->create(['category_id' => $category->id]);
        $changeRequest = ProductChangeRequest::create([
            'type' => ProductChangeRequest::TYPE_DELETE,
            'product_id' => $product->id,
            'requested_by' => $this->productManager()->id,
            'payload' => null,
            'status' => ProductChangeRequest::STATUS_PENDING,
        ]);

        foreach ([$this->productManager(), $this->adminUser()] as $notAllowed) {
            $this->actingAs($notAllowed)->get(route('admin.product-approvals.index'))->assertForbidden();
            $this->actingAs($notAllowed)->post(route('admin.product-approvals.approve', $changeRequest))->assertForbidden();
        }

        $this->actingAs($this->superAdminUser())
            ->get(route('admin.product-approvals.index'))
            ->assertOk();
    }

    public function test_approving_a_create_request_creates_the_product(): void
    {
        $category = $this->createCategory();
        $manager = $this->productManager();

        $this->actingAs($manager)->post(route('admin.products.store'), $this->productPayload($category, [
            'sku' => 'SKU-APPROVE-CREATE',
        ]));
        $changeRequest = ProductChangeRequest::query()->where('type', ProductChangeRequest::TYPE_CREATE)->firstOrFail();

        $response = $this->actingAs($this->superAdminUser())
            ->post(route('admin.product-approvals.approve', $changeRequest));

        $response->assertRedirect();
        $this->assertDatabaseHas('products', ['sku' => 'SKU-APPROVE-CREATE']);
        $changeRequest->refresh();
        $this->assertSame(ProductChangeRequest::STATUS_APPROVED, $changeRequest->status);
        $this->assertNotNull($changeRequest->reviewed_by);
        $this->assertNotNull($changeRequest->reviewed_at);
    }

    public function test_approving_an_update_request_applies_it(): void
    {
        $category = $this->createCategory();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'sku' => 'SKU-APPROVE-UPDATE',
            'name_en' => 'Before Approval',
        ]);
        $this->actingAs($this->productManager())->put(route('admin.products.update', $product), $this->productPayload($category, [
            'sku' => 'SKU-APPROVE-UPDATE',
            'name_en' => 'After Approval',
        ]));
        $changeRequest = ProductChangeRequest::query()->where('product_id', $product->id)->firstOrFail();

        $this->actingAs($this->superAdminUser())
            ->post(route('admin.product-approvals.approve', $changeRequest))
            ->assertRedirect();

        $product->refresh();
        $this->assertSame('After Approval', $product->name_en);
        $this->assertSame(ProductChangeRequest::STATUS_APPROVED, $changeRequest->fresh()->status);
    }

    public function test_approving_a_delete_request_deletes_the_product(): void
    {
        $category = $this->createCategory();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'sku' => 'SKU-APPROVE-DELETE',
        ]);
        $this->actingAs($this->productManager())->post(route('admin.products.request-destroy', $product));
        $changeRequest = ProductChangeRequest::query()->where('product_id', $product->id)->firstOrFail();

        $this->actingAs($this->superAdminUser())
            ->post(route('admin.product-approvals.approve', $changeRequest))
            ->assertRedirect();

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertSame(ProductChangeRequest::STATUS_APPROVED, $changeRequest->fresh()->status);
    }

    public function test_rejecting_leaves_the_product_untouched(): void
    {
        $category = $this->createCategory();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'sku' => 'SKU-REJECT-UPDATE',
            'name_en' => 'Stays The Same',
        ]);
        $this->actingAs($this->productManager())->put(route('admin.products.update', $product), $this->productPayload($category, [
            'sku' => 'SKU-REJECT-UPDATE',
            'name_en' => 'Should Not Apply',
        ]));
        $changeRequest = ProductChangeRequest::query()->where('product_id', $product->id)->firstOrFail();

        $this->actingAs($this->superAdminUser())
            ->post(route('admin.product-approvals.reject', $changeRequest), ['rejection_note' => 'Price looks wrong'])
            ->assertRedirect();

        $product->refresh();
        $this->assertSame('Stays The Same', $product->name_en);
        $changeRequest->refresh();
        $this->assertSame(ProductChangeRequest::STATUS_REJECTED, $changeRequest->status);
        $this->assertSame('Price looks wrong', $changeRequest->rejection_note);
    }
```

Run: `& "C:\laragon\bin\php\php-8.3.31-Win32-vs16-x64\php.exe" artisan test tests/Feature/Admin/ProductChangeApprovalTest.php --filter=approv`
Expected: FAIL — route `admin.product-approvals.*` does not exist.

- [ ] **Step 2: Add the routes**

Modify `routes/web.php` — add a new block right after the Products block (after `products.export-excel` and `products.edit-by-identifier`, before `purchase-planning.index`):

```php
        // Product change approvals — super_admin reviews what non-admin
        // roles submitted for products.
        Route::get('/product-approvals', [ProductApprovalController::class, 'index'])
            ->middleware('can:products.approve')
            ->name('product-approvals.index');
        Route::post('/product-approvals/{productChangeRequest}/approve', [ProductApprovalController::class, 'approve'])
            ->middleware(['can:products.approve', 'throttle:admin-write'])
            ->name('product-approvals.approve');
        Route::post('/product-approvals/{productChangeRequest}/reject', [ProductApprovalController::class, 'reject'])
            ->middleware(['can:products.approve', 'throttle:admin-write'])
            ->name('product-approvals.reject');
```

Add the import near the top of `routes/web.php`, alongside the other `Admin\*Controller` imports:
```php
use App\Http\Controllers\Admin\ProductApprovalController;
```

- [ ] **Step 3: Write the controller**

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductChangeRequest;
use App\Services\Products\ProductWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class ProductApprovalController extends Controller
{
    public function __construct(private readonly ProductWriteService $productWriter) {}

    public function index(): View
    {
        $pending = ProductChangeRequest::query()
            ->with(['product', 'requestedBy'])
            ->pending()
            ->orderBy('created_at')
            ->get();

        $reviewed = ProductChangeRequest::query()
            ->with(['product', 'requestedBy', 'reviewedBy'])
            ->whereIn('status', [ProductChangeRequest::STATUS_APPROVED, ProductChangeRequest::STATUS_REJECTED])
            ->where('reviewed_at', '>=', now()->subDays(30))
            ->orderByDesc('reviewed_at')
            ->limit(50)
            ->get();

        return view('admin.product-approvals.index', compact('pending', 'reviewed'));
    }

    public function approve(ProductChangeRequest $productChangeRequest): RedirectResponse
    {
        abort_unless($productChangeRequest->status === ProductChangeRequest::STATUS_PENDING, 404);

        if ($productChangeRequest->type !== ProductChangeRequest::TYPE_CREATE && ! $productChangeRequest->product) {
            return back()->with('error', __('This product no longer exists — reject this request instead.'));
        }

        try {
            match ($productChangeRequest->type) {
                ProductChangeRequest::TYPE_CREATE => $this->productWriter->applyCreate(
                    $productChangeRequest->payload,
                    $productChangeRequest->payload['image'] ?? null
                ),
                ProductChangeRequest::TYPE_UPDATE => $this->productWriter->applyUpdate(
                    $productChangeRequest->product,
                    $productChangeRequest->payload,
                    $productChangeRequest->payload['image'] ?? null,
                    (bool) ($productChangeRequest->payload['remove_image'] ?? false)
                ),
                ProductChangeRequest::TYPE_DELETE => $this->productWriter->applyDelete($productChangeRequest->product),
            };
        } catch (Throwable $e) {
            Log::error('product_change_request.approve_failed', [
                'id' => $productChangeRequest->id,
                'type' => $productChangeRequest->type,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', __('Could not apply this change. It has been left pending — please try again.'));
        }

        $productChangeRequest->update([
            'status' => ProductChangeRequest::STATUS_APPROVED,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', __('Change approved and applied.'));
    }

    public function reject(Request $request, ProductChangeRequest $productChangeRequest): RedirectResponse
    {
        abort_unless($productChangeRequest->status === ProductChangeRequest::STATUS_PENDING, 404);

        $data = $request->validate([
            'rejection_note' => ['nullable', 'string', 'max:500'],
        ]);

        $productChangeRequest->update([
            'status' => ProductChangeRequest::STATUS_REJECTED,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'rejection_note' => $data['rejection_note'] ?? null,
        ]);

        return back()->with('success', __('Change rejected.'));
    }
}
```

- [ ] **Step 4: Lint**

Run: `& "C:\laragon\bin\php\php-8.3.31-Win32-vs16-x64\php.exe" -l app/Http/Controllers/Admin/ProductApprovalController.php`
Expected: `No syntax errors detected`

- [ ] **Step 5: Run the tests**

Run: `& "C:\laragon\bin\php\php-8.3.31-Win32-vs16-x64\php.exe" artisan test tests/Feature/Admin/ProductChangeApprovalTest.php`
Expected: still FAIL on the two tests hitting `route('admin.product-approvals.index')` for a `GET` (`test_only_super_admin_can_reach_the_approval_routes`) — the view doesn't exist yet. That's expected; Task 7 adds it. Every other test (approve/reject logic) should PASS since `approve`/`reject` are `RedirectResponse`s with no view involved. If `test_only_super_admin_can_reach_the_approval_routes` fails only on the `get(...)->assertOk()` line with a missing-view error, that confirms routing + gates are wired correctly — proceed to Task 7.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Admin/ProductApprovalController.php routes/web.php tests/Feature/Admin/ProductChangeApprovalTest.php
git commit -m "Add ProductApprovalController: approve/reject staged product changes"
```

---

### Task 7: Approval page view

**Files:**
- Create: `resources/views/admin/product-approvals/index.blade.php`

- [ ] **Step 1: Write the view**

```blade
<x-app-layout>

<style>
    .bento-shadow { box-shadow: var(--admin-shadow-soft); }
    .kicker { font-size: 10px; font-weight: 700; letter-spacing: 0.22em; text-transform: uppercase; color: var(--text-muted); }
    .type-badge { display:inline-flex; align-items:center; gap:4px; border-radius:999px; padding:2px 10px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; }
    .type-badge.create { background:rgb(16 185 129 / .12); color:#047857; }
    .type-badge.update { background:rgb(59 130 246 / .12); color:#1d4ed8; }
    .type-badge.delete { background:rgb(244 63 94 / .12); color:#be123c; }
    .status-badge { display:inline-flex; align-items:center; gap:4px; border-radius:999px; padding:2px 10px; font-size:11px; font-weight:700; }
    .status-badge.approved { background:rgb(16 185 129 / .12); color:#047857; }
    .status-badge.rejected { background:rgb(244 63 94 / .12); color:#be123c; }
    .diff-row { display:grid; grid-template-columns: 140px 1fr 20px 1fr; gap:8px; align-items:center; font-size:12px; }
    .diff-old { color:#94a3b8; text-decoration:line-through; }
    .diff-new { color:#0f172a; font-weight:600; }
</style>

<div class="px-4 sm:px-6 lg:px-8 py-6 space-y-6">
    <div class="flex items-center gap-2 text-xs font-bold">
        <span class="text-slate-900">{{ __('Product Approvals') }}</span>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white bento-shadow">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <h3 class="text-sm font-bold text-slate-900">
                <i class="fas fa-clock mr-1 text-amber-600" aria-hidden="true"></i>
                {{ __('Pending') }}
            </h3>
            <span class="kicker">{{ number_format($pending->count()) }}</span>
        </div>

        @if($pending->isEmpty())
            <div class="px-5 py-10 text-center text-xs text-muted">{{ __('Nothing waiting for review.') }}</div>
        @else
            <div class="divide-y divide-slate-100">
                @foreach($pending as $changeRequest)
                    @php
                        $productLabel = $changeRequest->type === \App\Models\ProductChangeRequest::TYPE_CREATE
                            ? ($changeRequest->payload['name_en'] ?? __('New product'))
                            : ($changeRequest->product->name_en ?? __('Product no longer exists'));
                    @endphp
                    <div class="px-5 py-4 space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span class="type-badge {{ $changeRequest->type }}">{{ __(ucfirst($changeRequest->type)) }}</span>
                                <span class="text-sm font-bold text-slate-900">{{ $productLabel }}</span>
                                @if($changeRequest->product?->sku)
                                    <span class="font-mono text-xs text-slate-500">{{ $changeRequest->product->sku }}</span>
                                @endif
                            </div>
                            <span class="text-xs text-slate-500">
                                {{ __('Requested by :name :time', ['name' => $changeRequest->requestedBy->name ?? '—', 'time' => $changeRequest->created_at->diffForHumans()]) }}
                            </span>
                        </div>

                        @if($changeRequest->type === \App\Models\ProductChangeRequest::TYPE_UPDATE && $changeRequest->product)
                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3 space-y-1">
                                @foreach(\App\Models\ProductChangeRequest::CORE_FIELDS as $field)
                                    @php
                                        $old = $changeRequest->product->{$field};
                                        $new = $changeRequest->payload[$field] ?? null;
                                        $oldDisplay = is_array($old) ? implode(', ', $old) : $old;
                                        $newDisplay = is_array($new) ? implode(', ', $new) : $new;
                                    @endphp
                                    @if((string) $oldDisplay !== (string) $newDisplay)
                                        <div class="diff-row">
                                            <span class="text-slate-500">{{ $field }}</span>
                                            <span class="diff-old">{{ $oldDisplay }}</span>
                                            <i class="fas fa-arrow-right text-slate-400 rtl:rotate-180" aria-hidden="true"></i>
                                            <span class="diff-new">{{ $newDisplay }}</span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @elseif($changeRequest->type === \App\Models\ProductChangeRequest::TYPE_CREATE)
                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3 text-xs text-slate-700">
                                {{ __('Price') }}: {{ number_format((float) ($changeRequest->payload['price'] ?? 0), 2) }}
                                · {{ __('Stock') }}: {{ (int) ($changeRequest->payload['stock_quantity'] ?? 0) }}
                                · {{ __('SKU') }}: {{ $changeRequest->payload['sku'] ?? '—' }}
                            </div>
                        @endif

                        <div class="flex items-center gap-2">
                            <form method="POST" action="{{ route('admin.product-approvals.approve', $changeRequest) }}">
                                @csrf
                                <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-emerald-700">
                                    {{ __('Approve') }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.product-approvals.reject', $changeRequest) }}" class="flex items-center gap-2">
                                @csrf
                                <input type="text" name="rejection_note" maxlength="500" placeholder="{{ __('Reason (optional)') }}"
                                       class="rounded-lg border border-slate-200 px-2 py-1.5 text-xs">
                                <button type="submit" class="rounded-lg border border-rose-200 bg-white px-3 py-1.5 text-xs font-bold text-rose-700 hover:bg-rose-50">
                                    {{ __('Reject') }}
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white bento-shadow">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <h3 class="text-sm font-bold text-slate-900">{{ __('Recently reviewed') }}</h3>
            <span class="kicker">{{ __('Last 30 days') }}</span>
        </div>
        @if($reviewed->isEmpty())
            <div class="px-5 py-10 text-center text-xs text-muted">{{ __('Nothing reviewed yet.') }}</div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <th class="px-5 py-3">{{ __('Product') }}</th>
                        <th class="px-5 py-3">{{ __('Type') }}</th>
                        <th class="px-5 py-3">{{ __('Status') }}</th>
                        <th class="px-5 py-3">{{ __('Reviewed by') }}</th>
                        <th class="px-5 py-3 text-right">{{ __('When') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reviewed as $changeRequest)
                        <tr class="border-t border-slate-100">
                            <td class="px-5 py-2.5">{{ $changeRequest->product->name_en ?? ($changeRequest->payload['name_en'] ?? __('—')) }}</td>
                            <td class="px-5 py-2.5"><span class="type-badge {{ $changeRequest->type }}">{{ __(ucfirst($changeRequest->type)) }}</span></td>
                            <td class="px-5 py-2.5"><span class="status-badge {{ $changeRequest->status }}">{{ __(ucfirst($changeRequest->status)) }}</span></td>
                            <td class="px-5 py-2.5 text-slate-600">{{ $changeRequest->reviewedBy->name ?? '—' }}</td>
                            <td class="px-5 py-2.5 text-right text-xs text-slate-500">{{ $changeRequest->reviewed_at?->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

</x-app-layout>
```

- [ ] **Step 2: Run the full change-approval test file**

Run: `& "C:\laragon\bin\php\php-8.3.31-Win32-vs16-x64\php.exe" artisan test tests/Feature/Admin/ProductChangeApprovalTest.php`
Expected: PASS — every test, including `test_only_super_admin_can_reach_the_approval_routes`, is now green.

- [ ] **Step 3: Commit**

```bash
git add resources/views/admin/product-approvals/index.blade.php
git commit -m "Add the product approvals review page"
```

---

### Task 8: Sidebar link, page title, and notification bucket

**Files:**
- Modify: `resources/views/layouts/app.blade.php`
- Modify: `app/Http/Controllers/Admin/NotificationController.php`
- Test: `tests/Feature/Admin/ProductChangeApprovalTest.php`

- [ ] **Step 1: Write the failing test**

Add to `tests/Feature/Admin/ProductChangeApprovalTest.php`:

```php
    public function test_super_admin_sees_the_approvals_link_product_manager_does_not(): void
    {
        $this->actingAs($this->superAdminUser())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Product Approvals');

        $this->actingAs($this->productManager())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Product Approvals');
    }

    public function test_notification_bell_includes_pending_product_approvals(): void
    {
        $category = $this->createCategory();
        $product = Product::factory()->create(['category_id' => $category->id]);
        ProductChangeRequest::create([
            'type' => ProductChangeRequest::TYPE_DELETE,
            'product_id' => $product->id,
            'requested_by' => $this->productManager()->id,
            'payload' => null,
            'status' => ProductChangeRequest::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->superAdminUser())
            ->getJson(route('admin.notifications.index'));

        $response->assertOk();
        $response->assertJsonPath('counts.product_approvals', 1);
    }
```

Run: `& "C:\laragon\bin\php\php-8.3.31-Win32-vs16-x64\php.exe" artisan test tests/Feature/Admin/ProductChangeApprovalTest.php --filter="sees_the_approvals_link|notification_bell"`
Expected: FAIL — no sidebar link yet, no `product_approvals` key in the notification JSON yet.

- [ ] **Step 2: Add the sidebar link and page title**

Modify `resources/views/layouts/app.blade.php` — add to `$adminPageTitlePatterns` (right after the `'admin.analytics.*'` entry):

```php
                    'admin.analytics.*'            => __('Site Analytics'),
                    'admin.product-approvals.*'    => __('Product Approvals'),
```

Add a `$canApproveProducts` variable alongside the other `$can*` variables (right after `$canDashboard`):

```php
                $canDashboard  = $adminUserForNav?->can(\App\Models\User::PERMISSION_DASHBOARD_VIEW);
                $canApproveProducts = $adminUserForNav?->can('products.approve');
```

Add `$canApproveProducts` to the `$hasAdminGrp` composition so the "Administration" section renders even for a super admin who otherwise has nothing else in it:

```php
                $hasAdminGrp   = $canUsersView || $canSettings || $canActLogs || $canDealers || $canApproveProducts;
```

Add the link itself inside the `@if($hasAdminGrp)` "Administration" block, right after its opening `<div class="admin-nav-section" ...>`:

```php
                            @if($canApproveProducts)
                                @php
                                    $pendingProductApprovalsCount = \Illuminate\Support\Facades\Schema::hasTable('product_change_requests')
                                        ? \App\Models\ProductChangeRequest::query()->pending()->count()
                                        : 0;
                                @endphp
                                <a
                                    href="{{ route('admin.product-approvals.index') }}"
                                    class="admin-nav-link {{ $navItem(request()->routeIs('admin.product-approvals.*')) }}"
                                    data-admin-sidebar-tooltip="{{ __('Product Approvals') }}"
                                    @if(request()->routeIs('admin.product-approvals.*')) aria-current="page" @endif
                                >
                                    <x-ph-icon name="check-circle" class="admin-nav-icon" />
                                    <span class="admin-nav-label">{{ __('Product Approvals') }}</span>
                                    @if($pendingProductApprovalsCount > 0)
                                        <span class="admin-nav-badge" data-tone="accent">{{ $pendingProductApprovalsCount }}</span>
                                    @endif
                                </a>
                            @endif
```

- [ ] **Step 3: Add the notification bucket**

Modify `app/Http/Controllers/Admin/NotificationController.php`. Add the import:
```php
use App\Models\ProductChangeRequest;
```

Inside the `Cache::remember(...)` closure, after the `$dealerItems`/`$dealerRequestCount` block is computed, add:

```php
            $productApprovalRequests = Schema::hasTable('product_change_requests')
                ? ProductChangeRequest::query()->pending()->orderByDesc('created_at')->limit(5)->with('product')->get()
                : collect();

            $productApprovalItems = $productApprovalRequests->map(fn ($changeRequest) => [
                'key' => $this->makeKey('product_approval', $changeRequest->id, $changeRequest->created_at?->timestamp),
                'id' => $changeRequest->id,
                'title' => $changeRequest->product->name_en ?? ($changeRequest->payload['name_en'] ?? __('New product')),
                'subtitle' => __('Product change awaiting approval'),
                'meta' => ucfirst($changeRequest->type),
                'url' => route('admin.product-approvals.index'),
                'updated_at' => optional($changeRequest->created_at)->toIso8601String(),
            ])->values()->toArray();

            $productApprovalCount = Schema::hasTable('product_change_requests')
                ? ProductChangeRequest::query()->pending()->count()
                : 0;
```

Add `$productApprovalItems` to the `items` array and `$productApprovalCount` to `counts.total` and as its own `counts.product_approvals` key, and to the `items` array under key `product_approvals` — modify the closure's `return [...]`:

```php
            return [
                'counts' => [
                    'total' => $outCount + $lowCount + $dealerRequestCount + $productApprovalCount,
                    'out_of_stock' => $outCount,
                    'low_stock' => $lowCount,
                    'dealer_requests' => $dealerRequestCount,
                    'product_approvals' => $productApprovalCount,
                ],
                'items' => [
                    'out_of_stock' => $outItems,
                    'low_stock' => $lowItems,
                    'dealer_requests' => $dealerItems,
                    'product_approvals' => $productApprovalItems,
                ],
            ];
```

After the cached `$sharedPayload` is read, add the read/unread handling for the new bucket alongside the existing `$dealerItems` handling:

```php
        $productApprovalItems = collect($sharedPayload['items']['product_approvals'] ?? [])
            ->map(fn ($item) => $this->normalizeTimeField($item))
            ->values();
```

Add it to the `$allItems` concat:
```php
        $allItems = collect()
            ->concat($outItems)
            ->concat($lowItems)
            ->concat($dealerItems)
            ->concat($productApprovalItems);
```

Add it to the `$productApprovalItems`-with-read-flag mapping (alongside the existing three) and to the final JSON response's `counts`/`items`:

```php
        $productApprovalItems = $productApprovalItems->map(fn ($item) => array_merge($item, ['read' => in_array($item['key'], $readKeys, true)]));
```

```php
        $productApprovalCount = (int) ($sharedPayload['counts']['product_approvals'] ?? 0);
```

```php
        $unreadTotal = collect()
            ->concat($outItems)
            ->concat($lowItems)
            ->concat($dealerItems)
            ->concat($productApprovalItems)
            ->filter(fn ($item) => ! $item['read'])
            ->count();

        return response()->json([
            'counts' => [
                'total' => (int) ($sharedPayload['counts']['total'] ?? ($outCount + $lowCount + $dealerRequestCount + $productApprovalCount)),
                'unread_total' => $unreadTotal,
                'out_of_stock' => $outCount,
                'low_stock' => $lowCount,
                'low_stock_unread' => $lowUnreadCount,
                'dealer_requests' => $dealerRequestCount,
                'product_approvals' => $productApprovalCount,
            ],
            'threshold' => $lowStockThreshold,
            'items' => [
                'out_of_stock' => $outItems,
                'low_stock' => $lowItems,
                'dealer_requests' => $dealerItems,
                'product_approvals' => $productApprovalItems,
            ],
            'fetched_at' => now()->toIso8601String(),
        ]);
```

- [ ] **Step 4: Confirm the notification route name**

Run: `& "C:\laragon\bin\php\php-8.3.31-Win32-vs16-x64\php.exe" artisan route:list --name=admin.notifications`
Expected: a route named `admin.notifications.index` exists (used by the test in Step 1). If the actual name differs, update the test's `route(...)` call to match — do not rename the existing route.

- [ ] **Step 5: Run the tests**

Run: `& "C:\laragon\bin\php\php-8.3.31-Win32-vs16-x64\php.exe" artisan test tests/Feature/Admin/ProductChangeApprovalTest.php`
Expected: PASS — all tests green.

Run: `& "C:\laragon\bin\php\php-8.3.31-Win32-vs16-x64\php.exe" artisan test tests/Feature/Admin/AdminSidebarTest.php`
Expected: PASS — the sidebar's own structural tests (section-with-nothing-under-it, icon-per-link, active-page accent) are unaffected since `Product Approvals` only ever renders under `$canApproveProducts`, contributing to an already-visited "Administration" section.

- [ ] **Step 6: Commit**

```bash
git add resources/views/layouts/app.blade.php app/Http/Controllers/Admin/NotificationController.php tests/Feature/Admin/ProductChangeApprovalTest.php
git commit -m "Surface product approvals in the sidebar and notification bell"
```

---

### Task 9: Product forms — gate gallery to admin, add banners and the request-deletion button

**Files:**
- Modify: `resources/views/admin/products/create.blade.php`
- Modify: `resources/views/admin/products/edit.blade.php`
- Modify: `resources/views/admin/products/index.blade.php`
- Test: `tests/Feature/Admin/ProductChangeApprovalTest.php`

- [ ] **Step 1: Write the failing tests**

Add to `tests/Feature/Admin/ProductChangeApprovalTest.php`:

```php
    public function test_gallery_upload_is_hidden_from_non_admin_but_visible_to_admin(): void
    {
        $category = $this->createCategory();

        $this->actingAs($this->productManager())
            ->get(route('admin.products.create'))
            ->assertOk()
            ->assertDontSee('gallery_images[]', false);

        $this->actingAs($this->adminUser())
            ->get(route('admin.products.create'))
            ->assertOk()
            ->assertSee('gallery_images[]', false);
    }

    public function test_edit_page_shows_a_banner_when_a_change_is_pending(): void
    {
        $category = $this->createCategory();
        $product = Product::factory()->create(['category_id' => $category->id]);
        $manager = $this->productManager();

        $this->actingAs($manager)->put(route('admin.products.update', $product), $this->productPayload($category, [
            'sku' => $product->sku,
            'name_en' => 'Pending Name',
        ]));

        $this->actingAs($manager)
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('awaiting approval');
    }

    public function test_non_admin_sees_request_deletion_button_admin_sees_nothing_super_admin_sees_delete(): void
    {
        $category = $this->createCategory();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $this->actingAs($this->productManager())
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('Request Deletion')
            ->assertDontSee('Delete Permanently');

        $this->actingAs($this->adminUser())
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertDontSee('Request Deletion')
            ->assertDontSee('Delete Permanently');

        $this->actingAs($this->superAdminUser())
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertDontSee('Request Deletion')
            ->assertSee('Delete Permanently');
    }
```

Run: `& "C:\laragon\bin\php\php-8.3.31-Win32-vs16-x64\php.exe" artisan test tests/Feature/Admin/ProductChangeApprovalTest.php --filter="gallery_upload|shows_a_banner|request_deletion_button"`
Expected: FAIL on all three (gallery visible to everyone today, no banner, no "Request Deletion" button).

- [ ] **Step 2: Gate the gallery section in `create.blade.php`**

Modify `resources/views/admin/products/create.blade.php` — wrap the block found at (today's) lines 232–236:

```php
                                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                        <label class="block text-sm font-medium text-slate-700">{{ __('Gallery Images') }}</label>
                                        <input id="gallery_images" type="file" name="gallery_images[]" accept="image/*" multiple class="mt-2 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700">
                                        <p class="mt-1 text-xs text-slate-500">{{ __('Upload multiple images. The main product image stays primary by default.') }}</p>
                                    </div>
```

With:
```php
                                    @if(auth()->user()->isAdmin())
                                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                            <label class="block text-sm font-medium text-slate-700">{{ __('Gallery Images') }}</label>
                                            <input id="gallery_images" type="file" name="gallery_images[]" accept="image/*" multiple class="mt-2 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700">
                                            <p class="mt-1 text-xs text-slate-500">{{ __('Upload multiple images. The main product image stays primary by default.') }}</p>
                                        </div>
                                    @else
                                        <p class="text-xs text-slate-500">{{ __('Gallery photos can be added once a super admin has approved this product.') }}</p>
                                    @endif
```

- [ ] **Step 3: Gate the gallery section in `edit.blade.php`**

Modify `resources/views/admin/products/edit.blade.php` — wrap the block found at (today's) lines 252–282 (the "Add Gallery Images" upload block AND the existing-gallery management block that follows it):

```php
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                    <label class="block text-sm font-medium text-slate-700">{{ __('Add Gallery Images') }}</label>
                                    <input id="gallery_images" type="file" name="gallery_images[]" accept="image/*" multiple class="mt-2 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700">
                                </div>

                                @if($product->images->isNotEmpty())
                                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                                        ...
                                    </div>
                                @endif
```

With the same content wrapped in `@if(auth()->user()->isAdmin()) ... @endif`, plus an else-branch note:

```php
                                @if(auth()->user()->isAdmin())
                                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                        <label class="block text-sm font-medium text-slate-700">{{ __('Add Gallery Images') }}</label>
                                        <input id="gallery_images" type="file" name="gallery_images[]" accept="image/*" multiple class="mt-2 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700">
                                    </div>

                                    @if($product->images->isNotEmpty())
                                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                                            <p class="text-sm font-semibold text-slate-900">{{ __('Gallery') }}</p>
                                            <div class="mt-4 grid gap-3 md:grid-cols-2">
                                                @foreach($product->images as $image)
                                                    <div class="rounded-xl border border-slate-200 p-3">
                                                        <div class="flex gap-3">
                                                            <img src="{{ asset('storage/' . $image->path) }}" alt="{{ $image->alt_text ?: $product->name }}" class="h-16 w-16 rounded-lg object-cover">
                                                            <div class="min-w-0 flex-1 space-y-2">
                                                                <label class="flex items-center gap-2 text-xs font-semibold text-slate-700">
                                                                    <input type="radio" name="primary_image_id" value="{{ $image->id }}" @checked($image->is_primary) class="border-slate-300 text-info">
                                                                    {{ __('Primary') }}
                                                                </label>
                                                                <input type="number" name="gallery_sort_order[{{ $image->id }}]" value="{{ $image->sort_order }}" min="0" max="10000" class="w-full rounded-lg border-slate-300 px-2 py-1 text-xs dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" placeholder="{{ __('Sort') }}">
                                                                <input type="text" name="gallery_alt_text[{{ $image->id }}]" value="{{ $image->alt_text }}" maxlength="255" class="w-full rounded-lg border-slate-300 px-2 py-1 text-xs dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" placeholder="{{ __('Alt text') }}">
                                                            </div>
                                                        </div>
                                                        <label class="mt-2 inline-flex items-center gap-2 text-xs font-semibold text-rose-700">
                                                            <input type="checkbox" name="remove_gallery_image_ids[]" value="{{ $image->id }}" class="rounded border-slate-300">
                                                            {{ __('Remove') }}
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                @else
                                    <p class="text-xs text-slate-500">{{ __('Gallery photo changes need a super admin — ask them to make this edit, or approve your pending change first.') }}</p>
                                @endif
```

- [ ] **Step 4: Add the pending/rejected banner to `edit.blade.php`**

Modify `resources/views/admin/products/edit.blade.php` — add right after the existing `@if(session('success'))` block (today's lines 43–47):

```php
            @php
                $pendingChangeRequest = \App\Models\ProductChangeRequest::pendingFor($product->id)->first();
                $lastReviewedChangeRequest = \App\Models\ProductChangeRequest::query()
                    ->where('product_id', $product->id)
                    ->whereIn('status', [\App\Models\ProductChangeRequest::STATUS_REJECTED])
                    ->latest('reviewed_at')
                    ->first();
            @endphp

            @if($pendingChangeRequest)
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">
                    {{ __('A change to this product is awaiting approval.') }}
                    @can('products.approve')
                        <a href="{{ route('admin.product-approvals.index') }}" class="ml-2 underline">{{ __('Review it') }}</a>
                    @endcan
                </div>
            @elseif($lastReviewedChangeRequest)
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">
                    {{ __('Your last submitted change was declined.') }}
                    @if($lastReviewedChangeRequest->rejection_note)
                        {{ __('Reason:') }} {{ $lastReviewedChangeRequest->rejection_note }}
                    @endif
                </div>
            @endif
```

- [ ] **Step 5: Add the "Request Deletion" button to `index.blade.php`**

Modify `resources/views/admin/products/index.blade.php` — add an `@else` branch to the existing `@can('products.delete') ... @endcan` block (today's lines 707–730-ish):

```php
                            @can('products.delete')
                                {{-- unchanged --}}
                                ...
                            @elseif(! auth()->user()->isAdmin())
                                <form action="{{ route('admin.products.request-destroy', $product) }}"
                                      method="POST"
                                      data-danger-confirm
                                      data-danger-title="{{ __('Request Deletion') }}"
                                      data-danger-description="{{ __('This sends a deletion request to a super admin for review. The product stays live until they approve it.') }}"
                                      data-danger-subject="{{ $product->name }}"
                                      data-danger-meta="{{ $product->sku ?? '—' }}"
                                      data-danger-phrase="{{ $product->sku ?: 'REQUEST' }}"
                                      data-danger-action="{{ __('Request Deletion') }}"
                                      class="flex-1">
                                    @csrf
                                    <input type="hidden" name="return_to" value="{{ $currentProductsUrl }}">
                                    <button type="submit" class="btn w-full border border-amber-300 bg-amber-50 text-amber-700 hover:bg-amber-100" title="{{ __('Request Deletion') }}">
                                        <i class="fas fa-hourglass-half" aria-hidden="true"></i>
                                        {{ __('Request Deletion') }}
                                    </button>
                                </form>
                            @endcan
```

(Leave the existing `@can('products.delete')` block's own content exactly as it is today — only its closing tag changes from `@endcan` to the `@elseif`/`@endcan` shown above.)

- [ ] **Step 6: Run the tests**

Run: `& "C:\laragon\bin\php\php-8.3.31-Win32-vs16-x64\php.exe" artisan test tests/Feature/Admin/ProductChangeApprovalTest.php`
Expected: PASS — all tests green.

- [ ] **Step 7: Re-run the full product regression suite one more time**

Run: `& "C:\laragon\bin\php\php-8.3.31-Win32-vs16-x64\php.exe" artisan test tests/Feature/Admin/AdminProductsCrudTest.php`
Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add resources/views/admin/products/create.blade.php resources/views/admin/products/edit.blade.php resources/views/admin/products/index.blade.php tests/Feature/Admin/ProductChangeApprovalTest.php
git commit -m "Gate gallery editing to admin, add pending/rejected banners and request-deletion button"
```

---

## Final verification

- [ ] Run the whole admin product + approvals + sidebar surface once more:

Run: `& "C:\laragon\bin\php\php-8.3.31-Win32-vs16-x64\php.exe" artisan test tests/Feature/Admin/AdminProductsCrudTest.php tests/Feature/Admin/ProductChangeApprovalTest.php tests/Feature/Admin/AdminSidebarTest.php`
Expected: all PASS, zero failures.

- [ ] Confirm no stray routes/typos:

Run: `& "C:\laragon\bin\php\php-8.3.31-Win32-vs16-x64\php.exe" artisan route:list --name=product-approvals`
Expected: 3 routes (`index`, `approve`, `reject`), all under `/admin`.
