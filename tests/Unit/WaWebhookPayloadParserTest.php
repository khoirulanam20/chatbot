<?php

namespace Tests\Unit;

use App\Services\WaWebhookPayloadParser;
use Tests\TestCase;

class WaWebhookPayloadParserTest extends TestCase
{
    public function test_image_with_caption_in_content_and_media_url(): void
    {
        config(['services.chatery.base_url' => 'https://wa.firstudio.id/api']);

        $parsed = WaWebhookPayloadParser::parseInbound([
            'type'     => 'image',
            'content'  => 'Ini deskripsi produk',
            'mediaUrl' => 'https://wa.firstudio.id/media/s1/c1/photo.jpg',
        ]);

        $this->assertSame('image', $parsed['type']);
        $this->assertSame('Ini deskripsi produk', $parsed['message']);
        $this->assertSame('https://wa.firstudio.id/media/s1/c1/photo.jpg', $parsed['media_url']);
    }

    public function test_image_with_caption_field(): void
    {
        $parsed = WaWebhookPayloadParser::parseInbound([
            'type'     => 'image',
            'caption'  => 'Berapa harganya?',
            'mediaUrl' => 'https://example.com/photo.jpg',
        ]);

        $this->assertSame('Berapa harganya?', $parsed['message']);
        $this->assertSame('https://example.com/photo.jpg', $parsed['media_url']);
    }

    public function test_image_does_not_use_caption_as_media_url(): void
    {
        $parsed = WaWebhookPayloadParser::parseInbound([
            'type'    => 'image',
            'content' => 'Teks caption tanpa mediaUrl',
        ]);

        $this->assertNull($parsed);
    }

    public function test_image_uses_content_when_it_is_url(): void
    {
        $parsed = WaWebhookPayloadParser::parseInbound([
            'type'    => 'image',
            'content' => 'https://example.com/photo.jpg',
            'caption' => 'Lihat gambar ini',
        ]);

        $this->assertSame('Lihat gambar ini', $parsed['message']);
        $this->assertSame('https://example.com/photo.jpg', $parsed['media_url']);
    }

    public function test_relative_media_url_is_normalized(): void
    {
        config(['services.chatery.base_url' => 'https://wa.firstudio.id/api']);

        $this->assertSame(
            'https://wa.firstudio.id/media/bot-1/file.jpg',
            WaWebhookPayloadParser::normalizeMediaUrl('/media/bot-1/file.jpg')
        );
    }

    public function test_text_message_uses_text_field(): void
    {
        $parsed = WaWebhookPayloadParser::parseInbound([
            'type' => 'chat',
            'text' => 'Halo',
        ]);

        $this->assertSame('text', $parsed['type']);
        $this->assertSame('Halo', $parsed['message']);
    }
}
