<?php

namespace Tests\Feature\Admin;

use App\Mail\OperationalNotificationMail;
use App\Models\BackInStockSubscription;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminProductRequestsTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_manager_can_open_the_dedicated_product_requests_page(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_PRODUCT_MANAGER,
            'email_verified_at' => now(),
        ]);
        $customer = User::factory()->create([
            'name' => 'Waiting Customer',
            'email' => 'waiting@example.com',
        ]);
        $product = Product::factory()->for(Category::factory())->create([
            'name_en' => 'Requested Water Pump',
            'stock_quantity' => 0,
            'is_active' => true,
        ]);

        BackInStockSubscription::query()->create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.stock-requests.index'))
            ->assertOk()
            ->assertSee('Product Requests')
            ->assertSee('Requested Water Pump')
            ->assertSee('waiting@example.com')
            ->assertSee('Awaiting stock')
            ->assertSee('Automatic restock notifications');
    }

    public function test_waiting_customers_are_notified_automatically_when_stock_arrives(): void
    {
        Mail::fake();

        $customer = User::factory()->create([
            'email' => 'restock@example.com',
            'notify_stock_alerts' => true,
            'email_notifications' => true,
        ]);
        $product = Product::factory()->for(Category::factory())->create([
            'name_en' => 'Automatic Restock Product',
            'stock_quantity' => 0,
            'is_active' => true,
        ]);
        $subscription = BackInStockSubscription::query()->create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
        ]);

        $product->update(['stock_quantity' => 7]);

        $this->assertNotNull($subscription->fresh()->notified_at);
        Mail::assertQueued(OperationalNotificationMail::class, function (OperationalNotificationMail $mail) use ($customer): bool {
            return $mail->hasTo($customer->email)
                && str_contains($mail->subjectLine, 'Automatic Restock Product')
                && ! str_contains($mail->render(), 'Update your inventory levels');
        });
    }

    public function test_admin_cannot_send_restock_notifications_before_stock_arrives(): void
    {
        Mail::fake();

        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
        $customer = User::factory()->create();
        $product = Product::factory()->for(Category::factory())->create(['stock_quantity' => 0]);
        $subscription = BackInStockSubscription::query()->create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.stock-requests.notify', $product))
            ->assertRedirect()
            ->assertSessionHas('error', __('Add stock before sending customer notifications.'));

        $this->assertNull($subscription->fresh()->notified_at);
        Mail::assertNothingQueued();
    }
}
