# Admin Orders Page — Polish & Bug-Fix Design

**Date:** 2026-06-03
**Scope:** `resources/views/admin/orders/index.blade.php` + light tweaks to shared admin sidebar/topbar.
**Goal:** Premium SaaS-feeling polish (Linear/Stripe/Vercel/Notion direction) and functional bug fixes. **Preserve current layout structure** — no full redesign.

## Out of Scope
- `admin/orders/show.blade.php`, `admin/orders/invoice.blade.php`
- Any non-orders admin page
- Backend controller logic (`OrderController@index` filters already work end-to-end; only the form wiring on the front-end has issues)

## Visual Tokens
Already defined in `resources/css/app.css:899-915` under `.admin-shell` and match the requested palette. No token changes needed; orders page consumes them via existing `--admin-*` CSS variables.

```
--admin-bg #0f172a   --admin-surface #111827   --admin-card #1e293b
--admin-input #172033   --admin-sidebar #0b1220
--admin-border #334155   --admin-border-soft #263244
--admin-text #f8fafc   --admin-text-muted #cbd5e1   --admin-text-soft #94a3b8
--admin-accent #06b6d4   --admin-accent-hover #0891b2
```

Status colors stay inline in the page-scoped block (`--status-pending #f59e0b`, `--status-processing #8b5cf6`, `--status-shipped #38bdf8`, `--status-delivered #22c55e`, `--status-cancelled #ef4444`).

## Bugs to Fix

| # | Bug | Fix |
|---|-----|-----|
| 1 | Per-row kebab dropdown clipped by `.op-table-wrap` `overflow-x: auto` | Teleport menu to `<body>` via Alpine `x-teleport="body"`; position with fixed coords computed from trigger `getBoundingClientRect()` on open |
| 2 | Filter grid is `2fr 1fr 1fr` with 4 children → association wraps awkwardly | Re-layout to 12-col CSS grid: `search:4 / status:2 / from:2 / to:2 / association:2` on desktop (sums to 12), single column under 900px |
| 3 | Native `<input type="date">` calendar icon invisible on dark | Add `color-scheme: dark` to inputs; style `::-webkit-calendar-picker-indicator` with invert filter |
| 4 | Inconsistent button heights (`.op-export` 9/16, `.op-btn-primary` 7/14, icons 28²) | Standardize on heights: `sm` = 32px (`8px 14px`), `md` = 36px (`10px 16px`), `icon` = 32×32 |
| 5 | Stats grid layout breaks (6→3→2) with awkward middle breakpoint | Add `repeat(2, 1fr)` row at 540-700px; mobile (≤540px) becomes horizontal scroll-snap row of 6 cards |
| 6 | Status pill dot uses `box-shadow: 0 0 6px currentColor` glow → "neon" feel | Drop glow; 5px solid dot with `opacity: 0.9` only |
| 7 | Bulk-action bar appearing shifts the table down (layout shift) | Make `.op-bulk` `position: sticky; top: 0; z-index: 5` within the card; doesn't push content |
| 8 | "Clear" button doesn't appear when only `attention` chip is active | Acceptable — attention chips have their own "All" reset already; document this rather than touch it. No code change. |
| 9 | Filter form uses `style="display:contents"` wrapper trick which is fragile and the hidden `attention` input only renders conditionally | Convert to a single proper `<form>` wrapping the filter row + actions, with `attention` always present as hidden input when set |

## Visual Polish

### Statistics cards
- Value font 22px → 26px, weight 700, `tabular-nums`
- Label opacity tightened, letter-spacing 0.08em (unchanged)
- Top accent bar: 1px gradient `linear-gradient(90deg, transparent, var(--stat-a), transparent)` instead of 2px solid
- Hover: `transform: translateY(-1px)`, border lifts to `--border-default`, shadow strengthens slightly
- 6 cards desktop → 3 → 2 → 1 with scroll-snap on mobile

### Attention chips
- Keep pill shape; reduce font weight from 600 → 500 for off-state, 600 for on-state
- Active chip: solid `--accent-soft` background with 1px `--accent-border` (no glow)

### Filter section
- 12-col grid as described above
- Inputs: 36px height, 1px border, 8px radius, focus = 2px ring `rgba(6,182,212,0.25)` (down from 3px), no blur
- `color-scheme: dark` so date/select chevrons render correctly
- Labels: 10px, 0.05em letter-spacing, color `--text-muted`

### Apply Filters / Clear bar
- Right-aligned with primary on the right
- 36px height matching inputs
- "Clear" only when any of the tracked filter keys present

### Bulk actions bar
- Sticky to top of card when scrolling within the orders area
- Compact: 44px height, accent-soft background
- Apply / Clear buttons match 32px primary/ghost styles

### Orders table
- Sticky `<thead>` via `position: sticky; top: 0; z-index: 1; background: var(--surface-muted)`
- Row padding `13px 14px` → `14px 16px`
- Hover row `background: #1f2a3e` (a hair stronger than current `--surface-hover`)
- Order number: 13px weight 600 primary; `#id` 10px monospace muted (unchanged)
- Customer name: 12.5px weight 500 body; email 10.5px muted
- Total column: `tabular-nums`, currency label 10px faint, primary 13px weight 700
- Date column: stacked date / time, both `tabular-nums`
- Status & payment pills: ring style, 4px solid dot (no glow), 10.5px/600
- Row `:hover` raises opacity of actions icons from 0.85 → 1
- Empty state: centered SVG illustration (simple inbox icon) + 13px body copy + 12px secondary helper

### Action icons
- 32×32 (up from 28×28) for easier click target
- 1px `--border-soft` border, `--surface-elevated` background
- Hover: `--accent-text` color, `--accent-border` border, slight `translateY(-1px)`
- Kebab menu teleports to body, positioned via Alpine `x-init` + recalculated on resize/scroll

### Kebab menu
- Teleported to `<body>` via `<template x-teleport="body">`
- 220px wide, 8px radius, 1px border `--border-soft`, shadow `0 24px 48px -28px rgba(0,0,0,0.7)`
- Sections: "Update Status" form + (super admin only) "Archive Order" danger action
- Click-outside closes; ESC closes

### Pagination
- Already styled well; reduce gap from 4px → 6px
- Active page background `--accent` unchanged
- Disabled state opacity 0.4 (unchanged)
- "Showing X–Y of Z" text 11px muted

### Sidebar (shared, light touch)
- Active link: keep current pill but soften background to `rgba(6,182,212,0.10)` with 2px `--admin-accent` left bar
- Hover: `rgba(255,255,255,0.04)` background, no border change
- Icons remain 18px

### Topbar (shared, light touch)
- Bottom border: 1px `--admin-border-soft` (replace any default white-ish border)
- Background inherits `--admin-surface`
- Profile / notification icon buttons: 36×36, same icon-button treatment

## Responsiveness Breakpoints

| Width | Behavior |
|------|----------|
| ≥1280 | Full layout — 6 stats / 5-col filter grid / 9-col table |
| 1024-1279 | 6 stats / 5-col filter / 9-col table with `font-size: 11.5px` table |
| 768-1023 | 3 stats / single-column filters / horizontal scroll table (Items + payment method hidden) |
| 540-767 | 2 stats / single-column filters / table scroll |
| <540 | Horizontal scroll-snap stats / single-column filters / table scroll, action buttons collapse to icon-only kebab |

## Implementation Sequence

1. Replace inline style block with refactored, token-driven CSS (in same file, preserving page-scoping)
2. Restructure filter form into a single proper `<form>` element
3. Convert kebab menu to teleported Alpine component with positioning helper
4. Update markup for stats row (no structural change, only class refinements)
5. Add sticky `<thead>` and bulk-bar sticky behavior
6. Apply light tweaks to `resources/css/app.css` admin sidebar active state and topbar border
7. Manual verification across breakpoints

## Verification Plan

- Local Laragon → load `/admin/orders` with sample data
- Walk every filter: search / status / from / to / association / attention chip → confirm URL params persist after submit
- Apply bulk status to 2+ selected orders → confirm POST works and confirmation dialog appears
- Open kebab on bottom-most row → confirm menu isn't clipped
- Open kebab → "Update Status" → submit → confirm redirect with success flash
- Open kebab as super admin → "Archive Order" → confirm danger-confirm dialog appears
- Click "Export Excel" with filters active → confirm correct query params on link
- Resize browser through 1440 / 1200 / 1024 / 768 / 540 / 375 → confirm no horizontal overflow, no clipped UI
- Toggle dark mode (page is dark-only via admin shell — verify no light-mode bleed)
- Confirm `<input type="date">` calendar icon is visible

## Risks
- **Sidebar/topbar tweaks affect every admin page.** Mitigation: limit to two safe properties (active link background, topbar border). Visually verify dashboard, products, dealers, users after the change.
- **`x-teleport` requires Alpine 3+.** Verified — `<x-app-layout>` already loads Alpine 3.
- **Sticky `<thead>` interaction with `overflow-x` scroll.** Mitigation: only stick vertically (`top: 0`); horizontal scroll still works.
