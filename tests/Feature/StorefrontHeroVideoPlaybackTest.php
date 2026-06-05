<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorefrontHeroVideoPlaybackTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_hero_video_renders_as_background_autoplay_video_without_controls(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('home/hero/current.mp4', 'mp4');
        Setting::setValue('storefront_hero_video', 'home/hero/current.mp4');

        $response = $this->get(route('user.shop.home'));

        $response->assertOk();

        $html = $response->getContent();
        $this->assertIsString($html);
        $this->assertMatchesRegularExpression('/<video\b[^>]*data-hero-background-video[^>]*>/i', $html);
        preg_match('/<video\b[^>]*data-hero-background-video[^>]*>/i', $html, $matches);
        $videoTag = $matches[0] ?? '';

        $this->assertStringContainsString('autoplay', $videoTag);
        $this->assertStringContainsString('muted', $videoTag);
        $this->assertStringContainsString('loop', $videoTag);
        $this->assertStringContainsString('playsinline', $videoTag);
        $this->assertStringContainsString('webkit-playsinline', $videoTag);
        $this->assertStringContainsString('preload="auto"', $videoTag);
        $this->assertStringContainsString('disablepictureinpicture', $videoTag);
        $this->assertStringContainsString('disableremoteplayback', $videoTag);
        $this->assertStringContainsString('x-webkit-airplay="deny"', $videoTag);
        $this->assertStringNotContainsString('controls', $videoTag);
        $this->assertStringContainsString('window.setInterval', $html);
        $this->assertStringContainsString('video.controls = false', $html);
        $this->assertStringContainsString("'touchstart'", $html);
        $this->assertStringContainsString("'orientationchange'", $html);
        $this->assertStringContainsString('hero-background-video::-webkit-media-controls-start-playback-button', $html);
    }
}
