@php
    // Drop rows whose value is null or an empty/whitespace string. Keeps the grid
    // honest — no "ghost" empty rows for optional fields like IP scope, phone, etc.
    $rows = collect($items ?? [])
        ->filter(fn ($item) => isset($item['value']) && trim((string) $item['value']) !== '')
        ->values()
        ->all();
@endphp
@if (!empty($rows))
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:22px 0;border-top:1px solid #ebedf0;border-bottom:1px solid #ebedf0;">
    @foreach ($rows as $row)
    <tr class="em-meta-row">
        <td class="em-meta-bg em-meta-label"
            style="padding:14px 16px 14px 0;border-bottom:{{ $loop->last ? '0' : '1px solid #ebedf0' }};border-right:1px solid #ebedf0;color:#9aa0b5;font-family:'SFMono-Regular',Consolas,'Liberation Mono',Menlo,monospace;font-size:9.5px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;width:38%;vertical-align:middle;">
            {{ $row['label'] ?? '' }}
        </td>
        <td class="em-meta-val"
            style="padding:14px 0 14px 16px;border-bottom:{{ $loop->last ? '0' : '1px solid #ebedf0' }};color:#070740;font-family:'Space Grotesk','Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;font-size:14px;font-weight:700;vertical-align:middle;">
            <span dir="ltr" style="unicode-bidi:isolate;">{{ $row['value'] }}</span>
        </td>
    </tr>
    @endforeach
</table>
@endif
