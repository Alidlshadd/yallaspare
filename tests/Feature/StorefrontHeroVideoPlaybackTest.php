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
        Storage::disk('public')->put('home/hero/poster.jpg', 'poster');
        Setting::setValue('storefront_hero_video', 'home/hero/current.mp4');
        Setting::setValue('storefront_hero_image', 'home/hero/poster.jpg');

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
        // preload="none" plus a source that carries its URL on a data attribute:
        // the hero file is ~31MB, and fetching it on page load starved every
        // other request on the page.
        $this->assertStringContainsString('preload="none"', $videoTag);
        $this->assertStringContainsString('disablepictureinpicture', $videoTag);
        $this->assertStringContainsString('disableremoteplayback', $videoTag);
        $this->assertStringContainsString('controlslist="nodownload nofullscreen noremoteplayback"', $videoTag);
        $this->assertStringContainsString('x-webkit-airplay="deny"', $videoTag);
        $this->assertDoesNotMatchRegularExpression('/\scontrols(\s|>|=)/i', $videoTag);
        $this->assertStringContainsString('opacity-0', $videoTag);
        $this->assertStringContainsString('data-hero-video-fallback', $html);
        $this->assertStringContainsString('home/hero/poster.jpg', $html);
        $this->assertStringContainsString('hero-background-video::-webkit-media-controls-start-playback-button', $html);

        preg_match('/<source[^>]*>/i', $html, $sourceMatches);
        $sourceTag = $sourceMatches[0] ?? '';
        $this->assertStringContainsString('data-hero-video-src="', $sourceTag);
        $this->assertStringNotContainsString(' src="', $sourceTag);
    }

    public function test_hero_video_playback_logic_ships_in_storefront_bundle(): void
    {
        // The playback/recovery logic was moved out of an inline <script> into
        // resources/js/storefront.js so the page source stays compact and the
        // code ships minified. Assert the behaviour still lives in that module.
        $source = file_get_contents(resource_path('js/storefront.js'));
        $this->assertIsString($source);

        $this->assertStringContainsString('data-hero-background-video', $source);
        $this->assertStringContainsString('window.setInterval', $source);
        $this->assertStringContainsString('setHeroVideoVisible', $source);
        $this->assertStringContainsString("video.classList.toggle('opacity-100', visible)", $source);
        $this->assertStringContainsString("video.classList.toggle('opacity-0', !visible)", $source);
        $this->assertStringContainsString('video.controls = false', $source);
        $this->assertStringContainsString('video.volume = 0', $source);
        $this->assertStringContainsString('video.playbackRate = 1', $source);
        $this->assertStringContainsString('recoverFrozenHeroVideo', $source);
        $this->assertStringContainsString('frozenCount >= 3', $source);
        $this->assertStringContainsString("'webkitbeginfullscreen'", $source);
        $this->assertStringContainsString("'enterpictureinpicture'", $source);

        // A mobile autoplay block is lifted by the first user gesture, so that
        // listener retires itself. It used to run on every scroll, touch and
        // click, and rewriting a <video> element's attributes on every scroll
        // tick is what made the storefront stutter under the finger.
        $this->assertStringContainsString("'pointerdown', 'touchstart'", $source);
        $this->assertStringContainsString('once: true', $source);
        $this->assertStringNotContainsString("'click', 'scroll'", $source);
    }

    public function test_hero_video_only_downloads_once_it_is_on_screen(): void
    {
        // The file is the heaviest asset on the site. It must stay unfetched
        // until the visitor can actually see it, and stay unfetched entirely on
        // a metered or slow connection — the poster frame covers both cases.
        $source = file_get_contents(resource_path('js/storefront.js'));
        $this->assertIsString($source);

        $this->assertStringContainsString('IntersectionObserver', $source);
        $this->assertStringContainsString('startHeroVideo', $source);
        $this->assertStringContainsString('data-hero-video-src', $source);
        $this->assertStringContainsString('connectionIsSlow', $source);
        $this->assertStringContainsString('saveData', $source);
    }
}
