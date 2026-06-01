@php
    $brandName     = (string) ($brandName ?? ($systemSettings['site_name'] ?? 'YallaSpare'));
    $locale        = (string) ($locale ?? app()->getLocale());
    $isRtl         = in_array($locale, ['ar', 'ku'], true);
    $preheaderText = trim((string) ($preheader ?? ''));
    $recipientEmail = $recipientEmail ?? null;
    $recipientName  = $recipientName  ?? null;
    $specTag        = trim((string) ($specTag ?? 'YALLASPARE / SYS'));
    $logoUrl       = $logoUrl ?? ($systemSettings['site_logo_url'] ?? null);
    $absoluteLogoUrl = is_string($logoUrl) && $logoUrl !== ''
        ? (str_starts_with($logoUrl, 'http://') || str_starts_with($logoUrl, 'https://') ? $logoUrl : url($logoUrl))
        : null;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>{{ $title ?? $brandName }}</title>
    <style type="text/css">
        /* ─── CLIENT RESETS ──────────────────────────────────────── */
        body, table, td, a    { -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%; }
        table, td             { mso-table-lspace:0pt; mso-table-rspace:0pt; border-collapse:collapse; }
        img                   { -ms-interpolation-mode:bicubic; border:0; outline:none; text-decoration:none; display:block; }
        body                  { margin:0!important; padding:0!important; width:100%!important; min-width:100%; }

        /* ─── LINKS ──────────────────────────────────────────────── */
        a                     { color:#070740; text-decoration:none; }
        a:hover               { text-decoration:underline; }

        /* ─── MOBILE (down to 320px) ─────────────────────────────── */
        @media screen and (max-width:640px) {
            .em-shell         { padding:0!important; }
            .em-outer         { padding:14px 10px 22px!important; }
            .em-card          { border-radius:4px!important; }
            .em-hero          { padding:24px 22px!important; }
            .em-hero-mark     { font-size:15px!important; }
            .em-hero-spec     { font-size:9.5px!important; letter-spacing:1.2px!important; }
            .em-hero-logo     { width:26px!important; height:26px!important; }
            .em-rbar          { padding:10px 22px!important; }
            .em-body          { padding:32px 22px 28px!important; }
            .em-footer        { padding:18px 22px!important; }
            .em-footer-cell   { display:block!important; width:100%!important; text-align:center!important; padding:4px 0!important; }
            .em-title         { font-size:24px!important; line-height:30px!important; }
            .em-copy          { font-size:15px!important; line-height:25px!important; }
            .em-btn           { display:block!important; width:100%!important; box-sizing:border-box!important; text-align:center!important; padding:14px 18px!important; }
            .em-btn-wrap      { width:100%!important; }
            .em-code          { font-size:30px!important; letter-spacing:8px!important; padding-left:8px!important; }
            .em-meta-label    { display:block!important; width:100%!important; border-right:0!important; border-bottom:1px solid #ebedf0!important; }
            .em-meta-value    { display:block!important; width:100%!important; padding-top:0!important; }
            .em-hide-sm       { display:none!important; }
            .em-stack         { display:block!important; width:100%!important; }
            .em-card-legal    { padding:14px 16px!important; font-size:10.5px!important; }
        }

        /* ─── DARK MODE ──────────────────────────────────────────── */
        @media (prefers-color-scheme:dark) {
            body,
            .em-page-bg       { background-color:#070b1f!important; }
            .em-card          { background-color:#10112e!important; border-color:rgba(255,255,255,0.06)!important; }
            .em-body-bg       { background-color:#10112e!important; }
            .em-rbar          { background-color:#0f1135!important; border-bottom-color:#1e2060!important; }
            .em-rbar-text     { color:#94a3b8!important; }
            .em-rbar-email    { color:#60a5fa!important; }
            .em-kicker        { color:#94a3b8!important; }
            .em-sec-label     { color:#fca5a5!important; }
            .em-sec-dot       { background-color:#fca5a5!important; }
            .em-title         { color:#f1f5f9!important; }
            .em-copy          { color:#cbd5e1!important; }
            .em-strong        { color:#e2e8f0!important; }
            .em-muted         { color:#94a3b8!important; }
            .em-meta-row      { border-color:#1e2462!important; }
            .em-meta-bg       { background-color:#141550!important; }
            .em-meta-label    { color:#8a8ea3!important; }
            .em-meta-val      { color:#e2e8f0!important; }
            .em-alert-info    { background-color:#0d1b3e!important; border-color:#1e3a8a!important; color:#93c5fd!important; }
            .em-alert-warn    { background-color:#1c1400!important; border-color:#92400e!important; color:#fcd34d!important; }
            .em-alert-danger  { background-color:#1a0808!important; border-color:#9f1239!important; color:#fca5a5!important; }
            .em-alert-success { background-color:#041c10!important; border-color:#065f46!important; color:#6ee7b7!important; }
            .em-code-bg       { background-color:#0a0b38!important; border-color:#1e2462!important; }
            .em-code-text     { color:#a5b4fc!important; }
            .em-order-hdr     { background-color:#141550!important; color:#e2e8f0!important; }
            .em-order-row     { border-color:#1e2462!important; color:#cbd5e1!important; }
            .em-order-sku     { color:#94a3b8!important; }
            .em-totals-row    { border-color:#1e2462!important; }
            .em-totals-label  { color:#94a3b8!important; }
            .em-totals-val    { color:#e2e8f0!important; }
            .em-sec-notice    { background-color:#0a0b38!important; border-color:#1e2462!important; }
            .em-sec-title     { color:#e2e8f0!important; }
            .em-sec-text      { color:#94a3b8!important; }
            .em-footer-bg     { background-color:#0a0b2a!important; border-top-color:#1e2060!important; }
            .em-footer-text   { color:#94a3b8!important; }
            .em-footer-link   { color:#60a5fa!important; border-bottom-color:#1e2462!important; }
            .em-footer-dim    { color:#64748b!important; }
            .em-card-legal    { color:#475569!important; }
        }
    </style>
</head>
<body class="em-page-bg" style="margin:0;padding:0;background:#eef0f4;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;color:#0f172a;">

    {{-- PREHEADER (invisible preview text) --}}
    @if ($preheaderText !== '')
        <div aria-hidden="true" style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;font-size:1px;line-height:1px;">{{ $preheaderText }}&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;</div>
    @endif

    {{-- OUTER WRAPPER --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;background:#eef0f4;">
    <tr><td class="em-outer" align="center" style="padding:26px 16px 36px;">

        {{-- EMAIL CARD --}}
        <table role="presentation" class="em-card" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;width:100%;background:#ffffff;border-radius:4px;overflow:hidden;box-shadow:0 1px 0 rgba(0,0,0,0.04),0 24px 60px -24px rgba(7,7,64,0.18);border:1px solid #ececec;">

            {{-- ░░ HERO HEADER ░░ — navy with subtle dot-grid texture --}}
            <tr>
                <td class="em-hero" style="padding:28px 36px;background:#070740;background-image:radial-gradient(rgba(255,255,255,0.07) 1px, transparent 1px);background-size:22px 22px;background-position:0 0;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" dir="ltr" style="unicode-bidi:isolate;"><tr>
                        <td valign="middle" align="left">
                            <table role="presentation" cellpadding="0" cellspacing="0"><tr>
                                @if ($absoluteLogoUrl)
                                <td valign="middle" style="padding-right:10px;line-height:0;">
                                    <img src="{{ $absoluteLogoUrl }}" alt="{{ $brandName }}" width="32" height="32" class="em-hero-logo" style="display:block;width:32px;height:32px;object-fit:contain;border:0;outline:none;border-radius:4px;">
                                </td>
                                @endif
                                <td valign="middle">
                                    <span class="em-hero-mark" style="font-family:'Space Grotesk','Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;font-size:17px;font-weight:700;letter-spacing:-0.2px;color:#ffffff;">
                                        {{ strtoupper($brandName) }}
                                    </span>
                                </td>
                            </tr></table>
                        </td>
                        <td valign="middle" align="right">
                            <span class="em-hero-spec" style="font-family:'SFMono-Regular',Consolas,'Liberation Mono',Menlo,monospace;font-size:10px;color:#a4b3d4;letter-spacing:1.5px;text-transform:uppercase;">
                                {{ $specTag }}
                            </span>
                        </td>
                    </tr></table>
                </td>
            </tr>

            {{-- ░░ ACCENT STRIPE ░░ — 2px orange hairline, the only orange in the whole card --}}
            <tr>
                <td style="height:2px;background:#e85d2a;font-size:0;line-height:0;">&nbsp;</td>
            </tr>

            {{-- ░░ RECIPIENT BAR (optional) ░░ --}}
            @if ($recipientEmail || $recipientName)
            <tr>
                <td class="em-rbar" style="padding:11px 36px;background:#fafbfc;border-bottom:1px solid #ebedf0;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
                    <td class="em-rbar-text" style="font-family:'SFMono-Regular',Consolas,'Liberation Mono',Menlo,monospace;font-size:11px;color:#8a8ea3;font-weight:500;letter-spacing:0.4px;">
                        @if ($recipientName)
                            <strong style="color:#4a4e63;font-weight:700;">{{ $recipientName }}</strong>
                            &nbsp;&middot;&nbsp;
                        @endif
                        {{ __('Sent to') }}:&nbsp;<span class="em-rbar-email" dir="ltr" style="color:#070740;font-weight:600;unicode-bidi:isolate;">{{ $recipientEmail }}</span>
                    </td>
                    </tr></table>
                </td>
            </tr>
            @endif

            {{-- ░░ CONTENT ░░ --}}
            <tr>
                <td class="em-body em-body-bg" style="padding:44px 40px 36px;background:#ffffff;">
                    @yield('content')
                </td>
            </tr>

            {{-- ░░ FOOTER ░░ --}}
            <tr>
                <td class="em-footer-bg" style="background:#fafbfc;border-top:1px solid #ececec;">
                    @include('emails.components.footer', ['brandName' => $brandName])
                </td>
            </tr>

        </table>
        {{-- END EMAIL CARD --}}

        {{-- BELOW-CARD LEGAL --}}
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;margin:0 auto;"><tr>
        <td align="center" class="em-card-legal" style="padding:18px 20px 4px;font-size:11px;line-height:18px;color:#6b6f80;">
            &copy; {{ date('Y') }} {{ $brandName }}.
            {{ __('This is an automated message — please do not reply.') }}
        </td>
        </tr></table>

    </td></tr>
    </table>

</body>
</html>
