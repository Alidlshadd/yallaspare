<?php

namespace Tests\Feature\Admin;

use App\Exports\ActivityLogsExport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AdminActivityLogPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_log_identifies_the_admin_and_affected_user(): void
    {
        $admin = User::factory()->create([
            'name' => 'Audit Admin',
            'email' => 'audit-admin@example.com',
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
        $target = User::factory()->create([
            'name' => 'Target Customer',
            'email' => 'target-customer@example.com',
            'role' => User::ROLE_USER,
        ]);

        $this->actingAs($admin);
        $target->forceFill(['role' => User::ROLE_ADMIN])->save();

        $this->get(route('admin.activity-logs.index'))
            ->assertOk()
            ->assertSee('Audit Admin')
            ->assertSee('audit-admin@example.com')
            ->assertSee('Target Customer')
            ->assertSee('target-customer@example.com')
            ->assertSee('Performed by')
            ->assertSee('Affected record')
            ->assertSee('Changed fields')
            ->assertSee(route('admin.users.show', $target), false);
    }

    public function test_activity_logs_can_be_searched_by_target_identity(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
        $target = User::factory()->create([
            'name' => 'Searchable Target',
            'email' => 'search-target@example.com',
        ]);

        $this->actingAs($admin);
        $target->forceFill(['role' => User::ROLE_ADMIN])->save();

        $this->get(route('admin.activity-logs.index', ['q' => 'search-target@example.com']))
            ->assertOk()
            ->assertSee('Searchable Target')
            ->assertSee('search-target@example.com');
    }

    public function test_activity_log_export_contains_actor_and_target_identity(): void
    {
        $admin = User::factory()->create([
            'name' => 'Export Admin',
            'email' => 'export-admin@example.com',
            'role' => User::ROLE_SUPER_ADMIN,
        ]);
        $target = User::factory()->create([
            'name' => 'Export Target',
            'email' => 'export-target@example.com',
        ]);

        $this->actingAs($admin);
        $target->forceFill(['role' => User::ROLE_ADMIN])->save();

        $activity = Activity::query()
            ->with(['causer', 'subject'])
            ->where('subject_type', User::class)
            ->where('subject_id', $target->id)
            ->latest('id')
            ->firstOrFail();
        $export = new ActivityLogsExport();
        $row = $export->map($activity);

        $this->assertContains('Export Admin', $row);
        $this->assertContains('export-admin@example.com', $row);
        $this->assertContains('Export Target', $row);
        $this->assertContains('export-target@example.com', $row);
        $this->assertContains('target_name', $export->headings());
    }
}
