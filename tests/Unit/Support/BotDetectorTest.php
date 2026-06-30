<?php

namespace Tests\Unit\Support;

use App\Support\BotDetector;
use PHPUnit\Framework\TestCase;

class BotDetectorTest extends TestCase
{
    /** @dataProvider botUserAgents */
    public function test_detects_known_bots(string $userAgent): void
    {
        $this->assertTrue(BotDetector::isBot($userAgent));
    }

    public static function botUserAgents(): array
    {
        return [
            'googlebot' => ['Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'],
            'bingbot' => ['Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)'],
            'ahrefs' => ['Mozilla/5.0 (compatible; AhrefsBot/7.0; +http://ahrefs.com/robot/)'],
            'facebook' => ['facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)'],
            'headless chrome' => ['HeadlessChrome/120.0.0.0 Safari/537.36'],
            'lighthouse' => ['Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/118.0.5993.0 Safari/537.36 Lighthouse'],
            'generic spider' => ['SomethingSpider/1.0'],
        ];
    }

    public function test_treats_real_browsers_as_humans(): void
    {
        $this->assertFalse(BotDetector::isBot('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0 Safari/537.36'));
        $this->assertFalse(BotDetector::isBot('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15'));
    }

    public function test_treats_null_and_empty_as_bot(): void
    {
        $this->assertTrue(BotDetector::isBot(null));
        $this->assertTrue(BotDetector::isBot(''));
    }
}
