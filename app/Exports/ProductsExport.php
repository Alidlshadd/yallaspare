<?php

namespace App\Exports;

use App\Models\Product;
use App\Support\SpreadsheetSanitizer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductsExport implements FromCollection, WithHeadings, ShouldAutoSize, WithColumnFormatting, WithStyles, WithEvents, WithDrawings
{
    private ?Collection $productsCache = null;

    private function products(): Collection
    {
        if ($this->productsCache !== null) {
            return $this->productsCache;
        }

        $this->productsCache = Product::query()
            ->select([
                'id',
                'category_id',
                'name_en',
                'name_ar',
                'name_ku',
                'price',
                'stock_quantity',
                'dealer_price',
                'sku',
                'oem_number',
                'part_number',
                'warranty',
                'brand',
                'description_en',
                'description_ar',
                'description_ku',
                'image',
            ])
            ->with(['category:id,name_en'])
            ->orderBy('id')
            ->get();

        return $this->productsCache;
    }

    public function collection(): Collection
    {
        return $this->products()
            ->map(function (Product $product): array {
                return SpreadsheetSanitizer::row([
                    '',
                    (string) ($product->name_en ?? ''),
                    (string) ($product->name_ar ?? ''),
                    (string) ($product->name_ku ?? ''),
                    $product->price,
                    $product->stock_quantity,
                    $product->dealer_price !== null ? $product->dealer_price : null,
                    (string) ($product->sku ?? ''),
                    (string) ($product->oem_number ?? ''),
                    (string) ($product->part_number ?? ''),
                    (string) ($product->warranty ?? ''),
                    (string) ($product->brand ?? ''),
                    (string) ($product->description_en ?? ''),
                    (string) ($product->description_ar ?? ''),
                    (string) ($product->description_ku ?? ''),
                    (string) ($product->category?->name_en ?? ''),
                ]);
            });
    }

    public function headings(): array
    {
        return [
            'image',
            'name_en',
            'name_ar',
            'name_ku',
            'price',
            'stock_quantity',
            'dealer_price',
            'sku',
            'oem_number',
            'part_number',
            'warranty',
            'brand',
            'description_en',
            'description_ar',
            'description_ku',
            'category_name',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'E' => NumberFormat::FORMAT_NUMBER_00,
            'F' => NumberFormat::FORMAT_NUMBER,
            'G' => NumberFormat::FORMAT_NUMBER_00,
            'H' => NumberFormat::FORMAT_TEXT,
            'I' => NumberFormat::FORMAT_TEXT,
            'J' => NumberFormat::FORMAT_TEXT,
            'P' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function drawings(): array
    {
        $drawings = [];

        foreach ($this->products()->values() as $index => $product) {
            $imagePath = trim((string) $product->image);
            if ($imagePath === '') {
                continue;
            }

            $fullPath = storage_path('app/public/' . ltrim($imagePath, '/'));
            if (!is_file($fullPath)) {
                continue;
            }

            $drawing = new Drawing();
            $drawing->setName((string) ($product->name_en ?? 'Product image'));
            $drawing->setDescription('Product image');
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
                $sheet->setAutoFilter('A1:P1');
                $sheet->getColumnDimension('A')->setWidth(12);

                $sheet->getStyle("A1:P{$highestRow}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_TOP);

                // Description columns: wrap long text for better readability.
                $sheet->getStyle("M2:O{$highestRow}")
                    ->getAlignment()
                    ->setWrapText(true);

                // Arabic/Kurdish columns: right align text.
                $sheet->getStyle("C2:D{$highestRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $sheet->getStyle("N2:O{$highestRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                for ($row = 2; $row <= $highestRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(48);
                }
            },
        ];
    }
}
