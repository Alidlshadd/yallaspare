<?php

namespace Tests\Feature\Admin;

use App\Models\Governorate;
use App\Models\User;
use Database\Seeders\GovernorateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GovernorateShippingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(GovernorateSeeder::class);
    }

    private function makeAdmin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * @return array<int, array{id: int, delivery_days: int, shipping_fee: int}>
     */
    private function payloadFromCurrent(): array
    {
        return Governorate::query()
            ->ordered()
            ->get()
            ->map(fn (Governorate $governorate) => [
                'id' => $governorate->id,
                'delivery_days' => $governorate->delivery_days,
                'shipping_fee' => $governorate->shipping_fee,
            ])
            ->all();
    }

    public function test_the_page_lists_every_governorate(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get(route('admin.shipping.governorates'))
            ->assertOk()
            ->assertSeeText('Baghdad')
            ->assertSeeText('Salah ad-Din');
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    public static function rightToLeftLocales(): array
    {
        return [
            'arabic' => ['ar', 'بغداد'],
            'kurdish' => ['ku', 'بەغدا'],
        ];
    }

    #[DataProvider('rightToLeftLocales')]
    public function test_it_reads_right_to_left_in_arabic_and_kurdish(string $locale, string $baghdad): void
    {
        $response = $this->actingAs($this->makeAdmin())
            ->withSession(['locale' => $locale])
            ->get(route('admin.shipping.governorates'));

        $response->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSeeText($baghdad)
            // The English spelling stays underneath as the second line.
            ->assertSeeText('Baghdad');
    }

    public function test_an_admin_without_settings_permission_is_refused(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ORDER_MANAGER,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('admin.shipping.governorates'))
            ->assertForbidden();

        $this->actingAs($user)
            ->put(route('admin.shipping.governorates.update'), ['rows' => $this->payloadFromCurrent()])
            ->assertForbidden();
    }

    public function test_a_guest_is_sent_to_the_login_page(): void
    {
        $this->get(route('admin.shipping.governorates'))
            ->assertRedirect(route('login'));
    }

    public function test_the_whole_table_saves_in_one_request(): void
    {
        $rows = $this->payloadFromCurrent();
        $rows[0]['delivery_days'] = 1;
        $rows[0]['shipping_fee'] = 0;
        $rows[5]['delivery_days'] = 9;
        $rows[5]['shipping_fee'] = 12500;

        $this->actingAs($this->makeAdmin())
            ->put(route('admin.shipping.governorates.update'), ['rows' => $rows])
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('governorates', [
            'id' => $rows[0]['id'],
            'delivery_days' => 1,
            'shipping_fee' => 0,
        ]);
        $this->assertDatabaseHas('governorates', [
            'id' => $rows[5]['id'],
            'delivery_days' => 9,
            'shipping_fee' => 12500,
        ]);
    }

    public function test_rows_that_did_not_change_are_left_untouched(): void
    {
        $untouched = Governorate::query()->ordered()->skip(2)->first();
        $this->assertNotNull($untouched);
        $stamp = $untouched->updated_at;

        $rows = $this->payloadFromCurrent();
        $rows[0]['shipping_fee'] = 7000;

        $this->travel(1)->minute();

        $this->actingAs($this->makeAdmin())
            ->put(route('admin.shipping.governorates.update'), ['rows' => $rows])
            ->assertSessionHasNoErrors();

        $this->assertEquals(
            $stamp?->timestamp,
            $untouched->fresh()?->updated_at?->timestamp,
            'A row nobody edited was written anyway.'
        );
    }

    public function test_a_delivery_time_of_zero_is_rejected(): void
    {
        $rows = $this->payloadFromCurrent();
        $rows[0]['delivery_days'] = 0;

        $this->actingAs($this->makeAdmin())
            ->put(route('admin.shipping.governorates.update'), ['rows' => $rows])
            ->assertSessionHasErrors('rows.0.delivery_days');

        $this->assertDatabaseHas('governorates', [
            'id' => $rows[0]['id'],
            'delivery_days' => 3,
        ]);
    }

    public function test_a_negative_fee_is_rejected(): void
    {
        $rows = $this->payloadFromCurrent();
        $rows[1]['shipping_fee'] = -1;

        $this->actingAs($this->makeAdmin())
            ->put(route('admin.shipping.governorates.update'), ['rows' => $rows])
            ->assertSessionHasErrors('rows.1.shipping_fee');
    }

    public function test_a_payload_missing_a_governorate_is_rejected(): void
    {
        $rows = $this->payloadFromCurrent();
        array_pop($rows);

        $this->actingAs($this->makeAdmin())
            ->put(route('admin.shipping.governorates.update'), ['rows' => $rows])
            ->assertSessionHasErrors('rows');
    }
}
