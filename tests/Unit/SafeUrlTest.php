<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Rules\SafeUrl;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SafeUrlTest extends TestCase
{
    #[DataProvider('publicUrls')]
    public function test_accepts_public_http_urls(string $url): void
    {
        $this->assertTrue($this->passes($url), "Expected {$url} to pass");
    }

    #[DataProvider('unsafeUrls')]
    public function test_rejects_unsafe_urls(string $url): void
    {
        $this->assertFalse($this->passes($url), "Expected {$url} to fail");
    }

    public function test_rejects_urls_pointing_back_at_this_service(): void
    {
        config(['app.url' => 'https://sho.rt']);

        $this->assertFalse($this->passes('https://sho.rt/abc'));
        $this->assertTrue($this->passes('https://not-sho.rt/abc'));
    }

    public function test_rejects_urls_over_the_length_limit(): void
    {
        config(['shortener.max_url_length' => 50]);

        $this->assertFalse($this->passes('https://example.com/'.str_repeat('a', 60)));
    }

    public static function publicUrls(): array
    {
        return [
            ['https://example.com'],
            ['http://example.com/path?x=1&y=2#frag'],
            ['https://sub.domain.example.co.uk/very/long/path'],
            ['https://8.8.8.8/dns'],
            ['https://xn--80ak6aa92e.com'],
        ];
    }

    public static function unsafeUrls(): array
    {
        return [
            'no scheme' => ['example.com'],
            'ftp' => ['ftp://example.com/file'],
            'javascript' => ['javascript:alert(1)'],
            'file' => ['file:///etc/passwd'],
            'localhost' => ['http://localhost/admin'],
            'localhost subdomain' => ['http://app.localhost/'],
            'loopback ip' => ['http://127.0.0.1:8000/'],
            'short loopback' => ['http://127.1/'],
            'decimal ip' => ['http://2130706433/'],
            'private 10.x' => ['http://10.0.0.5/'],
            'private 192.168' => ['http://192.168.1.1/router'],
            'private 172.16' => ['http://172.16.0.1/'],
            'link local / cloud metadata' => ['http://169.254.169.254/latest/meta-data'],
            'ipv6 loopback' => ['http://[::1]/'],
            'bare hostname' => ['http://intranet/'],
            'dot local' => ['http://printer.local/'],
            'dot internal' => ['http://db.internal/'],
            'dot test' => ['http://myapp.test/'],
            'garbage' => ['not a url at all'],
        ];
    }

    private function passes(string $url): bool
    {
        return Validator::make(['url' => $url], ['url' => [new SafeUrl]])->passes();
    }
}
