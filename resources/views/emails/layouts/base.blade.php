@php
    $brandName = (string) ($brandName ?? ($systemSettings['site_name'] ?? 'YallaSpare'));
    $logoUrl = $logoUrl ?? ($systemSettings['site_logo_url'] ?? null);
    $absoluteLogoUrl = is_string($logoUrl) && $logoUrl !== ''
        ? (str_starts_with($logoUrl, 'http://') || str_starts_with($logoUrl, 'https://') ? $logoUrl : url($logoUrl))
        : null;
    $locale = (string) ($locale ?? app()->getLocale());
    $isRtl = in_array($locale, ['ar', 'ku'], true);
    $preheaderText = trim((string) ($preheader ?? ''));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>{{ $title ?? $brandName }}</title>
    <style>
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
        body { margin: 0; padding: 0; width: 100% !important; background: #eef2f7; }
        a { color: #070740; }
        .email-card { width: 100%; max-width: 640px; }
        .mobile-fluid { width: auto; }
        @media screen and (max-width: 680px) {
            .email-shell { padding: 18px 10px !important; }
            .email-card { width: 100% !important; }
            .email-header { padding: 26px 20px !important; }
            .email-body { padding: 26px 20px !important; }
            .email-footer { padding: 20px !important; }
            .email-title { font-size: 24px !important; line-height: 31px !important; }
            .email-copy { font-size: 15px !important; line-height: 24px !important; }
            .mobile-stack { display: block !important; width: 100% !important; }
            .mobile-full { width: 100% !important; }
            .mobile-fluid { width: 100% !important; }
            .email-button { display: block !important; width: 100% !important; box-sizing: border-box !important; }
            .email-code { font-size: 28px !important; letter-spacing: 7px !important; }
        }
        @media (prefers-color-scheme: dark) {
            .email-page { background: #0f172a !important; }
            .email-card { background: #ffffff !important; }
            .email-muted-card { background: #f8fafc !important; }
        }
    </style>
</head>
<body class="email-page" style="margin:0;padding:0;background:#eef2f7;color:#0f172a;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;">
    @if ($preheaderText !== '')
        <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;line-height:1px;font-size:1px;">
            {{ $preheaderText }}
        </div>
    @endif

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;background:#eef2f7;">
        <tr>
            <td class="email-shell" align="center" style="padding:32px 16px;">
                <table role="presentation" class="email-card" cellpadding="0" cellspacing="0" style="max-width:640px;width:100%;background:#ffffff;border-radius:24px;overflow:hidden;box-shadow:0 24px 70px rgba(15,23,42,0.14);border:1px solid #dbe3ef;">
                    <tr>
                        <td class="email-header" align="center" style="padding:34px 34px 30px;background:#070740;background-image:linear-gradient(135deg,#070740 0%,#11145f 56%,#1e293b 100%);">
                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto;">
                                <tr>
                                    <td align="center" style="padding:0;">
                                        @if ($absoluteLogoUrl)
                                            <img src="{{ $absoluteLogoUrl }}" width="52" height="52" alt="{{ $brandName }}" style="display:block;width:52px;height:52px;object-fit:contain;border-radius:14px;">
                                        @else
                                            <div style="width:52px;height:52px;border-radius:14px;background:#ffffff;color:#070740;font-size:18px;font-weight:800;line-height:52px;text-align:center;box-shadow:0 10px 30px rgba(0,0,0,0.18);">YS</div>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding-top:14px;color:#ffffff;font-size:20px;font-weight:800;letter-spacing:0.2px;">
                                        {{ $brandName }}
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding-top:6px;color:#cbd5e1;font-size:12px;font-weight:700;letter-spacing:1.6px;text-transform:uppercase;">
                                        {{ __('Auto parts operations') }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="email-body" style="padding:34px;">
                            @yield('content')
                        </td>
                    </tr>
                    <tr>
                        <td class="email-footer" style="padding:24px 34px 30px;background:#f8fafc;border-top:1px solid #e2e8f0;">
                            @include('emails.components.footer', ['brandName' => $brandName])
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
