<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Validation\Rules\Password;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Covers the seven live call sites that share Password::defaults(). None of them
 * were edited for this feature, so these tests double as proof that routing the
 * verifier through the container reaches all of them.
 */
class HibpPasswordFlowTest extends TestCase
{
    use RefreshDatabase;

    private const GOOD = 'Quarry7Lantern4Bridge';

    private const BAD = 'Compromised4Password9';

    /** @var list<MessageLogged> */
    private array $logged = [];

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Http::preventStrayRequests();
        config(['logging.channels.security' => ['driver' => 'null']]);

        // The suite runs as 'testing', where Password::defaults() deliberately
        // drops uncompromised(). Re-apply the production shape so these flows
        // exercise the verifier the way production does.
        Password::defaults(fn () => Password::min(10)->letters()->numbers()->uncompromised());

        $this->logged = [];
        Event::listen(MessageLogged::class, function (MessageLogged $event): void {
            $this->logged[] = $event;
        });
    }

    private function suffixOf(string $password): string
    {
        return substr(strtoupper(sha1($password)), 5);
    }

    /**
     * HIBP reports the given password as breached; everything else is inert.
     */
    private function fakeCompromised(string $password): void
    {
        Http::fake([
            'api.pwnedpasswords.com/*' => Http::response($this->suffixOf($password).":1337\n", 200),
            '*' => Http::response('', 200),
        ]);
    }

    private function fakeClean(): void
    {
        Http::fake([
            'api.pwnedpasswords.com/*' => Http::response("FFFF1111222233334444555566667777888:9\n", 200),
            '*' => Http::response('', 200),
        ]);
    }

    private function fakeHibpDown(): void
    {
        Http::fake([
            'api.pwnedpasswords.com/*' => function (): never {
                throw new ConnectionException('cURL error 28: Operation timed out');
            },
            '*' => Http::response('', 200),
        ]);
    }

    /** @return list<array<string, mixed>> */
    private function securityEvents(): array
    {
        $events = [];

        foreach ($this->logged as $entry) {
            if (($entry->context['event'] ?? null) === 'auth.hibp_unavailable') {
                $events[] = $entry->context;
            }
        }

        return $events;
    }

    private function assertNoSecretsLogged(string ...$passwords): void
    {
        $serialised = json_encode(array_map(
            fn (MessageLogged $e): array => ['message' => $e->message, 'context' => $e->context],
            $this->logged
        ), JSON_THROW_ON_ERROR);

        foreach ($passwords as $password) {
            $hash = strtoupper(sha1($password));

            foreach ([$password, $hash, substr($hash, 0, 5), substr($hash, 5)] as $needle) {
                $this->assertStringNotContainsString($needle, $serialised);
            }
        }
    }

    private function verifiedUser(array $attributes = []): User
    {
        return User::factory()->create($attributes + [
            'password' => Hash::make(self::GOOD),
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);
    }

    // ---------------------------------------------------------------- register

    public function test_web_registration_rejects_a_compromised_password(): void
    {
        $this->fakeCompromised(self::BAD);

        $this->post('/register', [
            'name' => 'Probe User',
            'email' => 'probe-register@example.com',
            'country_code' => '+964',
            'phone' => '07700000101',
            'password' => self::BAD,
            'password_confirmation' => self::BAD,
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'probe-register@example.com']);
    }

    public function test_web_registration_still_works_when_hibp_is_down(): void
    {
        $this->fakeHibpDown();

        $this->post('/register', [
            'name' => 'Probe User',
            'email' => 'probe-register-down@example.com',
            'country_code' => '+964',
            'phone' => '07700000102',
            'password' => self::GOOD,
            'password_confirmation' => self::GOOD,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'probe-register-down@example.com']);

        $events = $this->securityEvents();
        $this->assertCount(1, $events);
        $this->assertSame('register', $events[0]['flow']);
        $this->assertSame('timeout', $events[0]['failure_category']);
        $this->assertNoSecretsLogged(self::GOOD);
    }

    public function test_mobile_registration_rejects_a_compromised_password(): void
    {
        $this->fakeCompromised(self::BAD);

        $this->postJson('/api/mobile/register', [
            'name' => 'Probe User',
            'email' => 'probe-mobile@example.com',
            'phone' => '07700000103',
            'password' => self::BAD,
        ])->assertStatus(422)->assertJsonValidationErrors('password');
    }

    public function test_mobile_registration_still_works_when_hibp_is_down(): void
    {
        $this->fakeHibpDown();

        $this->postJson('/api/mobile/register', [
            'name' => 'Probe User',
            'email' => 'probe-mobile-down@example.com',
            'phone' => '07700000104',
            'password' => self::GOOD,
        ])->assertStatus(201);

        $this->assertSame('mobile_register', $this->securityEvents()[0]['flow']);
    }

    // ------------------------------------------------------------------- reset

    public function test_password_reset_rejects_a_compromised_password(): void
    {
        $user = $this->verifiedUser(['email' => 'probe-reset@example.com']);
        $token = PasswordBroker::createToken($user);

        $this->fakeCompromised(self::BAD);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => self::BAD,
            'password_confirmation' => self::BAD,
        ])->assertSessionHasErrors('password');
    }

    public function test_password_reset_still_works_when_hibp_is_down(): void
    {
        $user = $this->verifiedUser(['email' => 'probe-reset-down@example.com']);
        $token = PasswordBroker::createToken($user);
        $new = 'Sandpiper3Meadow8';

        $this->fakeHibpDown();

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => $new,
            'password_confirmation' => $new,
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check($new, $user->fresh()->password));
        $this->assertSame('reset', $this->securityEvents()[0]['flow']);
        $this->assertNoSecretsLogged($new);
    }

    // ------------------------------------------------------- authenticated web

    public function test_web_password_change_rejects_a_compromised_password(): void
    {
        $user = $this->verifiedUser();
        $this->fakeCompromised(self::BAD);

        $this->actingAs($user)->put('/password', [
            'current_password' => self::GOOD,
            'password' => self::BAD,
            'password_confirmation' => self::BAD,
        ])->assertSessionHasErrors('password', null, 'updatePassword');

        $this->assertTrue(Hash::check(self::GOOD, $user->fresh()->password));
    }

    public function test_web_password_change_still_works_when_hibp_is_down(): void
    {
        $user = $this->verifiedUser();
        $new = 'Trellis5Harbour2';
        $this->fakeHibpDown();

        $this->actingAs($user)->put('/password', [
            'current_password' => self::GOOD,
            'password' => $new,
            'password_confirmation' => $new,
        ]);

        $this->assertTrue(Hash::check($new, $user->fresh()->password));
        $this->assertSame('change', $this->securityEvents()[0]['flow']);
    }

    public function test_account_password_change_rejects_a_compromised_password(): void
    {
        $user = $this->verifiedUser();
        $this->fakeCompromised(self::BAD);

        $this->actingAs($user)->patch('/user/account/password', [
            'current_password' => self::GOOD,
            'password' => self::BAD,
            'password_confirmation' => self::BAD,
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check(self::GOOD, $user->fresh()->password));
    }

    public function test_account_password_change_reports_its_own_flow_label(): void
    {
        $user = $this->verifiedUser();
        $new = 'Kestrel6Willow3';
        $this->fakeHibpDown();

        $this->actingAs($user)->patch('/user/account/password', [
            'current_password' => self::GOOD,
            'password' => $new,
            'password_confirmation' => $new,
        ]);

        $this->assertSame('account_change', $this->securityEvents()[0]['flow']);
    }

    // ---------------------------------------------------------------- mobile

    public function test_mobile_password_change_rejects_a_compromised_password(): void
    {
        $user = $this->verifiedUser();
        Sanctum::actingAs($user, ['mobile:read', 'mobile:write']);

        $this->fakeCompromised(self::BAD);

        $this->patchJson('/api/mobile/profile/password', [
            'current_password' => self::GOOD,
            'password' => self::BAD,
            'password_confirmation' => self::BAD,
        ])->assertStatus(422)->assertJsonValidationErrors('password');
    }

    public function test_mobile_password_change_still_works_when_hibp_is_down(): void
    {
        $user = $this->verifiedUser();
        Sanctum::actingAs($user, ['mobile:read', 'mobile:write']);
        $new = 'Foxglove8Anchor4';

        $this->fakeHibpDown();

        $this->patchJson('/api/mobile/profile/password', [
            'current_password' => self::GOOD,
            'password' => $new,
            'password_confirmation' => $new,
        ])->assertOk();

        $this->assertSame('mobile_change', $this->securityEvents()[0]['flow']);
        $this->assertNoSecretsLogged($new, self::GOOD);
    }

    // ----------------------------------------------------------------- admin

    public function test_admin_password_action_rejects_a_compromised_password(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);
        $target = $this->verifiedUser();

        $this->fakeCompromised(self::BAD);

        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->patch("/admin/users/{$target->id}/password", [
                'password' => self::BAD,
                'password_confirmation' => self::BAD,
            ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check(self::GOOD, $target->fresh()->password));
    }

    public function test_admin_password_action_still_works_when_hibp_is_down(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);
        $target = $this->verifiedUser();
        $new = 'Cobblestone2Ridge7';

        $this->fakeHibpDown();

        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->patch("/admin/users/{$target->id}/password", [
                'password' => $new,
                'password_confirmation' => $new,
            ]);

        $this->assertTrue(Hash::check($new, $target->fresh()->password));
        $this->assertSame('admin_set', $this->securityEvents()[0]['flow']);
        $this->assertNoSecretsLogged($new);
    }

    // ------------------------------------------------------------------ misc

    public function test_a_clean_password_produces_no_availability_noise(): void
    {
        $this->fakeClean();

        $this->post('/register', [
            'name' => 'Probe User',
            'email' => 'probe-clean@example.com',
            'country_code' => '+964',
            'phone' => '07700000105',
            'password' => self::GOOD,
            'password_confirmation' => self::GOOD,
        ])->assertSessionHasNoErrors();

        $this->assertSame([], $this->securityEvents());
    }

    public function test_user_never_sees_an_hibp_error_message(): void
    {
        $this->fakeHibpDown();

        $response = $this->post('/register', [
            'name' => 'Probe User',
            'email' => 'probe-silent@example.com',
            'country_code' => '+964',
            'phone' => '07700000106',
            'password' => self::GOOD,
            'password_confirmation' => self::GOOD,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertStringNotContainsStringIgnoringCase('hibp', $response->getContent());
        $this->assertStringNotContainsStringIgnoringCase('pwned', $response->getContent());
    }
}
