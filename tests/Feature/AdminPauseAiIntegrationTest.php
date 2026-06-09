<?php

namespace Tests\Feature;

use App\Jobs\ProcessWhatsAppAgentReplyJob;
use App\Jobs\ProcessWhatsAppMessageJob;
use App\Models\Chatbot;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Tenant;
use App\Models\WaInstance;
use App\Services\AgentSessionService;
use App\Services\RAGService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Concerns\CreatesTestActors;
use Tests\TestCase;

class AdminPauseAiIntegrationTest extends TestCase
{
    use CreatesTestActors;
    use RefreshDatabase;

    private function createWaContext(): array
    {
        $tenant = $this->createTenant();
        $admin  = $this->createUser($tenant, 'admin', 'admin-pause@test.test');

        $chatbot = Chatbot::withoutGlobalScopes()->create([
            'tenant_id'   => $tenant->id,
            'name'        => 'Pause Bot',
            'temperature' => 0.7,
            'max_context' => 10,
            'language'    => 'id',
            'is_active'   => true,
            'settings'    => [
                'takeover_idle_minutes'   => 30,
                'pause_ai_on_human_reply' => true,
            ],
        ]);

        $wa = WaInstance::withoutGlobalScopes()->create([
            'tenant_id'    => $tenant->id,
            'chatbot_id'   => $chatbot->id,
            'instance_id'  => 'PauseTest',
            'phone_number' => '628123456789',
            'api_key'      => 'test-key',
            'status'       => 'active',
        ]);

        $contact = Contact::withoutGlobalScopes()->create([
            'tenant_id'  => $tenant->id,
            'identifier' => '628987654321',
            'channel'    => 'whatsapp',
        ]);

        $conversation = Conversation::create([
            'chatbot_id'      => $chatbot->id,
            'contact_id'      => $contact->id,
            'channel'         => 'whatsapp',
            'status'          => 'open',
            'is_ai_active'    => true,
            'last_message_at' => now(),
        ]);

        return compact('tenant', 'admin', 'chatbot', 'wa', 'contact', 'conversation');
    }

    public function test_panel_send_message_pauses_ai_and_blocks_customer_inbound(): void
    {
        ['admin' => $admin, 'conversation' => $conversation, 'wa' => $wa] = $this->createWaContext();

        $this->actingAs($admin)
            ->post("/admin/conversations/{$conversation->id}/message", [
                'message' => 'Halo, ini admin dari panel',
            ])
            ->assertRedirect();

        $conversation->refresh();
        $this->assertFalse($conversation->is_ai_active);
        $this->assertSame('handoff', $conversation->status);

        $rag = Mockery::mock(RAGService::class);
        $rag->shouldNotReceive('processMessage');
        $this->app->instance(RAGService::class, $rag);

        $job = new ProcessWhatsAppMessageJob([
            'from'       => '628987654321@s.whatsapp.net',
            'message'    => 'Customer balas lagi',
            'message_id' => 'customer-after-panel-001',
            'type'       => 'text',
        ], $wa->id);

        $job->handle(
            app(RAGService::class),
            app(\App\Services\WaOutboundService::class),
            app(AgentSessionService::class),
            app(\App\Services\TakeoverNotificationService::class),
            app(\App\Services\ChatImageService::class),
            app(\App\Services\WaChateryService::class),
            app(\App\Services\WaConversationResolver::class),
        );

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'role'            => 'user',
            'content'         => 'Customer balas lagi',
        ]);
    }

    public function test_wa_from_me_webhook_sync_pause_blocks_customer_inbound(): void
    {
        config(['services.chatery.webhook_secret' => null]);

        ['conversation' => $conversation, 'wa' => $wa] = $this->createWaContext();

        $response = $this->postJson('/api/webhook/whatsapp', [
            'event'     => 'message',
            'sessionId' => 'PauseTest',
            'data'      => [
                'type'    => 'text',
                'fromMe'  => true,
                'chatId'  => '628987654321@s.whatsapp.net',
                'content' => 'Admin balas dari HP',
                'id'      => 'agent-sync-pause-001',
            ],
        ]);

        $response->assertOk()
            ->assertJson(['status' => 'queued_agent_reply']);

        $conversation->refresh();
        $this->assertFalse($conversation->is_ai_active);
        $this->assertSame('handoff', $conversation->status);

        $agentJob = new ProcessWhatsAppAgentReplyJob([
            'customer_id' => '628987654321@s.whatsapp.net',
            'message'     => 'Admin balas dari HP',
            'message_id'  => 'agent-sync-pause-001',
            'type'        => 'text',
        ], $wa->id);

        $agentJob->handle(
            app(AgentSessionService::class),
            app(\App\Services\WaConversationResolver::class)
        );

        $rag = Mockery::mock(RAGService::class);
        $rag->shouldNotReceive('processMessage');
        $this->app->instance(RAGService::class, $rag);

        $inboundJob = new ProcessWhatsAppMessageJob([
            'from'       => '628987654321@s.whatsapp.net',
            'message'    => 'Customer balas setelah admin',
            'message_id' => 'customer-after-wa-admin-001',
            'type'       => 'text',
        ], $wa->id);

        $inboundJob->handle(
            app(RAGService::class),
            app(\App\Services\WaOutboundService::class),
            app(AgentSessionService::class),
            app(\App\Services\TakeoverNotificationService::class),
            app(\App\Services\ChatImageService::class),
            app(\App\Services\WaChateryService::class),
            app(\App\Services\WaConversationResolver::class),
        );

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'role'            => 'user',
            'content'         => 'Customer balas setelah admin',
        ]);
    }

    public function test_update_status_open_during_handoff_resumes_ai_via_end_session(): void
    {
        ['admin' => $admin, 'conversation' => $conversation] = $this->createWaContext();

        $conversation->update([
            'status'          => 'handoff',
            'is_ai_active'    => false,
            'last_message_at' => now(),
        ]);

        $this->actingAs($admin)
            ->patch("/admin/conversations/{$conversation->id}/status", ['status' => 'open'])
            ->assertRedirect();

        $conversation->refresh();
        $this->assertTrue($conversation->is_ai_active);
        $this->assertSame('open', $conversation->status);
    }

    public function test_prepare_for_inbound_repairs_orphan_state_with_recent_agent_message(): void
    {
        ['conversation' => $conversation] = $this->createWaContext();

        $conversation->update([
            'status'          => 'open',
            'is_ai_active'    => false,
            'last_message_at' => now(),
        ]);

        \App\Models\Message::create([
            'conversation_id' => $conversation->id,
            'role'            => 'agent',
            'content'         => 'Balasan admin',
        ]);

        $service = app(AgentSessionService::class);
        $prepared = $service->prepareForInbound($conversation->fresh()->load('chatbot'));

        $this->assertFalse($prepared->is_ai_active);
        $this->assertSame('handoff', $prepared->status);
        $this->assertTrue($service->isAiBlocked($prepared));
    }
}
