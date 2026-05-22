<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            if (! Schema::hasColumn('products', 'slug')) {
                $table->string('slug')->nullable()->after('name_en');
            }
        });

        DB::table('products')
            ->select(['id', 'name_en', 'slug'])
            ->orderBy('id')
            ->chunkById(200, function ($products): void {
                foreach ($products as $product) {
                    $currentSlug = trim((string) ($product->slug ?? ''));
                    if ($currentSlug !== '') {
                        continue;
                    }

                    $baseSlug = Str::slug((string) $product->name_en);
                    if ($baseSlug === '') {
                        $baseSlug = 'product';
                    }

                    $slug = $baseSlug;
                    $suffix = 2;
                    while (
                        DB::table('products')
                            ->where('slug', $slug)
                            ->where('id', '!=', $product->id)
                            ->exists()
                    ) {
                        $slug = $baseSlug . '-' . $suffix;
                        $suffix++;
                    }

                    DB::table('products')
                        ->where('id', $product->id)
                        ->update(['slug' => $slug]);
                }
            });

        if (! $this->hasIndex('products', 'products_slug_unique')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->unique('slug');
            });
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            if (Schema::hasColumn('products', 'slug')) {
                if ($this->hasIndex('products', 'products_slug_unique')) {
                    $table->dropUnique('products_slug_unique');
                }
                $table->dropColumn('slug');
            }
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        if ($connection->getDriverName() === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");

            foreach ($indexes as $row) {
                if (($row->name ?? null) === $index) {
                    return true;
                }
            }

            return false;
        }

        $database = $connection->getDatabaseName();

        $result = $connection->selectOne(
            'SELECT COUNT(*) AS aggregate FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $index]
        );

        return ((int) ($result->aggregate ?? 0)) > 0;
    }
};
