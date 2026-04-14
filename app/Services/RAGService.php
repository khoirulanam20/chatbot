<?php

namespace App\Services;

use App\Models\Chatbot;
use App\Models\Conversation;
use App\Models\KnowledgeChunk;
use App\Models\KnowledgeDocument;
use App\Models\Message;
use Illuminate\Support\Facades\Log;

class RAGService
{
    public function __construct(
        private SumopodService $sumopod
    ) {}

    public function processMessage(
        Conversation $conversation,
        string $userMessage
    ): array {
        $chatbot = $conversation->chatbot;

        // Gunakan konfigurasi AI milik tenant, fallback ke global config
        $sumopod = $this->sumopod->withTenantSettings(
            $chatbot->tenant?->getAiConfig() ?? []
        );

        $userMsg = $this->saveMessage($conversation, 'user', $userMessage);

        if (! $chatbot->is_active) {
            $reply = $chatbot->getFallbackMessage();
            $this->saveMessage($conversation, 'assistant', $reply);
            return ['content' => $reply, 'sources' => [], 'user_message_id' => $userMsg->id];
        }

        if ($this->containsHandoffTrigger($chatbot, $userMessage)) {
            $result = $this->triggerHandoff($conversation, $chatbot, $userMessage);
            $result['user_message_id'] = $userMsg->id;
            return $result;
        }

        try {
            $queryEmbedding = $sumopod->embed($userMessage);
            $chunks         = $this->semanticSearch($chatbot, $queryEmbedding);
            $context        = $this->buildContext($chunks);
            $history        = $this->getConversationHistory($conversation, $chatbot->max_context);
            $messages       = $this->buildMessages($chatbot, $history, $context, $userMessage);
            $result         = $sumopod->chat($messages, $chatbot);

            $sources = array_map(fn ($c) => [
                'document_name' => $c->document->name ?? '',
                'chunk_index'   => $c->chunk_index,
            ], $chunks);

            $message = $this->saveMessage(
                $conversation,
                'assistant',
                $result['content'],
                $result['tokens'],
                $sources
            );

            $conversation->update(['last_message_at' => now()]);

            return [
                'content'         => $result['content'],
                'sources'         => $sources,
                'message_id'      => $message->id,
                'user_message_id' => $userMsg->id,
            ];
        } catch (\Exception $e) {
            Log::error('RAG pipeline error', [
                'conversation_id' => $conversation->id,
                'error'           => $e->getMessage(),
            ]);

            $fallback = $chatbot->getFallbackMessage();
            $this->saveMessage($conversation, 'assistant', $fallback);

            return ['content' => $fallback, 'sources' => []];
        }
    }

    private function semanticSearch(Chatbot $chatbot, array $queryEmbedding, int $limit = 5): array
    {
        $documentIds = KnowledgeDocument::where('chatbot_id', $chatbot->id)
            ->where('status', 'indexed')
            ->pluck('id')
            ->toArray();

        if (empty($documentIds)) {
            return [];
        }

        $chunks = KnowledgeChunk::with('document')
            ->whereIn('document_id', $documentIds)
            ->whereNotNull('embedding')
            ->get();

        if ($chunks->isEmpty()) {
            return [];
        }

        $scored = $chunks->map(function ($chunk) use ($queryEmbedding) {
            $chunkEmbedding = json_decode($chunk->embedding, true);
            $similarity     = $this->cosineSimilarity($queryEmbedding, $chunkEmbedding ?? []);
            return ['chunk' => $chunk, 'score' => $similarity];
        });

        return $scored
            ->sortByDesc('score')
            ->take($limit)
            ->pluck('chunk')
            ->values()
            ->all();
    }

    private function cosineSimilarity(array $a, array $b): float
    {
        if (empty($a) || empty($b) || count($a) !== count($b)) {
            return 0.0;
        }

        $dot    = 0.0;
        $normA  = 0.0;
        $normB  = 0.0;

        foreach ($a as $i => $val) {
            $dot   += $val * $b[$i];
            $normA += $val * $val;
            $normB += $b[$i] * $b[$i];
        }

        $denom = sqrt($normA) * sqrt($normB);
        return $denom > 0 ? $dot / $denom : 0.0;
    }

    private function buildContext(array $chunks): string
    {
        if (empty($chunks)) {
            return '';
        }

        $context = "Gunakan informasi berikut sebagai referensi untuk menjawab:\n\n";
        foreach ($chunks as $i => $chunk) {
            $docName  = $chunk->document->name ?? 'Dokumen';
            $context .= "--- Sumber {$docName} ---\n{$chunk->content}\n\n";
        }

        return $context;
    }

    private function buildMessages(
        Chatbot $chatbot,
        \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Collection|array $history,
        string $context,
        string $userMessage
    ): array {
        $systemPrompt = $chatbot->system_prompt ?? "Kamu adalah asisten layanan pelanggan yang membantu.";

        if (! empty($context)) {
            $systemPrompt .= "\n\n" . $context;
        }

        $systemPrompt .= "\n\nJika tidak ada informasi yang relevan, katakan dengan jujur bahwa kamu tidak tahu.";

        $messages = [['role' => 'system', 'content' => $systemPrompt]];

        foreach ($history as $msg) {
            if (in_array($msg->role, ['user', 'assistant'])) {
                $messages[] = ['role' => $msg->role, 'content' => $msg->content];
            }
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        return $messages;
    }

    private function getConversationHistory(Conversation $conversation, int $limit): \Illuminate\Database\Eloquent\Collection
    {
        return $conversation->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->latest()
            ->limit($limit * 2)
            ->get()
            ->reverse();
    }

    private function containsHandoffTrigger(Chatbot $chatbot, string $message): bool
    {
        $triggers = $chatbot->handoff_triggers ?? [];
        $messageLower = strtolower($message);

        foreach ($triggers as $trigger) {
            if (str_contains($messageLower, strtolower($trigger))) {
                return true;
            }
        }

        return false;
    }

    private function triggerHandoff(Conversation $conversation, Chatbot $chatbot, string $userMessage): array
    {
        $conversation->update([
            'status'      => 'handoff',
            'is_ai_active' => false,
        ]);

        \App\Models\AgentHandoff::create([
            'conversation_id' => $conversation->id,
            'reason'          => 'Triggered by user request',
            'trigger_keyword' => $userMessage,
        ]);

        $reply = "Baik, saya akan menghubungkan Anda dengan agen kami. Mohon tunggu sebentar...";
        $this->saveMessage($conversation, 'assistant', $reply);

        return ['content' => $reply, 'sources' => [], 'handoff' => true];
    }

    private function saveMessage(
        Conversation $conversation,
        string $role,
        string $content,
        ?int $tokens = null,
        array $sources = []
    ): Message {
        return Message::create([
            'conversation_id' => $conversation->id,
            'role'            => $role,
            'content'         => $content,
            'tokens'          => $tokens,
            'sources'         => $sources ?: null,
        ]);
    }
}
