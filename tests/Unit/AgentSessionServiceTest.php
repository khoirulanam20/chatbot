<?php

namespace Tests\Unit;

use App\Models\Chatbot;
use App\Models\Conversation;
use App\Services\AgentSessionService;
use PHPUnit\Framework\TestCase;

class AgentSessionServiceTest extends TestCase
{
    public function test_is_in_handoff_requires_both_flags(): void
    {
        $service = new AgentSessionService();

        $open = new Conversation(['is_ai_active' => true, 'status' => 'open']);
        $this->assertFalse($service->isInHandoff($open));

        $handoff = new Conversation(['is_ai_active' => false, 'status' => 'handoff']);
        $this->assertTrue($service->isInHandoff($handoff));
    }

    public function test_get_idle_minutes_delegates_to_chatbot(): void
    {
        $service = new AgentSessionService();
        $chatbot = new Chatbot(['settings' => ['takeover_idle_minutes' => 15]]);

        $this->assertSame(15, $service->getIdleMinutes($chatbot));
    }

    public function test_is_ai_blocked_covers_orphan_and_handoff_states(): void
    {
        $service = new AgentSessionService();

        $active = new Conversation(['is_ai_active' => true, 'status' => 'open']);
        $this->assertFalse($service->isAiBlocked($active));

        $orphan = new Conversation(['is_ai_active' => false, 'status' => 'open']);
        $this->assertFalse($service->isAiBlocked($orphan));

        $handoff = new Conversation(['is_ai_active' => false, 'status' => 'handoff']);
        $this->assertTrue($service->isAiBlocked($handoff));
    }
}
