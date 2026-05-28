@php
    $brandName     = (string) ($brandName ?? ($systemSettings['site_name'] ?? 'YallaSpare'));
    $logoUrl       = $logoUrl ?? ($systemSettings['site_logo_url'] ?? null);
    $absoluteLogoUrl = is_string($logoUrl) && $logoUrl !== ''
        ? (str_starts_with($logoUrl, 'http://') || str_starts_with($logoUrl, 'https://') ? $logoUrl : url($logoUrl))
        : null;
    $locale        = (string) ($locale ?? app()->getLocale());
    $isRtl         = in_array($locale, ['ar', 'ku'], true);
    $preheaderText = trim((string) ($preheader ?? ''));
    $recipientEmail = $recipientEmail ?? null;
    $recipientName  = $recipientName ?? null;
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
        a                     { color:#3b82f6; text-decoration:none; }
        a:hover               { text-decoration:underline; }

        /* ─── MOBILE ─────────────────────────────────────────────── */
        @media screen and (max-width:640px) {
            .em-shell         { padding:0!important; }
            .em-outer         { padding:16px 12px 24px!important; }
            .em-card          { border-radius:16px!important; }
            .em-hero          { padding:30px 22px 26px!important; }
            .em-body          { padding:28px 22px!important; }
            .em-footer        { padding:24px 22px!important; }
            .em-title         { font-size:24px!important; line-height:32px!important; }
            .em-copy          { font-size:15px!important; line-height:26px!important; }
            .em-btn           { display:block!important; width:100%!important; box-sizing:border-box!important; text-align:center!important; padding:15px 20px!important; }
            .em-btn-wrap      { width:100%!important; }
            .em-code          { font-size:30px!important; letter-spacing:8px!important; }
            .em-rbar          { padding:11px 22px!important; }
            .em-meta-label    { display:block!important; width:100%!important; border-right:0!important; border-bottom:1px solid #e2e8f0; }
            .em-meta-value    { display:block!important; width:100%!important; }
            .em-hide-sm       { display:none!important; }
            .em-stack         { display:block!important; width:100%!important; }
        }

        /* ─── DARK MODE ──────────────────────────────────────────── */
        @media (prefers-color-scheme:dark) {
            body,
            .em-page-bg       { background-color:#02021a!important; }
            .em-card          { background-color:#0f1035!important; border-color:rgba(255,255,255,0.06)!important; }
            .em-body-bg       { background-color:#0f1035!important; }
            .em-rbar          { background-color:#111246!important; border-bottom-color:#1e2060!important; }
            .em-rbar-text     { color:#94a3b8!important; }
            .em-rbar-email    { color:#60a5fa!important; }
            .em-title         { color:#f1f5f9!important; }
            .em-copy          { color:#94a3b8!important; }
            .em-strong        { color:#cbd5e1!important; }
            .em-muted         { color:#64748b!important; }
            .em-meta-row      { border-color:#1e2462!important; }
            .em-meta-bg       { background-color:#141550!important; }
            .em-meta-val      { color:#cbd5e1!important; }
            .em-alert-info    { background-color:#0d1b3e!important; border-color:#1e3a8a!important; color:#93c5fd!important; }
            .em-alert-warn    { background-color:#1c1400!important; border-color:#92400e!important; color:#fcd34d!important; }
            .em-alert-danger  { background-color:#1a0808!important; border-color:#9f1239!important; color:#fca5a5!important; }
            .em-alert-success { background-color:#041c10!important; border-color:#065f46!important; color:#6ee7b7!important; }
            .em-code-bg       { background-color:#0a0b38!important; border-color:#1e2462!important; }
            .em-code-text     { color:#a5b4fc!important; }
            .em-order-hdr     { background-color:#141550!important; color:#e2e8f0!important; }
            .em-order-row     { border-color:#1e2462!important; color:#cbd5e1!important; }
            .em-order-sku     { color:#64748b!important; }
            .em-totals-row    { border-color:#1e2462!important; }
            .em-totals-label  { color:#94a3b8!important; }
            .em-totals-val    { color:#e2e8f0!important; }
            .em-sec-notice    { background-color:#0a0b38!important; border-color:#1e2462!important; }
            .em-sec-title     { color:#cbd5e1!important; }
            .em-sec-text      { color:#64748b!important; }
            .em-footer-bg     { background-color:#070720!important; border-top-color:#1e2060!important; }
            .em-footer-text   { color:#475569!important; }
            .em-footer-link   { color:#3b82f6!important; }
            .em-footer-dim    { color:#334155!important; }
        }
    </style>
</head>
<body class="em-page-bg" style="margin:0;padding:0;background:#040325;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;color:#0f172a;">

    {{-- PREHEADER (invisible preview text) --}}
    @if ($preheaderText !== '')
        <div aria-hidden="true" style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;font-size:1px;line-height:1px;">{{ $preheaderText }}&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;</div>
    @endif

    {{-- OUTER WRAPPER --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;background:#040325;">
    <tr><td class="em-outer" align="center" style="padding:32px 16px 40px;">

        {{-- EMAIL CARD --}}
        <table role="presentation" class="em-card" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;width:100%;background:#ffffff;border-radius:24px;overflow:hidden;box-shadow:0 40px 100px rgba(0,0,0,0.65),0 12px 32px rgba(0,0,0,0.35);border:1px solid rgba(255,255,255,0.07);">

            {{-- ░░ HERO HEADER ░░ --}}
            <tr>
                <td class="em-hero" align="center" style="padding:38px 40px 32px;background:#070740;background-image:linear-gradient(148deg,#040430 0%,#070740 45%,#101580 80%,#1a2090 100%);">
                    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto;">
                    <tr>
                        <td align="center">
                            @if ($absoluteLogoUrl)
                                <img src="{{ $absoluteLogoUrl }}" width="58" height="58" alt="{{ $brandName }}"
                                     style="display:block;width:58px;height:58px;object-fit:contain;border-radius:16px;margin:0 auto;box-shadow:0 8px 28px rgba(0,0,0,0.4);">
                            @else
                                <table role="presentation" cellpadding="0" cellspacing="0"><tr>
                                <td align="center" style="width:60px;height:60px;border-radius:16px;background:#ffffff;font-size:22px;font-weight:900;color:#070740;line-height:60px;text-align:center;box-shadow:0 8px 28px rgba(0,0,0,0.4);letter-spacing:-1px;">YS</td>
                                </tr></table>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding-top:16px;color:#ffffff;font-size:22px;font-weight:800;letter-spacing:-0.4px;">
                            {{ $brandName }}
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding-top:6px;color:#7c8db8;font-size:10.5px;font-weight:600;letter-spacing:2.8px;text-transform:uppercase;">
                            {{ __('Auto Parts & Commerce') }}
                        </td>
                    </tr>
                    </table>
                </td>
            </tr>

            {{-- ░░ BRAND ACCENT LINE ░░ — thin red stripe under hero, signature brand cue --}}
            <tr>
                <td style="height:3px;background:#dc2626;font-size:0;line-height:0;">&nbsp;</td>
            </tr>

            {{-- ░░ RECIPIENT BAR (optional) ░░ --}}
            @if ($recipientEmail || $recipientName)
            <tr>
                <td class="em-rbar" style="padding:11px 40px;background:#f0f4ff;border-bottom:1px solid #dde5f5;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
                    <td class="em-rbar-text" style="font-size:12px;color:#64748b;font-weight:500;">
                        @if ($recipientName)
                            <strong style="color:#374151;font-weight:700;">{{ $recipientName }}</strong>
                            &nbsp;&middot;&nbsp;
                        @endif
                        {{ __('Sent to') }}:&nbsp;<span class="em-rbar-email" style="color:#3b82f6;font-weight:600;">{{ $recipientEmail }}</span>
                    </td>
                    </tr></table>
                </td>
            </tr>
            @endif

            {{-- ░░ CONTENT ░░ --}}
            <tr>
                <td class="em-body em-body-bg" style="padding:40px 40px 36px;background:#ffffff;">
                    @yield('content')
                </td>
            </tr>

            {{-- ░░ FOOTER ░░ --}}
            <tr>
                <td class="em-footer-bg" style="background:#f8fafc;border-top:1px solid #e2e8f0;">
                    @include('emails.components.footer', ['brandName' => $brandName])
                </td>
            </tr>

        </table>
        {{-- END EMAIL CARD --}}

        {{-- BELOW-CARD LEGAL --}}
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;margin:0 auto;"><tr>
        <td align="center" style="padding:18px 20px 4px;">
            <p style="margin:0;font-size:11px;line-height:18px;color:#3a4060;">
                &copy; {{ date('Y') }} {{ $brandName }}. {{ __('All rights reserved.') }}&nbsp;&nbsp;
                {{ __('This is an automated message — please do not reply.') }}
            </p>
        </td>
        </tr></table>

    </td></tr>
    </table>

</body>
</html>
