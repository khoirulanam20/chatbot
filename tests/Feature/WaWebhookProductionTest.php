<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WaWebhookProductionTest extends TestCase
{
    public function test_webhook_allowed_when_secret_empty_in_production(): void
    {
        Queue::fake();
        config(['services.chatery.webhook_secret' => null]);
        app()->detectEnvironment(fn () => 'production');

        $response = $this->postJson('/api/webhook/whatsapp', ['event' => 'message']);

        $response->assertOk();
    }
}
