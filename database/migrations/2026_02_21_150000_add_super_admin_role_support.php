<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $roleSuperAdmin = 'super_admin';
        $roleAdmin = 'admin';
        $roleDealer = 'dealer';
        $roleUser = 'user';
        $allowedRoles = [$roleSuperAdmin, $roleAdmin, $roleDealer, $roleUser];

        DB::table('users')->where('role', 'superadmin')->update(['role' => $roleSuperAdmin]);
        DB::table('users')->where('role', 'super-admin')->update(['role' => $roleSuperAdmin]);

        DB::table('users')->where('role', 'customer')->update(['role' => $roleUser]);
        DB::table('users')->where('role', 'manager')->update(['role' => $roleDealer]);
        DB::table('users')->where('role', 'administrator')->update(['role' => $roleAdmin]);

        DB::table('users')
            ->where(function ($query) {
                $query->whereNull('role')->orWhere('role', '');
            })
            ->where('is_admin', true)
            ->update(['role' => $roleAdmin]);

        DB::table('users')
            ->where(function ($query) {
                $query->whereNull('role')->orWhere('role', '');
            })
            ->where('is_admin', false)
            ->update(['role' => $roleUser]);

        DB::table('users')
            ->whereNotIn('role', $allowedRoles)
            ->update(['role' => $roleUser]);

        DB::table('users')
            ->whereIn('role', [$roleSuperAdmin, $roleAdmin])
            ->update(['is_admin' => true]);

        DB::table('users')
            ->whereNotIn('role', [$roleSuperAdmin, $roleAdmin])
            ->update(['is_admin' => false]);

        $superAdminExists = DB::table('users')->where('role', $roleSuperAdmin)->exists();

        if (!$superAdminExists) {
            $candidate = DB::table('users')
                ->whereIn('role', [$roleAdmin, $roleSuperAdmin])
                ->orderBy('id')
                ->first();

            if ($candidate) {
                DB::table('users')
                    ->where('id', $candidate->id)
                    ->update([
                        'role' => $roleSuperAdmin,
                        'is_admin' => true,
                    ]);
            }
        }
    }

    public function down(): void
    {
        $roleSuperAdmin = 'super_admin';
        $roleAdmin = 'admin';

        DB::table('users')->where('role', $roleSuperAdmin)->update(['role' => $roleAdmin]);

        DB::table('users')
            ->whereIn('role', [$roleAdmin])
            ->update(['is_admin' => true]);

        DB::table('users')
            ->whereNotIn('role', [$roleAdmin])
            ->update(['is_admin' => false]);
    }
};
