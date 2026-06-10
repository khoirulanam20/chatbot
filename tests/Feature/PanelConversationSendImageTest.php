<?php

namespace Tests\Feature;

use App\Models\Chatbot;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Tenant;
use App\Models\WaInstance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesTestActors;
use Tests\TestCase;

class PanelConversationSendImageTest extends TestCase
{
    use CreatesTestActors;
    use RefreshDatabase;

    public function test_admin_send_image_to_wa_conversation_calls_chatery_send_image(): void
    {
        if (! function_exists('imagewebp')) {
            $this->markTestSkipped('GD WebP extension required');
        }

        Storage::fake('public');
        Http::fake([
            '*' => Http::response(['data' => ['id' => 'wa-img-sent-001']], 200),
        ]);

        $tenant = $this->createTenant();
        $admin  = $this->createUser($tenant, 'admin', 'admin-img@test.test');

        $chatbot = Chatbot::withoutGlobalScopes()->create([
            'tenant_id'   => $tenant->id,
            'name'        => 'Img Bot',
            'temperature' => 0.7,
            'max_context' => 10,
            'language'    => 'id',
            'is_active'   => true,
            'settings'    => ['pause_ai_on_human_reply' => true],
        ]);

        $wa = WaInstance::withoutGlobalScopes()->create([
            'tenant_id'    => $tenant->id,
            'chatbot_id'   => $chatbot->id,
            'instance_id'  => 'ImgTest',
            'phone_number' => '628123456789',
            'api_key'      => 'test-key',
            'status'       => 'active',
        ]);

        $lidChatId = '248618594336855@lid';

        $contact = Contact::withoutGlobalScopes()->create([
            'tenant_id'  => $tenant->id,
            'identifier' => '248618594336855',
            'channel'    => 'whatsapp',
            'metadata'   => ['wa_chat_id' => $lidChatId],
        ]);

        $conversation = Conversation::create([
            'chatbot_id'      => $chatbot->id,
            'contact_id'      => $contact->id,
            'channel'         => 'whatsapp',
            'status'          => 'open',
            'is_ai_active'    => true,
            'last_message_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post("/admin/conversations/{$conversation->id}/image", [
                'image'   => UploadedFile::fake()->image('produk.jpg'),
                'caption' => 'Ini foto produk',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        Http::assertSent(function ($request) use ($lidChatId) {
            $url = $request->url();
            $body = $request->data();

            return str_contains($url, 'whatsapp/chats/send-image')
                && ($body['chatId'] ?? '') === $lidChatId
                && ($body['caption'] ?? '') === 'Ini foto produk'
                && str_starts_with((string) ($body['imageUrl'] ?? ''), 'http')
                && ($body['sessionId'] ?? '') === 'ImgTest';
        });

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'role'            => 'agent',
            'content'         => 'Ini foto produk',
        ]);
    }
}
