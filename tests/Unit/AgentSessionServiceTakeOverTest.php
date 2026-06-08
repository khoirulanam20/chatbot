<?php

namespace Tests\Unit;

use App\Models\Chatbot;
use App\Models\Conversation;
use App\Services\AgentSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTestActors;
use Tests\TestCase;

class AgentSessionServiceTakeOverTest extends TestCase
{
    use CreatesTestActors;
    use RefreshDatabase;

    public function test_take_over_always_enters_handoff_even_when_pause_toggle_disabled(): void
    {
        $tenant = $this->createTenant();
        $admin = $this->createUser($tenant, 'admin');
        $chatbot = Chatbot::withoutGlobalScopes()->create([
            'tenant_id'   => $tenant->id,
            'name'        => 'Bot',
            'temperature' => 0.7,
            'max_context' => 5,
            'language'    => 'id',
            'is_active'   => true,
            'settings'    => ['pause_ai_on_human_reply' => false],
        ]);

        $conversation = Conversation::create([
            'chatbot_id'      => $chatbot->id,
            'channel'         => 'whatsapp',
            'status'          => 'open',
            'is_ai_active'    => true,
            'last_message_at' => now(),
        ]);

        app(AgentSessionService::class)->takeOver($conversation, $admin);
        $conversation->refresh();

        $this->assertFalse($conversation->is_ai_active);
        $this->assertSame('handoff', $conversation->status);
        $this->assertSame($admin->id, $conversation->assigned_agent_id);
    }
}
