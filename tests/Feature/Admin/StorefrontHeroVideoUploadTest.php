<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorefrontHeroVideoUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_manager_can_upload_valid_storefront_hero_mp4(): void
    {
        Storage::fake('public');
        $user = $this->settingsManager();
        $video = $this->fakeMp4Upload('hero.mp4');

        $response = $this
            ->withSession([
                'admin_2fa.verified_user_id' => $user->id,
                'auth.password_confirmed_at' => time(),
            ])
            ->actingAs($user)
            ->put(route('admin.settings.update'), array_merge($this->validPayload(), [
                'storefront_hero_video' => $video,
            ]));

        $response->assertRedirect(route('admin.settings.edit'));

        $storedPath = (string) Setting::getValue('storefront_hero_video');
        $this->assertMatchesRegularExpression('#^home/hero/[0-9a-f-]+\.mp4$#', $storedPath);
        Storage::disk('public')->assertExists($storedPath);
    }

    public function test_storefront_hero_video_rejects_fake_mp4_payload(): void
    {
        Storage::fake('public');
        $user = $this->settingsManager();
        $video = UploadedFile::fake()->createWithContent('hero.mp4', "<?php echo 'not a video';");

        $response = $this
            ->withSession([
                'admin_2fa.verified_user_id' => $user->id,
                'auth.password_confirmed_at' => time(),
            ])
            ->actingAs($user)
            ->from(route('admin.settings.edit'))
            ->put(route('admin.settings.update'), array_merge($this->validPayload(), [
                'storefront_hero_video' => $video,
            ]));

        $response
            ->assertRedirect(route('admin.settings.edit'))
            ->assertSessionHasErrors('storefront_hero_video');

        $this->assertSame('', (string) Setting::getValue('storefront_hero_video'));
        Storage::disk('public')->assertMissing('home/hero/hero.mp4');
    }

    private function settingsManager(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_SETTINGS_MANAGER,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'site_name' => 'Yalla Spare',
            'currency_code' => 'IQD',
            'currency_symbol' => 'IQD',
            'low_stock_threshold' => 5,
            'shipping_fee' => 5000,
            'storefront_hero_title' => 'Find the right spare parts faster',
            'storefront_hero_subtitle' => 'Browse saved categories, filter by vehicle, and shop available parts from one clean catalog.',
            'storefront_hero_button_label' => 'Shop now',
            'storefront_hero_button_url' => '',
        ];
    }

    private function fakeMp4Upload(string $name): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'hero-mp4-');
        $payload = pack('N', 24)
            . 'ftypisom'
            . pack('N', 512)
            . 'isomiso2avc1mp41'
            . str_repeat("\0", 1024);

        file_put_contents($path, $payload);

        return new UploadedFile($path, $name, 'video/mp4', null, true);
    }
}
