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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class WaHandoffIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function createWaContext(): array
    {
        $tenant = Tenant::create([
            'name'      => 'Test Tenant',
            'slug'      => 'test-tenant',
            'plan'      => 'pro',
            'is_active' => true,
        ]);

        $chatbot = Chatbot::withoutGlobalScopes()->create([
            'tenant_id'   => $tenant->id,
            'name'        => 'Test Bot',
            'temperature' => 0.7,
            'max_context' => 10,
            'language'    => 'id',
            'is_active'   => true,
            'settings'    => [
                'takeover_idle_minutes'   => 15,
                'pause_ai_on_human_reply' => true,
            ],
        ]);

        $wa = WaInstance::withoutGlobalScopes()->create([
            'tenant_id'   => $tenant->id,
            'chatbot_id'  => $chatbot->id,
            'instance_id' => 'Firsty',
            'phone_number'=> '628123456789',
            'api_key'     => 'test-key',
            'status'      => 'active',
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
            'status'          => 'handoff',
            'is_ai_active'    => false,
            'last_message_at' => now(),
        ]);

        return compact('wa', 'chatbot', 'contact', 'conversation');
    }

    public function test_inbound_job_does_not_call_rag_during_handoff(): void
    {
        ['wa' => $wa, 'conversation' => $conversation] = $this->createWaContext();

        $rag = Mockery::mock(RAGService::class);
        $rag->shouldNotReceive('processMessage');
        $this->app->instance(RAGService::class, $rag);

        $job = new ProcessWhatsAppMessageJob([
            'from'       => '628987654321@s.whatsapp.net',
            'message'    => 'Halo lagi',
            'message_id' => 'inbound-handoff-001',
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
            'content'         => 'Halo lagi',
        ]);
    }

    public function test_expire_command_resumes_ai_after_idle(): void
    {
        Carbon::setTestNow('2026-06-08 12:00:00');

        ['conversation' => $conversation] = $this->createWaContext();
        $conversation->update(['last_message_at' => now()->subMinutes(20)]);

        $expired = app(AgentSessionService::class)->expireIfDue($conversation->fresh()->load('chatbot'));

        $this->assertTrue($expired);
        $conversation->refresh();
        $this->assertTrue($conversation->is_ai_active);
        $this->assertSame('open', $conversation->status);

        Carbon::setTestNow();
    }
}
