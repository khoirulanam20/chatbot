<?php

namespace Tests\Feature;

use Tests\TestCase;

class WaWebhookProductionTest extends TestCase
{
    public function test_webhook_rejects_when_secret_empty_in_production(): void
    {
        config(['services.chatery.webhook_secret' => null]);
        app()->detectEnvironment(fn () => 'production');

        $response = $this->postJson('/api/webhook/whatsapp', ['event' => 'message']);

        $response->assertStatus(500)
            ->assertJson(['error' => 'Webhook secret not configured']);
    }
}
