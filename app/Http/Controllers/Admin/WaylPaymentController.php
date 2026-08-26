<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentProviderLog;
use App\Services\Payments\Providers\WaylPaymentService;
use App\Support\AdminLogger;
use App\Support\SqlSafe;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WaylPaymentController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in([
                Payment::STATUS_PAID,
                Payment::STATUS_PENDING,
                Payment::STATUS_FAILED,
                Payment::STATUS_CANCELLED,
            ])],
            'search' => ['nullable', 'string', 'max:120'],
            'period' => ['nullable', Rule::in(['today', '7_days', '30_days'])],
            'event_type' => ['nullable', Rule::in([
                PaymentProviderLog::EVENT_CREATE_LINK,
                PaymentProviderLog::EVENT_STATUS_CHECK,
                PaymentProviderLog::EVENT_HEALTH_CHECK,
                PaymentProviderLog::EVENT_WEBHOOK_RECEIVED,
                PaymentProviderLog::EVENT_WEBHOOK_REJECTED,
            ])],
            'api_result' => ['nullable', 'string', 'max:80'],
        ]);

        $paymentQuery = Payment::query()
            ->where('provider', 'wayl')
            ->with(['order:id,order_number']);

        if (! empty($filters['status'])) {
            $paymentQuery->where('status', $filters['status']);
        }
        if (! empty($filters['search'])) {
            $search = SqlSafe::searchTerm($filters['search']);
            $paymentQuery->where(function (Builder $query) use ($search): void {
                SqlSafe::whereLike($query, 'provider_reference', $search);
                SqlSafe::orWhereLike($query, 'provider_payment_id', $search);
                if (ctype_digit($search)) {
                    $query->orWhere('payments.id', (int) $search);
                }
                $query->orWhereHas('order', function (Builder $orderQuery) use ($search): void {
                    SqlSafe::whereLike($orderQuery, 'order_number', $search);
                });
            });
        }
        $this->applyPeriod($paymentQuery, $filters['period'] ?? null);

        $payments = $paymentQuery
            ->latest('id')
            ->paginate(15, ['*'], 'payments_page')
            ->withQueryString();

        $logQuery = PaymentProviderLog::query()
            ->where('provider', 'wayl')
            ->with(['order:id,order_number', 'payment:id']);

        if (! empty($filters['event_type'])) {
            $logQuery->where('event_type', $filters['event_type']);
        }
        if (! empty($filters['api_result'])) {
            SqlSafe::whereLike($logQuery, 'result', $filters['api_result']);
        }
        $this->applyPeriod($logQuery, $filters['period'] ?? null);

        $logs = $logQuery
            ->latest('id')
            ->paginate(15, ['*'], 'logs_page')
            ->withQueryString();

        $allPayments = Payment::query()->where('provider', 'wayl');
        $statusCounts = (clone $allPayments)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $since = now()->subDay();
        $recentPayments = Payment::query()->where('provider', 'wayl')->where('created_at', '>=', $since);
        $recentStatusCounts = (clone $recentPayments)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        $recentTotal = (int) (clone $recentPayments)->count();
        $recentPaid = (int) ($recentStatusCounts[Payment::STATUS_PAID] ?? 0);

        $latestHealth = PaymentProviderLog::query()
            ->where('provider', 'wayl')
            ->where('event_type', PaymentProviderLog::EVENT_HEALTH_CHECK)
            ->latest('id')
            ->first();

        $webhookLogs = PaymentProviderLog::query()->where('provider', 'wayl');
        $lastWebhook = (clone $webhookLogs)
            ->whereIn('event_type', [
                PaymentProviderLog::EVENT_WEBHOOK_RECEIVED,
                PaymentProviderLog::EVENT_WEBHOOK_REJECTED,
            ])
            ->latest('id')
            ->first();

        $baseUrl = trim((string) config('services.wayl.base_url', 'https://api.thewayl.com'));
        $baseHost = parse_url($baseUrl, PHP_URL_HOST);

        return view('admin.wayl.index', [
            'payments' => $payments,
            'logs' => $logs,
            'filters' => $filters,
            'statusCounts' => [
                'total' => (int) (clone $allPayments)->count(),
                'paid' => (int) ($statusCounts[Payment::STATUS_PAID] ?? 0),
                'pending' => (int) ($statusCounts[Payment::STATUS_PENDING] ?? 0),
                'failed' => (int) ($statusCounts[Payment::STATUS_FAILED] ?? 0),
                'cancelled' => (int) ($statusCounts[Payment::STATUS_CANCELLED] ?? 0),
            ],
            'metrics' => [
                'requests' => $recentTotal,
                'successful' => $recentPaid,
                'failed' => (int) ($recentStatusCounts[Payment::STATUS_FAILED] ?? 0),
                'pending' => (int) ($recentStatusCounts[Payment::STATUS_PENDING] ?? 0),
                'success_rate' => $recentTotal > 0 ? round(($recentPaid / $recentTotal) * 100, 1) : 0.0,
            ],
            'configuration' => [
                'enabled' => (bool) config('services.wayl.enabled', false),
                'environment' => strtoupper((string) config('services.wayl.env', 'test')),
                'currency' => strtoupper((string) config('payments.currency', 'IQD')),
                'token_configured' => filled(config('services.wayl.token')),
                'base_url' => is_string($baseHost) && $baseHost !== '' ? $baseHost : '—',
                'webhook_configured' => filled(config('services.wayl.webhook_url'))
                    && filled(config('services.wayl.webhook_secret')),
                'minimum_amount' => max(1, (int) config('payments.methods.wayl.minimum_amount', 3000)),
            ],
            'latestHealth' => $latestHealth,
            'webhook' => [
                'last_event' => $lastWebhook?->created_at,
                'valid' => (clone $webhookLogs)->where('event_type', PaymentProviderLog::EVENT_WEBHOOK_RECEIVED)->count(),
                'rejected' => (clone $webhookLogs)->where('event_type', PaymentProviderLog::EVENT_WEBHOOK_REJECTED)->count(),
            ],
            'debugVisible' => strtolower((string) config('services.wayl.env', 'test')) !== 'live',
        ]);
    }

    public function health(Request $request, WaylPaymentService $wayl): RedirectResponse
    {
        $result = $wayl->healthCheck();

        AdminLogger::log('wayl.connection_checked', null, [
            'connected' => $result['connected'],
            'http_status' => $result['http_status'],
            'admin_id' => $request->user()?->id,
        ]);

        if (! $result['connected']) {
            return back()->with('error', __('WAYL connection check failed: :message', [
                'message' => __($result['message']),
            ]));
        }

        return back()->with('success', __('WAYL connection is healthy. Authentication is valid.'));
    }

    private function applyPeriod(Builder $query, ?string $period): void
    {
        $from = match ($period) {
            'today' => now()->startOfDay(),
            '7_days' => now()->subDays(7),
            '30_days' => now()->subDays(30),
            default => null,
        };

        if ($from instanceof Carbon) {
            $query->where('created_at', '>=', $from);
        }
    }
}
