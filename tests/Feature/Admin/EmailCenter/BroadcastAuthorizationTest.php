<?php

namespace Tests\Feature\Admin\EmailCenter;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BroadcastAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_permitted_role_gets_403_on_store(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $response = $this->actingAs($user)->post('/admin/email/broadcasts', [
            'subject' => 'Hi',
            'body_html' => '<p>x</p>',
        ]);

        $response->assertForbidden();
    }

    public function test_admin_role_can_reach_recipients_preview(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post('/admin/email/broadcasts/recipients-preview', [
            'filters' => [],
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['count', 'first10', 'filters_normalized']);
    }
}
