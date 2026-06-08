<?php

namespace Tests\Unit;

use App\Models\Message;
use App\Services\VisionMessageFormatter;
use Tests\TestCase;

class VisionMessageFormatterTest extends TestCase
{
    public function test_supports_vision_for_gpt4o(): void
    {
        $this->assertTrue(VisionMessageFormatter::supportsVision('gpt-4o'));
        $this->assertTrue(VisionMessageFormatter::supportsVision('gpt-4o-mini'));
        $this->assertTrue(VisionMessageFormatter::supportsVision('gpt-40-mini'));
        $this->assertFalse(VisionMessageFormatter::supportsVision('gpt-3.5-turbo'));
    }

    public function test_normalize_model_typo_gpt40_mini(): void
    {
        $this->assertSame('gpt-4o-mini', VisionMessageFormatter::normalizeModel('gpt-40-mini'));
        $this->assertSame('gpt-4o', VisionMessageFormatter::normalizeModel('gpt-40'));
    }

    public function test_to_absolute_url_from_relative_path(): void
    {
        config(['app.url' => 'https://chatbot.test']);

        $this->assertSame(
            'https://chatbot.test/storage/chat-images/1/2/foo.webp',
            VisionMessageFormatter::toAbsoluteUrl('/storage/chat-images/1/2/foo.webp')
        );
    }

    public function test_format_image_user_message_multimodal(): void
    {
        config(['app.url' => 'https://chatbot.test']);

        $formatted = VisionMessageFormatter::formatImageUserMessage(
            '/storage/test.webp',
            'Apa ini?'
        );

        $this->assertSame('user', $formatted['role']);
        $this->assertCount(2, $formatted['content']);
        $this->assertSame('text', $formatted['content'][0]['type']);
        $this->assertSame('Apa ini?', $formatted['content'][0]['text']);
        $this->assertSame('image_url', $formatted['content'][1]['type']);
        $this->assertSame(
            'https://chatbot.test/storage/test.webp',
            $formatted['content'][1]['image_url']['url']
        );
    }

    public function test_format_message_uses_metadata_for_image(): void
    {
        config(['app.url' => 'https://chatbot.test']);

        $message = new Message([
            'role'     => 'user',
            'content'  => 'Lihat gambar',
            'metadata' => ['type' => 'image', 'url' => '/storage/img.webp'],
        ]);

        $formatted = VisionMessageFormatter::formatMessage($message);

        $this->assertSame('user', $formatted['role']);
        $this->assertIsArray($formatted['content']);
        $this->assertSame('image_url', $formatted['content'][1]['type']);
    }
}
