# Product Change Approvals — Design

**Date:** 2026-09-16
**Status:** Approved (user-approved via brainstorming, ready for implementation plan)
**Owner:** YallaSpare admin

---

## 1. Goal

Today, any user with the `products.manage` permission (in practice: `product_manager`, plus `admin` / `super_admin`) can create, edit, or (super admin only) hard-delete a product and it takes effect immediately.

The store owner wants a safety net for **everyone except `admin` and `super_admin`**: their product create/update actions, and a new "request deletion" action, must be reviewed and approved by a **super admin** before they touch the live catalogue. `admin` and `super_admin` keep writing directly, exactly as today — nothing changes for them.

---

## 2. Non-goals

- Bulk Excel import (`products.import`) and Bulk Stock Adjustment are **not** gated by this feature — they already have their own review/preview flow and are out of scope here (YAGNI: don't retrofit a second approval mechanism onto a flow that already has one).
- No email/SMS notification to the requester on approval/rejection — an in-app banner on the product edit page and the existing admin notification bell are enough for v1.
- No automatic cleanup of files uploaded for a request that is later rejected or never actioned — accepted as a small, bounded storage cost (flagged for a future pass if it matters).
- No partial/field-level approval (e.g. approve the price change but not the name change) — a request is approved or rejected as a whole.
- Category, ProductBrand, and Vehicle Fitment management are unaffected — only the `Product` model's own create/update/delete.

---

## 3. Roles & gates

| Concern | Rule | Implementation |
| --- | --- | --- |
| Who must go through approval | Any authenticated user who is **not** `admin` or `super_admin` | `! auth()->user()->isAdmin()` (existing helper — `isAdmin()` already returns true for both `admin` and `super_admin`) |
| Who can approve/reject | `super_admin` only | New gate `products.approve`, mirrors the existing `products.delete` gate: `Gate::define('products.approve', fn (User $user) => $user->isSuperAdmin());` |
| Who can request a deletion | Anyone with `products.manage` who is not admin/super_admin | New route, same permission middleware as store/update |

In practice, only `product_manager` (or a user with a custom permission grant that includes `products.manage`) is affected, since route middleware already restricts who reaches these actions at all — no new role enumeration needed.

---

## 4. Architecture

```
Non-admin submits create/update/delete-request
   │
   ▼
ProductController::store / update / requestDestroy
   │
   ├─ Resolve + validate input exactly as today (unchanged validation rules)
   ├─ Store any uploaded image(s) to disk exactly as today (files must exist
   │   so the reviewer can preview them — only the DB write is deferred)
   │
   ├─ auth()->user()->isAdmin()?
   │     ├─ yes → applyCreate() / applyUpdate() / applyDelete() directly (today's behavior, unchanged)
   │     └─ no  → ProductChangeRequest::create([... 'status' => 'pending']); redirect with
   │              "Submitted for review" message. Live `products` row untouched.
   │
Super admin visits /admin/product-approvals
   │
   ├─ approve(ProductChangeRequest $r)
   │     └─ DB transaction: applyCreate()/applyUpdate()/applyDelete() using $r->payload,
   │        then $r->update(['status' => 'approved', 'reviewed_by' => ..., 'reviewed_at' => ...])
   │
   └─ reject(ProductChangeRequest $r, ?string $note)
         └─ $r->update(['status' => 'rejected', 'rejection_note' => $note, 'reviewed_by' => ..., 'reviewed_at' => ...])
            (no DB write to the product; any staged files are simply never attached)
```

`applyCreate(array $fields, ?string $imagePath, array $galleryPaths): Product`, `applyUpdate(Product $product, array $fields, ...): Product`, and `applyDelete(Product $product): void` are extracted from the current bodies of `store()`, `update()`, and `destroy()` respectively, so the **same code** runs whether a change is applied instantly (admin) or after approval (super admin) — behavior can never drift between the two paths. This is the one meaningful refactor to existing controller code; the request-resolution/validation half of `store()`/`update()` is untouched.

---

## 5. Database

### 5.1 `product_change_requests`

| Column | Type | Notes |
| --- | --- | --- |
| id | bigIncrements | |
| type | string(10) | `create` \| `update` \| `delete` |
| product_id | bigInt nullable | FK `products(id)` nullOnDelete; null only for `create` while pending |
| requested_by | bigInt | FK `users(id)` |
| payload | json nullable | resolved field data (+ already-stored image paths) for `create`/`update`; null for `delete` |
| status | string(10) default `pending` | `pending` \| `approved` \| `rejected` |
| reviewed_by | bigInt nullable | FK `users(id)` |
| reviewed_at | timestamp nullable | |
| rejection_note | string(500) nullable | optional, shown to the requester |
| timestamps | | |

**Indexes:** `(status, created_at)` for the pending list; `(product_id)` for the per-product banner lookup.

If the target product is hard-deleted directly by a super admin while an unrelated request is still pending against it, `product_id` nulling (nullOnDelete) is enough — the approval page shows "product no longer exists" and only Reject is offered.

---

## 6. Request lifecycle by type

### 6.1 Create
- Non-admin submits the product form → files stored, full field set validated and normalized exactly as `store()` does today → `ProductChangeRequest{type: create, product_id: null, payload: {...fields, image, gallery}}`.
- The would-be product does **not** appear in the product list (no row exists yet) — it's visible only on the approvals page, rendered from `payload`.
- Approve → `applyCreate($payload)` runs, creating the real `Product` (+ `ProductImage` rows) for the first time.
- Reject → request marked rejected; uploaded files are left on disk (see Non-goals).

### 6.2 Update
- Non-admin submits an edit → resolved exactly as `update()` does today → `ProductChangeRequest{type: update, product_id: $product->id, payload: {...fields, image, gallery}}`.
- The live product is untouched — the admin/customer-facing catalogue still shows the old values.
- The product's edit page shows a banner: "A change to this product is awaiting approval" (visible to anyone who can edit the product; links to the approval-page entry if the viewer can approve).
- Approve → `applyUpdate($product, $payload)` runs. Reject → nothing changes on the product; banner clears; `rejection_note` is shown to the requester next time they open the edit page.
- **One pending request per product, regardless of type.** If a second edit (or a delete-request) is submitted while a request is already pending for that product, it's refused with a friendly message to wait for the first to be reviewed — enforced at the application layer (checked inside the same transaction that inserts the new request) since MySQL can't partial-unique-index without a generated column. Simpler than merging two pending diffs. This rule is shared by §6.2 and §6.3.

### 6.3 Delete (new capability)
- Today, non-admin users have no delete button at all (`products.delete` gate is `super_admin`-only and the route middleware matches). This feature adds a **"Request deletion"** action, visible to non-admins who can manage products, next to the (still admin/super-admin-only) real delete button.
- Creates `ProductChangeRequest{type: delete, product_id: $product->id, payload: null}` (subject to the same one-pending-per-product rule as §6.2).
- Approve → `applyDelete($product)` runs (today's `destroy()` body, verbatim, including the historical-reference release and file cleanup). Reject → nothing happens.

---

## 7. Admin UI

### 7.1 Approval page

New page, `super_admin`-only:

```php
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

List view: pending requests first (type badge, requester name, product name/SKU or "New product", submitted-at), each row expandable to a field-by-field diff (`update`: old → new per changed field; `create`: full summary; `delete`: product summary). Approve / Reject buttons per row, reject opens a small inline textarea for the optional note. A second tab or filter shows recently reviewed requests (approved/rejected, last 30 days) for accountability.

### 7.2 Sidebar & notifications

- New nav link under "Administration", `super_admin`-only (`@can('products.approve')`), with a pending-count badge — same `admin-nav-badge` pattern already used elsewhere in the sidebar.
- `$adminPageTitlePatterns` gets `'admin.product-approvals.*' => __('Product Approvals')`.
- `NotificationController@index` gets a fourth bucket, `product_approvals`, built the same way as the existing `dealer_requests` bucket (count + up-to-5 items linking into the approval page), included only for users who pass `products.approve`.

### 7.3 Per-product banner

On `admin/products/edit.blade.php`, if a pending `ProductChangeRequest` exists for this product: a banner above the form — "A change is awaiting approval" (viewer can also edit the pending payload's own summary read-only) — and if the most recent request was rejected, a dismissible banner shows the rejection note once.

---

## 8. Files plan

### Create
```
database/migrations/2026_09_16_000001_create_product_change_requests_table.php
app/Models/ProductChangeRequest.php
app/Http/Controllers/Admin/ProductApprovalController.php
resources/views/admin/product-approvals/index.blade.php
tests/Feature/Admin/ProductChangeApprovalTest.php
```

### Edit
```
app/Http/Controllers/Admin/ProductController.php     extract applyCreate/applyUpdate/applyDelete; branch on isAdmin(); add requestDestroy()
app/Providers/AuthServiceProvider.php                add products.approve gate
routes/web.php                                        product-approvals routes + products.request-destroy route
app/Http/Controllers/Admin/NotificationController.php add product_approvals bucket
resources/views/layouts/app.blade.php                 sidebar link + $adminPageTitlePatterns entry
resources/views/admin/products/edit.blade.php         pending/rejected banner
resources/views/admin/products/index.blade.php        "Request deletion" action for non-admins; small "pending" indicator per row
```

Exact extraction boundaries for the gallery-image helpers (`storeGalleryImages`, `updateExistingGalleryImages`, `syncPrimaryImage`) are decided during planning — they currently read `$request` directly, so the plan step picks whether to pass them a resolved array instead or capture their file inputs into the payload as already-stored paths.

---

## 9. Tests

1. **product manager create is queued, not applied** — non-admin `store()` leaves `products` count unchanged, creates one pending `ProductChangeRequest{type: create}`.
2. **product manager update is queued, not applied** — non-admin `update()` leaves the live product's fields unchanged, creates one pending `ProductChangeRequest{type: update}` with the new values in `payload`.
3. **admin/super_admin writes apply immediately** — regression check: existing `store`/`update`/`destroy` behavior for `admin` and `super_admin` is unchanged (no request row created).
4. **second pending update for the same product is refused** — a friendly redirect back, no duplicate request row.
5. **approve applies the change** — approving a `create`/`update`/`delete` request results in the expected `products` table state, `reviewed_by`/`reviewed_at` set.
6. **reject leaves the product untouched** — `rejection_note` stored, product state unchanged.
7. **only super_admin can reach the approval routes** — `admin`, `product_manager`, guest all get 403/redirect.
8. **non-admin can request deletion, admin still deletes directly** — `product_manager` hitting the new request-deletion route creates a pending `delete` request and does not remove the product; `super_admin` still uses the existing immediate `destroy()`.

---

## 10. Decisions log (from brainstorming)

| Question | Decision |
| --- | --- |
| Scope | Product module only (create/update/delete) — not orders, discounts, or other modules |
| Update granularity | Every field waits for approval — one uniform rule, not a sensitive-fields allowlist |
| Who is gated | Everyone except `admin`/`super_admin` (`! isAdmin()`) |
| Who approves | `super_admin` only (`isSuperAdmin()`), matching the existing `products.delete` gate |
| Storage model | Staged JSON payload in a new table; live `products` row untouched until approved |
| Bulk import / bulk stock adjustment | Out of scope — already have their own review flow |

---

## 11. Out of scope (for clarity)

- Order management approvals (a separate, unrelated feature the user raised afterward — to be brainstormed on its own).
- Category / ProductBrand / Vehicle Fitment approvals.
- Email/SMS notification on review outcome.
- Automatic cleanup of orphaned files from rejected/abandoned requests.
- Field-level partial approval.
