<?php

namespace Tests\Feature;

use App\Jobs\ProcessWhatsAppMessageJob;
use App\Models\Chatbot;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Tenant;
use App\Models\WaInstance;
use App\Services\AgentSessionService;
use App\Services\RAGService;
use App\Services\WaConversationResolver;
use App\Services\WaOutboundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class WaKeywordHandoffTest extends TestCase
{
    use RefreshDatabase;

    private function createWaContext(): array
    {
        $tenant = Tenant::create([
            'name'      => 'Test Tenant',
            'slug'      => 'kw-handoff',
            'plan'      => 'pro',
            'is_active' => true,
        ]);

        $chatbot = Chatbot::withoutGlobalScopes()->create([
            'tenant_id'   => $tenant->id,
            'name'        => 'KW Bot',
            'temperature' => 0.7,
            'max_context' => 10,
            'language'    => 'id',
            'is_active'   => true,
            'settings'    => [
                'takeover_keywords'       => ['cs', 'operator'],
                'takeover_hold_message'   => 'Baik, mohon ditunggu',
                'takeover_idle_minutes'   => 30,
                'pause_ai_on_human_reply' => true,
            ],
        ]);

        $wa = WaInstance::withoutGlobalScopes()->create([
            'tenant_id'    => $tenant->id,
            'chatbot_id'   => $chatbot->id,
            'instance_id'  => 'Firsty',
            'phone_number' => '628123456789',
            'api_key'      => 'test-key',
            'status'       => 'active',
        ]);

        $lidChatId = '248618594336855@lid';

        $contact = Contact::withoutGlobalScopes()->create([
            'tenant_id'  => $tenant->id,
            'identifier' => '248618594336855',
            'channel'    => 'whatsapp',
            'metadata'   => ['wa_chat_id' => $lidChatId],
        ]);

        $conversation = Conversation::create([
            'chatbot_id'      => $chatbot->id,
            'contact_id'      => $contact->id,
            'channel'         => 'whatsapp',
            'status'          => 'open',
            'is_ai_active'    => true,
            'last_message_at' => now(),
        ]);

        return compact('chatbot', 'wa', 'contact', 'conversation', 'lidChatId');
    }

    public function test_keyword_handoff_sends_hold_message_via_lid_chat_id(): void
    {
        ['wa' => $wa, 'conversation' => $conversation, 'lidChatId' => $lidChatId] = $this->createWaContext();

        $waOutbound = Mockery::mock(WaOutboundService::class);
        $waOutbound->shouldReceive('sendText')
            ->once()
            ->with(Mockery::on(fn ($instance) => $instance->id === $wa->id), $lidChatId, 'Baik, mohon ditunggu')
            ->andReturn(true);
        $this->app->instance(WaOutboundService::class, $waOutbound);

        $job = new ProcessWhatsAppMessageJob([
            'from'       => $lidChatId,
            'message'    => 'cs',
            'message_id' => 'kw-handoff-001',
            'type'       => 'text',
        ], $wa->id);

        $job->handle(
            app(RAGService::class),
            app(WaOutboundService::class),
            app(AgentSessionService::class),
            app(\App\Services\TakeoverNotificationService::class),
            app(\App\Services\ChatImageService::class),
            app(\App\Services\WaChateryService::class),
            app(WaConversationResolver::class),
        );

        $conversation->refresh();

        $this->assertFalse($conversation->is_ai_active);
        $this->assertSame('handoff', $conversation->status);
    }

    public function test_subsequent_message_after_keyword_handoff_does_not_invoke_rag(): void
    {
        ['wa' => $wa, 'conversation' => $conversation, 'lidChatId' => $lidChatId] = $this->createWaContext();

        $conversation->update([
            'status'          => 'handoff',
            'is_ai_active'    => false,
            'last_message_at' => now(),
        ]);

        $rag = Mockery::mock(RAGService::class);
        $rag->shouldNotReceive('processMessage');
        $this->app->instance(RAGService::class, $rag);

        $job = new ProcessWhatsAppMessageJob([
            'from'       => $lidChatId,
            'message'    => 'halo',
            'message_id' => 'kw-handoff-followup-001',
            'type'       => 'text',
        ], $wa->id);

        $job->handle(
            app(RAGService::class),
            app(WaOutboundService::class),
            app(AgentSessionService::class),
            app(\App\Services\TakeoverNotificationService::class),
            app(\App\Services\ChatImageService::class),
            app(\App\Services\WaChateryService::class),
            app(WaConversationResolver::class),
        );

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'role'            => 'user',
            'content'         => 'halo',
        ]);
    }

    public function test_contact_stores_wa_chat_id_from_lid_webhook(): void
    {
        ['wa' => $wa] = $this->createWaContext();

        $resolver = app(WaConversationResolver::class);
        $contact  = $resolver->findOrCreateContact($wa, '248618594336855@lid');

        $this->assertSame('248618594336855', $contact->identifier);
        $this->assertSame('248618594336855@lid', $contact->metadata['wa_chat_id']);
        $this->assertSame('248618594336855@lid', $resolver->resolveOutboundChatId($contact));
    }
}
