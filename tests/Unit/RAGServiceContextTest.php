<?php

namespace Tests\Unit;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\KnowledgeChunk;
use App\Models\KnowledgeDocument;
use App\Services\RAGService;
use App\Services\SumopodService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Concerns\CreatesTestActors;
use Tests\TestCase;

class RAGServiceContextTest extends TestCase
{
    use CreatesTestActors;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.sumopod.api_key'     => 'test-key',
            'services.sumopod.base_url'    => 'https://ai.example.com/v1',
            'services.sumopod.embed_model' => 'text-embedding-3-small',
            'services.sumopod.chat_model'  => 'gpt-4o-mini',
        ]);
    }

    private function createConversationWithKnowledge(array $chunkEmbedding): array
    {
        $tenant = $this->createTenant();
        $chatbot = $this->createChatbot($tenant);
        $chatbot->update([
            'settings' => array_merge($chatbot->settings ?? [], [
                'rag_min_similarity' => 0.35,
            ]),
        ]);

        $document = KnowledgeDocument::create([
            'chatbot_id'    => $chatbot->id,
            'name'          => 'Pelajaran Bahasa Indonesia',
            'original_name' => 'pelajaran-bi.txt',
            'type'          => 'text',
            'path'          => 'knowledge/test/pelajaran-bi.txt',
            'status'        => 'indexed',
        ]);

        KnowledgeChunk::create([
            'document_id' => $document->id,
            'chunk_index' => 0,
            'content'     => 'Materi pelajaran bahasa indonesia kelas 7 tentang teks narasi.',
            'embedding'   => json_encode($chunkEmbedding),
        ]);

        $contact = Contact::withoutGlobalScopes()->create([
            'tenant_id'  => $tenant->id,
            'identifier' => 'web_context_test',
            'channel'    => 'web',
        ]);

        $conversation = Conversation::create([
            'session_id'      => 'context-test-session',
            'chatbot_id'      => $chatbot->id,
            'contact_id'      => $contact->id,
            'channel'         => 'web',
            'status'          => 'open',
            'is_ai_active'    => true,
            'last_message_at' => now(),
        ]);

        $conversation->load('chatbot');

        return compact('chatbot', 'conversation');
    }

    public function test_rejects_out_of_context_question_without_calling_llm(): void
    {
        $kbEmbedding = [1.0, 0.0, 0.0];
        $queryEmbedding = [0.0, 1.0, 0.0];

        ['conversation' => $conversation] = $this->createConversationWithKnowledge($kbEmbedding);

        $sumopod = Mockery::mock(SumopodService::class);
        $sumopod->shouldReceive('withTenantSettings')->andReturnSelf();
        $sumopod->shouldReceive('embed')->once()->andReturn($queryEmbedding);
        $sumopod->shouldNotReceive('chatOnce');
        $this->app->instance(SumopodService::class, $sumopod);

        $result = app(RAGService::class)->processMessage(
            $conversation,
            'buatkan artikel tentang prabowo'
        );

        $this->assertTrue($result['out_of_context'] ?? false);
        $this->assertSame('Maaf, saya tidak dapat membantu dengan permintaan itu.', $result['content']);
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'role'            => 'assistant',
        ]);
    }

    public function test_allows_conversational_greeting_even_without_relevant_context(): void
    {
        $kbEmbedding = [1.0, 0.0, 0.0];
        $queryEmbedding = [0.0, 1.0, 0.0];

        ['conversation' => $conversation] = $this->createConversationWithKnowledge($kbEmbedding);

        $sumopod = Mockery::mock(SumopodService::class);
        $sumopod->shouldReceive('withTenantSettings')->andReturnSelf();
        $sumopod->shouldReceive('embed')->once()->andReturn($queryEmbedding);
        $sumopod->shouldReceive('chatOnce')->once()->andReturn([
            'content' => 'Halo! Ada yang bisa saya bantu?',
            'tokens'  => 10,
        ]);
        $this->app->instance(SumopodService::class, $sumopod);

        $result = app(RAGService::class)->processMessage($conversation, 'halo');

        $this->assertFalse($result['out_of_context'] ?? false);
        $this->assertStringContainsString('Halo', $result['content']);
    }

    public function test_allows_question_when_similarity_is_high_enough(): void
    {
        $embedding = [0.6, 0.8, 0.0];

        ['conversation' => $conversation] = $this->createConversationWithKnowledge($embedding);

        $sumopod = Mockery::mock(SumopodService::class);
        $sumopod->shouldReceive('withTenantSettings')->andReturnSelf();
        $sumopod->shouldReceive('embed')->once()->andReturn($embedding);
        $sumopod->shouldReceive('chatOnce')->once()->andReturn([
            'content' => 'Teks narasi adalah jenis teks yang menceritakan peristiwa.',
            'tokens'  => 20,
        ]);
        $this->app->instance(SumopodService::class, $sumopod);

        $result = app(RAGService::class)->processMessage(
            $conversation,
            'jelaskan teks narasi'
        );

        $this->assertFalse($result['out_of_context'] ?? false);
        $this->assertStringContainsString('narasi', strtolower($result['content']));
    }
}
