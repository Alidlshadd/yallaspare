<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('welcome_email_sent_at')->nullable();
        });

        // Accounts verified before this existed have long since been
        // welcomed in person, so to speak. Without this every one of them
        // would get a "welcome" the next time their row is saved.
        DB::table('users')
            ->where(function ($query): void {
                $query->whereNotNull('email_verified_at')->orWhereNotNull('phone_verified_at');
            })
            ->update(['welcome_email_sent_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('welcome_email_sent_at'));
    }
};
