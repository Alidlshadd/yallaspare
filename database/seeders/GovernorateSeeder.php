<?php

namespace Database\Seeders;

use App\Models\Governorate;
use Illuminate\Database\Seeder;

class GovernorateSeeder extends Seeder
{
    /**
     * The eighteen governorates of Iraq plus Halabja, keyed by a code that
     * never changes.
     *
     * @var array<int, array{code: string, name_en: string, name_ar: string, name_ku: string, sort_order: int}>
     */
    private const GOVERNORATES = [
        ['code' => 'baghdad', 'name_en' => 'Baghdad', 'name_ar' => 'بغداد', 'name_ku' => 'بەغدا', 'sort_order' => 1],
        ['code' => 'ninawa', 'name_en' => 'Ninawa', 'name_ar' => 'نينوى', 'name_ku' => 'نەینەوا', 'sort_order' => 2],
        ['code' => 'basrah', 'name_en' => 'Basrah', 'name_ar' => 'البصرة', 'name_ku' => 'بەسرە', 'sort_order' => 3],
        ['code' => 'anbar', 'name_en' => 'Anbar', 'name_ar' => 'الأنبار', 'name_ku' => 'ئەنبار', 'sort_order' => 4],
        ['code' => 'karbala', 'name_en' => 'Karbala', 'name_ar' => 'كربلاء', 'name_ku' => 'کەربەلا', 'sort_order' => 5],
        ['code' => 'najaf', 'name_en' => 'Najaf', 'name_ar' => 'النجف', 'name_ku' => 'نەجەف', 'sort_order' => 6],
        ['code' => 'diyala', 'name_en' => 'Diyala', 'name_ar' => 'ديالى', 'name_ku' => 'دیالە', 'sort_order' => 7],
        ['code' => 'dohuk', 'name_en' => 'Dohuk', 'name_ar' => 'دهوك', 'name_ku' => 'دهۆک', 'sort_order' => 8],
        ['code' => 'erbil', 'name_en' => 'Erbil', 'name_ar' => 'أربيل', 'name_ku' => 'هەولێر', 'sort_order' => 9],
        ['code' => 'sulaymaniyah', 'name_en' => 'Sulaymaniyah', 'name_ar' => 'السليمانية', 'name_ku' => 'سلێمانی', 'sort_order' => 10],
        ['code' => 'kirkuk', 'name_en' => 'Kirkuk', 'name_ar' => 'كركوك', 'name_ku' => 'کەرکوک', 'sort_order' => 11],
        ['code' => 'babil', 'name_en' => 'Babil', 'name_ar' => 'بابل', 'name_ku' => 'بابل', 'sort_order' => 12],
        ['code' => 'wasit', 'name_en' => 'Wasit', 'name_ar' => 'واسط', 'name_ku' => 'واسیت', 'sort_order' => 13],
        ['code' => 'maysan', 'name_en' => 'Maysan', 'name_ar' => 'ميسان', 'name_ku' => 'مەیسان', 'sort_order' => 14],
        ['code' => 'dhi-qar', 'name_en' => 'Dhi Qar', 'name_ar' => 'ذي قار', 'name_ku' => 'زیقار', 'sort_order' => 15],
        ['code' => 'qadisiyyah', 'name_en' => 'Qadisiyyah', 'name_ar' => 'القادسية', 'name_ku' => 'قادسیە', 'sort_order' => 16],
        ['code' => 'halabja', 'name_en' => 'Halabja', 'name_ar' => 'حلبجة', 'name_ku' => 'هەڵەبجە', 'sort_order' => 17],
        ['code' => 'muthanna', 'name_en' => 'Muthanna', 'name_ar' => 'المثنى', 'name_ku' => 'موسەننا', 'sort_order' => 18],
        ['code' => 'salah-ad-din', 'name_en' => 'Salah ad-Din', 'name_ar' => 'صلاح الدين', 'name_ku' => 'سەڵاحەدین', 'sort_order' => 19],
    ];

    /**
     * Safe to run again. Names and ordering are the seeder's to correct; the
     * delivery days and the fee belong to whoever set them in the admin panel,
     * so they are only written when the row is first created.
     */
    public function run(): void
    {
        foreach (self::GOVERNORATES as $governorate) {
            Governorate::query()->updateOrCreate(
                ['code' => $governorate['code']],
                [
                    'name_en' => $governorate['name_en'],
                    'name_ar' => $governorate['name_ar'],
                    'name_ku' => $governorate['name_ku'],
                    'sort_order' => $governorate['sort_order'],
                ],
            );
        }
    }

    public static function count(): int
    {
        return count(self::GOVERNORATES);
    }

    /**
     * The codes this seeder owns. A row with one of these is part of the
     * standard list: the seeder keeps its name in step, and the panel does not
     * offer to rename or remove it, because the next deploy would put it back.
     *
     * @return array<int, string>
     */
    public static function codes(): array
    {
        return array_column(self::GOVERNORATES, 'code');
    }
}
