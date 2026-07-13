<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\IraqiMobileNumber;
use App\Services\OtpiqDeliveryException;
use App\Services\OtpiqSmsService;
use App\Support\IraqiPhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class MessagingController extends Controller
{
    public function __construct(private readonly OtpiqSmsService $otpiq)
    {
    }

    public function index(): View
    {
        $withPhone = User::query()->whereNotNull('phone_normalized');

        $whatsappEnabled = (bool) config('services.otpiq.whatsapp.enabled', false);
        $whatsappConfigured = $this->otpiq->whatsappAvailable();
        $templateStatus = $this->otpiq->whatsappTemplateStatus();
        $whatsappReady = $this->otpiq->whatsappReady();
        $whatsappMissing = $this->whatsappMissing($whatsappEnabled, $templateStatus);

        $whatsappState = match (true) {
            ! $whatsappEnabled => 'disabled',
            $whatsappReady => 'ready',
            default => 'attention',
        };

        return view('admin.messaging.index', [
            'channels' => [
                'sms' => [
                    'label' => 'SMS',
                    'available' => $this->otpiq->smsAvailable(),
                    'state' => $this->otpiq->smsAvailable() ? 'ready' : 'attention',
                    'status' => $this->otpiq->smsAvailable() ? __('Ready') : __('Configuration required'),
                    'missing' => $this->otpiq->smsAvailable() ? [] : [__('OTPIQ API key')],
                ],
                'whatsapp' => [
                    'label' => 'WhatsApp',
                    'available' => $whatsappReady,
                    'state' => $whatsappState,
                    'status' => match ($whatsappState) {
                        'ready' => __('Ready'),
                        'disabled' => __('Disabled'),
                        default => __('Configuration required'),
                    },
                    'missing' => $whatsappMissing,
                    'template_alert' => $whatsappEnabled
                        && $whatsappConfigured
                        && $templateStatus['checked']
                        && $templateStatus['template_approved'] !== true,
                ],
            ],
            'stats' => [
                'with_phone' => (clone $withPhone)->count(),
                'verified' => (clone $withPhone)->whereNotNull('phone_verified_at')->count(),
                'unverified' => (clone $withPhone)->whereNull('phone_verified_at')->count(),
                'sms_opt_in' => (clone $withPhone)->where('sms_notifications', true)->count(),
                'whatsapp_opt_in' => (clone $withPhone)->where('whatsapp_notifications', true)->count(),
            ],
            'configuration' => [
                'api_key' => $this->otpiq->smsAvailable(),
                'base_url' => filled(config('services.otpiq.base_url')),
                'whatsapp_enabled' => $whatsappEnabled,
                'whatsapp_account' => filled(config('services.otpiq.whatsapp.account_id')),
                'whatsapp_phone' => filled(config('services.otpiq.whatsapp.phone_id')),
                'whatsapp_template' => filled(config('services.otpiq.whatsapp.template_name')),
                'whatsapp_language' => filled(config('services.otpiq.whatsapp.template_language')),
                'template_name' => trim((string) config('services.otpiq.whatsapp.template_name')),
                'template_language' => trim((string) config('services.otpiq.whatsapp.template_language', 'en')),
                'template_status' => $templateStatus,
            ],
        ]);
    }

    public function sendTest(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'channel' => ['required', Rule::in(['sms', 'whatsapp'])],
            'phone' => ['required', 'string', 'max:30', new IraqiMobileNumber()],
        ]);

        $channel = (string) $validated['channel'];
        $available = $channel === 'sms'
            ? $this->otpiq->smsAvailable()
            : $this->otpiq->whatsappReady();

        if (! $available) {
            return back()->withErrors([
                'channel' => $channel === 'whatsapp'
                    ? __('WhatsApp verification is currently unavailable.')
                    : __('SMS verification is currently unavailable.'),
            ])->withInput();
        }

        $recipient = new User();
        $recipient->phone = IraqiPhoneNumber::toE164($validated['phone']);
        $code = (string) random_int(100000, 999999);

        try {
            $this->otpiq->sendVerification($recipient, $code, $channel);
        } catch (Throwable $exception) {
            Log::error('Admin messaging test delivery failed', [
                'admin_id' => $request->user()?->id,
                'channel' => $channel,
                'category' => $exception instanceof OtpiqDeliveryException
                    ? $exception->category
                    : 'unexpected',
                'exception' => $exception::class,
            ]);

            return back()->withErrors([
                'phone' => __('The test message could not be sent. Check the provider configuration and try again'),
            ])->withInput();
        }

        Log::channel('security')->notice('security event', [
            'event' => 'admin.messaging_test_sent',
            'admin_id' => $request->user()?->id,
            'delivery_channel' => $channel,
        ]);

        return back()->with('success', __('A test verification code was sent via :channel', [
            'channel' => strtoupper($channel),
        ]));
    }

    /**
     * Human-readable list of what still blocks the WhatsApp channel.
     *
     * @param  array{checked: bool, template_approved: ?bool}  $templateStatus
     * @return array<int, string>
     */
    private function whatsappMissing(bool $whatsappEnabled, array $templateStatus): array
    {
        $missing = [];

        if (! $this->otpiq->smsAvailable()) {
            $missing[] = __('OTPIQ API key');
        }

        if (! $whatsappEnabled) {
            $missing[] = __('WhatsApp channel enabled');
        }

        if (blank(config('services.otpiq.whatsapp.account_id'))) {
            $missing[] = __('WhatsApp account ID');
        }

        if (blank(config('services.otpiq.whatsapp.phone_id'))) {
            $missing[] = __('WhatsApp phone ID');
        }

        if (blank(config('services.otpiq.whatsapp.template_name'))) {
            $missing[] = __('Approved template name');
        }

        if ($missing === [] && $templateStatus['checked'] && $templateStatus['template_approved'] !== true) {
            $missing[] = __('An approved WhatsApp verification template is required.');
        }

        return $missing;
    }
}
