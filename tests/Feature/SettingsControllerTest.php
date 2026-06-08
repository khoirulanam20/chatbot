<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\CreatesTestActors;
use Tests\TestCase;

class SettingsControllerTest extends TestCase
{
    use CreatesTestActors;
    use RefreshDatabase;

    public function test_operator_cannot_update_tenant_settings(): void
    {
        $tenant = $this->createTenant();
        $operator = $this->createUser($tenant, 'operator', 'operator@test.test');

        $response = $this->actingAs($operator)
            ->post('/admin/settings', [
                'ai_base_url' => 'https://api.example.com/v1',
            ]);

        $response->assertForbidden();
    }

    public function test_admin_can_save_encrypted_api_key_without_exposing_it(): void
    {
        $tenant = $this->createTenant();
        $admin = $this->createUser($tenant, 'admin', 'admin@test.test');

        $this->actingAs($admin)
            ->post('/admin/settings', [
                'ai_api_key'  => 'secret-tenant-key',
                'ai_base_url' => 'https://api.example.com/v1',
            ])
            ->assertRedirect();

        $tenant->refresh();
        $this->assertTrue($tenant->hasAiApiKey());
        $this->assertNotSame('secret-tenant-key', $tenant->settings['ai_api_key']);
        $this->assertSame('secret-tenant-key', Crypt::decryptString($tenant->settings['ai_api_key']));

        $page = $this->actingAs($admin)->get('/admin/settings');
        $page->assertOk()
            ->assertInertia(fn ($assert) => $assert
                ->component('settings/Index')
                ->where('tenantSettings.has_ai_api_key', true)
                ->missing('tenantSettings.ai_api_key')
            );
    }

    public function test_knowledge_url_crawl_stores_metadata_max_pages(): void
    {
        Queue::fake();

        $tenant = $this->createTenant();
        $admin = $this->createUser($tenant, 'admin', 'admin@test.test');
        $chatbot = $this->createChatbot($tenant);

        $this->actingAs($admin)
            ->post('/admin/knowledge/from-url', [
                'chatbot_id' => $chatbot->id,
                'url'          => 'https://example.com',
                'crawl_mode'   => 'crawl',
                'max_pages'    => 25,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('knowledge_documents', [
            'chatbot_id' => $chatbot->id,
            'path'       => 'https://example.com',
        ]);

        $document = \App\Models\KnowledgeDocument::first();
        $this->assertSame(25, $document->metadata['max_pages'] ?? null);
        $this->assertSame('crawl', $document->metadata['crawl_mode'] ?? null);
    }
}
