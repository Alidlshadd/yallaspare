<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * An account can exist before its owner has chosen how to sign in.
 *
 * Checkout no longer asks a visitor to register: they give a phone number,
 * prove it with a code, and the account is made for them. At that moment
 * there is no email address and no password — and writing a placeholder into
 * either would be a lie the rest of the application would have to live with.
 * So both columns hold NULL, and NULL means exactly one thing:
 *
 *   password IS NULL  — nobody has ever chosen credentials for this account.
 *
 * That is what lets a returning guest check out again on the same number
 * (another code to the same phone is no weaker than the first) while an
 * account whose owner has since set a password is sent to the sign-in page
 * instead. A unique index still forbids two accounts sharing an address;
 * NULLs are not equal to each other, so any number of them may coexist.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->setNullable(true);
    }

    public function down(): void
    {
        // Accounts that never got credentials cannot survive columns that
        // will not hold them. Their orders go with them, as the foreign key
        // on orders.user_id has always said.
        DB::table('users')->whereNull('email')->orWhereNull('password')->delete();

        $this->setNullable(false);
    }

    private function setNullable(bool $nullable): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) use ($nullable): void {
            $table->string('email')->nullable($nullable)->change();
            $table->string('password')->nullable($nullable)->change();
        });
    }
};
