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
use App\Support\DebugWaTrace;
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

        // #region agent log
        DebugWaTrace::log('H4', 'ProcessWhatsAppMessageJob.php:handle', 'job_handle_started', [
            'wa_instance_id' => $this->waInstanceId,
            'message_id'     => $messageId,
            'queue'          => $this->queue ?? 'default',
        ]);
        // #endregion

        $waInstance = WaInstance::withoutGlobalScopes()->find($this->waInstanceId);

        if (! $waInstance || ! $waInstance->chatbot) {
            Log::warning('WA instance not found', ['id' => $this->waInstanceId]);

            // #region agent log
            DebugWaTrace::log('H3', 'ProcessWhatsAppMessageJob.php:handle', 'job_abort_no_instance', [
                'wa_instance_id' => $this->waInstanceId,
                'found'          => (bool) $waInstance,
            ]);
            // #endregion

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

        $conversation = $conversationResolver->findOrCreateConversation($waInstance, $contact);

        $conversation->loadMissing('chatbot');
        $conversation = $agentSession->prepareForInbound($conversation);

        if ($agentSession->isAiBlocked($conversation)) {
            // #region agent log
            DebugWaTrace::log('H5', 'ProcessWhatsAppMessageJob.php:handle', 'job_handoff_blocked', [
                'conversation_id' => $conversation->id,
                'status'          => $conversation->status,
            ]);
            // #endregion

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

            $this->markProcessed($waInstance->id, $messageId);
            return;
        }

        $sessionId = $waInstance->instance_id ?: 'default';
        $apiKey = $waChatery->resolveApiKey($waInstance);
        if ($waInstance->typing_enabled && filled($apiKey)) {
            $waChatery->sendTyping($apiKey, $from, $sessionId);
        }

        if ($type === 'image') {
            try {
                $stored = $chatImageService->storeFromUrl(
                    $mediaUrl,
                    $waInstance->tenant_id,
                    $conversation->id,
                    $apiKey ?? ''
                );
            } catch (\Throwable $e) {
                Log::error('WA image store failed', [
                    'conversation_id' => $conversation->id,
                    'error'           => $e->getMessage(),
                ]);

                throw new RuntimeException('WA image store failed: ' . $e->getMessage());
            }

            $caption = $text !== '' ? $text : '[Gambar]';
            $result  = $rag->processImageMessage($conversation, $stored['url'], $caption);
        } else {
            $result = $rag->processMessage($conversation, $text);
        }

        $conversation->refresh();

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
                $from,
                $chunks,
                (int) ($result['pacing_ms'] ?? $chatbot->getHumanizeSettings()['pacing_ms'])
            )
            : $waOutbound->sendText($waInstance, $from, $result['content']);

        if (! $sent) {
            Log::error('WA outbound failed', [
                'wa_instance_id'  => $waInstance->id,
                'conversation_id' => $conversation->id,
                'from'            => $from,
            ]);

            // #region agent log
            DebugWaTrace::log('H5', 'ProcessWhatsAppMessageJob.php:handle', 'job_outbound_failed', [
                'conversation_id' => $conversation->id,
            ]);
            // #endregion

            throw new RuntimeException('WA outbound send failed');
        }

        // #region agent log
        DebugWaTrace::log('H5', 'ProcessWhatsAppMessageJob.php:handle', 'job_completed_ok', [
            'conversation_id' => $conversation->id,
            'message_id'      => $messageId,
        ]);
        // #endregion

        Log::info('WA job completed', [
            'conversation_id' => $conversation->id,
            'from'            => $from,
        ]);

        $this->markProcessed($waInstance->id, $messageId);
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
}
