<?php

namespace App\Exports;

use App\Models\Category;
use App\Support\SpreadsheetSanitizer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CategoriesExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithEvents, WithDrawings
{
    private ?Collection $categoriesCache = null;

    private function categories(): Collection
    {
        if ($this->categoriesCache !== null) {
            return $this->categoriesCache;
        }

        $this->categoriesCache = Category::query()
            ->select([
                'id',
                'name_en',
                'name_ar',
                'name_ku',
                'slug',
                'description',
                'image',
                'created_at',
                'updated_at',
            ])
            ->withCount('products')
            ->orderBy('id')
            ->get();

        return $this->categoriesCache;
    }

    public function collection(): Collection
    {
        return $this->categories()
            ->map(function (Category $category): array {
                return SpreadsheetSanitizer::row([
                    '',
                    (int) $category->id,
                    (string) ($category->name_en ?? ''),
                    (string) ($category->name_ar ?? ''),
                    (string) ($category->name_ku ?? ''),
                    (string) ($category->slug ?? ''),
                    (string) ($category->description ?? ''),
                    (int) ($category->products_count ?? 0),
                    $category->created_at?->format('Y-m-d H:i:s') ?? '',
                    $category->updated_at?->format('Y-m-d H:i:s') ?? '',
                ]);
            });
    }

    public function headings(): array
    {
        return [
            'image',
            'id',
            'name_en',
            'name_ar',
            'name_ku',
            'slug',
            'description',
            'products_count',
            'created_at',
            'updated_at',
        ];
    }

    public function drawings(): array
    {
        $drawings = [];

        foreach ($this->categories()->values() as $index => $category) {
            $imagePath = trim((string) $category->image);
            if ($imagePath === '') {
                continue;
            }

            $fullPath = storage_path('app/public/' . ltrim($imagePath, '/'));
            if (! is_file($fullPath)) {
                continue;
            }

            $drawing = new Drawing();
            $drawing->setName((string) ($category->name_en ?? 'Category image'));
            $drawing->setDescription('Category image');
            $drawing->setPath($fullPath);
            $drawing->setCoordinates('A' . ($index + 2));
            $drawing->setHeight(44);
            $drawing->setOffsetX(8);
            $drawing->setOffsetY(4);

            $drawings[] = $drawing;
        }

        return $drawings;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'FFFFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1F4E78'],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                $sheet->freezePane('A2');
                $sheet->setAutoFilter('A1:J1');
                $sheet->getColumnDimension('A')->setWidth(12);

                $sheet->getStyle("A1:J{$highestRow}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_TOP);

                $sheet->getStyle("D2:E{$highestRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $sheet->getStyle("G2:G{$highestRow}")
                    ->getAlignment()
                    ->setWrapText(true);

                for ($row = 2; $row <= $highestRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(48);
                }
            },
        ];
    }
}
