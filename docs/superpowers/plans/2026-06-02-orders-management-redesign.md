# Orders Management Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Apply the v6 "Premium Dark SaaS" visual design to `resources/views/admin/orders/index.blade.php` without touching backend, routes, or other admin pages.

**Architecture:** Single Blade file edit. Add a scoped `<style>` block under a `.orders-page` wrapper class so all custom CSS lives in one location and cannot leak. Replace Tailwind utility classes with semantic class names that the scoped CSS targets. Keep Blade structure (loops, route helpers, Alpine bindings) identical so the existing `AdminOrdersTest` feature test stays green throughout.

**Tech Stack:** Laravel Blade, Alpine.js (existing project usage), inline CSS with custom properties, Tailwind for layout-only utilities where useful.

**Source spec:** `docs/superpowers/specs/2026-06-02-orders-management-redesign-design.md`

**Mockup reference:** `.superpowers/brainstorm/700-1780349946/content/v6-premium.html`

---

## File map

Only one file is touched:

- **Modify:** `resources/views/admin/orders/index.blade.php`
  - Add a `<style>` block at the top
  - Wrap the existing `<div class="py-8">` with `<div class="orders-page">`
  - Replace Tailwind classes section-by-section with semantic `op-*` names

No new files. No file deletions.

---

## Baseline (do once before starting)

### Task 0: Baseline test pass

**Files:**
- Read: `tests/Feature/Admin/AdminOrdersTest.php`

- [ ] **Step 1: Read the test file** so you know what behaviors it covers

```bash
# (no command — read the file with the Read tool)
```

- [ ] **Step 2: Run the feature test to confirm it passes before any changes**

Run (from project root, with Laragon PHP in PATH):
```powershell
$env:Path = "C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64;$env:Path"
php artisan test --filter=AdminOrdersTest
```
Expected: all tests PASS. If any fail before you've touched anything, stop and report — those are pre-existing failures not caused by this plan.

- [ ] **Step 3: Visit `/admin/orders` in your browser** and take a screenshot of the current state as before-baseline. This is your reference for "did I break anything functionally."

---

## Task 1: Add scoped CSS block + design tokens

**Files:**
- Modify: `resources/views/admin/orders/index.blade.php`

- [ ] **Step 1: Open the file and locate the top**

You will insert the `<style>` block immediately AFTER the closing `</x-slot>` for the header (around line 21, before `@php`). All custom CSS lives here, scoped under `.orders-page`.

- [ ] **Step 2: Insert the scoped style block**

Add this exactly, between line 20 (`</x-slot>`) and line 21 (`@php`):

```blade
    @push('styles')
    <style>
        .orders-page {
            /* ─── tokens ─── */
            --surface-base: #131a2e;
            --surface-raised: #1c2438;
            --surface-elevated: #1f283f;
            --surface-input: #131a2e;
            --border-default: #2a3553;
            --border-row: #232b42;
            --border-checkbox: #475569;
            --text-primary: #f8fafc;
            --text-body: #e6ecf5;
            --text-secondary: #cbd5e1;
            --text-muted: #8a95b0;
            --text-faint: #6b7794;
            --text-disabled: #525f7d;
            --accent-from: #0891b2;
            --accent-to: #0e7490;
            --accent-glow: rgba(8,145,178,0.35);

            background: var(--surface-base);
            color: var(--text-body);
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
            min-height: 100vh;
        }
        .orders-page * { box-sizing: border-box; }
    </style>
    @endpush
```

- [ ] **Step 3: Wrap the existing content in `.orders-page`**

Find the line that currently reads:
```blade
    <div class="py-8">
```
Replace with:
```blade
    <div class="orders-page py-8">
```

- [ ] **Step 4: Render the page and confirm nothing visually breaks yet**

Visit `/admin/orders`. The page should now have a dark navy background but the inner content still uses old Tailwind styling. That's expected — we're going section by section.

- [ ] **Step 5: Re-run the feature test**

```powershell
php artisan test --filter=AdminOrdersTest
```
Expected: still all PASS. CSS additions never break Blade-rendering tests, so any failure means a Blade syntax error — go back and check.

- [ ] **Step 6: Commit**

```bash
git add resources/views/admin/orders/index.blade.php
git commit -m "feat(admin/orders): add scoped Premium Dark SaaS CSS token block"
```

---

## Task 2: Restyle header (title + subtitle + Export button)

**Files:**
- Modify: `resources/views/admin/orders/index.blade.php` (header `<x-slot>` block, lines 1-20)

- [ ] **Step 1: Extend the CSS block** with header tokens

Add this rule INSIDE the existing `<style>` block, after the `box-sizing` rule:

```css
.orders-page .op-hdr {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 22px;
    padding: 0 0 18px;
    border-bottom: 1px solid var(--border-default);
}
.orders-page .op-hdr h1 {
    margin: 0;
    font-size: 22px;
    font-weight: 700;
    color: var(--text-primary);
    letter-spacing: -0.025em;
}
.orders-page .op-hdr .sub {
    margin: 5px 0 0;
    font-size: 12px;
    color: var(--text-muted);
    font-weight: 500;
}
.orders-page .op-hdr .sub b {
    color: #fcd34d;
    font-weight: 600;
}
.orders-page .op-export {
    background: linear-gradient(135deg, var(--accent-from), var(--accent-to));
    color: white;
    padding: 9px 16px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    box-shadow: 0 1px 0 rgba(255,255,255,0.08) inset, 0 4px 14px -4px var(--accent-glow);
    border: 1px solid rgba(255,255,255,0.08);
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
}
.orders-page .op-export svg { width: 14px; height: 14px; }
```

- [ ] **Step 2: Replace the header markup**

Find lines 1-20 (the `<x-slot name="header">` block). Replace the entire `<x-slot name="header">…</x-slot>` block with:

```blade
    <x-slot name="header">
        <div class="orders-page" style="background: transparent; min-height: 0; padding: 0;">
            <div class="op-hdr">
                <div>
                    <h1>{{ __('Orders Management') }}</h1>
                    <p class="sub">
                        {{ __(':total total', ['total' => number_format($stats['total'] ?? 0)]) }}
                        @if(($stats['pending'] ?? 0) > 0)
                            · <b>{{ __(':n need attention', ['n' => $stats['pending']]) }}</b>
                        @endif
                    </p>
                </div>
                <a class="op-export" href="{{ route('admin.orders.export-excel', array_filter([
                        'from' => request('from'),
                        'to' => request('to'),
                        'status' => request('status'),
                    ], fn ($v) => $v !== null && $v !== '')) }}">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                    {{ __('Export Excel (.xlsx)') }}
                </a>
            </div>
        </div>
    </x-slot>
```

**Note:** The outer `.orders-page` wrapper here is so the header inherits the tokens; it overrides background/padding because the layout's header bar already has its own background.

- [ ] **Step 3: Render and verify**

Refresh `/admin/orders`. Header should show: dark title, muted subtitle with bold amber attention count, cyan-gradient Export button on the right, thin separator line under the whole thing.

- [ ] **Step 4: Run tests**

```powershell
php artisan test --filter=AdminOrdersTest
```
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add resources/views/admin/orders/index.blade.php
git commit -m "feat(admin/orders): restyle header with Premium tokens"
```

---

## Task 3: Restyle stat grid (6 cards)

**Files:**
- Modify: `resources/views/admin/orders/index.blade.php` (stats block, currently `grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-6 gap-4 mb-6`)

- [ ] **Step 1: Extend CSS** with stat tokens

Add INSIDE `.orders-page` `<style>` block:

```css
.orders-page .op-stats {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 10px;
    margin-bottom: 20px;
}
@media (max-width: 900px) {
    .orders-page .op-stats { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 540px) {
    .orders-page .op-stats { grid-template-columns: repeat(2, 1fr); }
}
.orders-page .op-stat {
    background: var(--surface-raised);
    border: 1px solid var(--border-default);
    border-radius: 12px;
    padding: 14px 16px;
    position: relative;
    overflow: hidden;
}
.orders-page .op-stat::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 2px;
    background: var(--stat-a, #475569);
    opacity: 0.7;
}
.orders-page .op-stat .l {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: var(--text-muted);
    font-weight: 600;
}
.orders-page .op-stat .v {
    font-size: 22px;
    font-weight: 700;
    margin-top: 6px;
    line-height: 1;
    color: var(--stat-vc, var(--text-primary));
    font-variant-numeric: tabular-nums;
    text-shadow: 0 0 12px var(--stat-glow, transparent);
}
.orders-page .op-stat.tot   { --stat-vc: var(--text-primary); }
.orders-page .op-stat.warn  { --stat-a: #d97706; --stat-vc: #fcd34d; --stat-glow: rgba(252,211,77,0.18); }
.orders-page .op-stat.idx   { --stat-a: #6366f1; --stat-vc: #c4b5fd; --stat-glow: rgba(196,181,253,0.18); }
.orders-page .op-stat.info  { --stat-a: #0284c7; --stat-vc: #93c5fd; --stat-glow: rgba(147,197,253,0.18); }
.orders-page .op-stat.ok    { --stat-a: #10b981; --stat-vc: #6ee7b7; --stat-glow: rgba(110,231,183,0.18); }
.orders-page .op-stat.err   { --stat-a: #dc2626; --stat-vc: #fda4af; --stat-glow: rgba(253,164,175,0.15); }
```

- [ ] **Step 2: Replace the stat block**

Find the existing stats block (starts with `<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-6 gap-4 mb-6">`). Replace the entire div and its 6 inner `.rounded-2xl` cards with:

```blade
            <div class="op-stats">
                <div class="op-stat tot"><div class="l">{{ __('Total') }}</div><div class="v">{{ number_format($stats['total'] ?? 0) }}</div></div>
                <div class="op-stat warn"><div class="l">{{ __('Pending') }}</div><div class="v">{{ number_format($stats['pending'] ?? 0) }}</div></div>
                <div class="op-stat idx"><div class="l">{{ __('Processing') }}</div><div class="v">{{ number_format($stats['processing'] ?? 0) }}</div></div>
                <div class="op-stat info"><div class="l">{{ __('Shipped') }}</div><div class="v">{{ number_format($stats['shipped'] ?? 0) }}</div></div>
                <div class="op-stat ok"><div class="l">{{ __('Delivered') }}</div><div class="v">{{ number_format($stats['delivered'] ?? 0) }}</div></div>
                <div class="op-stat err"><div class="l">{{ __('Cancelled') }}</div><div class="v">{{ number_format($stats['cancelled'] ?? 0) }}</div></div>
            </div>
```

- [ ] **Step 3: Render and verify**

Refresh page. You should see 6 stat cards on one row (or 3+3 on tablet, 2+2+2 on mobile), each with a colored 2px top stripe and a bright tabular value with subtle glow.

- [ ] **Step 4: Tests + commit**

```powershell
php artisan test --filter=AdminOrdersTest
```
```bash
git add resources/views/admin/orders/index.blade.php
git commit -m "feat(admin/orders): restyle 6 stat cards with colored stripes + glow"
```

---

## Task 4: Restyle attention chips

**Files:**
- Modify: `resources/views/admin/orders/index.blade.php` (chip block, the `@php $attentionChips = [...]` followed by `<div class="mb-4 flex flex-wrap gap-2">`)

- [ ] **Step 1: Extend CSS**

```css
.orders-page .op-chips {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 16px;
}
.orders-page .op-chip {
    background: var(--surface-raised);
    border: 1px solid var(--border-default);
    padding: 7px 14px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 600;
    color: var(--text-muted);
    transition: all .15s ease;
    text-decoration: none;
}
.orders-page .op-chip:hover {
    border-color: #475569;
    color: var(--text-secondary);
}
.orders-page .op-chip.on {
    background: linear-gradient(135deg, var(--accent-from), var(--accent-to));
    color: white;
    border-color: rgba(255,255,255,0.15);
    box-shadow: 0 4px 12px -3px var(--accent-glow);
}
```

- [ ] **Step 2: Replace the chip block markup**

Find the block starting with `@php $currentAttention = $attention ?? '';` (keep that `@php` block as-is). Replace the `<div class="mb-4 flex flex-wrap gap-2">…@foreach…</div>` with:

```blade
            <div class="op-chips">
                @foreach($attentionChips as $value => $label)
                    @php $isActive = $currentAttention === $value; @endphp
                    <a href="{{ request()->fullUrlWithQuery(['attention' => $value === '' ? null : $value, 'page' => null]) }}"
                       class="op-chip @if($isActive) on @endif">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
```

- [ ] **Step 3: Render and verify**

The chip row should now show pill-shaped buttons. The active one ("All" by default) has a cyan gradient with soft glow. Hover on inactive ones lightens them.

- [ ] **Step 4: Tests + commit**

```powershell
php artisan test --filter=AdminOrdersTest
```
```bash
git add resources/views/admin/orders/index.blade.php
git commit -m "feat(admin/orders): restyle attention chips"
```

---

## Task 5: Restyle filter card (search, status, dates, Apply/Clear)

**Files:**
- Modify: `resources/views/admin/orders/index.blade.php` (filter card wrapper + filter form)

- [ ] **Step 1: Extend CSS**

```css
.orders-page .op-card {
    background: var(--surface-raised);
    border: 1px solid var(--border-default);
    border-radius: 14px;
    overflow: hidden;
}
.orders-page .op-filter {
    padding: 14px;
    border-bottom: 1px solid var(--border-default);
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    gap: 10px;
}
@media (max-width: 900px) {
    .orders-page .op-filter { grid-template-columns: 1fr; }
}
.orders-page .op-input,
.orders-page .op-select {
    background: var(--surface-input);
    border: 1px solid var(--border-default);
    color: var(--text-body);
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-family: inherit;
    width: 100%;
}
.orders-page .op-input::placeholder { color: var(--text-disabled); }
.orders-page .op-input:focus,
.orders-page .op-select:focus { outline: 1px solid var(--accent-from); border-color: var(--accent-from); }
.orders-page .op-dates { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.orders-page .op-dates label {
    display: flex; flex-direction: column;
    font-size: 10px; font-weight: 600;
    color: var(--text-muted);
    text-transform: uppercase; letter-spacing: .05em;
    gap: 4px;
}
.orders-page .op-actions {
    display: flex; gap: 8px;
    padding: 12px 14px;
    border-bottom: 1px solid var(--border-default);
}
.orders-page .op-btn-primary {
    background: linear-gradient(135deg, var(--accent-from), var(--accent-to));
    color: white;
    padding: 7px 14px;
    border-radius: 7px;
    font-size: 11px;
    font-weight: 600;
    border: 1px solid rgba(255,255,255,0.08);
    text-decoration: none;
}
.orders-page .op-btn-ghost {
    background: transparent;
    border: 1px solid var(--border-default);
    color: var(--text-muted);
    padding: 7px 14px;
    border-radius: 7px;
    font-size: 11px;
    font-weight: 500;
    text-decoration: none;
}
.orders-page .op-btn-ghost:hover { color: var(--text-body); border-color: #475569; }
```

- [ ] **Step 2: Replace the table card wrapper + filter form**

Find the existing block starting with `<div class="rounded-2xl border border-slate-200 bg-white shadow-sm…"` (the big white card with the filter form and table inside).

Replace the WRAPPER div opening — keep the Alpine x-data block content (the `x-data="{ selected: [], allIds: …, … }"` part) — only swap the class:

OLD:
```blade
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900"
                 x-data="{ … }">
```
NEW:
```blade
            <div class="op-card"
                 x-data="{ … }">
```

Then find the filter `<form>` block. Replace it (the whole form including all inputs/selects/buttons) with:

```blade
                <div class="op-filter">
                    @if($currentAttention !== '')
                        <input type="hidden" name="attention" value="{{ $currentAttention }}" form="orders-filter-form">
                    @endif
                    <input form="orders-filter-form" class="op-input" type="text" name="search"
                           value="{{ request('search') }}"
                           placeholder="{{ __('Search order #, city, phone, user...') }}">
                    <select form="orders-filter-form" name="status" class="op-select">
                        <option value="">{{ __('All Statuses') }}</option>
                        @foreach($statusOptions as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>
                                {{ \App\Models\Order::statusMeta((string) $status)['label'] }}
                            </option>
                        @endforeach
                    </select>
                    <div class="op-dates">
                        <label>{{ __('From') }}<input form="orders-filter-form" class="op-input" type="date" name="from" value="{{ request('from') }}"></label>
                        <label>{{ __('To') }}<input form="orders-filter-form" class="op-input" type="date" name="to" value="{{ request('to') }}"></label>
                    </div>
                    <select form="orders-filter-form" name="association" class="op-select" style="grid-column: span 1;">
                        <option value="">{{ __('All Users') }}</option>
                        <option value="user" @selected(($association ?? '') === 'user')>{{ __('Retail Users') }}</option>
                        <option value="dealer" @selected(($association ?? '') === 'dealer')>{{ __('Dealers') }}</option>
                    </select>
                </div>
                <div class="op-actions">
                    <form id="orders-filter-form" method="GET" action="{{ route('admin.orders.index') }}" style="display:contents"></form>
                    <button form="orders-filter-form" type="submit" class="op-btn-primary">{{ __('Apply Filters') }}</button>
                    @if(request()->hasAny(['search', 'status', 'association', 'from', 'to']))
                        <a class="op-btn-ghost"
                           href="{{ route('admin.orders.index', $currentAttention !== '' ? ['attention' => $currentAttention] : []) }}">
                            {{ __('Clear') }}
                        </a>
                    @endif
                </div>
```

**Why the `form="orders-filter-form"` attribute and split:** The original form wrapped all inputs together. In the new structure I want the inputs visually grouped in one card section and the submit button in a separate action bar. HTML5 lets you keep them associated via the `form=` attribute even when they're not nested in the `<form>`.

- [ ] **Step 3: Render and verify**

Refresh. The filter card is now a dark slate panel containing four inputs (search, status, association, dates) on top, and an action bar with Apply + Clear below. Submitting the form should still filter the table (test by typing in search and clicking Apply).

- [ ] **Step 4: Tests + commit**

```powershell
php artisan test --filter=AdminOrdersTest
```
```bash
git add resources/views/admin/orders/index.blade.php
git commit -m "feat(admin/orders): restyle filter card and action buttons"
```

---

## Task 6: Restyle bulk action bar

**Files:**
- Modify: `resources/views/admin/orders/index.blade.php` (the `<div x-show="selected.length > 0">` block)

- [ ] **Step 1: Extend CSS**

```css
.orders-page .op-bulk {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 12px;
    background: linear-gradient(90deg, rgba(8,145,178,0.18), rgba(8,145,178,0.06));
    border-bottom: 1px solid rgba(56,189,248,0.2);
    padding: 12px 14px;
}
.orders-page .op-bulk .count {
    font-size: 12px;
    font-weight: 700;
    color: #93c5fd;
}
.orders-page .op-bulk form { display: flex; align-items: center; gap: 8px; margin-left: auto; }
.orders-page .op-bulk select {
    background: var(--surface-input);
    border: 1px solid var(--border-default);
    color: var(--text-body);
    padding: 6px 10px;
    border-radius: 7px;
    font-size: 11px;
}
```

- [ ] **Step 2: Replace the bulk bar markup**

Find the `<div x-show="selected.length > 0" x-cloak …>` block. Replace it with:

```blade
                <div x-show="selected.length > 0" x-cloak class="op-bulk">
                    <span class="count"><span x-text="selected.length"></span> {{ __('selected') }}</span>
                    <form method="POST" action="{{ route('admin.orders.bulk-status') }}"
                          @submit="if (!confirm('{{ __('Apply this status change to the selected orders?') }}')) $event.preventDefault()">
                        @csrf
                        <template x-for="id in selected" :key="id">
                            <input type="hidden" name="order_ids[]" :value="id">
                        </template>
                        <select name="status" required>
                            <option value="">{{ __('Set status…') }}</option>
                            @foreach($statusOptions as $status)
                                <option value="{{ $status }}">{{ \App\Models\Order::statusMeta((string) $status)['label'] }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="op-btn-primary">{{ __('Apply') }}</button>
                        <button type="button" @click="selected = []" class="op-btn-ghost">{{ __('Clear') }}</button>
                    </form>
                </div>
```

- [ ] **Step 3: Render and verify**

Refresh. Check a few order checkboxes — the bulk bar should appear above the filter section with a cyan-tinted gradient, the count text in bright blue, and the action buttons styled to match.

- [ ] **Step 4: Tests + commit**

```powershell
php artisan test --filter=AdminOrdersTest
```
```bash
git add resources/views/admin/orders/index.blade.php
git commit -m "feat(admin/orders): restyle bulk action bar"
```

---

## Task 7: Restyle table (header, rows, cells)

**Files:**
- Modify: `resources/views/admin/orders/index.blade.php` (the `<div class="overflow-x-auto"><table…>` block — header `<thead>` and body `<tbody>` non-pill cells)

- [ ] **Step 1: Extend CSS**

```css
.orders-page .op-table-wrap { overflow-x: auto; }
.orders-page .op-tbl {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
}
.orders-page .op-tbl thead th {
    background: var(--surface-elevated);
    color: var(--text-muted);
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .08em;
    font-weight: 700;
    padding: 11px 14px;
    text-align: left;
    border-bottom: 1px solid var(--border-default);
}
.orders-page .op-tbl tbody tr {
    border-bottom: 1px solid var(--border-row);
    transition: background .12s ease;
}
.orders-page .op-tbl tbody tr:last-child { border-bottom: none; }
.orders-page .op-tbl tbody tr:hover { background: rgba(255,255,255,0.015); }
.orders-page .op-tbl td { padding: 13px 14px; vertical-align: middle; }
.orders-page .op-tbl .num { font-weight: 600; color: var(--text-primary); display: block; font-size: 12px; margin-bottom: 2px; }
.orders-page .op-tbl .id  { font-family: 'JetBrains Mono', ui-monospace, monospace; color: var(--text-muted); font-size: 10px; }
.orders-page .op-tbl .who { color: var(--text-body); font-weight: 500; font-size: 12px; }
.orders-page .op-tbl .em  { color: var(--text-faint); font-size: 10px; }
.orders-page .op-tbl .ttl { font-weight: 700; color: var(--text-primary); font-variant-numeric: tabular-nums; text-align: right; }
.orders-page .op-tbl .ttl .cy { color: var(--text-faint); font-weight: 400; font-size: 10px; margin-left: 3px; }
.orders-page .op-tbl .meth { color: var(--text-disabled); font-size: 9.5px; margin-top: 3px; font-family: 'JetBrains Mono', monospace; }
.orders-page .op-tbl input[type="checkbox"] {
    width: 14px; height: 14px;
    accent-color: var(--accent-from);
    border-radius: 3px;
    background: var(--surface-elevated);
    border: 1.5px solid var(--border-checkbox);
    cursor: pointer;
}
.orders-page .op-tbl .date-d  { color: var(--text-secondary); font-size: 11px; }
.orders-page .op-tbl .date-t  { color: var(--text-faint); font-size: 9px; margin-top: 2px; }
.orders-page .op-tbl .empty   { color: var(--text-secondary); font-size: 12px; text-align: center; padding: 48px 16px; }
```

- [ ] **Step 2: Replace the `<table>` markup**

Find `<div class="overflow-x-auto"><table class="…">` and replace through `</table></div>` with:

```blade
                <div class="op-table-wrap">
                    <table class="op-tbl">
                        <thead>
                            <tr>
                                <th style="width:32px">
                                    <input type="checkbox"
                                           @change="toggleAll($event)"
                                           :checked="allSelected()"
                                           aria-label="{{ __('Select all') }}">
                                </th>
                                <th>{{ __('Order') }}</th>
                                <th>{{ __('User / Dealer') }}</th>
                                <th>{{ __('Items') }}</th>
                                <th style="text-align:right">{{ __('Total') }}</th>
                                <th>{{ __('Payment') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th style="text-align:right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orders as $order)
                                @php
                                    $isDealer = $order->user && $order->user->role === \App\Models\User::ROLE_DEALER;
                                    $statusMeta = \App\Models\Order::statusMeta((string) $order->status);
                                    $paymentMeta = \App\Models\Order::paymentStatusMeta((string) $order->payment_status);
                                    $allowedTransitions = $transitionOptions[$order->id] ?? [$order->status];
                                @endphp
                                <tr :class="selected.includes({{ $order->id }}) ? 'op-row-selected' : ''">
                                    <td>
                                        <input type="checkbox"
                                               value="{{ $order->id }}"
                                               x-model.number="selected"
                                               aria-label="{{ __('Select order #:order', ['order' => $order->order_number]) }}">
                                    </td>
                                    <td>
                                        <span class="num">{{ $order->order_number }}</span>
                                        <span class="id">#{{ $order->id }}</span>
                                        @if($order->cancellation_requested_at && $order->status !== \App\Models\Order::STATUS_CANCELLED)
                                            <div class="op-alert"><span>⚠</span>{{ __('Cancellation Requested') }}</div>
                                        @endif
                                        @if(($order->open_returns_count ?? 0) > 0)
                                            <div class="op-alert warn"><span>↩</span>{{ __('Return requests') }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="who">{{ $order->user?->name ?? __('Guest customer') }}</div>
                                        <div class="em">{{ $order->user?->email ?? '-' }}</div>
                                        @if($order->user)
                                            <span class="op-pill {{ $isDealer ? 'processing' : 'shipped' }}" style="margin-top:4px">{{ $isDealer ? __('Dealer') : __('User') }}</span>
                                        @endif
                                    </td>
                                    <td style="color: var(--text-secondary)">{{ $order->items_count }}</td>
                                    <td class="ttl">{{ number_format((float) $order->total_amount, $currencyDecimals) }}<span class="cy">{{ $currencyLabel }}</span></td>
                                    <td>
                                        <span class="op-pill {{ $order->payment_status }}">{{ $paymentMeta['label'] }}</span>
                                        @if($order->payment_method)
                                            <div class="meth">{{ $order->payment_method }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="op-pill {{ $order->status }}">{{ $statusMeta['label'] }}</span>
                                    </td>
                                    <td>
                                        <div class="date-d">{{ $order->created_at?->format('M d, Y') }}</div>
                                        <div class="date-t">{{ $order->created_at?->format('h:i A') }}</div>
                                    </td>
                                    <td>
                                        {{-- Actions column rebuilt in Task 9 --}}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="empty">{{ __('No orders found for the current filter.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
```

**Note:** Pills and alerts are styled in the NEXT task. They will look unstyled until then — that's expected. Actions column is intentionally empty here and will be rebuilt in Task 9.

- [ ] **Step 3: Render and verify**

Refresh. The table should now show: dark header row with uppercase muted labels, dark body rows with hover effect, monospace order IDs, bold tabular totals, and styled checkboxes. Pills/alerts/actions will look broken (no styling yet) — that's fine.

- [ ] **Step 4: Tests + commit**

```powershell
php artisan test --filter=AdminOrdersTest
```
```bash
git add resources/views/admin/orders/index.blade.php
git commit -m "feat(admin/orders): restyle table header and rows"
```

---

## Task 8: Status pills, alert badges, payment method styling

**Files:**
- Modify: `resources/views/admin/orders/index.blade.php` (style block only — markup already in place from Task 7)

- [ ] **Step 1: Extend CSS** with pill + alert styles

```css
.orders-page .op-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3.5px 10px;
    border-radius: 999px;
    font-size: 10.5px;
    font-weight: 600;
    border: 1px solid;
}
.orders-page .op-pill::before {
    content: '';
    width: 5px; height: 5px;
    border-radius: 50%;
    background: currentColor;
    box-shadow: 0 0 6px currentColor;
    opacity: 0.85;
}
/* Order statuses (match Order model status constants) */
.orders-page .op-pill.pending    { background: rgba(217,119,6,0.18);  color: #fcd34d; border-color: rgba(252,211,77,0.25); }
.orders-page .op-pill.processing { background: rgba(99,102,241,0.18); color: #c4b5fd; border-color: rgba(196,181,253,0.28); }
.orders-page .op-pill.shipped    { background: rgba(2,132,199,0.20);  color: #93c5fd; border-color: rgba(147,197,253,0.28); }
.orders-page .op-pill.delivered  { background: rgba(16,185,129,0.22); color: #86efac; border-color: rgba(134,239,172,0.30); }
.orders-page .op-pill.cancelled  { background: rgba(220,38,38,0.18);  color: #fda4af; border-color: rgba(253,164,175,0.25); }
/* Payment statuses */
.orders-page .op-pill.paid       { background: rgba(16,185,129,0.18); color: #6ee7b7; border-color: rgba(110,231,183,0.25); }
.orders-page .op-pill.failed     { background: rgba(220,38,38,0.18);  color: #fda4af; border-color: rgba(253,164,175,0.25); }
.orders-page .op-pill.refunded   { background: rgba(100,116,139,0.20); color: #cbd5e1; border-color: rgba(203,213,225,0.25); }

.orders-page .op-alert {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    background: rgba(220,38,38,0.14);
    color: #fda4af;
    border: 1px solid rgba(253,164,175,0.25);
    font-size: 9px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 4px;
    margin-top: 5px;
    text-transform: uppercase;
    letter-spacing: .05em;
}
.orders-page .op-alert.warn {
    background: rgba(217,119,6,0.14);
    color: #fcd34d;
    border-color: rgba(252,211,77,0.25);
}

.orders-page .op-row-selected { background: rgba(8,145,178,0.06); }
```

- [ ] **Step 2: Render and verify**

Refresh. Status pills now have glowing dots and tinted backgrounds matching each status. Cancel/Return alert badges are small uppercase pills in red/amber. Selected rows have a subtle cyan tint.

- [ ] **Step 3: Tests + commit**

```powershell
php artisan test --filter=AdminOrdersTest
```
```bash
git add resources/views/admin/orders/index.blade.php
git commit -m "feat(admin/orders): style status pills and alert badges"
```

---

## Task 9: Actions column with kebab "More" menu

**Files:**
- Modify: `resources/views/admin/orders/index.blade.php` (Actions `<td>` from Task 7 + new CSS for icon buttons and dropdown)

- [ ] **Step 1: Extend CSS** with action icon + dropdown styles

```css
.orders-page .op-acts { display: flex; gap: 4px; justify-content: flex-end; align-items: center; }
.orders-page .op-icon {
    width: 28px; height: 28px;
    display: flex; align-items: center; justify-content: center;
    border-radius: 6px;
    background: var(--surface-elevated);
    border: 1px solid var(--border-default);
    color: var(--text-muted);
    transition: all .12s ease;
    text-decoration: none;
    cursor: pointer;
}
.orders-page .op-icon:hover { color: #93c5fd; border-color: #475569; }
.orders-page .op-icon svg { width: 13px; height: 13px; }

/* Kebab dropdown */
.orders-page .op-menu {
    position: absolute;
    right: 0;
    margin-top: 6px;
    min-width: 220px;
    background: var(--surface-elevated);
    border: 1px solid var(--border-default);
    border-radius: 10px;
    box-shadow: 0 12px 32px -8px rgba(0,0,0,0.5);
    padding: 6px;
    z-index: 30;
}
.orders-page .op-menu .head {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: var(--text-faint);
    padding: 6px 10px 4px;
    font-weight: 700;
}
.orders-page .op-menu form { display: flex; align-items: center; gap: 6px; padding: 6px 8px; }
.orders-page .op-menu select {
    flex: 1;
    background: var(--surface-input);
    border: 1px solid var(--border-default);
    color: var(--text-body);
    padding: 6px 8px;
    border-radius: 6px;
    font-size: 11px;
}
.orders-page .op-menu button[type="submit"] {
    background: linear-gradient(135deg, var(--accent-from), var(--accent-to));
    color: white;
    padding: 6px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    border: 1px solid rgba(255,255,255,0.08);
}
.orders-page .op-menu .danger {
    display: block; width: 100%;
    text-align: left;
    padding: 8px 10px;
    border-radius: 6px;
    background: transparent;
    color: #fda4af;
    font-size: 11px;
    font-weight: 600;
    border: none;
}
.orders-page .op-menu .danger:hover { background: rgba(220,38,38,0.12); }
.orders-page .op-menu hr { border: 0; border-top: 1px solid var(--border-default); margin: 4px 0; }
```

- [ ] **Step 2: Replace the Actions cell**

Find the `<td>{{-- Actions column rebuilt in Task 9 --}}</td>` placeholder from Task 7. Replace it with the full actions cell:

```blade
                                    <td>
                                        <div class="op-acts">
                                            <a class="op-icon" href="{{ route('admin.orders.show', $order) }}" title="{{ __('View') }}">
                                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            </a>
                                            <a class="op-icon" href="{{ route('admin.orders.invoice', $order) }}" title="{{ __('Invoice') }}">
                                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            </a>
                                            <div x-data="{ open: false }" @click.outside="open = false" style="position: relative;">
                                                <button class="op-icon" @click="open = !open" type="button" title="{{ __('More') }}">
                                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><circle cx="5" cy="12" r="1.2"/><circle cx="12" cy="12" r="1.2"/><circle cx="19" cy="12" r="1.2"/></svg>
                                                </button>
                                                <div class="op-menu" x-show="open" x-cloak x-transition>
                                                    <div class="head">{{ __('Update Status') }}</div>
                                                    <form method="POST" action="{{ route('admin.orders.update-status', $order) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <select name="status">
                                                            @foreach($statusOptions as $status)
                                                                <option value="{{ $status }}"
                                                                        @selected($order->status === $status)
                                                                        @disabled(!in_array($status, $allowedTransitions, true))>
                                                                    {{ \App\Models\Order::statusMeta((string) $status)['label'] }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <button type="submit">{{ __('Save') }}</button>
                                                    </form>
                                                    @if(auth()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN)
                                                        <hr>
                                                        <form method="POST" action="{{ route('admin.orders.destroy', $order) }}"
                                                              data-danger-confirm
                                                              data-danger-title="{{ __('Archive Order') }}"
                                                              data-danger-description="{{ __('The order will be hidden from the active order list but kept for financial history and audit review.') }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="danger">{{ __('Archive Order') }}</button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
```

- [ ] **Step 3: Render and verify**

Refresh. Each row now has three icon buttons on the right: eye (View → goes to show page), document (Invoice → downloads PDF), kebab (3 dots → opens dropdown). Click the kebab on a row → a dark dropdown panel appears with "Update Status" + select + Save button, and (for super admin) a red "Archive Order" button below a separator. Click outside closes the dropdown.

- [ ] **Step 4: Tests + commit**

```powershell
php artisan test --filter=AdminOrdersTest
```
```bash
git add resources/views/admin/orders/index.blade.php
git commit -m "feat(admin/orders): rebuild Actions column with icons + kebab menu"
```

---

## Task 10: Restyle pagination

**Files:**
- Modify: `resources/views/admin/orders/index.blade.php` (the pagination block at the bottom of the table card)

- [ ] **Step 1: Extend CSS**

```css
.orders-page .op-pag {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 16px;
    border-top: 1px solid var(--border-default);
    color: var(--text-muted);
    font-size: 11px;
}
.orders-page .op-pag nav { display: flex; }
.orders-page .op-pag svg { width: 14px; height: 14px; }
/* Laravel default pagination uses Tailwind classes — override key ones */
.orders-page .op-pag .pagination,
.orders-page .op-pag ul {
    display: flex; gap: 4px; list-style: none; margin: 0; padding: 0;
}
.orders-page .op-pag a,
.orders-page .op-pag span {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 28px; height: 28px;
    padding: 0 8px;
    border-radius: 6px;
    background: var(--surface-elevated);
    border: 1px solid var(--border-default);
    color: var(--text-muted);
    font-size: 11px;
    font-weight: 600;
    text-decoration: none;
}
.orders-page .op-pag a:hover { color: var(--text-body); border-color: #475569; }
.orders-page .op-pag .active span,
.orders-page .op-pag span[aria-current="page"] {
    background: linear-gradient(135deg, var(--accent-from), var(--accent-to));
    color: white;
    border-color: rgba(255,255,255,0.15);
}
.orders-page .op-pag .disabled span {
    opacity: 0.4;
    cursor: not-allowed;
}
```

- [ ] **Step 2: Replace the pagination block**

Find the `@if($orders->hasPages())` block at the bottom. Replace with:

```blade
                @if($orders->hasPages())
                    <div class="op-pag">
                        <span>{{ __('Showing :from–:to of :total orders', [
                            'from' => $orders->firstItem() ?? 0,
                            'to' => $orders->lastItem() ?? 0,
                            'total' => $orders->total(),
                        ]) }}</span>
                        {{ $orders->links() }}
                    </div>
                @endif
```

**Note:** Laravel's default pagination renderer outputs Tailwind-styled HTML. The CSS overrides in Step 1 target the standard classes Laravel emits (`pagination`, `active`, `disabled`, etc.) so the rendered output picks up the dark theme without needing a custom view.

- [ ] **Step 3: Render and verify**

Visit a page where there are more than 12 orders. Pagination at the bottom should show: "Showing X–Y of Z orders" on the left, and styled page-number pills on the right with the active page in cyan gradient.

- [ ] **Step 4: Tests + commit**

```powershell
php artisan test --filter=AdminOrdersTest
```
```bash
git add resources/views/admin/orders/index.blade.php
git commit -m "feat(admin/orders): restyle pagination"
```

---

## Task 11: Final verification

**Files:**
- No file changes — verification only

- [ ] **Step 1: Run the full feature test suite for admin orders**

```powershell
php artisan test --filter=AdminOrdersTest
```
Expected: all PASS.

- [ ] **Step 2: Run broader admin tests for regressions**

```powershell
php artisan test --filter=Admin
```
Expected: all PASS. (Verifies no scoped CSS leaked, no Blade structural change leaked into other admin views.)

- [ ] **Step 3: Manual smoke test in browser**

Visit `/admin/orders` and verify:
1. **Header**: title, attention subtitle, cyan Export button — present and styled
2. **Stats**: 6 cards with colored stripes and glowing values
3. **Chips**: "All / Today pending / Needs shipping / Cancellation requests / Return requests" — active state cyan, others dark
4. **Filter**: type in search → submit → list filters; pick date range → submit; click Clear → resets
5. **Bulk update**: check 2+ row checkboxes → blue bar appears → pick status → Apply → confirmation prompt → submits
6. **Table**: order column shows order#, ID, alert badges (if any); customer column shows name, email, role pill; payment + status pills colored correctly; date shows date+time
7. **Actions**: View opens show page; Invoice triggers PDF download; kebab opens dropdown with Update Status form and Archive button
8. **Pagination**: navigate to page 2 → page 2 active and styled cyan
9. **Locale switch**: switch to AR → page reads RTL, all labels translated, layout doesn't break; same for KU

- [ ] **Step 4: Take a final screenshot** for comparison against the v6 mockup at `.superpowers/brainstorm/700-1780349946/content/v6-premium.html`. Visual match should be close to pixel-perfect.

- [ ] **Step 5: Cleanup** (only if you find anything)

If anything is off, write a follow-up task in this file at the bottom with the specific tweak needed. Don't fix it in this task — keep the verification step pure verification.

- [ ] **Step 6: Final tagging commit**

```bash
git commit --allow-empty -m "feat(admin/orders): Premium Dark SaaS redesign complete

All 10 tasks of docs/superpowers/plans/2026-06-02-orders-management-redesign.md
applied. Visual-only changes to resources/views/admin/orders/index.blade.php.
AdminOrdersTest still green."
```

---

## Verification checklist (run before declaring done)

- [ ] `php artisan test --filter=AdminOrdersTest` passes
- [ ] `php artisan test --filter=Admin` passes (no scoped-CSS leakage)
- [ ] `/admin/orders` renders in en, ar, ku without layout breaks
- [ ] All 8 columns visible and styled
- [ ] All buttons/forms functional (search, filter, chip click, bulk update, row update via kebab, Archive, Export, Invoice download, View)
- [ ] Pagination works
- [ ] No console errors in browser dev tools
- [ ] Visual match to v6 mockup

---

## Notes for the executing engineer

- **CSS scoping:** Every selector starts with `.orders-page` so styles cannot leak. If you find yourself writing a rule WITHOUT that prefix, stop — that's a bug.
- **Order matters in the style block:** Tokens first, then components. The order across the tasks above matches this.
- **Don't rename HTML form `name=` attributes** — they're contracts with the controller. Only swap classes.
- **The `form="orders-filter-form"` HTML5 trick** (Task 5) is intentional. If a reviewer flags it, point to spec section "Filter card" and explain the visual grouping reason.
- **The kebab menu (Task 9) is the one UX change** in this otherwise visual-only redesign. Spec acknowledges this. Status update and Archive moved INTO the kebab; they're still accessible.
- **If a test fails:** revert the last task with `git reset --hard HEAD~1` and re-apply more carefully. Each task is one commit specifically to make this easy.
