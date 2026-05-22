<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = trim((string) env('ADMIN_EMAIL', 'admin@yallaspare.com'));
        $name = trim((string) env('ADMIN_NAME', 'Super Admin'));
        $password = (string) env('ADMIN_PASSWORD', '');
        $isProduction = app()->environment('production');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('ADMIN_EMAIL must be a valid email address.');
        }

        if ($password === '') {
            if ($isProduction) {
                $this->command?->warn('AdminSeeder skipped: set ADMIN_PASSWORD to create the initial production admin.');
                return;
            }

            $password = 'password';
        }

        if ($isProduction && $this->isWeakProductionPassword($password)) {
            throw new RuntimeException('ADMIN_PASSWORD must be at least 12 characters and must not be a common default password.');
        }

        $user = User::query()->firstOrNew(['email' => $email]);

        if ($isProduction && $user->exists) {
            $this->command?->info('AdminSeeder skipped: production admin already exists.');
            return;
        }

        $user->forceFill([
            'name' => $name !== '' ? $name : 'Super Admin',
            'password' => Hash::make($password),
            'role' => User::ROLE_SUPER_ADMIN,
        ])->save();
    }

    private function isWeakProductionPassword(string $password): bool
    {
        $commonPasswords = [
            'password',
            'admin',
            'admin123',
            'admin123456',
            '12345678',
            '123456789',
            '1234567890',
            'yallaspare',
        ];

        return strlen($password) < 12 || in_array(strtolower($password), $commonPasswords, true);
    }
}
