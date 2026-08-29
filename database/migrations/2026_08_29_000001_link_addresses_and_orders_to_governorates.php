<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Spellings customers typed into the old free-text city field, mapped to
     * the governorate code they meant. Only used to rescue rows written
     * before the field existed; new addresses pick from a list.
     *
     * @var array<string, string>
     */
    private const CITY_ALIASES = [
        'bagdad' => 'baghdad',
        'bagdat' => 'baghdad',
        'baghdaad' => 'baghdad',
        'arbil' => 'erbil',
        'irbil' => 'erbil',
        'hawler' => 'erbil',
        'hewler' => 'erbil',
        'erbil city' => 'erbil',
        'slemani' => 'sulaymaniyah',
        'sulaimani' => 'sulaymaniyah',
        'sulaimaniya' => 'sulaymaniyah',
        'sulaimaniyah' => 'sulaymaniyah',
        'suleymaniye' => 'sulaymaniyah',
        'duhok' => 'dohuk',
        'dahuk' => 'dohuk',
        'basra' => 'basrah',
        'basara' => 'basrah',
        'mosul' => 'ninawa',
        'nineveh' => 'ninawa',
        'ninevah' => 'ninawa',
        'najaf city' => 'najaf',
        'tikrit' => 'salah-ad-din',
        'samarra' => 'salah-ad-din',
        'ramadi' => 'anbar',
        'fallujah' => 'anbar',
        'hilla' => 'babil',
        'hillah' => 'babil',
        'kut' => 'wasit',
        'amara' => 'maysan',
        'nasiriyah' => 'dhi-qar',
        'diwaniyah' => 'qadisiyyah',
        'samawah' => 'muthanna',
        'baquba' => 'diyala',
    ];

    public function up(): void
    {
        Schema::table('user_addresses', function (Blueprint $table): void {
            $table->foreignId('governorate_id')
                ->nullable()
                ->after('country')
                ->constrained()
                ->nullOnDelete();
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('governorate_id')
                ->nullable()
                ->after('delivery_city')
                ->constrained()
                ->nullOnDelete();

            // The governorate an operator added by hand can be renamed or
            // removed later. An invoice may not change under the customer, so
            // the name and the promise it carried are copied onto the order.
            $table->string('delivery_governorate', 64)->nullable()->after('governorate_id');
            $table->unsignedTinyInteger('delivery_days')->nullable()->after('delivery_governorate');
        });

        $this->backfillFromCityNames();
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropForeign(['governorate_id']);
            $table->dropColumn(['governorate_id', 'delivery_governorate', 'delivery_days']);
        });

        Schema::table('user_addresses', function (Blueprint $table): void {
            $table->dropForeign(['governorate_id']);
            $table->dropColumn('governorate_id');
        });
    }

    /**
     * Point existing rows at a governorate where the free-text city says
     * plainly which one it is. A city that cannot be matched is left alone:
     * shipping falls back to the flat fee, exactly as it did before.
     */
    private function backfillFromCityNames(): void
    {
        if (! Schema::hasTable('governorates')) {
            return;
        }

        $byCode = [];
        $lookup = [];

        foreach (DB::table('governorates')->get(['id', 'code', 'name_en', 'name_ar', 'name_ku']) as $governorate) {
            $byCode[(string) $governorate->code] = (int) $governorate->id;

            foreach ([$governorate->code, $governorate->name_en, $governorate->name_ar, $governorate->name_ku] as $name) {
                $key = $this->normalize((string) $name);

                if ($key !== '') {
                    $lookup[$key] = (int) $governorate->id;
                }
            }
        }

        foreach (self::CITY_ALIASES as $alias => $code) {
            if (isset($byCode[$code])) {
                $lookup[$this->normalize($alias)] = $byCode[$code];
            }
        }

        if ($lookup === []) {
            return;
        }

        $this->matchColumn('user_addresses', 'city', $lookup);
        $this->matchColumn('orders', 'delivery_city', $lookup);
    }

    /**
     * @param  array<string, int>  $lookup
     */
    private function matchColumn(string $table, string $cityColumn, array $lookup): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $cities = DB::table($table)
            ->whereNull('governorate_id')
            ->whereNotNull($cityColumn)
            ->distinct()
            ->pluck($cityColumn);

        foreach ($cities as $city) {
            $governorateId = $lookup[$this->normalize((string) $city)] ?? null;

            if ($governorateId === null) {
                continue;
            }

            DB::table($table)
                ->whereNull('governorate_id')
                ->where($cityColumn, $city)
                ->update(['governorate_id' => $governorateId]);
        }
    }

    private function normalize(string $value): string
    {
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? '';

        return mb_strtolower($value);
    }
};
