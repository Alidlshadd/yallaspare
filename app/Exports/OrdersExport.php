<?php

namespace App\Exports;

use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrdersExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(private array $filters = [])
    {
    }

    public function query(): Builder
    {
        return Order::query()
            ->with(['user:id,name,email,role'])
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
            'status',
            'payment_status',
            'payment_method',
            'subtotal',
            'shipping_fee',
            'discount_amount',
            'total_amount',
            'coupon_code',
            'customer_name',
            'customer_email',
            'customer_role',
            'delivery_city',
            'delivery_phone',
            'created_at',
        ];
    }

    public function map($order): array
    {
        return [
            $order->id,
            (string) $order->order_number,
            (string) $order->status,
            (string) $order->payment_status,
            (string) $order->payment_method,
            (float) $order->subtotal_amount,
            (float) $order->shipping_fee,
            (float) $order->discount_amount,
            (float) $order->total_amount,
            (string) ($order->coupon_code ?? ''),
            (string) ($order->user?->name ?? '[guest]'),
            (string) ($order->user?->email ?? ''),
            (string) ($order->user?->role ?? ''),
            (string) ($order->delivery_city ?? ''),
            (string) ($order->delivery_phone ?? ''),
            optional($order->created_at)->format('Y-m-d H:i'),
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
