<?php

use App\Support\ProductWarranty;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Warranty becomes a choice from a fixed list. Text already on file that
 * plainly names one of the periods ("2 months", "1 Year") is rewritten as its
 * code so it prints in every language; anything else is left exactly as typed
 * and keeps showing as it did.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'warranty')) {
            return;
        }

        $typed = DB::table('products')
            ->whereNotNull('warranty')
            ->where('warranty', '!=', '')
            ->distinct()
            ->pluck('warranty');

        foreach ($typed as $text) {
            $code = ProductWarranty::normalize((string) $text);

            if ($code !== null && $code !== $text) {
                DB::table('products')->where('warranty', $text)->update(['warranty' => $code]);
            }
        }
    }

    public function down(): void
    {
        // The typed text is not kept, and the codes read correctly either way.
    }
};
