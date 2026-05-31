{{-- Long-form anti-phishing / "if this wasn't you" notice. Used at the end of
     security-sensitive emails (reset-password, 2FA, security-alert). Distinct from
     <x-email-security-label> which is the short inline kicker. --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:24px;">
<tr>
    <td style="border-top:1px solid #ebedf0;padding-top:20px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td class="em-sec-notice" style="padding:16px 18px;background:#fafbfc;border:1px solid #ebedf0;border-radius:4px;">
                <p class="em-sec-title" style="margin:0 0 6px;font-family:'SFMono-Regular',Consolas,'Liberation Mono',Menlo,monospace;color:#9aa0b5;font-size:10px;font-weight:700;letter-spacing:1.8px;text-transform:uppercase;">
                    {{ __('Security notice') }}
                </p>
                <p class="em-sec-text" style="margin:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;color:#64748b;font-size:12.5px;line-height:20px;">
                    {{ $message ?? __('If this was not you, secure your account immediately and contact YallaSpare support.') }}
                </p>
            </td>
        </tr>
        </table>
    </td>
</tr>
</table>
