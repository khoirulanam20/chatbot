<?php

namespace Tests\Unit;

use App\Services\HumanizedResponseService;
use PHPUnit\Framework\TestCase;

class HumanizedResponseServiceTest extends TestCase
{
    private HumanizedResponseService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new HumanizedResponseService;
    }

    public function test_splits_on_delimiter(): void
    {
        $settings = ['split_bubbles' => true, 'avoid_markdown' => true];

        $result = $this->service->process("Halo!\n---\nAda yang bisa dibantu?", $settings, true);

        $this->assertCount(2, $result['chunks']);
        $this->assertSame('Halo!', $result['chunks'][0]);
        $this->assertStringContainsString('bisa dibantu', $result['chunks'][1]);
    }

    public function test_inactive_returns_single_chunk(): void
    {
        $result = $this->service->process('Satu pesan saja', ['split_bubbles' => true], false);

        $this->assertSame(['Satu pesan saja'], $result['chunks']);
    }

    public function test_strips_markdown_bullets(): void
    {
        $settings = ['split_bubbles' => false, 'avoid_markdown' => true];

        $result = $this->service->process("- Item satu\n- Item dua", $settings, true);

        $this->assertStringNotContainsString('- Item', $result['content']);
    }
}
