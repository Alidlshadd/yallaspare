<?php

namespace App\Exports;

use App\Models\ReturnRequest;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReturnsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(private array $filters = [])
    {
    }

    public function query(): Builder
    {
        return ReturnRequest::query()
            ->with(['order:id,order_number', 'user:id,name,email'])
            ->when($this->filters['from'] ?? null, fn (Builder $q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($this->filters['to'] ?? null, fn (Builder $q, $v) => $q->whereDate('created_at', '<=', $v))
            ->when($this->filters['status'] ?? null, fn (Builder $q, $v) => $q->where('status', $v))
            ->orderBy('id');
    }

    public function headings(): array
    {
        return [
            'id',
            'order_number',
            'type',
            'status',
            'reason',
            'admin_note',
            'refund_amount',
            'customer_name',
            'customer_email',
            'requested_at',
            'resolved_at',
            'created_at',
        ];
    }

    public function map($return): array
    {
        return [
            $return->id,
            (string) ($return->order?->order_number ?? ''),
            (string) ($return->type ?? ''),
            (string) $return->status,
            (string) ($return->reason ?? ''),
            (string) ($return->admin_note ?? ''),
            (float) ($return->refund_amount ?? 0),
            (string) ($return->user?->name ?? ''),
            (string) ($return->user?->email ?? ''),
            optional($return->requested_at)->format('Y-m-d H:i'),
            optional($return->resolved_at)->format('Y-m-d H:i'),
            optional($return->created_at)->format('Y-m-d H:i'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1F4E78']],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ],
        ];
    }
}
