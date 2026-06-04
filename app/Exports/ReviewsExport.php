<?php

namespace App\Exports;

use App\Models\ProductReview;
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

class ReviewsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(private array $filters = [])
    {
    }

    public function query(): Builder
    {
        return ProductReview::query()
            ->with(['product:id,name_en', 'user:id,name,email'])
            ->when($this->filters['from'] ?? null, fn (Builder $q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($this->filters['to'] ?? null, fn (Builder $q, $v) => $q->whereDate('created_at', '<=', $v))
            ->orderBy('id');
    }

    public function headings(): array
    {
        return [
            'id',
            'product_name',
            'customer_name',
            'customer_email',
            'rating',
            'title',
            'comment',
            'is_approved',
            'created_at',
        ];
    }

    public function map($review): array
    {
        return SpreadsheetSanitizer::row([
            $review->id,
            (string) ($review->product?->name_en ?? ''),
            (string) ($review->user?->name ?? ''),
            (string) ($review->user?->email ?? ''),
            (int) ($review->rating ?? 0),
            (string) ($review->title ?? ''),
            (string) ($review->comment ?? ''),
            $review->is_approved ? 'yes' : 'no',
            optional($review->created_at)->format('Y-m-d H:i'),
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
