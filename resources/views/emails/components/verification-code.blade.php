{{-- v2 code block: clean inset card with monospace 36px navy code.
     Used by email verification + admin 2FA. --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0 32px;">
<tr>
    <td align="center" class="em-code-bg"
        style="background:#fafbfc;border:1px solid #ebedf0;border-radius:4px;padding:28px 22px;">
        <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto;">
        <tr>
            <td align="center">
                <p dir="ltr" style="margin:0 0 14px;font-family:'SFMono-Regular',Consolas,'Liberation Mono',Menlo,monospace;font-size:10px;color:#9aa0b5;letter-spacing:2.5px;text-transform:uppercase;font-weight:700;unicode-bidi:isolate;">
                    {{ __('Verification code') }}
                </p>
                <div class="em-code em-code-text" dir="ltr"
                     style="font-family:'SFMono-Regular',Consolas,'Liberation Mono',Menlo,monospace;font-size:36px;line-height:1.1;letter-spacing:12px;color:#070740;font-weight:700;text-align:center;padding-left:12px;unicode-bidi:isolate;">
                    {{ $code }}
                </div>
                <p dir="ltr" style="margin:14px 0 0;font-family:'SFMono-Regular',Consolas,'Liberation Mono',Menlo,monospace;font-size:10px;color:#9aa0b5;letter-spacing:1.5px;text-transform:uppercase;font-weight:600;unicode-bidi:isolate;">
                    {{ __('Single-use — do not share') }}
                </p>
            </td>
        </tr>
        </table>
    </td>
</tr>
</table>
