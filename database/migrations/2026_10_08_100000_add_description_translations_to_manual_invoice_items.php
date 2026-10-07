<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A catalogue line's name in all three languages, kept on the line.
     *
     * An invoice line used to hold one description, in whichever language
     * the admin's screen was in when the product was picked — so an invoice
     * printed in Kurdish still named its parts in English. The line now
     * carries the product's three names, copied at the time like everything
     * else on it, and the document prints the one for its language.
     *
     * Lines already on file are filled in from the catalogue where their
     * description is still exactly one of the product's names. A line whose
     * text was typed or edited by hand is left without translations and
     * prints as written in every language.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('manual_invoice_items', 'description_translations')) {
            Schema::table('manual_invoice_items', function (Blueprint $table): void {
                $table->json('description_translations')->nullable();
            });
        }

        DB::table('manual_invoice_items')
            ->join('products', 'products.id', '=', 'manual_invoice_items.product_id')
            ->whereNull('manual_invoice_items.description_translations')
            ->orderBy('manual_invoice_items.id')
            ->select([
                'manual_invoice_items.id',
                'manual_invoice_items.description',
                'products.name_en',
                'products.name_ar',
                'products.name_ku',
            ])
            ->chunkById(200, function ($lines): void {
                foreach ($lines as $line) {
                    $names = array_filter([
                        'en' => trim((string) $line->name_en),
                        'ar' => trim((string) $line->name_ar),
                        'ku' => trim((string) $line->name_ku),
                    ], fn (string $name): bool => $name !== '');

                    if (! in_array(trim((string) $line->description), $names, true)) {
                        continue;
                    }

                    DB::table('manual_invoice_items')->where('id', $line->id)->update([
                        'description_translations' => json_encode($names, JSON_UNESCAPED_UNICODE),
                    ]);
                }
            }, 'manual_invoice_items.id', 'id');
    }

    public function down(): void
    {
        if (Schema::hasColumn('manual_invoice_items', 'description_translations')) {
            Schema::table('manual_invoice_items', function (Blueprint $table): void {
                $table->dropColumn('description_translations');
            });
        }
    }
};
