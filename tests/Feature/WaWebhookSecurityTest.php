<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WaWebhookSecurityTest extends TestCase
{
    public function test_webhook_rejects_missing_signature_when_secret_set(): void
    {
        config(['services.chatery.webhook_secret' => 'test-secret']);

        $response = $this->postJson('/api/webhook/whatsapp', ['event' => 'message']);

        $response->assertStatus(401);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        config(['services.chatery.webhook_secret' => 'test-secret']);

        $response = $this->postJson('/api/webhook/whatsapp', ['event' => 'message'], [
            'X-Chatery-Signature' => 'sha256=invalid',
        ]);

        $response->assertStatus(401);
    }
}
