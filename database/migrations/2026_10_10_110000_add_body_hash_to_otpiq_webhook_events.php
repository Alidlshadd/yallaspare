<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A fingerprint of the signed request body, unique per event.
 *
 * OTPiQ signs the timestamp and the body. The event id travels in a header
 * the signature does not cover, so a captured request could be sent again
 * under a new id and be stored as a new event. The body is covered, so its
 * hash is an identity nobody without the secret can change, and a unique
 * index makes the second arrival of the same body a duplicate — including
 * two arriving at the same instant.
 *
 * Rows from before this column have no hash; the request body was not kept
 * byte for byte, so it cannot be worked out afterwards.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('otpiq_webhook_events', 'body_hash')) {
            return;
        }

        Schema::table('otpiq_webhook_events', function (Blueprint $table): void {
            $table->char('body_hash', 64)->nullable()->after('event_id');
            $table->unique('body_hash', 'otpiq_events_body_hash_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('otpiq_webhook_events', 'body_hash')) {
            return;
        }

        Schema::table('otpiq_webhook_events', function (Blueprint $table): void {
            $table->dropUnique('otpiq_events_body_hash_unique');
            $table->dropColumn('body_hash');
        });
    }
};
