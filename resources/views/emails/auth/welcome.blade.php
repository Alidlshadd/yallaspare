@extends('emails.layouts.base')

@section('content')
    <p style="margin:0 0 8px;color:#16a34a;font-size:12px;font-weight:900;text-transform:uppercase;letter-spacing:1.5px;">{{ __('Welcome') }}</p>
    <h1 class="email-title" style="margin:0;color:#070740;font-size:28px;line-height:36px;font-weight:900;letter-spacing:-0.4px;">{{ __('Welcome to YallaSpare') }}</h1>
    <p class="email-copy" style="margin:14px 0 0;color:#475569;font-size:16px;line-height:26px;">
        {{ __('Your account is ready for secure auto parts ordering, saved addresses, order tracking, and account management.') }}
    </p>

    @if (!empty($actionUrl))
        <table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0;">
            <tr>
                <td>
                    @include('emails.components.button', ['url' => $actionUrl, 'label' => $actionText ?? __('Open dashboard')])
                </td>
            </tr>
        </table>
    @endif
@endsection
