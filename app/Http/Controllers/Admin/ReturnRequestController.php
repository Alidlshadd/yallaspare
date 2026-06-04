<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ReturnsExport;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\User;
use App\Services\Returns\ReturnRefundService;
use App\Support\AdminLogger;
use App\Support\SqlSafe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
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

    public function update(Request $request, ReturnRequest $return, ReturnRefundService $refunds): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:' . implode(',', ReturnRequest::allowedStatuses())],
            'admin_note' => ['nullable', 'string', 'max:3000'],
            'refund_amount' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
        ]);

        if ($data['status'] === ReturnRequest::STATUS_REFUNDED) {
            abort_unless($request->user()?->hasPermission(User::PERMISSION_FINANCE_MANAGE), 403);
        }

        try {
            $refunds->updateStatus(
                $return,
                (string) $data['status'],
                $data['admin_note'] ?? null,
                isset($data['refund_amount']) ? (float) $data['refund_amount'] : null,
                $request->user()
            );
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

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

    public function bulkUpdate(Request $request, ReturnRefundService $refunds): RedirectResponse
    {
        $data = $request->validate([
            'return_ids' => ['required', 'array', 'min:1', 'max:200'],
            'return_ids.*' => ['integer', 'min:1'],
            'status' => ['required', 'string', Rule::in(ReturnRequest::allowedStatuses())],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($data['status'] === ReturnRequest::STATUS_REFUNDED) {
            abort_unless($request->user()?->hasPermission(User::PERMISSION_FINANCE_MANAGE), 403);
        }

        $updated = 0;
        $skipped = 0;
        $skippedReasons = [];

        foreach (array_unique($data['return_ids']) as $returnId) {
            $return = ReturnRequest::query()->find($returnId);
            if (! $return) {
                $outcome = ['outcome' => 'skipped', 'reason' => "Return #{$returnId} not found"];
            } elseif ((string) $return->status === $data['status']) {
                $outcome = ['outcome' => 'skipped', 'reason' => "Return #{$return->id} already {$data['status']}"];
            } else {
                try {
                    $note = trim((string) ($data['admin_note'] ?? ''));
                    if ($note !== '' && trim((string) $return->admin_note) !== '') {
                        $note = trim((string) $return->admin_note) . "\n---\n" . $note;
                    }

                    $refunds->updateStatus(
                        $return,
                        (string) $data['status'],
                        $note !== '' ? $note : null,
                        null,
                        $request->user()
                    );

                    $outcome = ['outcome' => 'updated'];
                } catch (ValidationException $exception) {
                    $outcome = [
                        'outcome' => 'skipped',
                        'reason' => "Return #{$return->id}: " . implode(' ', $exception->validator->errors()->all()),
                    ];
                }
            }

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
