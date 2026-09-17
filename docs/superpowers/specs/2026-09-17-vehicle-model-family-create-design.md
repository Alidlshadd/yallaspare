# Vehicle Model Family — Standalone Create — Design

Date: 2026-09-17

## Purpose

Today a model family (e.g. "Camry") can only be created as a side effect of the "Add Variant"
form, by typing a `new_family_name_en` there. There is no way to create a bare family — an admin
adding a new brand must add a placeholder variant just to get a family to attach fitments under.
This adds a standalone way to create an empty family (0 variants) directly from the Vehicle
Finder index.

## Placement

Inline, per brand — not a separate page/route. Each brand's row/accordion on
`admin.vehicle-fitments.index` gets a small **"+ Add Family"** trigger next to its heading,
matching the existing "+ Add Brand" inline-form pattern already on that page. No brand dropdown
needed in the form; the brand is implicit from which row the trigger sits in.

Fields: Name (EN) — required. Name (AR) / Name (KU) — optional (same optionality as the existing
`updateFamily` validation).

## Route / Controller

- `POST admin.vehicle-fitments.families.store` → `VehicleFitmentController::storeFamily(Request $request)`
- Middleware: `can:`.`User::PERMISSION_PRODUCTS_MANAGE` + `throttle:admin-write` (same as
  `families.update` / `families.destroy`).
- Payload: `vehicle_brand_id`, `name_en`, `name_ar`, `name_ku`.

## Validation & duplicate handling

- `vehicle_brand_id`: required, `exists:vehicle_brands,id`
- `name_en`: required, string, max:120
- `name_ar`, `name_ku`: nullable, string, max:120
- **Reject on duplicate**, deliberately different from `storeModel()`'s silent
  `firstOrCreate` join: this screen is an explicit "create a new family" action, so a
  case/whitespace-insensitive match against `name_en` of the brand's existing families
  fails validation with "A '<name>' family already exists under this brand." Compare in PHP
  (load the brand's families, normalize with `mb_strtolower(trim($name))`) rather than a
  DB `LOWER()` clause, to stay portable across the MySQL/SQLite test driver split.

## Creation

On success: `VehicleModelFamily::create([...])` with `name` (legacy column) also set to the trimmed
`name_en` (mirrors what `storeModel()` does at creation time), plus `name_en`, `name_ar`, `name_ku`,
and `slug` from the existing `uniqueFamilySlug($brandId, $name)` helper. No variant is created —
the family starts empty and gets its first variant later through the existing "Add Variant" flow
by picking it from the `vehicle_model_family_id` dropdown there.

## Response

Validation failure: `redirect()->back()->withErrors($validator)->withInput()`, same as the
existing brand/family forms on that page. Success: redirect to `admin.vehicle-fitments.index`
with a success flash message.

## Testing

New cases in the vehicle-fitments admin test suite:
- super admin can create a family with just `name_en`
- `name_en` is required
- duplicate name under the same brand (including a differently-cased/whitespaced variant of an
  existing name) is rejected with a validation error, and no second row is created
- a user without `products.manage` gets 403

## Out of scope

- A dedicated family *edit* page (the existing inline rename on the index stays as-is).
- Any change to `storeModel()`'s existing join-on-duplicate behavior for the variant form.
- A family-merge tool for names that already drifted before this screen existed.
