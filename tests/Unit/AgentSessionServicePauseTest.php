<?php

namespace Tests\Unit;

use App\Models\Chatbot;
use App\Models\Conversation;
use App\Models\User;
use App\Services\AgentSessionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTestActors;
use Tests\TestCase;

class AgentSessionServicePauseTest extends TestCase
{
    use CreatesTestActors;
    use RefreshDatabase;

    public function test_pause_for_human_reply_sets_handoff_without_agent(): void
    {
        $tenant = $this->createTenant();
        $chatbot = $this->createChatbot($tenant);
        $conversation = Conversation::create([
            'chatbot_id'      => $chatbot->id,
            'channel'         => 'whatsapp',
            'status'          => 'open',
            'is_ai_active'    => true,
            'last_message_at' => now(),
        ]);

        app(AgentSessionService::class)->pauseForHumanReply($conversation);
        $conversation->refresh();

        $this->assertFalse($conversation->is_ai_active);
        $this->assertSame('handoff', $conversation->status);
        $this->assertNull($conversation->assigned_agent_id);
        $this->assertNotNull($conversation->agent_session_started_at);
    }

    public function test_pause_for_human_reply_skipped_when_toggle_disabled(): void
    {
        $tenant = $this->createTenant();
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

        $paused = app(AgentSessionService::class)->pauseForHumanReply($conversation);
        $conversation->refresh();

        $this->assertFalse($paused);
        $this->assertTrue($conversation->is_ai_active);
        $this->assertSame('open', $conversation->status);
    }

    public function test_pause_for_human_reply_assigns_agent_when_provided(): void
    {
        $tenant = $this->createTenant();
        $chatbot = $this->createChatbot($tenant);
        $admin = $this->createUser($tenant, 'admin');
        $conversation = Conversation::create([
            'chatbot_id'      => $chatbot->id,
            'channel'         => 'whatsapp',
            'status'          => 'open',
            'is_ai_active'    => true,
            'last_message_at' => now(),
        ]);

        app(AgentSessionService::class)->pauseForHumanReply($conversation, $admin);
        $conversation->refresh();

        $this->assertSame($admin->id, $conversation->assigned_agent_id);
    }

    public function test_expire_if_due_resumes_ai_after_idle_minutes(): void
    {
        Carbon::setTestNow('2026-06-08 12:00:00');

        $tenant = $this->createTenant();
        $chatbot = Chatbot::withoutGlobalScopes()->create([
            'tenant_id'   => $tenant->id,
            'name'        => 'Bot',
            'temperature' => 0.7,
            'max_context' => 5,
            'language'    => 'id',
            'is_active'   => true,
            'settings'    => ['takeover_idle_minutes' => 15],
        ]);

        $conversation = Conversation::create([
            'chatbot_id'      => $chatbot->id,
            'channel'         => 'whatsapp',
            'status'          => 'handoff',
            'is_ai_active'    => false,
            'last_message_at' => now()->subMinutes(16),
        ]);

        $service = app(AgentSessionService::class);
        $expired = $service->expireIfDue($conversation->load('chatbot'));

        $this->assertTrue($expired);
        $conversation->refresh();
        $this->assertTrue($conversation->is_ai_active);
        $this->assertSame('open', $conversation->status);

        Carbon::setTestNow();
    }
}
