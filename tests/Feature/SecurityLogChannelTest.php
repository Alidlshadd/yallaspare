<?php

namespace Tests\Feature;

use App\Exceptions\Handler;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Mockery;
use Mockery\LegacyMockInterface;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

class SecurityLogChannelTest extends TestCase
{
    use RefreshDatabase;

    private LegacyMockInterface $securityLog;

    protected function setUp(): void
    {
        parent::setUp();

        $this->securityLog = Mockery::spy(LoggerInterface::class);
        $otherLog = Mockery::spy(LoggerInterface::class);

        Log::shouldReceive('channel')
            ->andReturnUsing(function (string $channel) use ($otherLog) {
                return $channel === 'security' ? $this->securityLog : $otherLog;
            });
        Log::shouldIgnoreMissing();
    }

    public function test_failed_login_writes_security_entry(): void
    {
        // Failed event only fires when Auth::attempt actually runs, which
        // requires a matching user row (LoginRequest short-circuits otherwise).
        User::factory()->create([
            'email' => 'known@example.com',
            'email_verified_at' => now(),
        ]);

        $this->post(route('login'), [
            'email' => 'known@example.com',
            'password' => 'wrong-password-YallaTest2026',
        ]);

        $this->assertLoggedSecurity('auth.failed', 'warning', function (array $ctx) {
            return ($ctx['email'] ?? null) === 'known@example.com'
                && isset($ctx['ip'])
                && isset($ctx['guard']);
        });
    }

    public function test_user_2fa_wrong_code_writes_security_entry(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'two_factor_preference' => 'email',
        ]);

        $this->actingAs($user)
            ->post(route('user.two-factor.verify'), ['code' => '000000']);

        $this->assertLoggedSecurity('auth.2fa_failed', 'warning', function (array $ctx) use ($user) {
            return ($ctx['guard'] ?? null) === 'user'
                && ($ctx['user_id'] ?? null) === $user->id;
        });
    }

    public function test_user_2fa_locked_attempt_writes_security_entry(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'two_factor_preference' => 'email',
        ]);

        $key = 'user-2fa:' . $user->id . '|127.0.0.1';
        for ($i = 0; $i < 5; $i++) {
            RateLimiter::hit($key, 300);
        }

        $this->actingAs($user)
            ->post(route('user.two-factor.verify'), ['code' => '000000']);

        $this->assertLoggedSecurity('auth.2fa_locked_attempt', 'warning', function (array $ctx) use ($user) {
            return ($ctx['guard'] ?? null) === 'user'
                && ($ctx['user_id'] ?? null) === $user->id;
        });

        RateLimiter::clear($key);
    }

    public function test_admin_2fa_wrong_code_writes_security_entry(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.two-factor.verify'), ['code' => '000000']);

        $this->assertLoggedSecurity('auth.2fa_failed', 'warning', function (array $ctx) use ($admin) {
            return ($ctx['guard'] ?? null) === 'admin'
                && ($ctx['user_id'] ?? null) === $admin->id;
        });
    }

    public function test_admin_2fa_locked_attempt_writes_security_entry(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);

        $key = 'admin-2fa:' . $admin->id . '|127.0.0.1';
        for ($i = 0; $i < 5; $i++) {
            RateLimiter::hit($key, 300);
        }

        $this->actingAs($admin)
            ->post(route('admin.two-factor.verify'), ['code' => '000000']);

        $this->assertLoggedSecurity('auth.2fa_locked_attempt', 'warning', function (array $ctx) use ($admin) {
            return ($ctx['guard'] ?? null) === 'admin'
                && ($ctx['user_id'] ?? null) === $admin->id;
        });

        RateLimiter::clear($key);
    }

    public function test_authenticated_403_writes_security_entry(): void
    {
        $customer = User::factory()->create([
            'role' => User::ROLE_USER,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($customer)->get('/admin/dashboard');

        $this->assertLoggedSecurity('authz.forbidden', 'notice', function (array $ctx) use ($customer) {
            return ($ctx['user_id'] ?? null) === $customer->id;
        });
    }

    public function test_scoped_404_writes_security_entry(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/account/nonexistent-route-abc123')
            ->assertNotFound();

        $this->assertLoggedSecurity('authz.not_found', 'notice', function (array $ctx) use ($user) {
            return ($ctx['user_id'] ?? null) === $user->id;
        });
    }

    public function test_unscoped_404_does_not_write_security_entry(): void
    {
        $this->get('/nonexistent-shop-page-xyz-999')->assertNotFound();

        $this->securityLog->shouldNotHaveReceived('notice');
        $this->securityLog->shouldNotHaveReceived('warning');
    }

    public function test_throttle_exceeded_writes_security_entry(): void
    {
        $handler = app(Handler::class);
        $request = Request::create('/', 'GET', server: [
            'REMOTE_ADDR' => '10.20.30.40',
            'HTTP_USER_AGENT' => 'phpunit-throttle-test',
        ]);

        $handler->render($request, new ThrottleRequestsException('rate exceeded'));

        $this->assertLoggedSecurity('throttle.exceeded', 'warning', function (array $ctx) {
            return ($ctx['ip'] ?? null) === '10.20.30.40';
        });
    }

    private function assertLoggedSecurity(string $event, string $level, callable $contextMatcher): void
    {
        $this->securityLog->shouldHaveReceived($level)
            ->with('security event', Mockery::on(function ($context) use ($event, $contextMatcher) {
                return is_array($context)
                    && ($context['event'] ?? null) === $event
                    && $contextMatcher($context);
            }));
    }
}
