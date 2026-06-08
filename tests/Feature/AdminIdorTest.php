<?php

namespace Tests\Feature;

use App\Models\KnowledgeDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTestActors;
use Tests\TestCase;

class AdminIdorTest extends TestCase
{
    use CreatesTestActors;
    use RefreshDatabase;

    public function test_admin_cannot_delete_knowledge_document_from_other_tenant(): void
    {
        $tenantA = $this->createTenant('Tenant A', 'tenant-a');
        $tenantB = $this->createTenant('Tenant B', 'tenant-b');
        $adminA = $this->createUser($tenantA, 'admin', 'admin-a@test.test');
        $botB = $this->createChatbot($tenantB, 'Bot B');

        $document = KnowledgeDocument::create([
            'chatbot_id'    => $botB->id,
            'name'          => 'Secret Doc',
            'original_name' => 'secret.pdf',
            'type'          => 'url',
            'path'          => 'https://example.com/page',
            'status'        => 'indexed',
        ]);

        $response = $this->actingAs($adminA)
            ->delete("/admin/knowledge/{$document->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('knowledge_documents', ['id' => $document->id]);
    }
}
