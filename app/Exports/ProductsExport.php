<?php

namespace App\Exports;

use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductsExport implements FromCollection, WithHeadings, ShouldAutoSize, WithColumnFormatting, WithStyles, WithEvents
{
    public function collection(): Collection
    {
        return Product::query()
            ->select([
                'name_en',
                'name_ar',
                'name_ku',
                'price',
                'stock_quantity',
                'dealer_price',
                'sku',
                'brand',
                'description_en',
                'description_ar',
                'description_ku',
            ])
            ->orderBy('id')
            ->get()
            ->map(function (Product $product): array {
                return [
                    (string) ($product->name_en ?? ''),
                    (string) ($product->name_ar ?? ''),
                    (string) ($product->name_ku ?? ''),
                    $product->price,
                    $product->stock_quantity,
                    $product->dealer_price !== null ? $product->dealer_price : null,
                    (string) ($product->sku ?? ''),
                    (string) ($product->brand ?? ''),
                    (string) ($product->description_en ?? ''),
                    (string) ($product->description_ar ?? ''),
                    (string) ($product->description_ku ?? ''),
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Name EN',
            'Name AR',
            'Name KU',
            'Price',
            'Stock Quantity',
            'Dealer Price',
            'SKU',
            'Brand',
            'Description EN',
            'Description AR',
            'Description KU',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_NUMBER_00,
            'E' => NumberFormat::FORMAT_NUMBER,
            'F' => NumberFormat::FORMAT_NUMBER_00,
            'G' => NumberFormat::FORMAT_TEXT,
        ];
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
                $sheet->setAutoFilter('A1:K1');

                $sheet->getStyle("A1:K{$highestRow}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_TOP);

                // Description columns: wrap long text for better readability.
                $sheet->getStyle("I2:K{$highestRow}")
                    ->getAlignment()
                    ->setWrapText(true);

                // Arabic/Kurdish columns: right align text.
                $sheet->getStyle("B2:C{$highestRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $sheet->getStyle("J2:K{$highestRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            },
        ];
    }
}
