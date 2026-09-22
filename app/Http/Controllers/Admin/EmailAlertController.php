<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendAdminEmailAlert;
use App\Models\AdminEmailAlert;
use App\Models\Setting;
use App\Services\Email\AdminEmailAlertService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmailAlertController extends Controller
{
    public function index(Request $request, AdminEmailAlertService $service): View
    {
        $status = (string) $request->query('status', '');
        $statuses = ['queued', 'sending', 'sent', 'failed', 'simulated'];
        if (! in_array($status, $statuses, true)) {
            $status = '';
        }

        return view('admin.email.alerts', [
            'service' => $service,
            'status' => $status,
            'statuses' => $statuses,
            'logs' => AdminEmailAlert::query()->when($status !== '', fn ($query) => $query->where('status', $status))->latest('id')->paginate(25)->withQueryString(),
            'counts' => AdminEmailAlert::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order_recipients' => ['nullable', 'string', 'max:15000'],
            'system_recipients' => ['nullable', 'string', 'max:15000'],
            'order_enabled' => ['required', 'boolean'],
            'system_enabled' => ['required', 'boolean'],
            'locale' => ['required', Rule::in(['en', 'ar', 'ku'])],
        ]);

        $settings = ['admin_alert_locale' => $data['locale']];
        foreach (['order', 'system'] as $type) {
            $emails = array_values(array_unique(array_filter(array_map(
                fn ($email) => strtolower(trim($email)),
                preg_split('/[\r\n,;]+/', $data[$type.'_recipients'] ?? '') ?: [],
            ))));
            Validator::make([$type.'_recipients' => $emails], [
                $type.'_recipients' => ['array', 'max:50', $data[$type.'_enabled'] ? 'min:1' : 'min:0'],
                $type.'_recipients.*' => ['required', 'email:rfc', 'max:254', 'not_regex:/[\r\n]/'],
            ])->validate();
            $settings['admin_alert_'.$type.'_recipients'] = json_encode($emails);
            $settings['admin_alert_'.$type.'_enabled'] = $data[$type.'_enabled'] ? '1' : '0';
        }

        DB::transaction(fn () => Setting::setMany($settings));

        return back()->with('success', __('alerts.saved'));
    }

    public function test(Request $request, AdminEmailAlertService $service): RedirectResponse
    {
        $data = $request->validate(['type' => ['required', Rule::in(['order', 'system'])]]);
        if ($service->recipients($data['type']) === []) {
            return back()->withErrors(['recipients' => __('alerts.no_recipients')]);
        }

        $service->test($data['type']);

        return back()->with('success', __('alerts.test_finished'));
    }

    public function retry(AdminEmailAlert $alert): RedirectResponse
    {
        // Only an explicit retry can move failed mail back to queued.
        if (AdminEmailAlert::whereKey($alert->id)->where('status', 'failed')->update(['status' => 'queued', 'error_code' => null])) {
            (new SendAdminEmailAlert($alert->id))->handle();
        }

        return back()->with('success', __('alerts.retry_finished'));
    }
}
