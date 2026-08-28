@php
    use App\Support\EmailStyle;

    // Drop rows whose value is null or an empty/whitespace string. Keeps the grid
    // honest — no "ghost" empty rows for optional fields like IP scope, phone, etc.
    $rows = collect($items ?? [])
        ->filter(fn ($item) => isset($item['value']) && trim((string) $item['value']) !== '')
        ->values()
        ->all();

    // The divider sits between the two columns, so it lives on the label cell's
    // trailing edge — which is the left edge once the row mirrors in ar/ku.
    $labelEdge = EmailStyle::end();
    $labelPad  = EmailStyle::isRtl() ? '14px 0 14px 16px' : '14px 16px 14px 0';
    $valuePad  = EmailStyle::isRtl() ? '14px 16px 14px 0' : '14px 0 14px 16px';
@endphp
@if (!empty($rows))
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" dir="{{ EmailStyle::isRtl() ? 'rtl' : 'ltr' }}" style="margin:22px 0;border-top:1px solid #ebedf0;border-bottom:1px solid #ebedf0;">
    @foreach ($rows as $row)
    <tr class="em-meta-row">
        <td class="em-meta-bg em-meta-label" align="{{ EmailStyle::start() }}"
            style="padding:{{ $labelPad }};border-bottom:{{ $loop->last ? '0' : '1px solid #ebedf0' }};border-{{ $labelEdge }}:1px solid #ebedf0;color:#9aa0b5;font-family:{{ EmailStyle::mono() }};font-size:{{ EmailStyle::isRtl() ? '10.5px' : '9.5px' }};font-weight:700;{{ EmailStyle::caps() }}{{ EmailStyle::tracking('1.5px') }}width:38%;vertical-align:middle;">
            {{ $row['label'] ?? '' }}
        </td>
        <td class="em-meta-val" align="{{ EmailStyle::start() }}"
            style="padding:{{ $valuePad }};border-bottom:{{ $loop->last ? '0' : '1px solid #ebedf0' }};color:#070740;font-family:{{ EmailStyle::display() }};font-size:14px;font-weight:700;vertical-align:middle;">
            {{-- Values are a mix of Latin data (emails, order ids, IPs) and translated
                 sentences. Only the Latin ones get pinned to LTR; forcing an Arabic
                 sentence left-to-right would misplace its punctuation. --}}
            <span dir="{{ preg_match('/\p{Arabic}/u', (string) $row['value']) ? 'rtl' : 'ltr' }}" style="unicode-bidi:isolate;">{{ $row['value'] }}</span>
        </td>
    </tr>
    @endforeach
</table>
@endif
