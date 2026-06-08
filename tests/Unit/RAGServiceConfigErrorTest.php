<?php

namespace Tests\Unit;

use App\Models\Contact;
use App\Models\Conversation;
use App\Services\RAGService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTestActors;
use Tests\TestCase;

class RAGServiceConfigErrorTest extends TestCase
{
    use CreatesTestActors;
    use RefreshDatabase;

    public function test_configuration_error_returns_explicit_message(): void
    {
        config([
            'services.sumopod.api_key'     => '',
            'services.sumopod.base_url'    => 'https://ai.example.com/v1',
            'services.sumopod.embed_model' => 'text-embedding-3-small',
            'services.sumopod.chat_model'  => 'gpt-4o-mini',
        ]);

        $tenant = $this->createTenant();
        $chatbot = $this->createChatbot($tenant);

        $contact = Contact::withoutGlobalScopes()->create([
            'tenant_id'  => $tenant->id,
            'identifier' => 'web_test',
            'channel'    => 'web',
        ]);

        $conversation = Conversation::create([
            'session_id'      => 'cfg-error-session',
            'chatbot_id'      => $chatbot->id,
            'contact_id'      => $contact->id,
            'channel'         => 'web',
            'status'          => 'open',
            'is_ai_active'    => true,
            'last_message_at' => now(),
        ]);

        $conversation->load('chatbot');

        $result = app(RAGService::class)->processMessage($conversation, 'Halo');

        $this->assertStringContainsString('Konfigurasi AI belum lengkap', $result['content']);
    }
}
