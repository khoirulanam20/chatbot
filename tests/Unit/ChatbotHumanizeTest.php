<?php

namespace Tests\Unit;

use App\Models\Chatbot;
use PHPUnit\Framework\TestCase;

class ChatbotHumanizeTest extends TestCase
{
    public function test_humanize_disabled_without_explicit_config(): void
    {
        $chatbot = new Chatbot([
            'settings' => ['persona' => ['role' => 'Agen']],
        ]);

        $this->assertFalse($chatbot->hasExplicitHumanizeConfig());
        $this->assertFalse($chatbot->isHumanizeEnabledFor('web'));
        $this->assertFalse($chatbot->isHumanizeEnabledFor('whatsapp'));
    }

    public function test_humanize_enabled_when_explicitly_saved(): void
    {
        $chatbot = new Chatbot([
            'settings' => [
                'persona' => [
                    'humanize' => [
                        'enabled' => true,
                        'channels' => ['web'],
                    ],
                ],
            ],
        ]);

        $this->assertTrue($chatbot->isHumanizeEnabledFor('web'));
        $this->assertFalse($chatbot->isHumanizeEnabledFor('whatsapp'));
    }

    public function test_compose_humanize_block_empty_when_disabled(): void
    {
        $chatbot = new Chatbot(['settings' => []]);

        $this->assertSame('', $chatbot->composeHumanizeBlock('web'));
    }
}
