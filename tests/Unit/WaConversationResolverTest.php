<?php

namespace Tests\Unit;

use App\Models\Chatbot;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Tenant;
use App\Models\WaInstance;
use App\Services\WaConversationResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WaConversationResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_normalizes_chat_id_and_reuses_same_contact(): void
    {
        $tenant = Tenant::create([
            'name' => 'T', 'slug' => 't', 'plan' => 'pro', 'is_active' => true,
        ]);
        $chatbot = Chatbot::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id, 'name' => 'B', 'temperature' => 0.7,
            'max_context' => 5, 'language' => 'id', 'is_active' => true,
        ]);
        $wa = WaInstance::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id, 'chatbot_id' => $chatbot->id,
            'instance_id' => 's1', 'api_key' => 'k', 'status' => 'active',
        ]);

        $resolver = app(WaConversationResolver::class);

        $contactA = $resolver->findOrCreateContact($wa, '628987654321@s.whatsapp.net');
        $contactB = $resolver->findOrCreateContact($wa, '628987654321');

        $this->assertSame($contactA->id, $contactB->id);
        $this->assertSame('628987654321', $contactA->identifier);
    }

    public function test_finds_recent_conversation_for_same_contact(): void
    {
        $tenant = Tenant::create([
            'name' => 'T', 'slug' => 't', 'plan' => 'pro', 'is_active' => true,
        ]);
        $chatbot = Chatbot::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id, 'name' => 'B', 'temperature' => 0.7,
            'max_context' => 5, 'language' => 'id', 'is_active' => true,
        ]);
        $wa = WaInstance::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id, 'chatbot_id' => $chatbot->id,
            'instance_id' => 's1', 'api_key' => 'k', 'status' => 'active',
        ]);

        $contact = Contact::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id, 'identifier' => '628987654321', 'channel' => 'whatsapp',
        ]);

        $existing = Conversation::create([
            'chatbot_id' => $chatbot->id, 'contact_id' => $contact->id,
            'channel' => 'whatsapp', 'status' => 'open', 'is_ai_active' => true,
            'last_message_at' => now()->subHours(2),
        ]);

        $resolver = app(WaConversationResolver::class);
        $found = $resolver->findOrCreateConversation($wa, $contact);

        $this->assertSame($existing->id, $found->id);
    }
}
