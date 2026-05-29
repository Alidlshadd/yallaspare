@php
    $rows = $broadcasts->map(function ($b) {
        $fs = $b->filters_snapshot ?? [];
        $summary = collect([
            $fs['roles'] ?? [],
            $fs['locales'] ?? [],
            $fs['email_verified'] ?? null,
        ])->flatten()->filter()->implode(' · ');
        return [
            'id' => $b->id,
            'subject' => $b->subject,
            'admin_email' => $b->admin?->email ?? __('Unknown'),
            'created_at_human' => $b->created_at?->diffForHumans(),
            'filter_summary' => $summary !== '' ? $summary : __('All users'),
            'recipient_count' => $b->recipient_count,
            'sent_count' => $b->sent_count,
            'failed_count' => $b->failed_count,
            'status' => $b->status,
        ];
    })->values();
@endphp
<script id="history-data" type="application/json">@json($rows)</script>
