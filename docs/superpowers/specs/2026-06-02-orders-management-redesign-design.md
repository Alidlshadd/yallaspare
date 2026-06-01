# Orders Management — Premium Dark SaaS Redesign

**Date:** 2026-06-02
**Status:** Approved (v6 Premium direction)
**Scope:** Visual redesign of `resources/views/admin/orders/index.blade.php` only

---

## Goal

Replace the current Tailwind-default look of the Admin Orders index with a custom, premium dark-SaaS aesthetic (Linear / Vercel / Stripe family). The page must feel professional and modern — vivid enough to read at a glance, but never neon or gamified.

The existing functionality (payment column, attention chips, bulk update, date filter, export button, COD auto-paid behavior, etc.) all stays. This work is **visual only**.

---

## Non-goals

- No changes to `OrderController`, routes, models, or DB. All backend stays.
- No changes to the `show` view, the invoice template, or any other admin page.
- No new features, columns, or actions. Same eight columns as today.
- No light-mode variant in this iteration. Dark mode is the default and only mode for this page.
- No layout structure changes beyond styling — same header, stats, chips, filter, table, pagination order.

---

## Design tokens

### Surfaces

| Token | Value | Use |
|---|---|---|
| `--surface-base` | `#131a2e` | Page background (the dark navy slate) |
| `--surface-raised` | `#1c2438` | Cards, stat boxes, table card wrapper |
| `--surface-elevated` | `#1f283f` | Table header, pagination buttons, icon buttons |
| `--surface-input` | `#131a2e` | Inputs (same as page bg — recessed look) |

### Borders

| Token | Value | Use |
|---|---|---|
| `--border-default` | `#2a3553` | Cards, inputs, chips, table rows |
| `--border-row` | `#232b42` | Table row separators (slightly softer) |
| `--border-checkbox` | `#475569` | Unchecked checkbox border |

### Text

| Token | Value | Use |
|---|---|---|
| `--text-primary` | `#f8fafc` | Headings, key values, numbers |
| `--text-body` | `#e6ecf5` | Body text, customer names |
| `--text-secondary` | `#cbd5e1` | Dates (visible level) |
| `--text-muted` | `#8a95b0` | Labels, table headers, sub-text |
| `--text-faint` | `#6b7794` | Emails, fine print |
| `--text-disabled` | `#525f7d` | Placeholders, method codes |

### Accent (cyan — primary action)

| Token | Value | Use |
|---|---|---|
| `--accent-from` | `#0891b2` | Export button + active chip gradient start |
| `--accent-to` | `#0e7490` | Same gradient end |
| `--accent-glow` | `rgba(8,145,178,0.35)` | Soft shadow on accent buttons |

### Status palette (Tailwind-300 family)

Each status has three values: a darker top-stripe accent (`a`), a bright text/value color (`vc`), and a faint glow (`glow`). Glow opacity is **0.15–0.20** — visible but not neon.

| Status | a (stripe) | vc (text) | glow |
|---|---|---|---|
| Pending / Warn | `#d97706` | `#fcd34d` | `rgba(252,211,77,0.18)` |
| Processing | `#6366f1` | `#c4b5fd` | `rgba(196,181,253,0.18)` |
| Shipped | `#0284c7` | `#93c5fd` | `rgba(147,197,253,0.18)` |
| Delivered / Paid | `#10b981` | `#6ee7b7` | `rgba(110,231,183,0.18)` |
| Cancelled / Error | `#dc2626` | `#fda4af` | `rgba(253,164,175,0.15)` |
| Total (neutral) | – | `#f8fafc` | – |

### Typography

- **Sans:** Inter, system-ui, -apple-system, 'Segoe UI', sans-serif
- **Mono:** 'JetBrains Mono', ui-monospace, monospace (order IDs only)
- Page title: 22px / 700 / `letter-spacing: -0.025em`
- Stat values: 22px / 700 / `font-variant-numeric: tabular-nums`
- Table body: 12px / 400 (or 600 for primary text)
- Table headers: 10px / 700 / `text-transform: uppercase` / `letter-spacing: 0.08em`
- Pills: 10.5px / 600
- Labels: 10px / 600 / uppercase / `letter-spacing: 0.08em`

### Radii & spacing

- Cards / table wrapper: `border-radius: 14px`
- Stat cards: `border-radius: 12px`
- Buttons & inputs: `border-radius: 7–8px`
- Pills: `border-radius: 999px`
- Frame outer padding: `26px 30px`
- Card inner padding: `14px`

---

## Component-level changes

### Header

```
┌──────────────────────────────────────────────────────────┐
│  Orders Management                       [↓ Export Excel]│
│  342 total · 12 need attention · synced just now         │
├──────────────────────────────────────────────────────────┤  ← 1px border-default
```

- Title `<h1>` in `--text-primary`.
- Subtitle with three pieces (`total · attention · synced`), comma-free.
  - **"12 need attention"** uses `--vc` of the warn palette (bright amber) inline, so the eye lands on it.
- Export button is a single `<a>` with:
  - Gradient bg `var(--accent-from)` → `var(--accent-to)`
  - White text, inline download SVG (h-3.5 w-3.5)
  - `box-shadow: 0 1px 0 rgba(255,255,255,0.08) inset, 0 4px 14px -4px rgba(8,145,178,0.4)`
- Followed by an 18px-padded section break with a 1px bottom border in `--border-default`.

### Stat grid (6 cards)

`grid-cols-6 gap-2.5 mb-5`. Each card:

```
┌────────────┐
│ ▌stripe    │  ← absolute top, 2px tall, color = --a, opacity 0.7
│ PENDING    │  label: --text-muted, 10px uppercase
│ 12         │  value: --vc, 22px 700, text-shadow 0 0 12px --glow
└────────────┘
```

Order in the grid (left → right):
1. Total (neutral, no stripe color emphasis — `--text-primary`)
2. Pending (warn)
3. Processing (indigo)
4. Shipped (info / blue)
5. Delivered (ok / green)
6. Cancelled (err / rose)

### Attention chips

Pill-shaped row, gap-2, below stats:

- **All** (default active state): `--accent-from` → `--accent-to` gradient, white text, soft accent glow
- **Today pending / Needs shipping / Cancellation requests / Return requests**: `--surface-raised` bg, `--border-default` border, `--text-muted` text
- Hover: border `#475569`, text `--text-secondary`

### Filter card

A single `--surface-raised` card with three sections vertically stacked:

1. **Filter row** (`padding: 14px`, border-bottom): grid `2fr 1fr 1fr`
   - Search input (full width on first cell)
   - Status select
   - Date range (two `1fr 1fr` inputs in a nested grid)
2. **Action row** (`padding: 12px 14px`, border-bottom): `Apply Filters` (primary gradient button) + `Clear` (ghost outline)
3. **Table** (no padding, full bleed)

Inputs:
- bg `--surface-input` (same as page — recessed effect)
- border `--border-default`, radius 8px
- placeholder `--text-disabled`
- focus: outline 1px `--accent-from`

### Table

Eight columns, same as current:

| col | width | content | notes |
|---|---|---|---|
| 1 | 32px | Checkbox | 14×14, 1.5px border `--border-checkbox`, radius 3px |
| 2 | auto | Order # + ID + alert | Two-line: bold name + mono ID, optional alert badge below |
| 3 | auto | Customer name + email | Name (`--text-body` 12/500) + email (`--text-faint` 10) |
| 4 | auto-right | Total | bold tabular-nums, currency code in `--text-faint` 10 |
| 5 | auto | Payment pill + method | Pill on top, `cash_on_delivery` (mono, `--text-disabled`, 9.5px) below |
| 6 | auto | Status pill | Single pill |
| 7 | auto | Date | "Jun 01" (`--text-secondary` 11) + time below (`--text-faint` 9) |
| 8 | auto-right | Actions | 3 icon buttons in a row (View, Invoice, More/menu) |

Header: `--surface-elevated` bg, `--text-muted` 10px uppercase 700, 11px vertical padding.

Body rows: 13px padding, `--border-row` 1px bottom (none on last), hover `rgba(255,255,255,0.015)`.

### Status pills

Reusable pattern. Two parts: small filled dot (5×5, `box-shadow: 0 0 6px currentColor` at 0.85 opacity) + text. Background is the status `--glow` color, text is `--vc`, border is the same as glow but +0.07 opacity. No gradient.

```
●  Pending     ← dot uses currentColor + 6px glow
```

Padding: `3.5px 10px`, radius `999px`, font 10.5px / 600.

### Alert badges (Cancel requested, Return open)

Tiny inline badge under the order ID, similar pill style but smaller:

- Cancel: red palette (`rgba(220,38,38,0.14)` bg, `#fda4af` text)
- Return: warn palette (`rgba(217,119,6,0.14)` bg, `#fcd34d` text)

`9px / 700 / uppercase / letter-spacing 0.05em`, `padding 2px 7px`, radius 4px.

### Action icon buttons

`28×28` square buttons with `--surface-elevated` bg, `--border-default` border, `--text-muted` icon color. 6px radius. Hover: icon color `--vc info` (sky-300), border `#475569`.

Three buttons: View (eye SVG), Invoice (document SVG), More (3-dot SVG).

The status update form + Archive button move into the **More** menu (kebab popover) to keep the row clean. This is **a visual reorganization, not a feature change** — they remain accessible.

### Pagination

Bottom of table card, `padding: 14px 16px`, border-top in `--border-default`:

- Left: "Showing X of Y orders" (`--text-muted` 11)
- Right: chevrons + numbered pages. Active page = cyan gradient. Inactive = `--surface-elevated` button.

### Bulk action bar (existing behavior, restyled)

Same Alpine `x-data="{ selected: [] }"` structure. When `selected.length > 0`, show a sticky bar above the table card:

- bg: subtle gradient `rgba(8,145,178,0.12)` over the surface
- left text: "**N selected**" in `--vc info`
- right side: status select + Apply (gradient button) + Clear (ghost)

---

## File touched

**Only one file:**

- `resources/views/admin/orders/index.blade.php`

All Tailwind utility classes get replaced with a custom CSS block at the top of the view (scoped via a `.orders-page` wrapper class to avoid leaking styles). Token values live as CSS custom properties on `.orders-page`, so future tweaks are one-place.

The blade structure (loops, conditionals, route helpers, Alpine bindings) stays identical. Only `class=""` attributes change.

---

## Why this approach

- **Single-file edit** keeps blast radius tiny — easy to review, easy to revert.
- **CSS custom properties** make follow-up tone adjustments (e.g., "a hair more saturated") a one-line change instead of a global search.
- **Scoped via `.orders-page` wrapper** — no risk of polluting other admin views; if user later wants to apply the same theme to Users, Products, etc., we extract to a shared partial.

---

## Risks / open questions

- **Alpine integration:** the existing bulk-action `x-data` block must work in the new wrapper without conflict. Verified in mockup mentally; needs a quick smoke test after implementation.
- **RTL (Arabic):** all spacing and icon placement should mirror automatically since we use logical `gap` and `padding`. The action column's `text-align: right` may need a `rtl:text-left` override — confirm during implementation.
- **i18n labels:** no new translation keys needed (all label text already exists from previous iterations).

---

## Out of scope (deliberately deferred)

- Bringing this theme to the order **show** page (`admin.orders.show`) — separate spec if user wants it.
- Bringing the theme to other admin pages — pattern is reusable but not in this iteration.
- Hover/focus animation polish (button micro-interactions) — could be a follow-up.
- Customizable accent (let admin pick brand color in settings) — beyond current scope.

---

## Verification

After implementation, manually verify in browser:

1. **Visual match**: open `/admin/orders`, compare against the v6 mockup in `.superpowers/brainstorm/700-1780349946/content/v6-premium.html`. Tokens should match within a pixel.
2. **All existing features work**: payment column displays, attention chips filter, bulk update bar appears when rows selected, date filter works, Export Excel downloads, COD auto-paid still triggers on delivery, pagination navigates.
3. **Translations**: switch to AR and KU — text reads correctly, layout doesn't break, no untranslated keys leak through.
4. **No regression on other admin pages** — Users, Products, Categories should look exactly as before (scoped class confirms this).
