<?php

namespace Tests\Feature\Admin;

use App\Jobs\SendAdminEmailAlert;
use App\Models\AdminEmailAlert;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Services\Email\AdminEmailAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\PendingMail;
use Illuminate\Mail\SentMessage;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class AdminEmailAlertsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['mail.default' => 'array', 'mail.from.address' => 'support@yallaspare.com']);
    }

    private function manager(): User
    {
        return User::factory()->create(['role' => User::ROLE_SETTINGS_MANAGER, 'email_verified_at' => now()]);
    }

    private function configure(string $type, array $emails): void
    {
        Setting::setMany(['admin_alert_'.$type.'_enabled' => '1', 'admin_alert_'.$type.'_recipients' => json_encode($emails)]);
    }

    private function order(): Order
    {
        $order = new Order;
        $order->forceFill([
            'user_id' => User::factory()->create()->id, 'order_number' => 'ALERT-'.uniqid(), 'total_amount' => 25000,
            'subtotal_amount' => 20000, 'shipping_fee' => 5000, 'grand_total' => 25000,
            'status' => Order::STATUS_PENDING, 'payment_status' => Order::PAYMENT_PENDING,
            'payment_method' => 'cash_on_delivery', 'delivery_address' => 'Test Street',
            'delivery_city' => 'Erbil', 'delivery_phone' => '07500000000',
        ])->save();

        return $order;
    }

    public function test_settings_manager_can_view_and_save_deduplicated_recipients(): void
    {
        $this->actingAs($this->manager())->get(route('admin.email-alerts.index'))->assertOk()->assertSee('Order &amp; System Alerts', false);
        $this->put(route('admin.email-alerts.update'), [
            'order_enabled' => 1, 'system_enabled' => 1, 'locale' => 'ar',
            'order_recipients' => "Owner@example.com\nowner@example.com, team@example.com",
            'system_recipients' => 'tech@example.com',
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(['owner@example.com', 'team@example.com'], app(AdminEmailAlertService::class)->recipients('order'));
        $this->assertSame('ar', Setting::getValue('admin_alert_locale'));
    }

    public function test_non_managers_cannot_read_change_test_or_retry_alerts(): void
    {
        $this->configure('order', ['owner@example.com']);
        app(AdminEmailAlertService::class)->test('order');
        $alert = AdminEmailAlert::first();
        $this->actingAs(User::factory()->create(['role' => User::ROLE_USER, 'email_verified_at' => now()]));
        $this->get(route('admin.email-alerts.index'))->assertForbidden();
        $this->put(route('admin.email-alerts.update'), [])->assertForbidden();
        $this->post(route('admin.email-alerts.test'), ['type' => 'order'])->assertForbidden();
        $this->post(route('admin.email-alerts.retry', $alert))->assertForbidden();
    }

    public function test_invalid_recipients_do_not_partially_save_settings(): void
    {
        $this->actingAs($this->manager())->put(route('admin.email-alerts.update'), [
            'order_enabled' => 1, 'system_enabled' => 1, 'locale' => 'en',
            'order_recipients' => 'valid@example.com', 'system_recipients' => 'not-an-email',
        ])->assertSessionHasErrors('system_recipients.0');
        $this->assertNull(Setting::getValue('admin_alert_order_recipients'));
    }

    public function test_enabled_groups_require_recipients_and_limit_list_size(): void
    {
        $this->actingAs($this->manager());
        $base = ['order_enabled' => 1, 'system_enabled' => 0, 'locale' => 'en', 'system_recipients' => ''];
        $this->put(route('admin.email-alerts.update'), $base + ['order_recipients' => ''])->assertSessionHasErrors('order_recipients');
        $this->put(route('admin.email-alerts.update'), $base + ['order_recipients' => implode(',', array_map(fn ($i) => "user{$i}@example.com", range(1, 51)))])->assertSessionHasErrors('order_recipients');
    }

    public function test_committed_order_queues_one_email_per_recipient_only_once(): void
    {
        Bus::fake();
        $this->configure('order', ['owner@example.com', 'team@example.com']);
        $order = DB::transaction(fn () => $this->order());
        app(AdminEmailAlertService::class)->orderPlaced($order);
        $this->assertDatabaseCount('admin_email_alerts', 2);
        Bus::assertDispatchedTimes(SendAdminEmailAlert::class, 2);
        $alert = AdminEmailAlert::first();
        $this->assertSame('queued', $alert->status);
        $this->assertSame(route('admin.orders.show', $order), $alert->context['action_url']);
    }

    public function test_rolled_back_order_does_not_send_alerts(): void
    {
        Bus::fake();
        $this->configure('order', ['owner@example.com']);
        try {
            DB::transaction(function () {
                $this->order();
                throw new RuntimeException('Rollback');
            });
        } catch (RuntimeException $e) {
            $this->assertSame('Rollback', $e->getMessage());
        }
        $this->assertDatabaseCount('admin_email_alerts', 0);
        Bus::assertNotDispatched(SendAdminEmailAlert::class);
    }

    public function test_disabled_alerts_do_not_send(): void
    {
        Bus::fake();
        DB::transaction(fn () => $this->order());
        app(AdminEmailAlertService::class)->systemError(new RuntimeException('Test'));
        $this->assertDatabaseCount('admin_email_alerts', 0);
        Bus::assertNotDispatched(SendAdminEmailAlert::class);
    }

    public function test_queue_failure_does_not_break_a_committed_order(): void
    {
        $this->configure('order', ['owner@example.com']);
        Bus::shouldReceive('dispatch')->once()->andThrow(new RuntimeException('Queue unavailable'));
        $order = DB::transaction(fn () => $this->order());
        $this->assertDatabaseHas('orders', ['id' => $order->id]);
        $this->assertDatabaseHas('admin_email_alerts', ['status' => 'failed', 'error_code' => 'RuntimeException']);
    }

    public function test_real_transport_acceptance_marks_alert_sent(): void
    {
        $this->configure('order', ['owner@example.com']);
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.transport' => 'smtp']);
        $pending = \Mockery::mock(PendingMail::class);
        $pending->shouldReceive('sendNow')->once()->andReturn(\Mockery::mock(SentMessage::class));
        Mail::shouldReceive('to')->once()->with('owner@example.com')->andReturn($pending);
        app(AdminEmailAlertService::class)->test('order');
        $this->assertSame('sent', AdminEmailAlert::first()->status);
        $this->assertNotNull(AdminEmailAlert::first()->sent_at);
    }

    public function test_test_email_records_each_result_and_simulated_transport(): void
    {
        $this->configure('order', ['owner@example.com', 'team@example.com']);
        $this->actingAs($this->manager())->post(route('admin.email-alerts.test'), ['type' => 'order'])->assertSessionHasNoErrors();
        $this->assertSame(2, AdminEmailAlert::where('status', 'simulated')->count());
        $this->assertCount(2, Mail::mailer('array')->getSymfonyTransport()->messages());
        $this->get(route('admin.email-alerts.index', ['status' => 'simulated']))->assertOk()->assertSee('owner@example.com')->assertSee('team@example.com');
    }

    public function test_mail_failure_is_recorded_for_each_recipient_without_exposing_secrets(): void
    {
        $this->configure('order', ['owner@example.com', 'team@example.com']);
        Mail::shouldReceive('to')->twice()->andThrow(new RuntimeException('secret-smtp-password'));
        app(AdminEmailAlertService::class)->test('order');
        $this->assertSame(2, AdminEmailAlert::where('status', 'failed')->count());
        $this->assertStringNotContainsString('secret-smtp-password', AdminEmailAlert::all()->toJson());
    }

    public function test_failed_message_can_be_retried_but_completed_messages_are_not_resent(): void
    {
        $this->configure('order', ['owner@example.com']);
        app(AdminEmailAlertService::class)->test('order');
        $alert = AdminEmailAlert::first();
        $alert->update(['status' => 'failed', 'error_code' => 'TransportException']);
        $this->actingAs($this->manager())->post(route('admin.email-alerts.retry', $alert))->assertRedirect();
        $this->assertSame('simulated', $alert->fresh()->status);
        $this->assertNull($alert->fresh()->error_code);
        $this->post(route('admin.email-alerts.retry', $alert))->assertRedirect();
        $this->assertCount(2, Mail::mailer('array')->getSymfonyTransport()->messages());
    }

    public function test_system_errors_are_throttled_and_do_not_expose_exception_content(): void
    {
        $this->configure('system', ['tech@example.com']);
        $error = new RuntimeException('secret-customer-data');
        app(AdminEmailAlertService::class)->systemError($error);
        app(AdminEmailAlertService::class)->systemError($error);
        $this->assertDatabaseCount('admin_email_alerts', 1);
        $this->assertSame('simulated', AdminEmailAlert::first()->status);
        $this->assertStringNotContainsString('secret-customer-data', AdminEmailAlert::first()->toJson());
        $this->travel(11)->minutes();
        app(AdminEmailAlertService::class)->systemError($error);
        $this->assertDatabaseCount('admin_email_alerts', 2);
    }

    public function test_reported_exceptions_use_the_configured_system_recipients(): void
    {
        $this->configure('system', ['tech@example.com']);
        report(new RuntimeException('Reported failure'));
        $this->assertDatabaseHas('admin_email_alerts', ['type' => 'system', 'recipient' => 'tech@example.com']);
    }

    public function test_failed_job_summary_uses_saved_recipients_and_delivery_history(): void
    {
        $this->configure('system', ['tech@example.com']);
        DB::table('failed_jobs')->insert([
            'uuid' => 'alert-job-test', 'connection' => 'database', 'queue' => 'mail',
            'payload' => json_encode(['displayName' => 'ExampleJob']), 'exception' => 'secret-exception', 'failed_at' => now(),
        ]);
        $this->artisan('queue:alert-failed')->assertSuccessful();
        $this->assertDatabaseHas('admin_email_alerts', ['recipient' => 'tech@example.com', 'status' => 'simulated']);
        $this->assertStringNotContainsString('secret-exception', AdminEmailAlert::first()->toJson());
    }

    public function test_all_email_languages_render_and_restore_application_locale(): void
    {
        $this->configure('order', ['owner@example.com']);
        foreach (['en', 'ar', 'ku'] as $locale) {
            Setting::setValue('admin_alert_locale', $locale);
            app()->setLocale('en');
            app(AdminEmailAlertService::class)->test('order');
            $this->assertSame('en', app()->getLocale());
            $this->assertSame('simulated', AdminEmailAlert::latest('id')->first()->status);
        }
    }
}
