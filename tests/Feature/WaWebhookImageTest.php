<?php

namespace Tests\Feature;

use App\Jobs\ProcessWhatsAppMessageJob;
use App\Models\Chatbot;
use App\Models\Tenant;
use App\Models\WaInstance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WaWebhookImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_queues_image_message(): void
    {
        Queue::fake();
        config(['services.chatery.webhook_secret' => null]);

        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
            'plan' => 'pro',
            'is_active' => true,
        ]);
        $chatbot = Chatbot::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Test',
            'temperature' => 0.7,
            'max_context' => 10,
            'language' => 'id',
            'is_active' => true,
        ]);
        $wa = WaInstance::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'chatbot_id' => $chatbot->id,
            'instance_id' => 'Firsty',
            'phone_number' => '628123456789',
            'api_key' => 'test-key',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/webhook/whatsapp', [
            'event' => 'message',
            'sessionId' => 'Firsty',
            'data' => [
                'type' => 'image',
                'senderPhone' => '628987654321',
                'chatId' => '628987654321@s.whatsapp.net',
                'content' => 'https://example.com/photo.jpg',
                'mediaUrl' => 'https://example.com/photo.jpg',
                'id' => 'msg-img-001',
                'fromMe' => false,
            ],
        ]);

        $response->assertOk()
            ->assertJson(['status' => 'queued']);

        Queue::assertPushed(ProcessWhatsAppMessageJob::class, function ($job) use ($wa) {
            return $job->waInstanceId === $wa->id
                && ($job->payload['type'] ?? '') === 'image'
                && ! empty($job->payload['media_url']);
        });
    }
}
