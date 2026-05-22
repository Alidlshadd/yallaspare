<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Support\AdminLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReturnRequestController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));

        $baseQuery = ReturnRequest::query()
            ->with([
                'order:id,order_number,status,total_amount,payment_status,delivery_city,delivery_address,delivery_phone',
                'user:id,name,email,phone,role',
            ])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nested) use ($search): void {
                    $nested->where('reason', 'like', '%' . $search . '%')
                        ->orWhereHas('order', fn ($orderQuery) => $orderQuery->where('order_number', 'like', '%' . $search . '%'))
                        ->orWhereHas('user', function ($userQuery) use ($search): void {
                            $userQuery->where('name', 'like', '%' . $search . '%')
                                ->orWhere('email', 'like', '%' . $search . '%');
                        });
                });
            });

        $statsQuery = clone $baseQuery;
        $statusCounts = (clone $statsQuery)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count) => (int) $count);

        $openStatuses = [
            ReturnRequest::STATUS_REQUESTED,
            ReturnRequest::STATUS_APPROVED,
            ReturnRequest::STATUS_RECEIVED,
        ];

        $stats = [
            'total' => (int) (clone $statsQuery)->count(),
            'open' => (int) (clone $statsQuery)->whereIn('status', $openStatuses)->count(),
            'refunded' => (int) ($statusCounts[ReturnRequest::STATUS_REFUNDED] ?? 0),
            'closed' => (int) (clone $statsQuery)
                ->whereIn('status', [
                    ReturnRequest::STATUS_REJECTED,
                    ReturnRequest::STATUS_REFUNDED,
                    ReturnRequest::STATUS_CLOSED,
                ])
                ->count(),
            'refund_total' => (float) (clone $statsQuery)->sum('refund_amount'),
        ];

        $requests = $baseQuery
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return view('admin.returns.index', [
            'requests' => $requests,
            'statuses' => ReturnRequest::allowedStatuses(),
            'stats' => $stats,
            'statusCounts' => $statusCounts,
        ]);
    }

    public function update(Request $request, ReturnRequest $return): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:' . implode(',', ReturnRequest::allowedStatuses())],
            'admin_note' => ['nullable', 'string', 'max:3000'],
            'refund_amount' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
        ]);

        $return->update([
            'status' => $data['status'],
            'admin_note' => trim((string) ($data['admin_note'] ?? '')) ?: null,
            'refund_amount' => isset($data['refund_amount']) ? round((float) $data['refund_amount'], 2) : $return->refund_amount,
            'resolved_at' => in_array($data['status'], [ReturnRequest::STATUS_REJECTED, ReturnRequest::STATUS_REFUNDED, ReturnRequest::STATUS_CLOSED], true)
                ? now()
                : null,
        ]);

        if ($data['status'] === ReturnRequest::STATUS_REFUNDED) {
            $return->order?->update(['payment_status' => Order::PAYMENT_REFUNDED]);
        }

        AdminLogger::log('return_request.updated', $return, [
            'status' => $data['status'],
            'order_number' => $return->order?->order_number,
        ]);

        return back()->with('success', __('Return request updated.'));
    }
}
