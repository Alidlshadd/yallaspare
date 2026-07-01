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

        $zones = $this->zones();
        $aliases = $this->aliases();
        $now = now();

        foreach ($zones as $city => $districts) {
            foreach ($districts as $district) {
                $aliasText = $aliases[$city . '|' . $district] ?? null;
                $exists = DB::table('delivery_zones')
                    ->where('city', $city)
                    ->where('district', $district)
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('delivery_zones')->insert([
                    'city' => $city,
                    'district' => $district,
                    'shipping_fee' => 0,
                    'free_shipping_min' => null,
                    'delivery_days_min' => 1,
                    'delivery_days_max' => 3,
                    'cash_on_delivery_enabled' => true,
                    'is_active' => true,
                    'notes' => $aliasText ? 'Also known as: ' . $aliasText : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('delivery_zones')) {
            return;
        }

        foreach ($this->zones() as $city => $districts) {
            DB::table('delivery_zones')
                ->where('city', $city)
                ->whereIn('district', $districts)
                ->where('shipping_fee', 0)
                ->whereNull('free_shipping_min')
                ->delete();
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function zones(): array
    {
        return [
            'Al Anbar' => [
                'Al-Qa\'im',
                'Ar-Rutba',
                'Anah',
                'Fallujah',
                'Haditha',
                'Hit',
                'Ramadi',
                'Rawah',
            ],
            'Babil' => [
                'Al-Mahawil',
                'Al-Musayab',
                'Hashimiya',
                'Hilla',
            ],
            'Baghdad' => [
                'Abu Ghraib',
                'Adhamiyah',
                'Al Istiqlal',
                'Al Rashid',
                'Al-Za\'franiya',
                'Kadhimiya',
                'Karadah',
                'Karkh',
                'Mada\'in',
                'Mahmudiya',
                'Mansour',
                'New Baghdad',
                'Rasafa',
                'Sadr City',
                'Taji',
                'Tarmia',
            ],
            'Basra' => [
                'Abu Al-Khaseeb',
                'Al-Faw',
                'Al-Midaina',
                'Al-Qurna',
                'Al-Zubair',
                'Basra',
                'Shatt Al-Arab',
            ],
            'Dhi Qar' => [
                'Al-Chibayish',
                'Al-Rifa\'i',
                'Al-Shatra',
                'Nassriya',
                'Suq Al-Shoyokh',
            ],
            'Diyala' => [
                'Al-Khalis',
                'Al-Muqdadiya',
                'Baladrooz',
                'Baquba',
                'Khanaqin',
                'Kifri',
            ],
            'Duhok' => [
                'Akre',
                'Amadiya',
                'Bardarash',
                'Duhok',
                'Shekhan',
                'Sumel',
                'Zakho',
            ],
            'Erbil' => [
                'Choman',
                'Erbil',
                'Erbil Countryside',
                'Harir',
                'Koy Sanjaq',
                'Makhmur',
                'Mergasur',
                'Rawanduz',
                'Shaqlawa',
                'Soran',
                'Taqtaq',
            ],
            'Halabja' => [
                'Bamo',
                'Byara',
                'Halabja',
                'Khurmal',
                'Sirwan',
            ],
            'Karbala' => [
                'Ayn al-Tamr',
                'Al-Hindiya',
                'Karbala',
            ],
            'Kirkuk' => [
                'Al-Dibs',
                'Al-Hawiga',
                'Daquq',
                'Kirkuk',
            ],
            'Maysan' => [
                'Ali Al-Gharbi',
                'Al-Kahla',
                'Al-Maimouna',
                'Amarah',
                'Majar al-Kabir',
                'Qal\'at Saleh',
            ],
            'Muthanna' => [
                'Al-Khidhir',
                'Al-Rumaitha',
                'Al-Salman',
                'Samawa',
            ],
            'Najaf' => [
                'Al-Manathera',
                'Al-Meshkhab',
                'Kufa',
                'Najaf',
            ],
            'Nineveh' => [
                'Al-Ba\'aj',
                'Bakhdida',
                'Hatra',
                'Makhmur',
                'Mosul',
                'Sinjar',
                'Tal Afar',
                'Tel Keppe',
            ],
            'Qadisiyah' => [
                'Afaq',
                'Al-Shamiya',
                'Diwaniya',
                'Hamza',
            ],
            'Saladin' => [
                'Al-Daur',
                'Al-Shirqat',
                'Baiji',
                'Balad',
                'Dujail',
                'Samarra',
                'Tikrit',
                'Tuz Khurmatu',
            ],
            'Sulaymaniyah' => [
                'Chamchamal',
                'Darbandikhan',
                'Dokan',
                'Halabjay Taza',
                'Kalar',
                'Mawat',
                'Penjwin',
                'Pshdar',
                'Qaladiza',
                'Qaradagh',
                'Rania',
                'Saidsadiq',
                'Sharbazher',
                'Sulaymaniyah',
            ],
            'Wasit' => [
                'Al-Aziziyah',
                'Al-Hai',
                'Al-Na\'maniya',
                'Al-Suwaira',
                'Badra',
                'Kut',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function aliases(): array
    {
        return [
            'Al Anbar|Hit' => 'Heet, Hīt',
            'Al Anbar|Ramadi' => 'Ar-Ramadi',
            'Baghdad|Adhamiyah' => 'Al-Adhamiyah, Adhamiya',
            'Baghdad|Kadhimiya' => 'Kadhimiyah, Al-Kadhimiya',
            'Baghdad|Karadah' => 'Karrada',
            'Baghdad|Karkh' => 'Al-Karkh',
            'Baghdad|Mada\'in' => 'Al-Mada\'in, Madain',
            'Baghdad|Mahmudiya' => 'Mahmoudiyah, Al-Mahmudiya',
            'Baghdad|New Baghdad' => '9 Nissan, Al-Jadida',
            'Baghdad|Rasafa' => 'Rusafa, Al-Rasafa',
            'Baghdad|Tarmia' => 'Tarmiyah, Al Tarmia',
            'Basra|Basra' => 'Basrah, Al-Basrah',
            'Basra|Al-Faw' => 'Fao, Faw',
            'Dhi Qar|Nassriya' => 'Nasiriyah, Nasiriya, An Nasiriyah',
            'Dhi Qar|Suq Al-Shoyokh' => 'Suq Al-Shuyukh',
            'Diyala|Baquba' => 'Ba\'quba, Baqubah',
            'Duhok|Duhok' => 'Dohuk, Dahuk',
            'Erbil|Erbil' => 'Arbil, Hawler, Hewler',
            'Erbil|Koy Sanjaq' => 'Koya, Koysinjaq, Koy Sanjaq',
            'Erbil|Harir' => 'Hareer',
            'Erbil|Soran' => 'Diana, Rawanduz area',
            'Karbala|Karbala' => 'Kerbala',
            'Maysan|Amarah' => 'Amara, Al-Amarah',
            'Maysan|Majar al-Kabir' => 'Al-Mejar Al-Kabi',
            'Muthanna|Samawa' => 'Al-Samawa, Samawah',
            'Najaf|Najaf' => 'An Najaf',
            'Nineveh|Bakhdida' => 'Qaraqosh, Al-Hamdaniya',
            'Nineveh|Mosul' => 'Mawsil, Al-Mawsil',
            'Nineveh|Tal Afar' => 'Tel Afar',
            'Qadisiyah|Diwaniya' => 'Al Diwaniyah, Diwaniyah',
            'Saladin|Saladin' => 'Salah Al-Din',
            'Saladin|Tuz Khurmatu' => 'Tooz, Tuz Khurmato',
            'Sulaymaniyah|Sulaymaniyah' => 'Sulaimani, Slemani, Sulaymani',
            'Sulaymaniyah|Darbandikhan' => 'Darbandokeh',
            'Sulaymaniyah|Qaladiza' => 'Qaladze, Pshdar',
            'Wasit|Kut' => 'Al-Kut',
        ];
    }
};
