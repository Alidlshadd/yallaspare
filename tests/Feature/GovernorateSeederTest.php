<?php

namespace Tests\Feature;

use App\Models\Governorate;
use Database\Seeders\GovernorateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GovernorateSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_nineteen_governorates(): void
    {
        $this->seed(GovernorateSeeder::class);

        $this->assertSame(19, Governorate::query()->count());
        $this->assertSame(
            ['baghdad', 'ninawa', 'basrah'],
            Governorate::query()->ordered()->limit(3)->pluck('code')->all()
        );
    }

    public function test_running_it_again_neither_duplicates_rows_nor_overwrites_the_rates(): void
    {
        $this->seed(GovernorateSeeder::class);

        $erbil = Governorate::query()->where('code', 'erbil')->firstOrFail();
        $erbil->update(['delivery_days' => 1, 'shipping_fee' => 4500]);

        // Pretend a later release corrected a spelling, which the seeder is
        // allowed to push, unlike the rates.
        $erbil->update(['name_en' => 'Erbill']);

        $this->seed(GovernorateSeeder::class);

        $this->assertSame(19, Governorate::query()->count());

        $erbil->refresh();
        $this->assertSame(1, $erbil->delivery_days);
        $this->assertSame(4500, $erbil->shipping_fee);
        $this->assertSame('Erbil', $erbil->name_en);
    }

    public function test_the_name_follows_the_active_locale(): void
    {
        $this->seed(GovernorateSeeder::class);

        $baghdad = Governorate::query()->where('code', 'baghdad')->firstOrFail();

        $this->assertSame('Baghdad', $baghdad->name);

        $this->app->setLocale('ar');
        $this->assertSame('بغداد', $baghdad->fresh()?->name);

        $this->app->setLocale('ku');
        $this->assertSame('بەغدا', $baghdad->fresh()?->name);
    }
}
