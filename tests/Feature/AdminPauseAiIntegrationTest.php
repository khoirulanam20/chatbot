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
use Illuminate\Support\Facades\Queue;
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

    public function test_wa_from_me_webhook_does_not_pause_ai(): void
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
        $this->assertTrue($conversation->is_ai_active);
        $this->assertSame('open', $conversation->status);

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

        $conversation->refresh();
        $this->assertTrue($conversation->is_ai_active);
        $this->assertSame('open', $conversation->status);
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'role'            => 'agent',
            'content'         => 'Admin balas dari HP',
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

    public function test_panel_reply_keeps_ai_paused_when_last_message_is_stale(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            '*' => \Illuminate\Support\Facades\Http::response(['data' => ['id' => 'panel-msg-001']], 200),
        ]);

        ['admin' => $admin, 'conversation' => $conversation, 'wa' => $wa, 'chatbot' => $chatbot] = $this->createWaContext();

        $chatbot->update([
            'settings' => [
                'takeover_idle_minutes'   => 1,
                'pause_ai_on_human_reply' => true,
            ],
        ]);
        $this->assertSame(1, $chatbot->fresh()->getTakeoverIdleMinutes());

        $conversation->update([
            'last_message_at' => now()->subMinutes(5),
        ]);

        $this->actingAs($admin)
            ->post("/admin/conversations/{$conversation->id}/message", [
                'message' => 'Admin balas dari panel setelah jeda lama',
            ])
            ->assertRedirect();

        $conversation->refresh();
        $this->assertFalse($conversation->is_ai_active);
        $this->assertSame('handoff', $conversation->status);

        $agentSession = app(AgentSessionService::class);
        $prepared = $agentSession->prepareForInbound($conversation->fresh()->load('chatbot'));

        $this->assertTrue($agentSession->isAiBlocked($prepared));

        $rag = Mockery::mock(RAGService::class);
        $rag->shouldNotReceive('processMessage');
        $this->app->instance(RAGService::class, $rag);

        $inboundJob = new ProcessWhatsAppMessageJob([
            'from'       => '628987654321@s.whatsapp.net',
            'message'    => 'Customer balas setelah admin',
            'message_id' => 'customer-after-stale-admin-001',
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
    }

    public function test_wa_admin_and_customer_use_same_conversation_when_chat_id_is_lid(): void
    {
        config(['services.chatery.webhook_secret' => null]);

        ['chatbot' => $chatbot, 'wa' => $wa] = $this->createWaContext();

        $lidChatId = '123456789012345@lid';

        $contact = Contact::withoutGlobalScopes()->create([
            'tenant_id'  => $chatbot->tenant_id,
            'identifier' => '123456789012345',
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

        Queue::fake();

        $this->postJson('/api/webhook/whatsapp', [
            'event'     => 'message',
            'sessionId' => 'PauseTest',
            'data'      => [
                'type'    => 'text',
                'fromMe'  => true,
                'chatId'  => $lidChatId,
                'content' => 'Admin balas via LID chat',
                'id'      => 'agent-lid-001',
            ],
        ])->assertOk()->assertJson(['status' => 'queued_agent_reply']);

        $conversation->refresh();
        $this->assertTrue($conversation->is_ai_active);
        $this->assertSame('open', $conversation->status);

        $agentJob = new ProcessWhatsAppAgentReplyJob([
            'customer_id' => $lidChatId,
            'message'     => 'Admin balas via LID chat',
            'message_id'  => 'agent-lid-001',
            'type'        => 'text',
        ], $wa->id);

        $agentJob->handle(
            app(AgentSessionService::class),
            app(\App\Services\WaConversationResolver::class)
        );

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'role'            => 'agent',
            'content'         => 'Admin balas via LID chat',
        ]);

        \Illuminate\Support\Facades\Http::fake([
            '*' => \Illuminate\Support\Facades\Http::response(['data' => ['id' => 'ai-reply-lid-001']], 200),
        ]);

        $rag = Mockery::mock(RAGService::class);
        $rag->shouldReceive('processMessage')
            ->once()
            ->with(
                Mockery::on(fn ($conv) => $conv->id === $conversation->id),
                'Customer balas setelah admin LID'
            )
            ->andReturn([
                'content' => 'Balasan AI',
                'chunks'  => ['Balasan AI'],
            ]);
        $this->app->instance(RAGService::class, $rag);

        $inboundJob = new ProcessWhatsAppMessageJob([
            'from'       => $lidChatId,
            'message'    => 'Customer balas setelah admin LID',
            'message_id' => 'customer-lid-001',
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
    }

    public function test_customer_inbound_prefers_chat_id_over_sender_phone(): void
    {
        config(['services.chatery.webhook_secret' => null]);

        ['conversation' => $conversation, 'wa' => $wa] = $this->createWaContext();

        $conversation->update([
            'status'          => 'handoff',
            'is_ai_active'    => false,
            'last_message_at' => now(),
        ]);

        $rag = Mockery::mock(RAGService::class);
        $rag->shouldNotReceive('processMessage');
        $this->app->instance(RAGService::class, $rag);

        $inboundJob = new ProcessWhatsAppMessageJob([
            'from'       => '628987654321@s.whatsapp.net',
            'message'    => 'Pesan customer',
            'message_id' => 'customer-chatid-priority-001',
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
            'content'         => 'Pesan customer',
        ]);
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
