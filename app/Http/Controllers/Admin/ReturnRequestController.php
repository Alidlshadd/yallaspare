<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ReturnsExport;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Support\AdminLogger;
use App\Support\SqlSafe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

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
                    SqlSafe::whereLike($nested, 'reason', $search);
                    $nested->orWhereHas('order', fn ($orderQuery) => SqlSafe::whereLike($orderQuery, 'order_number', $search));
                    $nested->orWhereHas('user', function ($userQuery) use ($search): void {
                        SqlSafe::whereLike($userQuery, 'name', $search);
                        SqlSafe::orWhereLike($userQuery, 'email', $search);
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
            $return->order?->forceFill(['payment_status' => Order::PAYMENT_REFUNDED])->save();
        }

        AdminLogger::log('return_request.updated', $return, [
            'status' => $data['status'],
            'order_number' => $return->order?->order_number,
        ]);

        return back()->with('success', __('Return request updated.'));
    }

    public function exportExcel(Request $request)
    {
        try {
            return Excel::download(
                new ReturnsExport([
                    'from' => $request->query('from'),
                    'to' => $request->query('to'),
                    'status' => $request->query('status'),
                ]),
                'returns.xlsx'
            );
        } catch (\Throwable $e) {
            Log::error('Returns Excel export failed', [
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', __('Failed to export return requests. Please try again.'));
        }
    }

    public function bulkUpdate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'return_ids' => ['required', 'array', 'min:1', 'max:200'],
            'return_ids.*' => ['integer', 'min:1'],
            'status' => ['required', 'string', Rule::in(ReturnRequest::allowedStatuses())],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $updated = 0;
        $skipped = 0;
        $skippedReasons = [];

        foreach (array_unique($data['return_ids']) as $returnId) {
            $outcome = DB::transaction(function () use ($returnId, $data): array {
                $return = ReturnRequest::query()
                    ->whereKey($returnId)
                    ->with('order:id,order_number')
                    ->lockForUpdate()
                    ->first();

                if (! $return) {
                    return ['outcome' => 'skipped', 'reason' => "Return #{$returnId} not found"];
                }

                $previousStatus = (string) $return->status;
                if ($previousStatus === $data['status']) {
                    return ['outcome' => 'skipped', 'reason' => "Return #{$return->id} already {$data['status']}"];
                }

                $return->status = $data['status'];
                if (! empty($data['admin_note'])) {
                    $existing = trim((string) $return->admin_note);
                    $return->admin_note = $existing !== ''
                        ? $existing . "\n---\n" . $data['admin_note']
                        : $data['admin_note'];
                }

                if (in_array($data['status'], [ReturnRequest::STATUS_REFUNDED, ReturnRequest::STATUS_CLOSED, ReturnRequest::STATUS_REJECTED], true)) {
                    $return->resolved_at = now();
                }

                $return->save();

                AdminLogger::log('return.status_changed_bulk', $return, [
                    'from' => $previousStatus,
                    'to' => $data['status'],
                    'order_number' => $return->order?->order_number,
                ]);

                return ['outcome' => 'updated'];
            });

            if ($outcome['outcome'] === 'updated') {
                $updated++;
            } else {
                $skipped++;
                if (count($skippedReasons) < 10) {
                    $skippedReasons[] = $outcome['reason'];
                }
            }
        }

        if ($skipped > 0) {
            return back()->with('error', __(':updated updated, :skipped skipped — :reasons', [
                'updated' => $updated,
                'skipped' => $skipped,
                'reasons' => implode(' | ', $skippedReasons),
            ]));
        }

        return back()->with('success', __('Bulk: :updated return requests updated.', ['updated' => $updated]));
    }
}
