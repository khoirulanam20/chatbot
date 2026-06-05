<?php

namespace Tests\Unit;

use App\Models\Chatbot;
use App\Services\AgentSessionService;
use PHPUnit\Framework\TestCase;

class ChatbotTakeoverTest extends TestCase
{
    public function test_takeover_keywords_merge_settings_and_legacy(): void
    {
        $chatbot = new Chatbot([
            'handoff_triggers' => ['agen', 'cs'],
            'settings' => [
                'takeover_keywords' => ['Hubungi Admin', ''],
            ],
        ]);

        $this->assertSame(
            ['Hubungi Admin', 'agen', 'cs'],
            $chatbot->getTakeoverKeywords()
        );
    }

    public function test_takeover_idle_minutes_fallback_chain(): void
    {
        $chatbot = new Chatbot(['settings' => []]);
        $this->assertSame(AgentSessionService::DEFAULT_MINUTES, $chatbot->getTakeoverIdleMinutes());

        $chatbot = new Chatbot(['settings' => ['agent_session_minutes' => 45]]);
        $this->assertSame(45, $chatbot->getTakeoverIdleMinutes());

        $chatbot = new Chatbot(['settings' => ['takeover_idle_minutes' => 15]]);
        $this->assertSame(15, $chatbot->getTakeoverIdleMinutes());
    }

    public function test_takeover_hold_message_prefers_settings(): void
    {
        $chatbot = new Chatbot([
            'settings' => [
                'takeover_hold_message' => 'Mohon tunggu admin ya.',
                'agent_session_message' => 'Tunggu agen.',
            ],
        ]);

        $this->assertSame('Mohon tunggu admin ya.', $chatbot->getTakeoverHoldMessage());
    }
}
