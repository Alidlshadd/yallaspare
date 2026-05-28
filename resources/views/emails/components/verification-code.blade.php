<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:26px 0;">
<tr>
    <td align="center" class="em-code-bg"
        style="background:#f0f4ff;border:1.5px solid #c7d2fe;border-radius:20px;padding:28px 24px;">
        <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto;">
        <tr>
            <td align="center">
                <p style="margin:0 0 12px;color:#4f46e5;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:2.5px;">
                    {{ __('Verification code') }}
                </p>
                <div class="em-code em-code-text"
                     style="font-family:'SFMono-Regular',Consolas,'Liberation Mono',Menlo,monospace;font-size:38px;line-height:1.1;letter-spacing:10px;color:#070740;font-weight:900;text-align:center;">
                    {{ $code }}
                </div>
                <p style="margin:14px 0 0;color:#6366f1;font-size:11px;font-weight:600;letter-spacing:0.3px;">
                    {{ __('Single-use &mdash; do not share') }}
                </p>
            </td>
        </tr>
        </table>
    </td>
</tr>
</table>
