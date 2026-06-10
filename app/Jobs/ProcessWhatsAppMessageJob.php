<?php

namespace App\Jobs;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\WaInstance;
use App\Services\AgentSessionService;
use App\Services\ChatImageService;
use App\Services\RAGService;
use App\Services\TakeoverNotificationService;
use App\Services\WaChateryService;
use App\Services\WaConversationResolver;
use App\Services\WaOutboundService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ProcessWhatsAppMessageJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 180;
    public int $tries   = 1;

    public function __construct(
        public readonly array $payload,
        public readonly int $waInstanceId
    ) {
        $this->onQueue('whatsapp');
    }

    public function uniqueId(): string
    {
        $messageId = $this->payload['message_id'] ?? 'no-id';

        return "{$this->waInstanceId}:{$messageId}";
    }

    public function handle(
        RAGService $rag,
        WaOutboundService $waOutbound,
        AgentSessionService $agentSession,
        TakeoverNotificationService $takeoverNotifications,
        ChatImageService $chatImageService,
        WaChateryService $waChatery,
        WaConversationResolver $conversationResolver
    ): void {
        $from      = $this->payload['from'] ?? '';
        $text      = $this->payload['message'] ?? '';
        $messageId = $this->payload['message_id'] ?? null;
        $type      = $this->payload['type'] ?? 'text';
        $mediaUrl  = $this->payload['media_url'] ?? null;

        Log::info('WA job started', [
            'wa_instance_id' => $this->waInstanceId,
            'from'           => $from,
            'message_id'     => $messageId,
            'type'           => $type,
        ]);

        $waInstance = WaInstance::withoutGlobalScopes()->find($this->waInstanceId);

        if (! $waInstance || ! $waInstance->chatbot) {
            Log::warning('WA instance not found', ['id' => $this->waInstanceId]);

            return;
        }

        $chatbot = $waInstance->chatbot;

        if (empty($from)) {
            Log::warning('WA job skipped: empty from', [
                'wa_instance_id' => $this->waInstanceId,
            ]);
            return;
        }

        if ($type === 'image') {
            if (empty($mediaUrl)) {
                Log::warning('WA job skipped: image without media_url', [
                    'wa_instance_id' => $this->waInstanceId,
                ]);
                return;
            }
        } elseif (empty($text)) {
            Log::warning('WA job skipped: empty message', [
                'wa_instance_id' => $this->waInstanceId,
            ]);
            return;
        }

        if ($messageId) {
            $doneKey = "wa_done:{$waInstance->id}:{$messageId}";
            if (Cache::has($doneKey)) {
                Log::info('WA job skipped: duplicate message', [
                    'wa_instance_id' => $waInstance->id,
                    'message_id'     => $messageId,
                ]);

                return;
            }
        }

        $contact = $conversationResolver->findOrCreateContact($waInstance, $from);

        if ($contact->is_blacklisted) {
            Log::info('WA job skipped: blacklisted contact', ['from' => $from]);
            return;
        }

        $conversation   = $conversationResolver->findOrCreateConversation($waInstance, $contact);
        $outboundChatId = $conversationResolver->resolveOutboundChatId($contact);

        $lock = Cache::lock(AgentSessionService::conversationLockKey($conversation->id), 180);

        if (! $lock->block(30)) {
            Log::warning('WA job could not acquire conversation lock', [
                'conversation_id' => $conversation->id,
                'message_id'      => $messageId,
            ]);

            throw new RuntimeException('Could not acquire conversation lock');
        }

        try {
            $conversation->loadMissing('chatbot');
            $conversation = $agentSession->prepareForInbound($conversation);

            if ($agentSession->isAiBlocked($conversation)) {
                $this->handleHandoffInbound(
                    $conversation,
                    $text,
                    $agentSession,
                    $takeoverNotifications,
                    $waInstance->id,
                    $messageId
                );

                return;
            }

            $sessionId = $waInstance->instance_id ?: 'default';
            $apiKey      = $waChatery->resolveApiKey($waInstance);
            if ($waInstance->typing_enabled && filled($apiKey)) {
                $waChatery->sendTyping($apiKey, $outboundChatId, $sessionId);
            }

            if ($type === 'image') {
                $stored = $this->storeInboundImage(
                    $chatImageService,
                    $waChatery,
                    $mediaUrl,
                    $apiKey,
                    $waInstance->tenant_id,
                    $conversation->id
                );

                $caption = $text !== '' ? $text : '[Gambar]';
                $result  = $rag->processImageMessage($conversation, $stored['url'], $caption);
            } else {
                $result = $rag->processMessage($conversation, $text);
            }

            $conversation->refresh();

            $handoffTriggered = ! empty($result['handoff']);
            $replyContent     = trim((string) ($result['content'] ?? ''));

            if ($agentSession->isAiBlocked($conversation)) {
                if ($handoffTriggered && $replyContent !== '') {
                    $sent = $waOutbound->sendText($waInstance, $outboundChatId, $replyContent);

                    if (! $sent) {
                        Log::error('WA handoff hold message outbound failed', [
                            'conversation_id' => $conversation->id,
                            'outbound_chat_id' => $outboundChatId,
                        ]);

                        throw new RuntimeException('WA handoff hold message send failed');
                    }

                    Log::info('WA handoff hold message sent', [
                        'conversation_id' => $conversation->id,
                        'outbound_chat_id' => $outboundChatId,
                    ]);
                } else {
                    Log::info('WA reply skipped (handoff after RAG)', [
                        'conversation_id' => $conversation->id,
                        'is_ai_active'    => $conversation->is_ai_active,
                        'status'          => $conversation->status,
                    ]);
                }

                $this->markProcessed($waInstance->id, $messageId);

                return;
            }

            if (! empty($result['silent']) || ($result['content'] ?? '') === '') {
                Log::info('WA reply skipped (handoff/silent)', [
                    'conversation_id' => $conversation->id,
                    'is_ai_active'    => $conversation->is_ai_active,
                    'status'          => $conversation->status,
                ]);

                $this->markProcessed($waInstance->id, $messageId);

                return;
            }

            $chunks   = $result['chunks'] ?? [$result['content']];
            $humanize = $chatbot->isHumanizeEnabledFor('whatsapp');

            Log::info('WA job sending reply', [
                'conversation_id' => $conversation->id,
                'is_ai_active'    => $conversation->is_ai_active,
                'status'          => $conversation->status,
                'chunk_count'     => count($chunks),
            ]);

            $sent = $humanize && count($chunks) > 1
                ? $waOutbound->sendChunks(
                    $waInstance,
                    $outboundChatId,
                    $chunks,
                    (int) ($result['pacing_ms'] ?? $chatbot->getHumanizeSettings()['pacing_ms'])
                )
                : $waOutbound->sendText($waInstance, $outboundChatId, $result['content']);

            if (! $sent) {
                Log::error('WA outbound failed', [
                    'wa_instance_id'  => $waInstance->id,
                    'conversation_id' => $conversation->id,
                    'from'            => $from,
                ]);

                throw new RuntimeException('WA outbound send failed');
            }

            Log::info('WA job completed', [
                'conversation_id' => $conversation->id,
                'from'            => $from,
            ]);

            $this->markProcessed($waInstance->id, $messageId);
        } finally {
            $lock->release();
        }
    }

    private function handleHandoffInbound(
        Conversation $conversation,
        string $text,
        AgentSessionService $agentSession,
        TakeoverNotificationService $takeoverNotifications,
        int $waInstanceId,
        ?string $messageId
    ): void {
        Message::create([
            'conversation_id' => $conversation->id,
            'role'            => 'user',
            'content'         => $text,
        ]);
        $agentSession->touchActivity($conversation);
        $takeoverNotifications->notifyNewMessageDuringHandoff($conversation, $text);

        Log::info('WA job handoff: message saved, no AI reply', [
            'conversation_id' => $conversation->id,
            'status'          => $conversation->status,
        ]);

        $this->markProcessed($waInstanceId, $messageId);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('WA job failed', [
            'wa_instance_id' => $this->waInstanceId,
            'from'           => $this->payload['from'] ?? null,
            'message_id'     => $this->payload['message_id'] ?? null,
            'error'          => $e->getMessage(),
        ]);
    }

    private function markProcessed(int $waInstanceId, ?string $messageId): void
    {
        if ($messageId) {
            Cache::put("wa_done:{$waInstanceId}:{$messageId}", true, now()->addDay());
        }
    }

    /**
     * @return array{path: string, url: string, size: int, mime: string}
     */
    private function storeInboundImage(
        ChatImageService $chatImageService,
        WaChateryService $waChatery,
        string $mediaUrl,
        ?string $apiKey,
        int $tenantId,
        int $conversationId
    ): array {
        try {
            return $chatImageService->storeFromUrl(
                $mediaUrl,
                $tenantId,
                $conversationId,
                $apiKey
            );
        } catch (\Throwable $e) {
            if (! filled($apiKey)) {
                throw $e;
            }

            $binary = $waChatery->downloadMedia($apiKey, WaWebhookPayloadParser::normalizeMediaUrl($mediaUrl));

            if ($binary === null) {
                Log::error('WA image store failed', [
                    'conversation_id' => $conversationId,
                    'media_url'       => $mediaUrl,
                    'error'           => $e->getMessage(),
                ]);

                throw new RuntimeException('WA image store failed: ' . $e->getMessage());
            }

            return $chatImageService->storeFromContents($binary, $tenantId, $conversationId);
        }
    }
}
