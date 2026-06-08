<?php

namespace Tests\Unit;

use App\Support\UrlSafety;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UrlSafetyTest extends TestCase
{
    #[DataProvider('blockedUrlsProvider')]
    public function test_rejects_private_or_local_urls(string $url): void
    {
        $this->expectException(\RuntimeException::class);

        UrlSafety::assertPublicHttpUrl($url);
    }

    public static function blockedUrlsProvider(): array
    {
        return [
            'localhost' => ['http://localhost/page'],
            'loopback ip' => ['http://127.0.0.1/admin'],
            'metadata endpoint' => ['http://169.254.169.254/latest/meta-data'],
            'file scheme' => ['file:///etc/passwd'],
        ];
    }

    public function test_allows_public_https_url(): void
    {
        UrlSafety::assertPublicHttpUrl('https://example.com/docs');

        $this->assertTrue(true);
    }
}
