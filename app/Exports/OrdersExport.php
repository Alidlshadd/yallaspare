<?php

namespace App\Exports;

use App\Models\Order;
use App\Models\User;
use App\Support\SqlSafe;
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
        $search = trim((string) ($this->filters['search'] ?? ''));
        $statusInput = strtolower(trim((string) ($this->filters['status'] ?? '')));
        $status = Order::normalizedStatus($statusInput);
        $association = strtolower(trim((string) ($this->filters['association'] ?? '')));
        $attention = strtolower(trim((string) ($this->filters['attention'] ?? '')));

        return Order::query()
            ->with(['user:id,name,email,role'])
            ->whereNull('archived_at')
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $nested) use ($search) {
                    SqlSafe::whereLike($nested, 'order_number', $search);
                    SqlSafe::orWhereLike($nested, 'delivery_phone', $search);
                    SqlSafe::orWhereLike($nested, 'delivery_city', $search);
                    $nested->orWhereHas('user', function (Builder $userQuery) use ($search) {
                        SqlSafe::whereLike($userQuery, 'name', $search);
                        SqlSafe::orWhereLike($userQuery, 'email', $search);
                    });
                });
            })
            ->when($statusInput !== '' && in_array($status, Order::allowedStatuses(), true), fn (Builder $q) => $q->where('status', $status))
            ->when($attention === 'today_pending', fn (Builder $q) => $q
                ->where('status', Order::STATUS_PENDING)
                ->whereDate('created_at', now()->toDateString()))
            ->when($attention === 'needs_shipping', fn (Builder $q) => $q->where('status', Order::STATUS_PROCESSING))
            ->when($attention === 'cancellation_requests', fn (Builder $q) => $q
                ->whereNotNull('cancellation_requested_at')
                ->where('status', '!=', Order::STATUS_CANCELLED))
            ->when($attention === 'open_returns', fn (Builder $q) => $q
                ->whereHas('returnRequests', fn (Builder $returnQuery) => $returnQuery->whereIn('status', ['requested', 'approved', 'received'])))
            ->when($association === 'dealer', fn (Builder $q) => $q->whereHas('user', fn (Builder $userQuery) => $userQuery->where('role', User::ROLE_DEALER)))
            ->when($association === 'user', fn (Builder $q) => $q->whereHas('user', fn (Builder $userQuery) => $userQuery->where('role', User::ROLE_USER)))
            ->when($this->filters['from'] ?? null, fn (Builder $q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($this->filters['to'] ?? null, fn (Builder $q, $v) => $q->whereDate('created_at', '<=', $v))
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
