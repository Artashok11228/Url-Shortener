<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\Browser;
use App\Enums\Device;
use App\Services\UserAgentParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UserAgentParserTest extends TestCase
{
    private const string CHROME_DESKTOP = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';

    private const string EDGE_DESKTOP = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36 Edg/124.0.0.0';

    private const string SAFARI_IPHONE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';

    private const string FIREFOX_ANDROID_TABLET = 'Mozilla/5.0 (Android 13; Tablet; rv:109.0) Gecko/109.0 Firefox/115.0';

    private const string CHROME_ANDROID_PHONE = 'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Mobile Safari/537.36';

    private const string OPERA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36 OPR/110.0.0.0';

    private const string GOOGLEBOT = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';

    #[DataProvider('browsers')]
    public function test_detects_browser(?string $userAgent, Browser $expected): void
    {
        $this->assertSame($expected, (new UserAgentParser)->browser($userAgent));
    }

    #[DataProvider('devices')]
    public function test_detects_device(?string $userAgent, Device $expected): void
    {
        $this->assertSame($expected, (new UserAgentParser)->device($userAgent));
    }

    public static function browsers(): array
    {
        return [
            [self::CHROME_DESKTOP, Browser::Chrome],
            [self::EDGE_DESKTOP, Browser::Edge],
            [self::SAFARI_IPHONE, Browser::Safari],
            [self::FIREFOX_ANDROID_TABLET, Browser::Firefox],
            [self::OPERA, Browser::Opera],
            ['curl/8.4.0', Browser::Other],
            [null, Browser::Other],
        ];
    }

    public static function devices(): array
    {
        return [
            [self::CHROME_DESKTOP, Device::Desktop],
            [self::SAFARI_IPHONE, Device::Mobile],
            [self::CHROME_ANDROID_PHONE, Device::Mobile],
            [self::FIREFOX_ANDROID_TABLET, Device::Tablet],
            [self::GOOGLEBOT, Device::Bot],
            ['curl/8.4.0', Device::Bot],
            [null, Device::Desktop],
        ];
    }
}
