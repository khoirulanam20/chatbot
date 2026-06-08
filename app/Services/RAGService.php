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
        private SumopodService $sumopod,
        private AgentSessionService $agentSession,
        private HumanizedResponseService $humanizedResponse,
        private TakeoverNotificationService $takeoverNotifications
    ) {}

    public function processImageMessage(
        Conversation $conversation,
        string $imageUrl,
        ?string $caption = null
    ): array {
        $chatbot = $conversation->chatbot;
        $channel = $conversation->channel ?? 'web';
        $caption = trim((string) $caption) ?: '[Gambar]';

        $sumopod = $this->resolveSumopodForChatbot($chatbot);

        $metadata = [
            'type' => 'image',
            'url'  => $imageUrl,
        ];

        $userMsg = $this->saveMessage($conversation, 'user', $caption, metadata: $metadata);

        $conversation = $this->agentSession->prepareForInbound($conversation);

        if ($this->agentSession->isAiBlocked($conversation)) {
            return $this->respondDuringHandoff($conversation, $chatbot, $userMsg->id, $caption);
        }

        if (! $chatbot->is_active) {
            $reply = $chatbot->getFallbackMessage();
            $this->saveMessage($conversation, 'assistant', $reply);

            return $this->wrapResponse($reply, $chatbot, $channel, [
                'sources'         => [],
                'user_message_id' => $userMsg->id,
            ]);
        }

        try {
            $model = $sumopod->resolveVisionModel($chatbot);
        } catch (\RuntimeException $e) {
            $reply = $e->getMessage();
            $this->saveMessage($conversation, 'assistant', $reply);

            return $this->wrapResponse($reply, $chatbot, $channel, [
                'sources'         => [],
                'user_message_id' => $userMsg->id,
            ]);
        }

        if (! VisionMessageFormatter::supportsVision($model)) {
            $reply = 'Maaf, model gambar di Settings → AI belum mendukung analisis gambar. Isi Model Gambar dengan model vision (mis. gpt-4o).';
            $this->saveMessage($conversation, 'assistant', $reply);

            return $this->wrapResponse($reply, $chatbot, $channel, [
                'sources'         => [],
                'user_message_id' => $userMsg->id,
            ]);
        }

        if ($caption !== '[Gambar]' && $this->containsHandoffTrigger($chatbot, $caption)) {
            $result = $this->triggerHandoff($conversation, $chatbot, $caption);
            $result['user_message_id'] = $userMsg->id;

            return $result;
        }

        try {
            $chunks  = [];
            $context = '';

            if ($caption !== '[Gambar]') {
                $queryEmbedding = $sumopod->embed($caption);
                $chunks         = $this->semanticSearch($chatbot, $queryEmbedding);
                $context        = $this->buildContext($chunks);
            }

            $history  = $this->getConversationHistory($conversation, $chatbot->max_context, excludeId: $userMsg->id);
            $messages = $this->buildVisionMessages($chatbot, $history, $context, $imageUrl, $caption, $channel);
            $humanizeActive = $chatbot->isHumanizeEnabledFor($channel);

            $temperature = $humanizeActive
                ? min(0.9, ($chatbot->temperature ?? 0.7) + 0.1)
                : ($chatbot->temperature ?? 0.7);

            $result = $sumopod->chatOnce($messages, $chatbot, overrideModel: $model, temperature: $temperature);

            $processed = $this->humanizedResponse->process(
                $result['content'],
                $chatbot->getHumanizeSettings(),
                $humanizeActive
            );

            $sources = array_map(fn ($c) => [
                'document_name' => $c->document->name ?? '',
                'chunk_index'   => $c->chunk_index,
            ], $chunks);

            $message = $this->saveMessage(
                $conversation,
                'assistant',
                $processed['content'],
                $result['tokens'],
                $sources
            );

            $conversation->update(['last_message_at' => now()]);

            return $this->wrapResponse($processed['content'], $chatbot, $channel, [
                'chunks'          => $processed['chunks'],
                'sources'         => $sources,
                'message_id'      => $message->id,
                'user_message_id' => $userMsg->id,
                'metadata'        => $metadata,
            ]);
        } catch (\Throwable $e) {
            return $this->handlePipelineError($conversation, $chatbot, $channel, $e, $userMsg->id, 'RAG vision pipeline error');
        }
    }

    public function processMessage(
        Conversation $conversation,
        string $userMessage
    ): array {
        $chatbot = $conversation->chatbot;

        $sumopod = $this->resolveSumopodForChatbot($chatbot);

        $userMsg = $this->saveMessage($conversation, 'user', $userMessage);

        $conversation = $this->agentSession->prepareForInbound($conversation);

        if ($this->agentSession->isAiBlocked($conversation)) {
            return $this->respondDuringHandoff($conversation, $chatbot, $userMsg->id, $userMessage);
        }

        if (! $chatbot->is_active) {
            $reply = $chatbot->getFallbackMessage();
            $this->saveMessage($conversation, 'assistant', $reply);
            return $this->wrapResponse($reply, $chatbot, $conversation->channel, [
                'sources' => [],
                'user_message_id' => $userMsg->id,
            ]);
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
            $channel        = $conversation->channel ?? 'web';
            $history        = $this->getConversationHistory($conversation, $chatbot->max_context);
            $messages       = $this->buildMessages($chatbot, $history, $context, $userMessage, $channel);
            $humanizeActive = $chatbot->isHumanizeEnabledFor($channel);

            $temperature = $humanizeActive
                ? min(0.9, ($chatbot->temperature ?? 0.7) + 0.1)
                : ($chatbot->temperature ?? 0.7);
            $result = $sumopod->chatOnce($messages, $chatbot, temperature: $temperature);

            $processed = $this->humanizedResponse->process(
                $result['content'],
                $chatbot->getHumanizeSettings(),
                $humanizeActive
            );

            $sources = array_map(fn ($c) => [
                'document_name' => $c->document->name ?? '',
                'chunk_index'   => $c->chunk_index,
            ], $chunks);

            $message = $this->saveMessage(
                $conversation,
                'assistant',
                $processed['content'],
                $result['tokens'],
                $sources
            );

            $conversation->update(['last_message_at' => now()]);

            return $this->wrapResponse($processed['content'], $chatbot, $channel, [
                'chunks'          => $processed['chunks'],
                'sources'         => $sources,
                'message_id'      => $message->id,
                'user_message_id' => $userMsg->id,
            ]);
        } catch (\Throwable $e) {
            return $this->handlePipelineError(
                $conversation,
                $chatbot,
                $conversation->channel ?? 'web',
                $e,
                $userMsg->id,
                'RAG pipeline error'
            );
        }
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function wrapResponse(string $content, Chatbot $chatbot, string $channel, array $extra = []): array
    {
        $humanize = $chatbot->getHumanizeSettings();
        $chunks = $extra['chunks'] ?? [$content];

        return array_merge($extra, [
            'content'      => $content,
            'chunks'       => $chunks,
            'pacing_ms'    => $chatbot->isHumanizeEnabledFor($channel) ? (int) $humanize['pacing_ms'] : 0,
        ]);
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
        string $userMessage,
        string $channel = 'web'
    ): array {
        $systemPrompt = $chatbot->getEffectiveSystemPrompt();

        if (! empty($context)) {
            $systemPrompt .= "\n\n" . $context;
        }

        $humanizeBlock = $chatbot->composeHumanizeBlock($channel);
        if ($humanizeBlock !== '') {
            $systemPrompt .= "\n\n" . $humanizeBlock;
        }

        $systemPrompt .= "\n\nJika tidak ada informasi yang relevan, katakan dengan jujur bahwa kamu tidak tahu.";

        $messages = [['role' => 'system', 'content' => $systemPrompt]];

        foreach ($history as $msg) {
            if (in_array($msg->role, ['user', 'assistant'])) {
                $messages[] = VisionMessageFormatter::formatMessage($msg);
            }
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        return $messages;
    }

    private function buildVisionMessages(
        Chatbot $chatbot,
        \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Collection|array $history,
        string $context,
        string $imageUrl,
        string $caption,
        string $channel = 'web'
    ): array {
        $systemPrompt = $chatbot->getEffectiveSystemPrompt();

        if (! empty($context)) {
            $systemPrompt .= "\n\n" . $context;
        }

        $humanizeBlock = $chatbot->composeHumanizeBlock($channel);
        if ($humanizeBlock !== '') {
            $systemPrompt .= "\n\n" . $humanizeBlock;
        }

        $systemPrompt .= "\n\nKamu dapat melihat dan menganalisis gambar yang dikirim user.";
        $systemPrompt .= "\n\nJika tidak ada informasi yang relevan, katakan dengan jujur bahwa kamu tidak tahu.";

        $messages = [['role' => 'system', 'content' => $systemPrompt]];

        foreach ($history as $msg) {
            if (in_array($msg->role, ['user', 'assistant'])) {
                $messages[] = VisionMessageFormatter::formatMessage($msg);
            }
        }

        $messages[] = VisionMessageFormatter::formatImageUserMessage($imageUrl, $caption);

        return $messages;
    }

    private function getConversationHistory(
        Conversation $conversation,
        int $limit,
        ?int $excludeId = null
    ): \Illuminate\Database\Eloquent\Collection {
        $query = $conversation->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->latest()
            ->limit($limit * 2);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->get()->reverse();
    }

    private function containsHandoffTrigger(Chatbot $chatbot, string $message): bool
    {
        $triggers = $chatbot->getTakeoverKeywords();
        $messageLower = strtolower($message);

        foreach ($triggers as $trigger) {
            $trigger = trim(strtolower((string) $trigger));
            if ($trigger === '') {
                continue;
            }

            // Keyword pendek (<=3 char) harus match kata utuh agar tidak false-positive
            if (mb_strlen($trigger) <= 3) {
                $pattern = '/\b' . preg_quote($trigger, '/') . '\b/u';
                if (preg_match($pattern, $messageLower)) {
                    return true;
                }
                continue;
            }

            if (str_contains($messageLower, $trigger)) {
                return true;
            }
        }

        return false;
    }

    private function triggerHandoff(Conversation $conversation, Chatbot $chatbot, string $userMessage): array
    {
        $this->agentSession->enterHandoffByKeyword($conversation, $userMessage);

        \App\Models\AgentHandoff::create([
            'conversation_id' => $conversation->id,
            'reason'          => 'Triggered by takeover keyword',
            'trigger_keyword' => $userMessage,
        ]);

        $this->takeoverNotifications->notifyTakeoverRequested($conversation, $userMessage);

        $reply = $this->agentSession->getTakeoverHoldMessage($chatbot);
        $this->saveMessage($conversation, 'assistant', $reply);
        $this->agentSession->touchActivity($conversation);

        return $this->wrapResponse($reply, $chatbot, $conversation->channel ?? 'web', [
            'sources'       => [],
            'handoff'       => true,
            'agent_session' => true,
        ]);
    }

    private function respondDuringHandoff(
        Conversation $conversation,
        Chatbot $chatbot,
        int $userMessageId,
        string $userMessage
    ): array {
        $this->agentSession->touchActivity($conversation);
        $this->takeoverNotifications->notifyNewMessageDuringHandoff($conversation, $userMessage);

        return [
            'content'         => '',
            'chunks'          => [],
            'sources'         => [],
            'user_message_id' => $userMessageId,
            'agent_session'   => true,
            'silent'          => true,
            'pacing_ms'       => 0,
        ];
    }

    private function saveMessage(
        Conversation $conversation,
        string $role,
        string $content,
        ?int $tokens = null,
        array $sources = [],
        ?array $metadata = null
    ): Message {
        return Message::create([
            'conversation_id' => $conversation->id,
            'role'            => $role,
            'content'         => $content,
            'tokens'          => $tokens,
            'sources'         => $sources ?: null,
            'metadata'        => $metadata,
        ]);
    }

    private function resolveSumopodForChatbot(Chatbot $chatbot): SumopodService
    {
        return $this->sumopod->withTenantSettings(
            $chatbot->tenant?->getAiConfig() ?? []
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function handlePipelineError(
        Conversation $conversation,
        Chatbot $chatbot,
        string $channel,
        \Throwable $e,
        ?int $userMessageId,
        string $logContext
    ): array {
        Log::error($logContext, [
            'conversation_id' => $conversation->id,
            'error'           => $e->getMessage(),
        ]);

        if (SumopodService::isConfigurationError($e)) {
            $reply = 'Konfigurasi AI belum lengkap. Hubungi admin untuk memeriksa Pengaturan AI.';
            $this->saveMessage($conversation, 'assistant', $reply);

            return $this->wrapResponse($reply, $chatbot, $channel, [
                'sources'         => [],
                'user_message_id' => $userMessageId,
            ]);
        }

        $fallback = $chatbot->getFallbackMessage();
        $this->saveMessage($conversation, 'assistant', $fallback);

        return $this->wrapResponse($fallback, $chatbot, $channel, [
            'sources'         => [],
            'user_message_id' => $userMessageId,
        ]);
    }
}
