# Storefront Announcement Popup — Design

Date: 2026-07-22 · Chosen storefront design: **B — Poster Card** (full-bleed image, text over bottom scrim)

## Purpose

Admin-managed advertisement/announcement popup shown on the storefront. Not an email-capture
tool: the popup carries a title, description, image, and an optional button linking anywhere
(product, category, campaign page). An email field was explicitly ruled out of scope for now.

## Data model — `popups` table

| Column | Type | Notes |
| --- | --- | --- |
| title_en / title_ar / title_ku | string(160) | EN required; AR/KU optional, fall back to EN via `LocalizedText` |
| description_en / _ar / _ku | text nullable | optional |
| button_label_en / _ar / _ku | string(60) nullable | button renders only when label + URL present |
| button_url | string(2048) nullable | `javascript:`/`data:` schemes rejected (same rule as hero button) |
| image_path | string nullable | stored via `SecureImageStorage` under `popups/`; navy gradient fallback when absent |
| is_active | bool default true | admin toggle |
| starts_at / ends_at | datetime nullable | null = open-ended |
| pages | json | subset of `all, home, shop, product, cart, checkout` |
| frequency | string | `every_visit` \| `once_per_session` \| `once_per_days` |
| frequency_days | smallint default 7 | used only with `once_per_days` |
| delay_seconds | smallint default 3 | wait before opening |

Model `App\Models\Popup` uses `LogsActivity` (matches other admin-managed models),
`localizedTitle()/localizedDescription()/localizedButtonLabel()` accessors, and a cached
`Popup::activeForPage(string $pageKey)` lookup (cache flushed on saved/deleted).

## Admin (Marketing section)

`Admin\PopupController` CRUD guarded by `PERMISSION_SETTINGS_MANAGE` + `throttle:admin-write`
on writes: index (list with status/schedule badges + toggle + delete), create, edit.
Views follow the navy `#04042a` + amber card system used by `admin/categories`.
Sidebar entry "Popups" in the Marketing group; page-title mapping added in `layouts/app.blade.php`.

## Storefront (design B)

Partial `partials/store-popup.blade.php` included from `layouts/store.blade.php`. Server picks
the newest eligible popup for the current page (route name → page key: `home|shop|product|cart|checkout`,
`all` matches every store page). Poster card: image covers the card, dark scrim at the bottom,
title/description/button on top, close ✕, dimmed blurred backdrop. RTL-safe, three breakpoints.

Client JS (nonce'd inline script, `data-*` hooks only — CSP forbids inline handlers):
- waits `delay_seconds`, then opens with a fade/rise animation (reduced-motion respected)
- frequency gate: `every_visit` always; `once_per_session` via `sessionStorage`;
  `once_per_days` via `localStorage` timestamp (`ys_popup_{id}`)
- closing (✕, backdrop, Esc) or clicking the button records the timestamp

## i18n

Every new `__()` string added to `lang/en.json`, `lang/ar.json`, `lang/ku.json`
(EN/AR/KU parity; avoids the missing-key truncation handler).

## Testing

Feature tests: admin CRUD + permissions (`tests/Feature/Admin/AdminPopupManagementTest.php`)
and storefront eligibility (active window, page targeting, inactive hidden)
(`tests/Feature/StorefrontPopupTest.php`).

## Future (out of scope now)

Optional email-capture variant; A/B/C template choice per popup; per-popup impression stats.
