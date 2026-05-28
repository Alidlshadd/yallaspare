<table role="presentation" width="100%" cellpadding="0" cellspacing="0">

    {{-- Support line --}}
    <tr>
        <td class="em-footer" style="padding:26px 40px 0;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td align="center">
                    <p class="em-footer-text" style="margin:0 0 6px;font-size:13px;font-weight:700;color:#374151;">
                        {{ $brandName ?? 'YallaSpare' }} — {{ __('Auto Parts & Commerce') }}
                    </p>
                    <p class="em-footer-text" style="margin:0;font-size:12px;color:#64748b;line-height:20px;">
                        {{ __('Questions?') }}
                        <a href="mailto:support@yallaspare.com" class="em-footer-link" style="color:#3b82f6;font-weight:600;text-decoration:none;">support@yallaspare.com</a>
                    </p>
                </td>
            </tr>
            </table>
        </td>
    </tr>

    {{-- Divider --}}
    <tr>
        <td style="padding:20px 40px 0;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
            <tr><td style="height:1px;background:#e2e8f0;font-size:1px;line-height:1px;">&nbsp;</td></tr>
            </table>
        </td>
    </tr>

    {{-- Anti-phishing security notice --}}
    <tr>
        <td style="padding:16px 40px 0;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td class="em-sec-notice" align="center"
                    style="padding:14px 18px;background:#f0f4ff;border:1px solid #dde5f5;border-radius:12px;">
                    <p class="em-footer-dim" style="margin:0 0 4px;font-size:11.5px;font-weight:700;color:#374151;letter-spacing:0.3px;">
                        &#x1F512; {{ __('Security notice') }}
                    </p>
                    <p class="em-footer-dim" style="margin:0;font-size:11px;color:#64748b;line-height:18px;">
                        {{ __(':brand will never ask for your password, OTP, or card number by email.', ['brand' => $brandName ?? 'YallaSpare']) }}
                        {{ __('Always verify you are at') }} <strong>yallaspare.com</strong> {{ __('before entering any credentials.') }}
                    </p>
                </td>
            </tr>
            </table>
        </td>
    </tr>

    {{-- Legal & links --}}
    <tr>
        <td class="em-footer" style="padding:18px 40px 26px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td align="center">
                    <p class="em-footer-text" style="margin:0 0 8px;font-size:11px;color:#94a3b8;line-height:18px;">
                        &copy; {{ date('Y') }} {{ $brandName ?? 'YallaSpare' }}.
                        {{ __('All rights reserved.') }}
                    </p>
                    <p style="margin:0;font-size:11px;color:#94a3b8;line-height:18px;">
                        <a href="{{ url('/privacy') }}" class="em-footer-link" style="color:#64748b;text-decoration:none;">{{ __('Privacy Policy') }}</a>
                        &nbsp;&middot;&nbsp;
                        <a href="{{ url('/terms') }}" class="em-footer-link" style="color:#64748b;text-decoration:none;">{{ __('Terms of Service') }}</a>
                        &nbsp;&middot;&nbsp;
                        <a href="{{ url('/contact') }}" class="em-footer-link" style="color:#64748b;text-decoration:none;">{{ __('Contact Us') }}</a>
                    </p>
                </td>
            </tr>
            </table>
        </td>
    </tr>

</table>
