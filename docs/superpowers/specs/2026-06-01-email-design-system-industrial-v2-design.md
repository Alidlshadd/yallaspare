# Email Design System — Industrial v2

**Date:** 2026-06-01
**Owner:** Frontend / brand
**Scope:** Transactional email templates only. No website page changes, no admin UI changes.
**Status:** Design approved, ready for implementation planning.

## 1. Goal

Replace the current "Mailchimp-default" e-mail look with a distinctive, automotive-mechanical visual system that:

- Reads as **YallaSpare** specifically (not as a generic SaaS template).
- Establishes clear visual hierarchy (eye knows where to go first).
- Stays clean and premium — not crowded, not aggressive.
- Works the same across 6 transactional emails so the brand feels coherent.

## 2. Scope

### In scope — 6 customer-facing transactional emails

| Email | Blade file | Use case |
|---|---|---|
| Welcome | `emails/auth/welcome.blade.php` | New account opened |
| Order status | `emails/orders/status.blade.php` | Placed / shipped / delivered / cancelled |
| Email verification | `emails/auth/verify-email.blade.php` | Click-to-verify link |
| Password reset | `emails/auth/reset-password.blade.php` | Security flow |
| Admin 2FA code | `emails/admin/two-factor-code.blade.php` | Sign-in code |
| Security alert | `emails/admin/security-alert.blade.php` | Suspicious activity |

### Inherits automatically (base layout only, body content unchanged)

- `emails/dealer/notification.blade.php`
- `emails/operational/notification.blade.php`
- `emails/inventory/low-stock-alert.blade.php`
- Any `emails/support/*` templates

These pick up the new hero, accent line, and footer through `@extends('emails.layouts.base')` without bespoke work.

### Out of scope (explicit)

- Website pages (auth, welcome hero, storefront, account, admin panel)
- Admin email center UI (`resources/views/admin/email/*`)
- Plain-text variants in `emails/text/*` (leave as-is)
- New brand color tokens beyond what D-1/D-2 already established

## 3. User-stated refinements (binding)

These are non-negotiable design constraints from the approval message:

1. **Primary CTA always navy** (`#070740`). Never red/orange.
2. **Red only for semantic security/danger labels.** No red dolgu, no red button fills, no red headlines.
3. **Orange/red accent very subtle — 1–2px line maximum.** No solid colored backgrounds or large accent areas.
4. **Mobile-safe.** Layout must hold at 320px width minimum.
5. **Footer readable in dark-mode email clients** (Gmail dark, Apple Mail dark, Outlook dark).
6. **Gmail + Apple Mail + Outlook compatibility** — table-based markup, inline styles, no flexbox/grid for layout structure.
7. **RTL works correctly for Arabic and Kurdish.**

## 4. Brand tokens used

Already established in earlier commits:

| Token | Hex | Purpose | Source |
|---|---|---|---|
| primary (navy) | `#070740` | Hero bg, headlines, CTAs | D-1, D-2 |
| primary-hover | `#0a0d3f` | CTA hover (web-only — emails don't have hover state) | D-1 |
| accent (orange) | `#e85d2a` | 2px hairline under hero only | Pre-existing storefront token |
| danger / security | `#b91c1c` | Security label dot + caps text only | Per D-2 strategy |

**No new tokens introduced by this spec.** Reuses what's already in `tailwind.config.js` and inline email styles.

## 5. System primitives

Each email is composed from these reusable units. All units must be table-based (not flex/grid) for Outlook compatibility.

### 5.1 Hero (universal)

- Background: solid navy `#070740`
- Texture: 22×22px radial-dot pattern at `rgba(255,255,255,0.07)` — adds tactile feel without weight
- Padding: `28px 36px` desktop, `24px 22px` mobile
- Two-column row: brand mark left, monospace spec tag right (e.g., `SYS / WELCOME`, `ORD / YS-1029847`, `SEC / 2FA`)
- Brand mark: `YALLASPARE` in Space Grotesk / Inter 700, 17px, letter-spacing `-0.2px`
- Spec tag: SF Mono / Consolas 10px, color `#a4b3d4`, letter-spacing `1.5px`, uppercase

### 5.2 Accent stripe

- 2px solid `#e85d2a` directly under hero
- Full width
- Single visual cue — no other orange anywhere

### 5.3 Body container

- Background: pure white `#ffffff`
- Padding: `44px 40px 36px` desktop, `32px 22px 28px` mobile
- Max width 560px on desktop, 100% on mobile

### 5.4 Kicker (pre-headline label)

- Above headline, replaces the heavy "ACCOUNT · ACTIVATED" black tag from v1
- SF Mono 10.5px, `#8a8ea3`, letter-spacing `2.2px`, uppercase
- Examples: `Account · Activated`, `Order · Confirmed`, `Order · Shipped`

### 5.5 Headline

- Space Grotesk / Inter 700, 30px desktop / 24px mobile
- Color `#070740`
- Line-height `1.15`, letter-spacing `-0.6px`
- 1–2 lines max
- Plain navy — **no orange word accent** (revised from v1)

### 5.6 Lede paragraph

- System sans 15px, color `#4a4e63`, line-height `1.62`
- 1–3 sentences

### 5.7 Meta-list block (key/value pairs)

- 2 columns desktop, stacks to 1 column on mobile
- Bordered top + bottom with `1px solid #ebedf0`, padding `18px 0`
- Each item: monospace key (9.5px, `#9aa0b5`, caps) + bold value (Space Grotesk 700, 14px, navy)
- Used in welcome (Üye No / Region), can extend to other emails as needed

### 5.8 Order-row + totals (order status only)

- Top-bordered row list, `1px solid #ebedf0`
- Each row: 44×44 placeholder thumb (or product image when available) + name + SKU + price
- Totals block below: subtotal / shipping / discount / **total** (last row weight-700)

### 5.9 Code block (verification + 2FA)

- Centered in inset card (`#fafbfc` bg, 1px `#ebedf0` border, 4px radius, padding `28px 22px`)
- Code: SF Mono 36px, 700, navy, letter-spacing 12px
- Help caption below: monospace 10.5px, `#9aa0b5`, e.g., "5 dakika geçerli"

### 5.10 CTA button

- Padding `13px 26px`
- Background `#070740`, color `#fff`
- Space Grotesk 600, 13.5px
- Border-radius `3px` (sharp but not razor)
- Arrow `→` appended with 70% opacity for direction cue
- No border, no shadow, no hover state (emails don't render hover reliably)

### 5.11 Security label (security/2FA/reset only)

- Inline-flex: 6px dot + caps text
- SF Mono 10.5px, color `#b91c1c`, letter-spacing `2.5px`
- Sits where the kicker would sit (replaces it)
- Examples: `Security · Sign-in Code`, `Security · Password Reset`, `Security · Account Alert`

### 5.12 Footer

- Background `#fafbfc` (light grey), top border `1px solid #ececec`
- Padding `22px 36px`
- Two-column: copyright left (monospace 10.5px, `#8a8ea3`) + support email link right
- **Dark-mode override:** `@media (prefers-color-scheme: dark)` switches to bg `#0f1035`, text `#94a3b8`, link `#60a5fa` (existing rules in base.blade.php — extend, don't remove)

## 6. Per-email composition

| Email | Kicker / Label | Headline | Body block | CTA |
|---|---|---|---|---|
| Welcome | Kicker: "Account · Activated" | "Hesabın hazır, {name}." | lede + meta-list (member no, region) | "Hesabımı aç →" |
| Order status | Kicker: "Order · {Status}" | "Siparişin {status}." | lede + order-rows + totals | "Siparişi gör →" |
| Email verification | Kicker: "Account · Verify" | "E-postanı doğrula." | lede + code-block OR plain CTA | "Doğrula →" |
| Password reset | Security label: "Security · Password Reset" | "Parolanı sıfırla." | lede + reset CTA | "Parolayı sıfırla →" |
| Admin 2FA | Security label: "Security · Sign-in Code" | "Doğrulama kodun:" | code-block | "Paneli aç →" |
| Security alert | Security label: "Security · Account Alert" | "Hesabında olağan dışı aktivite." | lede + activity meta-list | "Hesabımı incele →" |

## 7. Email client compatibility

Constraint: production templates must use `<table>` layouts and inline styles. The mockup in `.superpowers/brainstorm/.../direction-B-v2.html` uses CSS flex for browser preview only; that does **not** translate to email production.

### Layout rules

- Body wrapper: `<table role="presentation" width="100%">` (Outlook fix: `mso-table-lspace:0pt;mso-table-rspace:0pt`)
- Multi-column blocks (meta-list, footer, hero row): use two-cell `<table>` rows, not flex
- All visual styling: inline `style="..."` attributes; class-based fallbacks only inside `<style>` for mobile media queries
- No `position`, no `float`, no CSS grid, no `flex`

### Inherited from current `base.blade.php` (keep)

- VML conditional comments for Outlook (none currently needed for this design)
- `mso-` table fixes
- `-webkit-text-size-adjust:100%` resets
- `@media (max-width:640px)` mobile breakpoint
- `@media (prefers-color-scheme:dark)` dark overrides

### Add to base.blade.php for this design

- Dot-grid texture on hero via inline `background-image: radial-gradient(rgba(255,255,255,0.07) 1px, transparent 1px); background-size: 22px 22px;` — supported by all modern clients; Outlook degrades to solid navy gracefully
- Light footer dark-mode rules (footer bg `#0f1035`, text `#94a3b8`, link `#60a5fa`) — already in base.blade.php's `em-footer-bg`/`em-footer-text`/`em-footer-link` classes; verify they fire correctly

## 8. RTL handling (Arabic + Kurdish)

The current `base.blade.php` sets `dir="rtl"` on `<html>` when locale is `ar` or `ku`. Browser/email-client behavior:

- Text alignment flips automatically when `dir="rtl"` is set on parent — **no extra work needed for plain text**
- Two-column tables (hero row, meta-list, footer): when wrapped in `dir="rtl"` parent, cell order flips automatically — verify in QA
- Brand mark text (`YALLASPARE`) stays LTR even in RTL — use `dir="ltr"` on that `<span>` to lock it
- Monospace spec tags (`SYS / WELCOME`, `SEC / 2FA`): also force `dir="ltr"`
- Order numbers, codes, SKUs, prices: all numeric content — wrap in `dir="ltr"` spans to prevent digit re-ordering

### Pattern for safe LTR text inside RTL document

```html
<span dir="ltr" style="unicode-bidi: isolate;">SYS / WELCOME</span>
```

## 9. Mobile breakpoint behavior (≤640px)

Apply via `<style>` in `<head>` (current base.blade.php already does this):

- Body container padding: `32px 22px 28px` (down from `44px 40px 36px`)
- Hero padding: `24px 22px`
- Headline: 24px (down from 30px)
- Meta-list: stack to 1 column (`display: block` on `<td>` cells via class)
- Order rows: keep horizontal row but reduce thumb to 36×36
- Code block letter-spacing: 8px (down from 12px to fit narrow viewports)
- Footer: stack copyright + email vertically with center alignment

## 10. Files to change

### Modify (restyle existing components — keep their public API)

| File | Change |
|---|---|
| `resources/views/emails/layouts/base.blade.php` | New hero structure (dot grid + 2-cell mark/spec row); 2px accent stripe (kept from D-2); light footer with dark-mode override; mobile media query updates for new dimensions. |
| `resources/views/emails/components/meta-grid.blade.php` | Restyle to v2 system: monospace caps key (9.5px `#9aa0b5`), Space Grotesk 700 navy value (14px), hairline `#ebedf0` borders, 2-column → mobile stack. Keep existing `$items` prop API. |
| `resources/views/emails/components/verification-code.blade.php` | Restyle to v2 code-block: centered, `#fafbfc` bg, 1px border, SF Mono 36px 700 navy with 12px tracking, optional help caption. Keep `$code` prop API. |
| `resources/views/emails/components/order-summary.blade.php` | Restyle to v2 rows + totals: thumb placeholder + product name + SKU + price; subtotal/shipping/discount/total table; bold last-row total. Keep existing prop API. |
| `resources/views/emails/components/footer.blade.php` | Restyle to light footer (`#fafbfc` bg, monospace 10.5px `#8a8ea3`, two-column copyright + email link). Add dark-mode `@media` overrides to base.blade.php style block. |
| `resources/views/emails/components/alert.blade.php` | Minor visual tweaks only — softer corners, monospace caps label, ensure colors align with v2 palette (info / warn / danger / success tones). Keep `$tone` + `$message` API. |
| `resources/views/emails/components/status-badge.blade.php` | Minor visual tweak — make it monospace caps with 1px border to match v2 mechanical feel. Keep `$status` API. |
| `resources/views/emails/components/divider.blade.php` | Change to `1px #ebedf0` hairline (if different today). |
| `resources/views/emails/components/security-notice.blade.php` | This is the long-form notice block. Restyle to v2 muted card. **Distinct from new `security-label` below.** |

### Modify per-email blade files (add kicker/label, otherwise minimal)

| File | Change |
|---|---|
| `resources/views/emails/auth/welcome.blade.php` | Replace hard-coded eyebrow `<p>` with `<x-email-kicker text="Account · Activated">`. Keep existing meta-grid, button. |
| `resources/views/emails/auth/verify-email.blade.php` | Replace existing `color:#4f46e5` eyebrow `<p>` with `<x-email-kicker text="Account · Verify">`. Keep meta-grid, verification-code, alert, security-notice. |
| `resources/views/emails/auth/reset-password.blade.php` | Replace existing `color:#dc2626` eyebrow `<p>` with `<x-email-security-label text="Security · Password Reset">`. Otherwise unchanged. |
| `resources/views/emails/admin/two-factor-code.blade.php` | Replace existing `color:#dc2626` eyebrow `<p>` with `<x-email-security-label text="Security · Sign-in Code">`. Keep verification-code. |
| `resources/views/emails/admin/security-alert.blade.php` | Replace existing `color:#dc2626` eyebrow `<p>` with `<x-email-security-label text="Security · Account Alert">`. Keep meta-grid for activity context. |
| `resources/views/emails/orders/status.blade.php` | Replace existing eyebrow with `<x-email-kicker text="Order · {Status}">`. Keep status-badge, order-summary, button. |

### Add (only truly new primitives)

| File | Purpose |
|---|---|
| `resources/views/components/email-kicker.blade.php` | Pre-headline label, monospace caps, color `#8a8ea3`. Replaces ad-hoc `<p style="color:#4f46e5">` patterns. Props: `text`. |
| `resources/views/components/email-security-label.blade.php` | Pre-headline security label: red dot + caps text. Replaces ad-hoc `<p style="color:#dc2626">` patterns in 3 security emails. Props: `text`. |

(Using `<x-...>` Blade components at top-level `resources/views/components/` namespace because email partials currently use `@include`-style; the two new ones become cleaner first-class components. Existing `@include('emails.components.xxx')` patterns stay as-is — no migration needed.)

### Do NOT touch

- `resources/views/emails/text/*` — plain-text variants stay as-is
- `resources/views/emails/components/button.blade.php` — already navy from D-2 commit `868595b`, primary variant is correct
- `resources/views/emails/dealer/notification.blade.php` — picks up base + footer changes automatically
- `resources/views/emails/operational/notification.blade.php` — same
- `resources/views/emails/inventory/low-stock-alert.blade.php` — same
- Any non-email blade file
- Localization JSON files (no copy changes)

## 11. Verification plan

After implementation:

1. **Compile:** `php artisan view:clear`, render each of the 6 emails using `Mail::fake()` test or dedicated preview route to a static HTML file
2. **Email client matrix:** drop the rendered HTML into [Litmus](https://litmus.com) / [Email on Acid](https://www.emailonacid.com) (or screenshot manually in Gmail web + Apple Mail + Outlook web)
3. **Mobile:** Chrome devtools 320px / 375px / 414px
4. **RTL:** switch app locale to `ar`, render each email, verify mirroring; same for `ku`
5. **Dark mode:** macOS Mail with dark scheme, Gmail dark theme, Outlook dark
6. **Test suite:** `php artisan test` — existing email tests must continue passing; add Snapshot tests if not present
7. **Build:** `npm run build` (this is a Tailwind-aware project; verify no token regressions)

## 12. Risks & open questions

- **Outlook desktop gradients:** dot-grid via `radial-gradient` may render as solid in Outlook 2007–2016. Acceptable: graceful degrade to solid navy hero.
- **`unicode-bidi: isolate` in older Outlook:** may not be supported. Mitigation: use `<bdo dir="ltr">` wrapper as fallback if QA shows digit reordering.
- **Dark mode footer link contrast:** `#60a5fa` on `#0f1035` passes WCAG AA at 14px+; smaller text may fail. Footer text stays at 10.5px minimum.
- **Per-email body content** is otherwise unchanged in this spec — only the chrome (hero/accent/footer) and a few primitives (kicker/meta-list/code-block) reorganize how content is structured. Copy stays the same.
- **`emails/support/*` templates** were not enumerated; they will inherit base changes but per-template body refactoring is out of scope for this PR. Flag for a follow-up if visual drift becomes noticeable.

## 13. Out of scope (re-emphasized)

- Website page redesign of any kind
- Admin email center UI (`resources/views/admin/email/index.blade.php`, `outbox.blade.php`)
- Auth pages, welcome hero CTA, layouts/auth-split, layouts/app — these remain on their existing red CTA theme and will be handled by separate D-task work
- New brand tokens (D-1 and D-2 are the source of truth)
- Localization copy changes (only structural blade refactor)

## 14. Approval

User selected B (Industrial) at v2 with the refinements in §3 on 2026-06-01. Design is approved. Spec self-review complete (no placeholders, no contradictions, scope bounded). Ready for implementation plan.
