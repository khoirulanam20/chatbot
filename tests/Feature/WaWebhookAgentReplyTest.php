<?php

namespace Tests\Feature;

use App\Jobs\ProcessWhatsAppAgentReplyJob;
use App\Jobs\ProcessWhatsAppMessageJob;
use App\Models\Chatbot;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Tenant;
use App\Models\WaInstance;
use App\Services\WaOutboundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WaWebhookAgentReplyTest extends TestCase
{
    use RefreshDatabase;

    private function createWaSetup(int $idleMinutes = 30): array
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
                'takeover_idle_minutes'   => $idleMinutes,
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

        return compact('tenant', 'chatbot', 'wa');
    }

    public function test_from_me_webhook_queues_agent_reply_job(): void
    {
        Queue::fake();
        config(['services.chatery.webhook_secret' => null]);

        ['wa' => $wa] = $this->createWaSetup();

        $response = $this->postJson('/api/webhook/whatsapp', [
            'event'     => 'message',
            'sessionId' => 'Firsty',
            'data'      => [
                'type'        => 'text',
                'fromMe'      => true,
                'chatId'      => '628987654321@s.whatsapp.net',
                'content'     => 'Halo, ini admin',
                'id'          => 'agent-msg-001',
            ],
        ]);

        $response->assertOk()
            ->assertJson(['status' => 'queued_agent_reply']);

        Queue::assertPushed(ProcessWhatsAppAgentReplyJob::class, function ($job) use ($wa) {
            return $job->waInstanceId === $wa->id
                && ($job->payload['message'] ?? '') === 'Halo, ini admin'
                && str_contains($job->payload['customer_id'] ?? '', '628987654321');
        });

        Queue::assertNotPushed(ProcessWhatsAppMessageJob::class);
    }

    public function test_agent_reply_job_pauses_ai_and_saves_message(): void
    {
        config(['services.chatery.webhook_secret' => null]);

        ['chatbot' => $chatbot, 'wa' => $wa] = $this->createWaSetup();

        $contact = Contact::withoutGlobalScopes()->create([
            'tenant_id'  => $chatbot->tenant_id,
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

        $job = new ProcessWhatsAppAgentReplyJob([
            'customer_id' => '628987654321@s.whatsapp.net',
            'message'     => 'Balasan admin dari HP',
            'message_id'  => 'agent-msg-002',
            'type'        => 'text',
        ], $wa->id);

        $job->handle(
            app(\App\Services\AgentSessionService::class),
            app(\App\Services\WaConversationResolver::class)
        );

        $conversation->refresh();

        $this->assertFalse($conversation->is_ai_active);
        $this->assertSame('handoff', $conversation->status);
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'role'            => 'agent',
            'content'         => 'Balasan admin dari HP',
        ]);
    }

    public function test_from_me_with_outbound_cache_is_ignored(): void
    {
        Queue::fake();
        config(['services.chatery.webhook_secret' => null]);

        ['wa' => $wa] = $this->createWaSetup();

        Cache::put("wa_outbound:{$wa->id}:id:bot-msg-123", true, now()->addMinutes(10));

        $response = $this->postJson('/api/webhook/whatsapp', [
            'event'     => 'message',
            'sessionId' => 'Firsty',
            'data'      => [
                'type'    => 'text',
                'fromMe'  => true,
                'chatId'  => '628987654321@s.whatsapp.net',
                'content' => 'Ini balasan AI',
                'id'      => 'bot-msg-123',
            ],
        ]);

        $response->assertOk()
            ->assertJson(['status' => 'ignored_outbound_echo']);

        Queue::assertNothingPushed();
    }

    public function test_agent_reply_does_not_pause_ai_when_toggle_disabled(): void
    {
        config(['services.chatery.webhook_secret' => null]);

        ['chatbot' => $chatbot, 'wa' => $wa] = $this->createWaSetup();

        $chatbot->update([
            'settings' => array_merge($chatbot->settings ?? [], [
                'pause_ai_on_human_reply' => false,
            ]),
        ]);

        $contact = Contact::withoutGlobalScopes()->create([
            'tenant_id'  => $chatbot->tenant_id,
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

        $job = new ProcessWhatsAppAgentReplyJob([
            'customer_id' => '628987654321@s.whatsapp.net',
            'message'     => 'Balasan admin',
            'message_id'  => 'agent-msg-disabled',
            'type'        => 'text',
        ], $wa->id);

        $job->handle(
            app(\App\Services\AgentSessionService::class),
            app(\App\Services\WaConversationResolver::class)
        );

        $conversation->refresh();

        $this->assertTrue($conversation->is_ai_active);
        $this->assertSame('open', $conversation->status);
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'role'            => 'agent',
            'content'         => 'Balasan admin',
        ]);
    }

    public function test_from_me_with_content_hash_echo_still_pauses_ai(): void
    {
        config(['services.chatery.webhook_secret' => null]);

        ['chatbot' => $chatbot, 'wa' => $wa] = $this->createWaSetup();

        $contact = Contact::withoutGlobalScopes()->create([
            'tenant_id'  => $chatbot->tenant_id,
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

        Cache::put(
            'wa_outbound:' . $wa->id . ':hash:628987654321:' . md5('Pesan sama dengan bot'),
            true,
            now()->addMinutes(10)
        );

        $response = $this->postJson('/api/webhook/whatsapp', [
            'event'     => 'message',
            'sessionId' => 'Firsty',
            'data'      => [
                'type'    => 'text',
                'fromMe'  => true,
                'chatId'  => '628987654321@s.whatsapp.net',
                'content' => 'Pesan sama dengan bot',
                'id'      => 'agent-hash-echo-001',
            ],
        ]);

        $response->assertOk()
            ->assertJson(['status' => 'queued_agent_reply']);

        $conversation->refresh();

        $this->assertFalse($conversation->is_ai_active);
        $this->assertSame('handoff', $conversation->status);
    }

    public function test_outbound_echo_detection_by_content_hash(): void
    {
        ['wa' => $wa] = $this->createWaSetup();

        Cache::put(
            'wa_outbound:' . $wa->id . ':hash:628987654321:' . md5('Pesan bot'),
            true,
            now()->addMinutes(10)
        );

        $this->assertTrue(WaOutboundService::isOutboundEcho(
            $wa->id,
            null,
            '628987654321@s.whatsapp.net',
            'Pesan bot'
        ));
    }
}
