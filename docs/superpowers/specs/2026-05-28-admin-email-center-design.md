# Admin Email Center — Design Spec

**Status:** Approved for implementation
**Author:** Claude (Opus 4.7) + Alidlshadd
**Date:** 2026-05-28

## Context

`/admin/email` is currently a single-page settings + test-email UI. Goal: turn it into a 4-tab email operations center on the same URL.

- **Settings** — existing SMTP/readiness/test mail, kept; visual polish via Preline.
- **Broadcast** — admin filters users → composes a rich-text email with attachments → previews → sends as a queued batch.
- **History** — list of past broadcasts with per-recipient outcomes.
- **Template Preview** — render any of the 9 transactional templates in EN/AR/KU/RTL using the existing `/admin/email/preview/{template}` route.

Visual decisions locked: Preline pill tabs (red filled active) · Broadcast = 3 balanced columns (filters / editor / preview-send) · History = card list.

Technical decisions locked: **Preline UI** for components (Tailwind plugin), **TipTap** for the rich editor, **HTMLPurifier** (`ezyang/htmlpurifier`) for server-side sanitization, **Bus::batch** for queued send, **SecureImageStorage** pattern for attachments (10MB per-file / 25MB total / jpg-png-webp-pdf / SVG rejected), **lang JSON** for canned templates, **single-language send** (dil filtresi alıcı kapsamını belirler — admin tek dilde yazar).

## Architecture overview

```
Routes (admin group, IsAdmin + 2FA + RBAC)
   /admin/email                      → EmailController@index (renders all 4 tabs)
   /admin/email/recipients-preview   → EmailController@previewRecipients (AJAX filter)
   /admin/email/broadcasts           → EmailBroadcastController@store
   /admin/email/broadcasts/{id}      → EmailBroadcastController@show
   /admin/email/broadcasts/{id}.json → EmailBroadcastController@showJson (drawer fetch)
   /admin/email/preview/{template}   → EmailController@preview (already exists)

Backend
   App\Models\EmailBroadcast            (header)
   App\Models\EmailBroadcastRecipient   (per-user row)
   App\Mail\BroadcastMail               (Mailable, ShouldQueue)
   App\Jobs\SendBroadcastEmailJob       (single recipient send, Bus::batch member)
   App\Support\HtmlSanitizer            (wraps HTMLPurifier with our allow-list)
   App\Support\RecipientFilter          (applies UI filters → User query)

UI (Blade + Preline + Alpine)
   resources/views/admin/email/index.blade.php           (shell + tab nav + hash sync)
   resources/views/admin/email/partials/_settings.blade.php
   resources/views/admin/email/partials/_broadcast.blade.php
   resources/views/admin/email/partials/_history.blade.php
   resources/views/admin/email/partials/_preview.blade.php
   resources/views/emails/broadcast.blade.php            (BroadcastMail's view — reuses shared header/footer)
```

## Schema (2 migrations)

**`email_broadcasts`**
- `id`, `admin_user_id` (fk users, nullOnDelete), `subject` (string 255), `body_html` (longText — purified), `attachments` (json — array of `{path, original_name, mime, size}`), `filters_snapshot` (json — exact filter payload at send time), `recipient_count` (uint), `sent_count` (uint default 0), `failed_count` (uint default 0), `status` (enum: queued/sending/completed/failed; default queued), `batch_id` (nullable string — Bus batch id for status polling), `sent_at` (timestamp nullable), `created_at`/`updated_at`.

**`email_broadcast_recipients`**
- `id`, `broadcast_id` (fk → broadcasts, cascade), `user_id` (fk users nullOnDelete), `email` (string 255 — denormalized for audit), `status` (enum: queued/sent/failed; default queued), `error_message` (text nullable), `sent_at` (timestamp nullable), `created_at`/`updated_at`. Index `(broadcast_id, status)` for the detail drawer count queries.

Both models use `$guarded` for `status`/`sent_count`/`failed_count`/`sent_at` (P1 mass-assignment pattern). `EmailBroadcast` has a `recipients()` hasMany; `recipient()` belongsTo on the row model.

## Filter pipeline (`App\Support\RecipientFilter`)

Single class, single responsibility: takes an array of filter params, returns a `User::query()` builder. Filters (all AND-combined):

- `roles[]` — `whereIn('role', $roles)`. Empty array = no filter.
- `dealer_statuses[]` — applies only when `roles[]` contains `dealer` (silently ignored otherwise).
- `order_state` — `none` / `active` (last 90 days) / `old` / `any` via `whereHas('orders', ...)`.
- `locales[]` — `whereIn('locale_preference', $locales)`. THIS is the broadcast scope filter (single-language strategy).
- `email_verified` — `verified` / `unverified` / `any`.
- `manual_include[]` and `manual_exclude[]` — user IDs explicitly added/removed in UI; final set = `(filtered ∪ manual_include) − manual_exclude`.

`previewRecipients` returns `{ count, first10: [...], filters_normalized: {...} }`. The actual `store` re-runs the query (no trusting client list) and serializes the normalized filter payload to `filters_snapshot`.

## Broadcast send flow

1. POST `/admin/email/broadcasts` with `subject`, `body_html` (TipTap output), `attachments[]` (uploaded files), `filters` (json).
2. Controller validates → sanitizes `body_html` via `HtmlSanitizer::clean()` → stores attachments via `SecureImageStorage` (PDF whitelist added) → runs `RecipientFilter` → creates `EmailBroadcast` + bulk-inserts `EmailBroadcastRecipient` rows (status=queued).
3. Dispatches `Bus::batch( recipients.map → SendBroadcastEmailJob ) ->onQueue('mail-broadcast') ->dispatch()`. Saves `batch_id` on the broadcast.
4. Job sends `BroadcastMail` per recipient with the user's locale via `Mail::to($user)` (HasLocalePreference auto-applies) → on success updates recipient row to sent + increments `sent_count`; on exception → status=failed + `error_message` + `failed_count`. Final batch completion callback marks broadcast `status=completed` (or `failed` if all jobs failed).
5. Activity log entry on broadcast creation (Spatie).

`HtmlSanitizer::clean()` allows: `p strong em u s a[href|title|target=_blank|rel=noopener] ul ol li h1 h2 h3 br img[src|alt] blockquote span[style*=color]`. Strips everything else. Forbids `script style iframe object embed form input on*` (HTMLPurifier default + explicit deny).

## UI tabs (resources/views/admin/email/index.blade.php)

Wrapper: `<div x-data="emailCenter()" x-init="init()">` with Alpine state `tab`. Each tab button calls `setTab('settings'|'broadcast'|'history'|'preview')` which writes `window.location.hash`. `init()` reads hash on load. Preline class patterns for the visual (pills, dark mode aware).

- **`_settings.blade.php`** — current mail-summary / send-test / readiness-checks reorganised into 3 Preline cards. No behavioural change.
- **`_broadcast.blade.php`** — 3 columns inside `grid lg:grid-cols-[280px_1fr_280px] gap-6`. Left: filter form (chip group multi-select). Center: subject input + TipTap mount point + attachment dropzone + canned-template dropdown. Right: live recipient counter (debounced fetch from `recipients-preview` endpoint), first-10 list, three buttons (Önizle modal · Bana test gönder · Gönder). Confirmation modal (Preline) opens when recipient_count > 100.
- **`_history.blade.php`** — card list per visual decision. Each card: subject, sender, filter summary chips, recipient/sent/failed counts, status badge, click → opens drawer with full recipient breakdown (paginated 50/page).
- **`_preview.blade.php`** — template select (9 entries) + locale select (en/ar/ku) + `<iframe>` pointing at `/admin/email/preview/{slug}?locale=X` (the route I built last session).

## Authorization & rate limiting

- New `User::PERMISSION_EMAIL_BROADCAST = 'email.broadcast'`. Defaults: super_admin and admin. Other roles cannot see the Broadcast or History tabs (Alpine reads a server-rendered flag) and route is `can:email.broadcast`.
- Settings tab keeps `can:settings.manage`.
- `RateLimiter::for('email-broadcast', fn (Request $r) => Limit::perMinutes(5, 3)->by($r->user()?->id))` — 3 broadcasts per 5 minutes per admin.
- Send throttle to queue worker: job uses `WithoutOverlapping` + worker concurrency limits the per-second send rate (no need for per-message rate limiter in app code; SMTP provider is the bottleneck).

## Security cross-checks

- Mass-assignment: new models put privilege/state fields in `$guarded` (P1 pattern).
- File upload: `SecureImageStorage::storeAttachment()` extension I'll add — rejects unknown MIME, rejects SVG explicitly, validates against allowed list (`image/jpeg`, `image/png`, `image/webp`, `application/pdf`), file size enforced at validator + storage layer.
- HTML stored XSS: HTMLPurifier on write, never `{!! !!}` on broadcast detail (we render via the broadcast template which is escaped at the right boundary).
- CSRF: default Laravel.
- Recipient list comes from a server-side re-run of the filter query, never from the client-submitted user IDs.

## JS / build deps

- `npm install preline` → import in `app.js` after Alpine.
- `npm install @tiptap/core @tiptap/starter-kit @tiptap/extension-link @tiptap/extension-image`.
- `composer require ezyang/htmlpurifier`.
- `tailwind.config.js` → add `'./node_modules/preline/preline.js'` to content + `require('preline/plugin')` to plugins.

## Tests (TDD scope)

Feature tests under `tests/Feature/Admin/EmailCenter/`:

1. `RecipientFilterTest` — each filter axis returns the correct user set (factory-built fixtures: dealers active/inactive, customers with/without orders, locale variations).
2. `BroadcastAuthorizationTest` — non-permission roles get 403 on store + history + recipients-preview routes.
3. `BroadcastRateLimitTest` — 4th broadcast within 5 min returns 429.
4. `BroadcastSanitizationTest` — `<script>`/`<iframe>`/`onerror=` stripped from stored `body_html`.
5. `BroadcastMailRenderTest` — mailable renders with subject + body + attachments, respects recipient locale.
6. `AttachmentValidationTest` — SVG rejected (422), oversized rejected, valid image/pdf accepted.

Unit-level tests for `HtmlSanitizer` (purifier wrapper) and `RecipientFilter` (one query case per filter axis).

## Verification

1. `composer install && npm install && npm run build`
2. `php artisan migrate`
3. `php artisan test --filter EmailCenter`
4. Manual: admin sign-in → `/admin/email` → walk through Settings · Broadcast (filter → write → preview → send to admin's own email) · History (drawer opens) · Preview (load each template + locale).
5. Browser console: no CSP errors (the `'unsafe-eval'` we added earlier covers TipTap; Preline is a CSP-friendly plugin).

## Out of scope (YAGNI — explicit)

Open/click tracking · A/B test · Scheduled send · Segment save · Template save · Unsubscribe management · Mobile API broadcast endpoint. The history table can support these later without schema change.

## Implementation phasing (for the writing-plans skill)

- **Phase 1** (foundation, this commit cycle): Preline install + tab shell + Settings polish + Preview tab wired to existing route + `PERMISSION_EMAIL_BROADCAST` registered. No broadcast code yet. Verifiable: 4 tabs render, Settings & Preview fully functional.
- **Phase 2** (the heavy lift): Schema + models + Mailable + Job + HtmlSanitizer + RecipientFilter + TipTap + attachments + filter UI + send flow + tests. Verifiable: end-to-end broadcast to admin's own email succeeds.
- **Phase 3** (close the loop): History tab card list + drawer + activity log + final polish.

Each phase commits separately, each deployable independently.
