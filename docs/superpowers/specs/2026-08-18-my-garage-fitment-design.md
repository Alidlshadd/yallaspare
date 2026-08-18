# My Garage — Vehicle Fitment — Design

Date: 2026-08-18 · Chosen concept: **03 — Garage Plate** (saved vehicle, product page opens with the verdict)

## Purpose

The product page's compatibility section listed fitment rows and left the customer to match
their own car against them. It answered "which vehicles does this fit", when the question being
asked is "does this fit *mine*".

Three concepts were reviewed (01 Instrument Cluster — a year gauge; 02 Configurator Console — a
step flow; 03 Garage Plate — a saved vehicle). 03 was chosen. Its weakness is that the plate only
exists once a vehicle has been saved, so the empty state carries 02's step flow and doubles as the
thing that saves the vehicle.

## What was wrong with the old section

| Problem | Detail |
| --- | --- |
| Brand read as hierarchy | Brand is stored as `SSANGYONG / KGM`; the template joined it to the model with another slash, so `SSANGYONG / KGM / Rexton` looked like three levels when there are two facts |
| Brand repeated per row | Took the loudest position on every card while the model — the only thing that differed — sat last |
| Unlabelled values | `3.2` with no indication it is an engine |
| Data gaps shown as facts | `Any year` / `Any engine` describe missing data, not fitment, and carried the same visual weight as a real range |

## Storage — session, not the user record

`App\Support\Garage` wraps a single session key, `garage.vehicle`:

```php
['brand_id' => int, 'brand' => string, 'model_id' => int, 'model' => string, 'year' => int|null]
```

Session rather than a `users` column so a guest gets the same benefit as a signed-in customer and
no migration was needed to ship it. Moving it onto the account later means changing `Garage::get()`
and `Garage::put()` and nothing else — no caller touches the session directly.

`Garage::put()` resolves the brand and model **names from the database** rather than trusting the
request, so a tampered form cannot put arbitrary text on the plate.

## Verdict rules — `Garage::verdict(iterable $fitments)`

Returns `['state' => 'fits'|'misses'|'unknown', 'row' => array|null]`.

| Situation | Result |
| --- | --- |
| No saved vehicle | `unknown` — the picker is shown |
| No fitment row for the saved model | `misses`, row `null` |
| Row exists, `year_from`/`year_to` both null | `fits` — an unbounded row covers every year, so a saved year must never disqualify it |
| Row exists, vehicle saved without a year | `fits` — no reason to tell the customer no |
| Row exists with bounds, year inside | `fits` |
| Row exists with bounds, year outside | `misses` |

The two "no reason to say no" rules are the easy accidents here; both have explicit tests.

## Routes

| Method | URI | Name | Middleware |
| --- | --- | --- | --- |
| POST | `/garage` | `garage.store` | `throttle:public-write` |
| DELETE | `/garage` | `garage.destroy` | `throttle:public-write` |

`GarageController@store` validates `vehicle_model_id` (`exists:vehicle_models,id`) and an optional
`year` bounded to 1950…current+2 — the field is a picker, so a stray value only ever arrives from a
tampered form. Both actions redirect `back()`.

## Component — `<x-shop.fitment :fitments="$vehicleFitments" />`

Rendered from `shop/show.blade.php` when structured fitments exist; the legacy
`compatible_models` chips and the "available on request" note remain as the fallback below it.

The fitment rows passed in carry `model_id`, `from`, `to`, `engineRaw` alongside the existing
display fields — added to the mapping at the top of `show.blade.php`.

**With a vehicle saved:** the plate (navy header band, brand, model, year chip, dashed tear-line,
"Change vehicle" → `DELETE /garage`), then the verdict panel (green/rose), then the remaining models.

**With nothing saved:** a three-node step rail, then model tiles, then year tiles *only* when the
chosen model has a range, then a result panel with **Save to My Garage** (submits the form) and
**Start over**.

## Client behaviour — `Alpine.data('fitmentPicker')`

State only: which model, which year, which step. No requests; every fitment row is already on the page.

Two constraints shaped this:

- **Model choices are rendered by Blade, not `x-for`.** They exist in the HTML without JS and to a
  crawler; Alpine only styles and reads them. Handlers take the model id as a plain number
  (`pickModelById(12)`), which is all the CSP evaluator needs.
- **The payload arrives on `data-models` / `data-labels` attributes**, parsed in `init()`. Blade's
  `@js()` compiles to `JSON.parse(...)`, and the CSP build of Alpine refuses to reach a global from
  an expression — `x-data="fitmentPicker(@js($models))"` fails with `Undefined variable: JSON`.

A `<template x-if>` inside an `<svg>` also does not work — a `<template>` is not valid SVG content
and the browser drops it, so the fit/miss icons are separate `<svg>` elements toggled with `x-show`.

## Layout note

The section is **stacked, never two columns**. It lives in the product page's narrow right column,
and a `lg:` breakpoint reads the viewport rather than that column — side by side collapsed the
verdict into one word per line at desktop widths.

## Wording

`Any year` / `Any engine` are gone from this section. Coverage is stated positively — "all years",
"All model years and engine options" — because an unbounded row means the whole model is covered,
not that data is missing.

## Tests — `tests/Feature/ProductFitmentDetailTest.php`

The previous assertions pinned the markup this change deliberately removes and were rewritten.

- picker appears when nothing is saved, offering every listed model
- saved vehicle inside the range → "Exact fit", picker not shown alongside
- saved vehicle outside the range → told the listed range
- model without year bounds fits regardless of the saved year
- vehicle this part does not list → incompatible
- saving persists model id, DB-resolved name, and year
- unknown model id rejected, nothing saved
- clearing removes it
- legacy `compatible_models` still render when there are no structured fitments

## Follow-ups, not in this change

- The saved vehicle could filter `/shop`, search and recommendations — the main reason 03 was
  chosen over 01 and 02, and none of it is wired up yet.
- Multiple saved vehicles (the plate header band has room for a switcher).
- Persisting to the account for signed-in customers so it survives a new session.
