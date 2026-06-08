<?php

namespace Tests\Feature;

use App\Jobs\ProcessWhatsAppMessageJob;
use App\Models\Chatbot;
use App\Models\Tenant;
use App\Models\WaInstance;
use App\Services\RAGService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class WaWebhookInboundTest extends TestCase
{
    use RefreshDatabase;

    private function createWaSetup(): WaInstance
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
        ]);

        return WaInstance::withoutGlobalScopes()->create([
            'tenant_id'    => $tenant->id,
            'chatbot_id'   => $chatbot->id,
            'instance_id'  => 'Firsty',
            'phone_number' => '628123456789',
            'api_key'      => 'test-key',
            'status'       => 'active',
        ]);
    }

    public function test_webhook_queues_inbound_with_chat_id_only(): void
    {
        Queue::fake();
        config(['services.chatery.webhook_secret' => null]);

        $wa = $this->createWaSetup();

        $response = $this->postJson('/api/webhook/whatsapp', [
            'event'     => 'message',
            'sessionId' => 'Firsty',
            'data'      => [
                'type'    => 'text',
                'fromMe'  => false,
                'chatId'  => '628987654321@s.whatsapp.net',
                'content' => 'Halo bot',
                'id'      => 'inbound-chatid-001',
            ],
        ]);

        $response->assertOk()
            ->assertJson(['status' => 'queued']);

        Queue::assertPushed(ProcessWhatsAppMessageJob::class, function ($job) use ($wa) {
            return $job->waInstanceId === $wa->id
                && ($job->payload['message'] ?? '') === 'Halo bot'
                && str_contains($job->payload['from'] ?? '', '628987654321');
        });
    }

    public function test_inbound_job_runs_when_webhook_lock_already_set(): void
    {
        config(['services.chatery.webhook_secret' => null]);

        $wa = $this->createWaSetup();
        $messageId = 'inbound-lock-001';

        Cache::put("wa_lock:{$wa->id}:{$messageId}", true, now()->addMinutes(10));

        Http::fake([
            '*' => Http::response(['data' => ['id' => 'bot-reply-001']], 200),
        ]);

        $rag = Mockery::mock(RAGService::class);
        $rag->shouldReceive('processMessage')
            ->once()
            ->andReturn([
                'content' => 'Halo, ada yang bisa dibantu?',
                'chunks'  => ['Halo, ada yang bisa dibantu?'],
            ]);
        $this->app->instance(RAGService::class, $rag);

        $job = new ProcessWhatsAppMessageJob([
            'from'       => '628987654321@s.whatsapp.net',
            'message'    => 'Halo bot',
            'message_id' => $messageId,
            'type'       => 'text',
        ], $wa->id);

        $job->handle(
            app(RAGService::class),
            app(\App\Services\WaOutboundService::class),
            app(\App\Services\AgentSessionService::class),
            app(\App\Services\TakeoverNotificationService::class),
            app(\App\Services\ChatImageService::class),
            app(\App\Services\WaChateryService::class),
            app(\App\Services\WaConversationResolver::class),
        );

        $this->assertTrue(Cache::has("wa_done:{$wa->id}:{$messageId}"));
    }

    public function test_from_me_string_false_is_treated_as_inbound(): void
    {
        Queue::fake();
        config(['services.chatery.webhook_secret' => null]);

        $this->createWaSetup();

        $response = $this->postJson('/api/webhook/whatsapp', [
            'event'     => 'message',
            'sessionId' => 'Firsty',
            'data'      => [
                'type'        => 'text',
                'fromMe'      => 'false',
                'chatId'      => '628987654321@s.whatsapp.net',
                'content'     => 'Pesan customer',
                'id'          => 'inbound-fromme-false',
            ],
        ]);

        $response->assertOk()
            ->assertJson(['status' => 'queued']);

        Queue::assertPushed(ProcessWhatsAppMessageJob::class);
        Queue::assertNotPushed(\App\Jobs\ProcessWhatsAppAgentReplyJob::class);
    }
}
