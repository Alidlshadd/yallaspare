<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td style="font-size:13px;line-height:21px;color:#64748b;text-align:center;">
            {{ __('This message was sent by :brand to keep your account and orders secure.', ['brand' => $brandName ?? 'YallaSpare']) }}
        </td>
    </tr>
    <tr>
        <td style="padding-top:12px;font-size:12px;line-height:20px;color:#94a3b8;text-align:center;">
            {{ __('Need help? Contact support@yallaspare.com. Never share verification codes or password links with anyone.') }}
        </td>
    </tr>
    <tr>
        <td style="padding-top:14px;font-size:11px;line-height:18px;color:#94a3b8;text-align:center;">
            {{ __('YallaSpare Auto Parts System') }} - {{ __('Secure transactional email') }}
        </td>
    </tr>
</table>
