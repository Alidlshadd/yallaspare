# Standalone Vehicle Model Family Creation — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let an admin create an empty vehicle model family (e.g. "Camry") directly from the Vehicle Finder index, without first creating a variant.

**Architecture:** One new route (`POST admin.vehicle-fitments.families.store`) and one new controller method (`VehicleFitmentController::storeFamily()`) reusing the existing `nullableText()`/`uniqueFamilySlug()` helpers. The UI is an inline `<details>` trigger + form added to each brand's block on the existing `admin.vehicle-fitments.index` page — no new route/view file. Unlike the existing variant form's `firstOrCreate()` (which silently joins an existing family), this endpoint rejects a case/whitespace-insensitive duplicate name within the same brand, because it is an explicit "create" action.

**Tech Stack:** Laravel 12, Blade, PHPUnit (`RefreshDatabase`), the project's plain-array-key JSON translations (`lang/en.json`, `lang/ar.json`, `lang/ku.json`).

**Spec:** `docs/superpowers/specs/2026-09-17-vehicle-model-family-create-design.md`

---

## Task 1: Failing tests for the new endpoint and the index page trigger

**Files:**
- Create: `tests/Feature/Admin/VehicleModelFamilyCreateTest.php`

- [ ] **Step 1: Write the test file**

```php
<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\VehicleBrand;
use App\Models\VehicleModelFamily;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Standalone family creation.
 *
 * Before this, a model family could only come into being as a side effect of
 * the "Add Variant" form (typing a name into new_family_name_en). An admin
 * adding a brand new brand had no way to lay out its model families before
 * the first variant existed.
 */
class VehicleModelFamilyCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_family_can_be_created_with_just_an_english_name(): void
    {
        $brand = VehicleBrand::query()->create(['name' => 'KGM', 'slug' => 'kgm']);

        $this->actingAsAdmin()
            ->post(route('admin.vehicle-fitments.families.store'), [
                'vehicle_brand_id' => $brand->id,
                'name_en' => 'Camry',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $family = VehicleModelFamily::query()->where('vehicle_brand_id', $brand->id)->firstOrFail();

        $this->assertSame('Camry', $family->name);
        $this->assertSame('Camry', $family->name_en);
        $this->assertNull($family->name_ar);
        $this->assertNull($family->name_ku);
        $this->assertSame('camry', $family->slug);
        $this->assertSame(0, $family->variants()->count());
    }

    public function test_arabic_and_kurdish_names_are_saved_when_given(): void
    {
        $brand = VehicleBrand::query()->create(['name' => 'KGM', 'slug' => 'kgm']);

        $this->actingAsAdmin()->post(route('admin.vehicle-fitments.families.store'), [
            'vehicle_brand_id' => $brand->id,
            'name_en' => 'Camry',
            'name_ar' => 'كامري',
            'name_ku' => 'کامری',
        ])->assertSessionHasNoErrors();

        $family = VehicleModelFamily::query()->where('vehicle_brand_id', $brand->id)->firstOrFail();

        $this->assertSame('كامري', $family->name_ar);
        $this->assertSame('کامری', $family->name_ku);
    }

    public function test_the_english_name_is_required(): void
    {
        $brand = VehicleBrand::query()->create(['name' => 'KGM', 'slug' => 'kgm']);

        $this->actingAsAdmin()
            ->post(route('admin.vehicle-fitments.families.store'), ['vehicle_brand_id' => $brand->id])
            ->assertSessionHasErrors('name_en');

        $this->assertSame(0, VehicleModelFamily::query()->where('vehicle_brand_id', $brand->id)->count());
    }

    public function test_a_duplicate_name_under_the_same_brand_is_rejected(): void
    {
        $brand = VehicleBrand::query()->create(['name' => 'KGM', 'slug' => 'kgm']);
        VehicleModelFamily::query()->create([
            'vehicle_brand_id' => $brand->id,
            'name' => 'Camry',
            'name_en' => 'Camry',
            'slug' => 'camry',
        ]);

        // Differs only by case and surrounding whitespace.
        $this->actingAsAdmin()
            ->post(route('admin.vehicle-fitments.families.store'), [
                'vehicle_brand_id' => $brand->id,
                'name_en' => '  camry  ',
            ])
            ->assertSessionHasErrors('name_en');

        $this->assertSame(1, VehicleModelFamily::query()->where('vehicle_brand_id', $brand->id)->count());
    }

    public function test_the_same_name_is_allowed_under_a_different_brand(): void
    {
        $kgm = VehicleBrand::query()->create(['name' => 'KGM', 'slug' => 'kgm']);
        $toyota = VehicleBrand::query()->create(['name' => 'Toyota', 'slug' => 'toyota']);
        VehicleModelFamily::query()->create([
            'vehicle_brand_id' => $kgm->id,
            'name' => 'Camry',
            'name_en' => 'Camry',
            'slug' => 'camry',
        ]);

        $this->actingAsAdmin()
            ->post(route('admin.vehicle-fitments.families.store'), [
                'vehicle_brand_id' => $toyota->id,
                'name_en' => 'Camry',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(
            1,
            VehicleModelFamily::query()->where('vehicle_brand_id', $toyota->id)->where('name_en', 'Camry')->count()
        );
    }

    public function test_a_user_without_the_products_permission_is_forbidden(): void
    {
        $brand = VehicleBrand::query()->create(['name' => 'KGM', 'slug' => 'kgm']);
        $customer = User::factory()->create(['role' => User::ROLE_USER, 'email_verified_at' => now()]);

        $this->actingAs($customer)
            ->post(route('admin.vehicle-fitments.families.store'), [
                'vehicle_brand_id' => $brand->id,
                'name_en' => 'Camry',
            ])
            ->assertForbidden();

        $this->assertSame(0, VehicleModelFamily::query()->count());
    }

    public function test_the_index_page_shows_an_add_family_trigger_for_each_brand(): void
    {
        VehicleBrand::query()->create(['name' => 'KGM', 'slug' => 'kgm']);

        $this->actingAsAdmin()
            ->get(route('admin.vehicle-fitments.index'))
            ->assertOk()
            ->assertSeeText('Add Family')
            ->assertSeeText('Create Family');
    }

    private function actingAsAdmin(): self
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->forceFill(['role' => User::ROLE_SUPER_ADMIN])->save();

        return $this->actingAs($admin)->withSession(['admin_2fa.verified_user_id' => $admin->id]);
    }
}
```

- [ ] **Step 2: Run it and confirm it fails**

Run: `"C:\laragon\bin\php\php-8.3.31-Win32-vs16-x64\php.exe" artisan test tests/Feature/Admin/VehicleModelFamilyCreateTest.php`

Expected: every test fails, most with `Symfony\Component\Routing\Exception\RouteNotFoundException: Route [admin.vehicle-fitments.families.store] not defined.` — this confirms the tests are actually exercising the missing route, not passing by accident.

- [ ] **Step 3: Commit the failing test**

```bash
git add tests/Feature/Admin/VehicleModelFamilyCreateTest.php
git commit -m "test: add failing coverage for standalone family creation"
```

---

## Task 2: Route

**Files:**
- Modify: `routes/web.php:642-644`

- [ ] **Step 1: Add the route**

Find this block (the `families.update` route):

```php
        Route::patch('/vehicle-fitments/families/{family}', [VehicleFitmentController::class, 'updateFamily'])
            ->middleware(['can:'.User::PERMISSION_PRODUCTS_MANAGE, 'throttle:admin-write'])
            ->name('vehicle-fitments.families.update');
```

Replace it with (new route added directly above, same middleware as every other family/brand write route on this page):

```php
        Route::post('/vehicle-fitments/families', [VehicleFitmentController::class, 'storeFamily'])
            ->middleware(['can:'.User::PERMISSION_PRODUCTS_MANAGE, 'throttle:admin-write'])
            ->name('vehicle-fitments.families.store');
        Route::patch('/vehicle-fitments/families/{family}', [VehicleFitmentController::class, 'updateFamily'])
            ->middleware(['can:'.User::PERMISSION_PRODUCTS_MANAGE, 'throttle:admin-write'])
            ->name('vehicle-fitments.families.update');
```

- [ ] **Step 2: Run the test file again**

Run: `"C:\laragon\bin\php\php-8.3.31-Win32-vs16-x64\php.exe" artisan test tests/Feature/Admin/VehicleModelFamilyCreateTest.php`

Expected: the `RouteNotFoundException` is gone. Tests now fail with `Call to undefined method App\Http\Controllers\Admin\VehicleFitmentController::storeFamily()` — the next task fixes that.

- [ ] **Step 3: Commit**

```bash
git add routes/web.php
git commit -m "feat: add the vehicle-fitments.families.store route"
```

---

## Task 3: Controller method

**Files:**
- Modify: `app/Http/Controllers/Admin/VehicleFitmentController.php:891` (insert immediately before `updateFamily`)

- [ ] **Step 1: Add `storeFamily()`**

Find this line (the start of `updateFamily`):

```php
    public function updateFamily(Request $request, VehicleModelFamily $family): RedirectResponse
    {
```

Replace it with (new method inserted directly above; `updateFamily` itself is unchanged):

```php
    public function storeFamily(Request $request): RedirectResponse
    {
        $request->merge([
            'name_en' => trim((string) $request->input('name_en')),
        ]);

        $data = $request->validate([
            'vehicle_brand_id' => ['required', 'exists:vehicle_brands,id'],
            'name_en' => ['required', 'string', 'max:120'],
            'name_ar' => ['nullable', 'string', 'max:120'],
            'name_ku' => ['nullable', 'string', 'max:120'],
        ]);

        $brandId = (int) $data['vehicle_brand_id'];
        $name = trim((string) $data['name_en']);

        // Unlike storeModel()'s firstOrCreate() (which joins an existing family
        // when a variant is added under a name that already exists), this is an
        // explicit "create a new family" action: a name that already exists
        // here is a mistake to reject, not an instruction to reuse it.
        $duplicate = VehicleModelFamily::query()
            ->where('vehicle_brand_id', $brandId)
            ->get(['id', 'name', 'name_en'])
            ->contains(fn (VehicleModelFamily $existing): bool => mb_strtolower(trim((string) ($existing->name_en ?: $existing->name))) === mb_strtolower($name));

        if ($duplicate) {
            return back()
                ->withErrors(['name_en' => __('A family named :name already exists under this brand.', ['name' => $name])])
                ->withInput();
        }

        VehicleModelFamily::query()->create([
            'vehicle_brand_id' => $brandId,
            'name' => $name,
            'name_en' => $name,
            'name_ar' => $this->nullableText($data['name_ar'] ?? null),
            'name_ku' => $this->nullableText($data['name_ku'] ?? null),
            'slug' => $this->uniqueFamilySlug($brandId, $name),
        ]);

        return back()->with('success', __('Model family created.'));
    }

    public function updateFamily(Request $request, VehicleModelFamily $family): RedirectResponse
    {
```

- [ ] **Step 2: Run the test file again**

Run: `"C:\laragon\bin\php\php-8.3.31-Win32-vs16-x64\php.exe" artisan test tests/Feature/Admin/VehicleModelFamilyCreateTest.php`

Expected: `test_the_index_page_shows_an_add_family_trigger_for_each_brand` still fails (`Add Family` / `Create Family` not found in the response — the view doesn't have the trigger yet). Every other test should now pass — `back()->with(...)`/`withErrors(...)` work without a view.

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/Admin/VehicleFitmentController.php
git commit -m "feat: add VehicleFitmentController::storeFamily()"
```

---

## Task 4: Language keys

**Files:**
- Modify: `lang/en.json:3229`
- Modify: `lang/ar.json:3229`
- Modify: `lang/ku.json:3229`

Four new keys are needed: `Add Family`, `Create Family`, `Model family created.`, and `A family named :name already exists under this brand.`. `TranslationCoverageTest` requires all three locale files to carry exactly the same key set, so all three edits are required together.

- [ ] **Step 1: `lang/en.json`**

Find:

```json
    "Vehicle variant removed.": "Vehicle variant removed.",
    "Model family updated.": "Model family updated.",
```

Replace with:

```json
    "Vehicle variant removed.": "Vehicle variant removed.",
    "Add Family": "Add Family",
    "Create Family": "Create Family",
    "Model family created.": "Model family created.",
    "A family named :name already exists under this brand.": "A family named :name already exists under this brand.",
    "Model family updated.": "Model family updated.",
```

- [ ] **Step 2: `lang/ar.json`**

Find:

```json
    "Vehicle variant removed.": "تم حذف فئة المركبة.",
    "Model family updated.": "تم تحديث عائلة الطراز.",
```

Replace with:

```json
    "Vehicle variant removed.": "تم حذف فئة المركبة.",
    "Add Family": "إضافة عائلة",
    "Create Family": "إنشاء العائلة",
    "Model family created.": "تم إنشاء عائلة الطراز.",
    "A family named :name already exists under this brand.": "توجد بالفعل عائلة باسم :name ضمن هذه الماركة.",
    "Model family updated.": "تم تحديث عائلة الطراز.",
```

- [ ] **Step 3: `lang/ku.json`**

Find:

```json
    "Vehicle variant removed.": "جۆری ئۆتۆمبێل سڕایەوە.",
    "Model family updated.": "خێزانی مۆدێل نوێکرایەوە.",
```

Replace with:

```json
    "Vehicle variant removed.": "جۆری ئۆتۆمبێل سڕایەوە.",
    "Add Family": "زیادکردنی خێزان",
    "Create Family": "دروستکردنی خێزان",
    "Model family created.": "خێزانی مۆدێل دروستکرا.",
    "A family named :name already exists under this brand.": "خێزانێک بە ناوی :name پێشتر لەژێر ئەم براندەدا هەیە.",
    "Model family updated.": "خێزانی مۆدێل نوێکرایەوە.",
```

- [ ] **Step 4: Validate the JSON is well-formed**

Run: `"C:\laragon\bin\php\php-8.3.31-Win32-vs16-x64\php.exe" -r "json_decode(file_get_contents('lang/en.json'), true, 512, JSON_THROW_ON_ERROR); json_decode(file_get_contents('lang/ar.json'), true, 512, JSON_THROW_ON_ERROR); json_decode(file_get_contents('lang/ku.json'), true, 512, JSON_THROW_ON_ERROR); echo 'ok';"`

Expected: `ok` with no exception.

- [ ] **Step 5: Commit**

```bash
git add lang/en.json lang/ar.json lang/ku.json
git commit -m "i18n: add strings for standalone family creation"
```

---

## Task 5: View — inline "Add Family" trigger per brand

**Files:**
- Modify: `resources/views/admin/vehicle-fitments/index.blade.php:474`

- [ ] **Step 1: Insert the trigger + form**

Find (the opening of each brand's family list, right after the navy header):

```blade
                            <div class="space-y-2.5 p-3">
                                @forelse($brand->modelFamilies as $family)
```

Replace with (new `<details>` block inserted as the first child, before the existing family loop):

```blade
                            <div class="space-y-2.5 p-3">
                                <details class="overflow-hidden rounded-xl border border-dashed border-amber-300 bg-amber-50/60 dark:border-amber-500/30 dark:bg-amber-500/5">
                                    <summary class="flex cursor-pointer list-none items-center gap-1.5 px-4 py-2.5 text-[11px] font-bold uppercase tracking-[.08em] text-amber-700 dark:text-amber-400">
                                        <i class="fas fa-plus text-[9px]" aria-hidden="true"></i>{{ __('Add Family') }}
                                    </summary>
                                    <form method="POST" action="{{ route('admin.vehicle-fitments.families.store') }}" class="grid gap-2 border-t border-dashed border-amber-300 p-3 dark:border-amber-500/30 sm:grid-cols-[1fr_1fr_1fr_auto] sm:items-end">
                                        @csrf
                                        <input type="hidden" name="vehicle_brand_id" value="{{ $brand->id }}">
                                        <label class="block"><span class="vf-lbl">{{ __('Family Name — English') }}</span><input name="name_en" required maxlength="120" class="vf-inp"></label>
                                        <label class="block"><span class="vf-lbl">{{ __('Family Name — Arabic') }}</span><input name="name_ar" maxlength="120" dir="rtl" class="vf-inp"></label>
                                        <label class="block"><span class="vf-lbl">{{ __('Family Name — Kurdish') }}</span><input name="name_ku" maxlength="120" dir="rtl" class="vf-inp"></label>
                                        <button class="vf-btn primary sm">{{ __('Create Family') }}</button>
                                    </form>
                                </details>
                                @forelse($brand->modelFamilies as $family)
```

This reuses the page's existing `.vf-lbl` / `.vf-inp` / `.vf-btn` classes and the same dashed-amber-card look already used for the family rename form a few lines below it — no new CSS.

- [ ] **Step 2: Run the test file again**

Run: `"C:\laragon\bin\php\php-8.3.31-Win32-vs16-x64\php.exe" artisan test tests/Feature/Admin/VehicleModelFamilyCreateTest.php`

Expected: all 7 tests pass.

- [ ] **Step 3: Commit**

```bash
git add resources/views/admin/vehicle-fitments/index.blade.php
git commit -m "feat: add an inline Add Family trigger to the Vehicle Finder index"
```

---

## Task 6: Verify and ship

**Files:** none (verification only)

- [ ] **Step 1: Run the new tests, the translation guard, and the duplicate-name suite**

Run: `"C:\laragon\bin\php\php-8.3.31-Win32-vs16-x64\php.exe" artisan test tests/Feature/Admin/VehicleModelFamilyCreateTest.php tests/Feature/TranslationCoverageTest.php tests/Feature/Admin/VehicleVariantDuplicateNameTest.php`

Expected: all pass. (Per project convention, this is the targeted run — don't re-run the full suite for reassurance; Pint/Larastan below catch the rest.)

- [ ] **Step 2: Static analysis and style**

Run: `"C:\laragon\bin\php\php-8.3.31-Win32-vs16-x64\php.exe" vendor/bin/pint --test`
Expected: `{"tool":"pint","result":"passed"}`

Run: `"C:\laragon\bin\php\php-8.3.31-Win32-vs16-x64\php.exe" vendor/bin/phpstan analyse --memory-limit=1G`
Expected: `[OK] No errors`

- [ ] **Step 3: Push**

```bash
git push
```

Deploying to production is out of scope for this session — see `deploy/deploy.sh` and the `[No deploy access]` project note; the user deploys manually.
