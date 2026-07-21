<?php

namespace Tests\Feature;

use App\Models\Popup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontPopupTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_popup_renders_on_home_page(): void
    {
        $popup = Popup::factory()->create([
            'title_en' => 'Grand opening sale',
            'pages' => ['all'],
        ]);

        $this->get(route('user.shop.home'))
            ->assertOk()
            ->assertSee('data-store-popup', false)
            ->assertSee('Grand opening sale');
    }

    public function test_inactive_popup_is_not_rendered(): void
    {
        Popup::factory()->create([
            'title_en' => 'Hidden popup',
            'is_active' => false,
        ]);

        $this->get(route('user.shop.home'))
            ->assertOk()
            ->assertDontSee('data-store-popup', false)
            ->assertDontSee('Hidden popup');
    }

    public function test_expired_popup_is_not_rendered(): void
    {
        Popup::factory()->create([
            'title_en' => 'Old campaign',
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->subDay(),
        ]);

        $this->get(route('user.shop.home'))->assertOk()->assertDontSee('Old campaign');
    }

    public function test_future_scheduled_popup_is_not_rendered(): void
    {
        Popup::factory()->create([
            'title_en' => 'Upcoming campaign',
            'starts_at' => now()->addDay(),
        ]);

        $this->get(route('user.shop.home'))->assertOk()->assertDontSee('Upcoming campaign');
    }

    public function test_page_targeting_limits_where_popup_appears(): void
    {
        Popup::factory()->create([
            'title_en' => 'Shop only popup',
            'pages' => ['shop'],
        ]);

        $this->get(route('user.shop.home'))->assertOk()->assertDontSee('Shop only popup');
        $this->get(route('shop.index'))->assertOk()->assertSee('Shop only popup');
    }

    public function test_newest_eligible_popup_wins(): void
    {
        Popup::factory()->create(['title_en' => 'Older popup']);
        Popup::factory()->create(['title_en' => 'Newer popup']);

        $this->get(route('user.shop.home'))
            ->assertOk()
            ->assertSee('Newer popup')
            ->assertDontSee('Older popup');
    }
}
