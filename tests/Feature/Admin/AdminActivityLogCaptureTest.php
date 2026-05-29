<?php

namespace Tests\Feature\Admin;

use App\Models\Coupon;
use App\Models\Discount;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AdminActivityLogCaptureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_role_change_is_captured_in_activity_log(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
        $target = User::factory()->create([
            'role' => User::ROLE_USER,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin);

        $target->forceFill(['role' => User::ROLE_ADMIN])->save();

        $activity = Activity::query()
            ->where('subject_type', User::class)
            ->where('subject_id', $target->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($activity, 'Role change must create an activity log entry');
        $this->assertSame('updated', $activity->description);
        $this->assertArrayHasKey('role', (array) ($activity->properties['attributes'] ?? []));
        $this->assertSame(User::ROLE_ADMIN, $activity->properties['attributes']['role']);
        $this->assertSame(User::ROLE_USER, $activity->properties['old']['role']);
    }

    public function test_preference_values_never_leak_into_user_activity_log(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'theme_preference' => 'light',
            'locale_preference' => 'en',
        ]);

        $user->forceFill([
            'theme_preference' => 'dark',
            'locale_preference' => 'ar',
            'notify_promotions' => false,
        ])->save();

        $activities = Activity::query()
            ->where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->get();

        foreach ($activities as $activity) {
            $attributes = (array) ($activity->properties['attributes'] ?? []);
            $old = (array) ($activity->properties['old'] ?? []);

            foreach (['theme_preference', 'locale_preference', 'notify_promotions'] as $forbidden) {
                $this->assertArrayNotHasKey($forbidden, $attributes, "$forbidden must never appear in activity log");
                $this->assertArrayNotHasKey($forbidden, $old, "$forbidden must never appear in activity log");
            }
        }
    }

    public function test_user_password_is_never_logged(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
        ]);
        $target = User::factory()->create([
            'role' => User::ROLE_USER,
        ]);

        $this->actingAs($admin);

        $target->forceFill([
            'role' => User::ROLE_ADMIN,
            'password' => bcrypt('newpassword1'),
        ])->save();

        $activity = Activity::query()
            ->where('subject_type', User::class)
            ->where('subject_id', $target->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertArrayNotHasKey('password', (array) ($activity->properties['attributes'] ?? []));
        $this->assertArrayNotHasKey('password', (array) ($activity->properties['old'] ?? []));
    }

    public function test_discount_changes_logged_but_used_count_excluded(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $this->actingAs($admin);

        $discount = Discount::query()->create([
            'name' => 'Spring Sale',
            'scope' => 'catalog',
            'type' => 'percent',
            'value' => '10.00',
            'is_active' => true,
        ]);

        $discount->forceFill(['value' => '15.00', 'used_count' => 5])->save();

        $activity = Activity::query()
            ->where('subject_type', Discount::class)
            ->where('subject_id', $discount->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertArrayHasKey('value', (array) ($activity->properties['attributes'] ?? []));
        $this->assertArrayNotHasKey('used_count', (array) ($activity->properties['attributes'] ?? []));
    }

    public function test_setting_changes_are_logged(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $this->actingAs($admin);

        Setting::query()->create(['key' => 'shipping_fee', 'value' => '5000']);

        $activity = Activity::query()
            ->where('subject_type', Setting::class)
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame('created', $activity->description);
    }
}
