{{-- Minimal v2 footer: brand + support contact, tiny legal line below.
     Long-form security notice is now per-email via <security-notice> when needed.
     Dark-mode overrides live on .em-footer-bg / .em-footer-text / .em-footer-link
     in resources/views/emails/layouts/base.blade.php. --}}

<table role="presentation" width="100%" cellpadding="0" cellspacing="0">

    {{-- Primary line: brand · support contact (stacks centered on mobile) --}}
    <tr>
        <td class="em-footer" style="padding:22px 36px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
                <td class="em-footer-cell em-footer-text" valign="middle" align="left"
                    style="font-family:'SFMono-Regular',Consolas,'Liberation Mono',Menlo,monospace;font-size:10.5px;color:#8a8ea3;letter-spacing:1px;text-transform:uppercase;">
                    &copy; {{ date('Y') }} {{ strtoupper($brandName ?? 'YallaSpare') }}
                </td>
                <td class="em-footer-cell" valign="middle" align="right"
                    style="font-family:'SFMono-Regular',Consolas,'Liberation Mono',Menlo,monospace;font-size:10.5px;letter-spacing:0.4px;">
                    <a href="mailto:support@yallaspare.com" class="em-footer-link" dir="ltr"
                       style="color:#4a4e63;text-decoration:none;border-bottom:1px solid #d8dae0;padding-bottom:1px;unicode-bidi:isolate;">support@yallaspare.com</a>
                </td>
            </tr></table>
        </td>
    </tr>

    {{-- Secondary line: legal links, very muted --}}
    <tr>
        <td style="padding:0 36px 20px;">
            <p class="em-footer-dim" style="margin:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;font-size:11px;color:#9aa0b5;line-height:18px;text-align:center;">
                <a href="{{ url('/privacy') }}" class="em-footer-link" style="color:#9aa0b5;text-decoration:none;">{{ __('Privacy Policy') }}</a>
                &nbsp;&middot;&nbsp;
                <a href="{{ url('/terms') }}" class="em-footer-link" style="color:#9aa0b5;text-decoration:none;">{{ __('Terms of Service') }}</a>
                &nbsp;&middot;&nbsp;
                <a href="{{ url('/contact') }}" class="em-footer-link" style="color:#9aa0b5;text-decoration:none;">{{ __('Contact Us') }}</a>
            </p>
        </td>
    </tr>

</table>
