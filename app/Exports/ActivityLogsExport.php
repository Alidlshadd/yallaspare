<?php

namespace App\Exports;

use App\Support\SpreadsheetSanitizer;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Spatie\Activitylog\Models\Activity;

class ActivityLogsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(private array $filters = [])
    {
    }

    public function query(): Builder
    {
        return Activity::query()
            ->with(['causer:id,name,email', 'subject'])
            ->when($this->filters['from'] ?? null, fn (Builder $q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($this->filters['to'] ?? null, fn (Builder $q, $v) => $q->whereDate('created_at', '<=', $v))
            ->when($this->filters['subject_type'] ?? null, fn (Builder $q, $v) => $q->where('subject_type', $v))
            ->when($this->filters['log_name'] ?? null, fn (Builder $q, $v) => $q->where('log_name', $v))
            ->orderByDesc('id');
    }

    public function headings(): array
    {
        return [
            'id',
            'log_name',
            'event',
            'description',
            'subject_type',
            'subject_id',
            'causer_name',
            'causer_email',
            'changed_attributes',
            'previous_values',
            'created_at',
        ];
    }

    public function map($activity): array
    {
        $properties = (array) ($activity->properties ?? []);
        $attributes = (array) ($properties['attributes'] ?? []);
        $old = (array) ($properties['old'] ?? []);

        return SpreadsheetSanitizer::row([
            $activity->id,
            (string) ($activity->log_name ?? ''),
            (string) ($activity->event ?? ''),
            (string) ($activity->description ?? ''),
            class_basename((string) $activity->subject_type),
            (int) ($activity->subject_id ?? 0),
            (string) (optional($activity->causer)->name ?? ''),
            (string) (optional($activity->causer)->email ?? ''),
            $attributes !== [] ? json_encode($attributes, JSON_UNESCAPED_UNICODE) : '',
            $old !== [] ? json_encode($old, JSON_UNESCAPED_UNICODE) : '',
            optional($activity->created_at)->format('Y-m-d H:i'),
        ]);
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
