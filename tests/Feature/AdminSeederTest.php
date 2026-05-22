<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class AdminSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        $this->clearAdminEnv();

        parent::tearDown();
    }

    public function test_admin_seeder_keeps_local_default_outside_production(): void
    {
        $this->runAdminSeeder();

        $admin = User::query()->where('email', 'admin@yallaspare.com')->first();

        $this->assertNotNull($admin);
        $this->assertSame(User::ROLE_SUPER_ADMIN, $admin->role);
        $this->assertTrue(Hash::check('password', $admin->password));
    }

    public function test_admin_seeder_skips_production_without_explicit_password(): void
    {
        $this->app['env'] = 'production';

        $this->runAdminSeeder();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_admin_seeder_rejects_weak_production_password(): void
    {
        $this->app['env'] = 'production';
        $this->setAdminEnv('ADMIN_PASSWORD', 'password');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ADMIN_PASSWORD must be at least 12 characters');

        $this->runAdminSeeder();
    }

    public function test_admin_seeder_creates_production_admin_with_explicit_strong_password(): void
    {
        $this->app['env'] = 'production';
        $this->setAdminEnv('ADMIN_EMAIL', 'owner@example.test');
        $this->setAdminEnv('ADMIN_NAME', 'Owner Admin');
        $this->setAdminEnv('ADMIN_PASSWORD', 'Use-A-Long-Secret-2026');

        $this->runAdminSeeder();

        $admin = User::query()->where('email', 'owner@example.test')->first();

        $this->assertNotNull($admin);
        $this->assertSame('Owner Admin', $admin->name);
        $this->assertSame(User::ROLE_SUPER_ADMIN, $admin->role);
        $this->assertTrue(Hash::check('Use-A-Long-Secret-2026', $admin->password));
    }

    private function setAdminEnv(string $key, string $value): void
    {
        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }

    private function runAdminSeeder(): void
    {
        $this->app->make(AdminSeeder::class)->run();
    }

    private function clearAdminEnv(): void
    {
        foreach (['ADMIN_NAME', 'ADMIN_EMAIL', 'ADMIN_PASSWORD'] as $key) {
            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);
        }
    }
}
