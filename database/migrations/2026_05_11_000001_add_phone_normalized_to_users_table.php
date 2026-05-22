<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'phone_normalized')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('phone_normalized', 40)->nullable()->after('phone');
                $table->unique('phone_normalized', 'users_phone_normalized_unique');
            });
        }

        $seenPhones = [];

        DB::table('users')
            ->select(['id', 'phone'])
            ->whereNotNull('phone')
            ->orderBy('id')
            ->chunkById(200, function ($users) use (&$seenPhones): void {
                foreach ($users as $user) {
                    $normalized = User::normalizePhone($user->phone);

                    if ($normalized === null || isset($seenPhones[$normalized])) {
                        DB::table('users')
                            ->where('id', $user->id)
                            ->update(['phone_normalized' => null]);
                        continue;
                    }

                    $seenPhones[$normalized] = true;

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['phone_normalized' => $normalized]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'phone_normalized')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropUnique('users_phone_normalized_unique');
                $table->dropColumn('phone_normalized');
            });
        }
    }
};
