{{ $title ?? $subjectLine ?? 'YallaSpare' }}

{{ $intro ?? $bodyText ?? '' }}

@if (!empty($actionUrl ?? null) && !empty($actionText ?? null))
{{ $actionText }}: {{ $actionUrl }}
@endif

{{ __('Need help? Contact support@yallaspare.com. Never share verification codes or password links with anyone.') }}
