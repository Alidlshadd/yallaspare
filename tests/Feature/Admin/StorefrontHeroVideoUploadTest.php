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

    public function test_storefront_hero_video_accepts_octet_stream_mp4_under_50mb(): void
    {
        Storage::fake('public');
        $user = $this->settingsManager();
        $video = $this->fakeMp4Upload('12983897_3840_2160_30fps.mp4', 'application/octet-stream');

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

    public function test_storefront_hero_video_accepts_valid_mp4_with_text_like_bytes_inside_stream(): void
    {
        Storage::fake('public');
        $user = $this->settingsManager();
        $video = $this->fakeMp4Upload(
            '12983897_3840_2160_30fps.mp4',
            'video/mp4',
            $this->fakeMp4Bytes() . str_repeat("\0", 1024) . '<script'
        );

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

    public function test_storefront_hero_video_rejects_oversized_file(): void
    {
        Storage::fake('public');
        $user = $this->settingsManager();
        $video = UploadedFile::fake()->create('hero.mp4', 51201, 'video/mp4');

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
    }

    public function test_storefront_hero_video_rejects_non_video_file(): void
    {
        Storage::fake('public');
        $user = $this->settingsManager();
        $video = UploadedFile::fake()->createWithContent('hero.txt', 'not a video');

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

    public function test_storefront_hero_video_rejects_mp4_without_ftyp_container_marker(): void
    {
        Storage::fake('public');
        $user = $this->settingsManager();
        $video = UploadedFile::fake()->createWithContent('hero.mp4', str_repeat("\0", 1024));

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
    }

    public function test_failed_storefront_hero_video_upload_does_not_delete_current_video(): void
    {
        Storage::fake('public');
        $user = $this->settingsManager();
        $currentPath = 'home/hero/current.mp4';
        Storage::disk('public')->put($currentPath, $this->fakeMp4Bytes());
        Setting::setValue('storefront_hero_video', $currentPath);
        $video = UploadedFile::fake()->createWithContent('hero.mp4', "<?php echo 'not a video';");

        $response = $this
            ->withSession([
                'admin_2fa.verified_user_id' => $user->id,
                'auth.password_confirmed_at' => time(),
            ])
            ->actingAs($user)
            ->from(route('admin.settings.edit'))
            ->put(route('admin.settings.update'), array_merge($this->validPayload(), [
                'remove_storefront_hero_video' => '1',
                'storefront_hero_video' => $video,
            ]));

        $response
            ->assertRedirect(route('admin.settings.edit'))
            ->assertSessionHasErrors('storefront_hero_video');

        $this->assertSame($currentPath, (string) Setting::getValue('storefront_hero_video'));
        Storage::disk('public')->assertExists($currentPath);
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

    private function fakeMp4Upload(string $name, string $mimeType = 'video/mp4', ?string $bytes = null): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'hero-mp4-');
        file_put_contents($path, $bytes ?? $this->fakeMp4Bytes());

        return new UploadedFile($path, $name, $mimeType, null, true);
    }

    private function fakeMp4Bytes(): string
    {
        return pack('N', 24)
            . 'ftypisom'
            . pack('N', 512)
            . 'isomiso2avc1mp41'
            . str_repeat("\0", 1024);
    }
}
