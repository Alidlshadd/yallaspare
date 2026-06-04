<?php

namespace App\Exports;

use App\Models\User;
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

class UsersExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(private array $filters = [])
    {
    }

    public function query(): Builder
    {
        return User::query()
            ->when($this->filters['role'] ?? null, fn (Builder $q, $v) => $q->where('role', $v))
            ->when($this->filters['dealer_status'] ?? null, fn (Builder $q, $v) => $q->where('dealer_status', $v))
            ->orderBy('id');
    }

    public function headings(): array
    {
        return [
            'id',
            'name',
            'email',
            'phone',
            'role',
            'dealer_status',
            'dealer_discount',
            'email_verified',
            'locale_preference',
            'created_at',
        ];
    }

    public function map($user): array
    {
        return SpreadsheetSanitizer::row([
            $user->id,
            (string) ($user->name ?? ''),
            (string) ($user->email ?? ''),
            (string) ($user->phone ?? ''),
            (string) ($user->role ?? ''),
            (string) ($user->dealer_status ?? ''),
            $user->dealer_discount !== null ? (float) $user->dealer_discount : null,
            $user->email_verified_at ? 'yes' : 'no',
            (string) ($user->locale_preference ?? ''),
            optional($user->created_at)->format('Y-m-d H:i'),
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
