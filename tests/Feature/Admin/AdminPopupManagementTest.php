<?php

namespace Tests\Feature\Admin;

use App\Models\Popup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPopupManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
    }

    public function test_admin_can_view_popup_index(): void
    {
        $popup = Popup::factory()->create();

        $this->actingAs($this->admin())
            ->get(route('admin.popups.index'))
            ->assertOk()
            ->assertSee($popup->title_en);
    }

    public function test_regular_user_cannot_access_popup_management(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('admin.popups.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_popup(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.popups.store'), [
                'title_en' => 'Summer campaign',
                'description_en' => 'Big discounts on brake pads.',
                'button_label_en' => 'Shop now',
                'button_url' => '/shop',
                'pages' => ['home', 'shop'],
                'frequency' => 'once_per_days',
                'frequency_days' => 3,
                'delay_seconds' => 2,
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.popups.index'));

        $this->assertDatabaseHas('popups', [
            'title_en' => 'Summer campaign',
            'frequency' => 'once_per_days',
            'frequency_days' => 3,
            'is_active' => true,
        ]);

        $this->assertSame(['home', 'shop'], Popup::query()->first()->pages);
    }

    public function test_javascript_button_url_is_rejected(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.popups.store'), [
                'title_en' => 'Bad popup',
                'button_url' => 'javascript:alert(1)',
                'pages' => ['all'],
                'frequency' => 'every_visit',
                'delay_seconds' => 0,
            ])
            ->assertSessionHasErrors('button_url');

        $this->assertDatabaseCount('popups', 0);
    }

    public function test_selecting_all_collapses_page_targeting(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.popups.store'), [
                'title_en' => 'Everywhere popup',
                'pages' => ['all', 'home', 'cart'],
                'frequency' => 'once_per_session',
                'delay_seconds' => 0,
            ]);

        $this->assertSame(['all'], Popup::query()->first()->pages);
    }

    public function test_admin_can_toggle_and_delete_popup(): void
    {
        $popup = Popup::factory()->create(['is_active' => true]);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->patch(route('admin.popups.toggle', $popup))
            ->assertRedirect(route('admin.popups.index'));

        $this->assertFalse($popup->fresh()->is_active);

        $this->actingAs($admin)
            ->delete(route('admin.popups.destroy', $popup))
            ->assertRedirect(route('admin.popups.index'));

        $this->assertDatabaseCount('popups', 0);
    }

    public function test_end_date_must_not_precede_start_date(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.popups.store'), [
                'title_en' => 'Bad schedule',
                'pages' => ['all'],
                'frequency' => 'every_visit',
                'delay_seconds' => 0,
                'starts_at' => '2026-08-10T10:00',
                'ends_at' => '2026-08-01T10:00',
            ])
            ->assertSessionHasErrors('ends_at');
    }
}
