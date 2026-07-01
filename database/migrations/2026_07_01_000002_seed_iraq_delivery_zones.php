<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('delivery_zones')) {
            return;
        }

        $now = now();
        $cities = [
            'Al Anbar',
            'Babil',
            'Baghdad',
            'Basra',
            'Dhi Qar',
            'Diyala',
            'Duhok',
            'Erbil',
            'Halabja',
            'Karbala',
            'Kirkuk',
            'Maysan',
            'Muthanna',
            'Najaf',
            'Nineveh',
            'Qadisiyah',
            'Saladin',
            'Sulaymaniyah',
            'Wasit',
        ];

        foreach ($cities as $city) {
            $exists = DB::table('delivery_zones')
                ->where('city', $city)
                ->whereNull('district')
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('delivery_zones')->insert([
                'city' => $city,
                'district' => null,
                'shipping_fee' => 0,
                'free_shipping_min' => null,
                'delivery_days_min' => 1,
                'delivery_days_max' => 3,
                'cash_on_delivery_enabled' => true,
                'is_active' => true,
                'notes' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('delivery_zones')) {
            return;
        }

        DB::table('delivery_zones')
            ->whereIn('city', [
                'Al Anbar',
                'Babil',
                'Baghdad',
                'Basra',
                'Dhi Qar',
                'Diyala',
                'Duhok',
                'Erbil',
                'Halabja',
                'Karbala',
                'Kirkuk',
                'Maysan',
                'Muthanna',
                'Najaf',
                'Nineveh',
                'Qadisiyah',
                'Saladin',
                'Sulaymaniyah',
                'Wasit',
            ])
            ->whereNull('district')
            ->where('shipping_fee', 0)
            ->whereNull('free_shipping_min')
            ->delete();
    }
};
