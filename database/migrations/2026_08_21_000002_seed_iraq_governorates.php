<?php

use Database\Seeders\GovernorateSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * The list arrives with the schema.
 *
 * Leaving it to `db:seed` meant a deploy that ran migrations still opened the
 * page on an empty table, which is exactly what happened the first time. The
 * seeder stays — it is still the single source of the list, and this only
 * calls it — so there is nothing to keep in step.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('governorates')) {
            return;
        }

        (new GovernorateSeeder)->run();
    }

    public function down(): void
    {
        // The rows belong to the table, and dropping the table takes them.
    }
};
