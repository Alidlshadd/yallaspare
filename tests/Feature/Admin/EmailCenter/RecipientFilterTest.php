<?php

namespace Tests\Feature\Admin\EmailCenter;

use App\Models\User;
use App\Support\RecipientFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipientFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_filter_returns_only_matching_roles(): void
    {
        User::factory()->create(['role' => User::ROLE_DEALER]);
        User::factory()->create(['role' => User::ROLE_USER]);
        User::factory()->create(['role' => User::ROLE_USER]);

        $filter = new RecipientFilter(['roles' => ['user']]);
        $this->assertSame(2, $filter->query()->count());
    }

    public function test_dealer_status_only_applies_when_dealer_role_selected(): void
    {
        User::factory()->create(['role' => User::ROLE_DEALER, 'dealer_status' => User::DEALER_STATUS_ACTIVE]);
        User::factory()->create(['role' => User::ROLE_DEALER, 'dealer_status' => User::DEALER_STATUS_INACTIVE]);
        User::factory()->create(['role' => User::ROLE_USER]);

        $filter = new RecipientFilter(['roles' => ['dealer'], 'dealer_statuses' => ['active']]);
        $this->assertSame(1, $filter->query()->count());
    }

    public function test_locale_filter_limits_to_users_with_preferred_locale(): void
    {
        User::factory()->create(['locale_preference' => 'ku']);
        User::factory()->create(['locale_preference' => 'en']);
        User::factory()->create(['locale_preference' => 'ar']);

        $filter = new RecipientFilter(['locales' => ['ku', 'ar']]);
        $this->assertSame(2, $filter->query()->count());
    }

    public function test_email_verified_filter(): void
    {
        User::factory()->create(['email_verified_at' => now()]);
        User::factory()->unverified()->create();

        $this->assertSame(1, (new RecipientFilter(['email_verified' => 'verified']))->query()->count());
        $this->assertSame(1, (new RecipientFilter(['email_verified' => 'unverified']))->query()->count());
    }

    public function test_manual_include_and_exclude_apply_after_filters(): void
    {
        $a = User::factory()->create(['role' => User::ROLE_USER]);
        $b = User::factory()->create(['role' => User::ROLE_USER]);
        $excluded = User::factory()->create(['role' => User::ROLE_USER]);
        $extra = User::factory()->create(['role' => User::ROLE_DEALER]);

        $filter = new RecipientFilter([
            'roles' => ['user'],
            'manual_include' => [$extra->id],
            'manual_exclude' => [$excluded->id],
        ]);

        $ids = $filter->query()->pluck('id')->all();

        $this->assertContains($a->id, $ids);
        $this->assertContains($b->id, $ids);
        $this->assertContains($extra->id, $ids);
        $this->assertNotContains($excluded->id, $ids);
    }

    public function test_empty_filter_returns_all_users(): void
    {
        User::factory()->count(3)->create();

        $this->assertSame(3, (new RecipientFilter([]))->query()->count());
    }
}
