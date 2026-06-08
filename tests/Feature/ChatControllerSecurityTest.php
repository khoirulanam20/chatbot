<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTestActors;
use Tests\TestCase;

class ChatControllerSecurityTest extends TestCase
{
    use CreatesTestActors;
    use RefreshDatabase;

    public function test_session_bound_to_chatbot_rejects_mismatch(): void
    {
        $tenantA = $this->createTenant('Tenant A', 'tenant-a');
        $tenantB = $this->createTenant('Tenant B', 'tenant-b');
        $botA = $this->createChatbot($tenantA, 'Bot A');
        $botB = $this->createChatbot($tenantB, 'Bot B');

        $sessionId = 'shared-session-id';

        $contact = Contact::withoutGlobalScopes()->create([
            'tenant_id'  => $tenantA->id,
            'identifier' => 'web_' . $sessionId,
            'channel'    => 'web',
        ]);

        Conversation::create([
            'session_id'      => $sessionId,
            'chatbot_id'      => $botA->id,
            'contact_id'      => $contact->id,
            'channel'         => 'web',
            'status'          => 'open',
            'is_ai_active'    => true,
            'last_message_at' => now(),
        ]);

        $response = $this->postJson('/api/chat/message', [
            'bot_id'     => $botB->id,
            'session_id' => $sessionId,
            'message'    => 'Halo',
        ]);

        $response->assertForbidden();
    }

    public function test_history_requires_matching_bot_id(): void
    {
        $tenant = $this->createTenant();
        $botA = $this->createChatbot($tenant, 'Bot A');
        $botB = $this->createChatbot($tenant, 'Bot B');
        $sessionId = 'history-session';

        $contact = Contact::withoutGlobalScopes()->create([
            'tenant_id'  => $tenant->id,
            'identifier' => 'web_' . $sessionId,
            'channel'    => 'web',
        ]);

        $conversation = Conversation::create([
            'session_id'      => $sessionId,
            'chatbot_id'      => $botA->id,
            'contact_id'      => $contact->id,
            'channel'         => 'web',
            'status'          => 'open',
            'is_ai_active'    => true,
            'last_message_at' => now(),
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'role'            => 'user',
            'content'         => 'Pesan rahasia',
        ]);

        $wrongBot = $this->getJson("/api/chat/history/{$sessionId}?bot_id={$botB->id}");
        $wrongBot->assertOk()->assertJsonPath('messages', []);

        $correctBot = $this->getJson("/api/chat/history/{$sessionId}?bot_id={$botA->id}");
        $correctBot->assertOk()
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.content', 'Pesan rahasia');
    }
}
