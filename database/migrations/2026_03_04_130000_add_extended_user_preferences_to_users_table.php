<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'two_factor_preference')) {
                $table->string('two_factor_preference', 20)->default('off')->after('notify_stock_alerts');
            }

            if (! Schema::hasColumn('users', 'login_alerts')) {
                $table->boolean('login_alerts')->default(true)->after('two_factor_preference');
            }

            if (! Schema::hasColumn('users', 'session_timeout')) {
                $table->string('session_timeout', 10)->default('30')->after('login_alerts');
            }

            if (! Schema::hasColumn('users', 'email_notifications')) {
                $table->boolean('email_notifications')->default(true)->after('session_timeout');
            }

            if (! Schema::hasColumn('users', 'sms_notifications')) {
                $table->boolean('sms_notifications')->default(false)->after('email_notifications');
            }

            if (! Schema::hasColumn('users', 'whatsapp_notifications')) {
                $table->boolean('whatsapp_notifications')->default(false)->after('sms_notifications');
            }

            if (! Schema::hasColumn('users', 'marketing_consent')) {
                $table->boolean('marketing_consent')->default(false)->after('whatsapp_notifications');
            }

            if (! Schema::hasColumn('users', 'currency_preference')) {
                $table->string('currency_preference', 10)->default('USD')->after('marketing_consent');
            }

            if (! Schema::hasColumn('users', 'timezone_preference')) {
                $table->string('timezone_preference', 64)->default('Asia/Baghdad')->after('currency_preference');
            }

            if (! Schema::hasColumn('users', 'date_format_preference')) {
                $table->string('date_format_preference', 20)->default('dmy')->after('timezone_preference');
            }

            if (! Schema::hasColumn('users', 'default_contact_method')) {
                $table->string('default_contact_method', 20)->default('phone')->after('date_format_preference');
            }

            if (! Schema::hasColumn('users', 'default_delivery_note')) {
                $table->string('default_delivery_note', 255)->nullable()->after('default_contact_method');
            }

            if (! Schema::hasColumn('users', 'express_checkout')) {
                $table->boolean('express_checkout')->default(false)->after('default_delivery_note');
            }

            if (! Schema::hasColumn('users', 'font_size_preference')) {
                $table->string('font_size_preference', 20)->default('default')->after('express_checkout');
            }

            if (! Schema::hasColumn('users', 'reduced_motion')) {
                $table->boolean('reduced_motion')->default(false)->after('font_size_preference');
            }

            if (! Schema::hasColumn('users', 'high_contrast_mode')) {
                $table->boolean('high_contrast_mode')->default(false)->after('reduced_motion');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [
                'two_factor_preference',
                'login_alerts',
                'session_timeout',
                'email_notifications',
                'sms_notifications',
                'whatsapp_notifications',
                'marketing_consent',
                'currency_preference',
                'timezone_preference',
                'date_format_preference',
                'default_contact_method',
                'default_delivery_note',
                'express_checkout',
                'font_size_preference',
                'reduced_motion',
                'high_contrast_mode',
            ];

            $existing = array_values(array_filter($columns, static fn (string $column): bool => Schema::hasColumn('users', $column)));

            if ($existing !== []) {
                $table->dropColumn($existing);
            }
        });
    }
};
