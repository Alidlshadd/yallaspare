<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@yallaspare.com'],
            [
                'name' => 'Supar Admin',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
            ]
        );
    }
}
//php artisan db:seed --class=AdminSeeder
