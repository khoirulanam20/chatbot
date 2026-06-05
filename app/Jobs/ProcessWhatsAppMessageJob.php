<?php

namespace App\Jobs;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\WaInstance;
use App\Services\AgentSessionService;
use App\Services\RAGService;
use App\Services\TakeoverNotificationService;
use App\Services\WaChateryService;
use App\Services\WaOutboundService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ProcessWhatsAppMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 180;
    public int $tries   = 2;

    public function __construct(
        public readonly array $payload,
        public readonly int $waInstanceId
    ) {
        $this->onQueue('whatsapp');
    }

    public function handle(
        RAGService $rag,
        WaOutboundService $waOutbound,
        AgentSessionService $agentSession,
        TakeoverNotificationService $takeoverNotifications
    ): void {
        $from      = $this->payload['from'] ?? '';
        $text      = $this->payload['message'] ?? '';
        $messageId = $this->payload['message_id'] ?? null;

        Log::info('WA job started', [
            'wa_instance_id' => $this->waInstanceId,
            'from'           => $from,
            'message_id'     => $messageId,
        ]);

        $waInstance = WaInstance::find($this->waInstanceId);

        if (! $waInstance || ! $waInstance->chatbot) {
            Log::warning('WA instance not found', ['id' => $this->waInstanceId]);
            return;
        }

        $chatbot = $waInstance->chatbot;

        if (empty($from) || empty($text)) {
            Log::warning('WA job skipped: empty from or message', [
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

        $contact = Contact::withoutGlobalScopes()->firstOrCreate(
            [
                'tenant_id'  => $waInstance->tenant_id,
                'identifier' => $from,
                'channel'    => 'whatsapp',
            ],
            ['name' => $from]
        );

        if ($contact->is_blacklisted) {
            Log::info('WA job skipped: blacklisted contact', ['from' => $from]);
            return;
        }

        $conversation = Conversation::where('chatbot_id', $chatbot->id)
            ->where('contact_id', $contact->id)
            ->whereIn('status', ['open', 'handoff'])
            ->where('channel', 'whatsapp')
            ->where('last_message_at', '>=', now()->subHours(24))
            ->latest()
            ->first();

        if (! $conversation) {
            $conversation = Conversation::create([
                'chatbot_id' => $chatbot->id,
                'contact_id' => $contact->id,
                'channel'    => 'whatsapp',
                'status'     => 'open',
                'is_ai_active' => true,
                'last_message_at' => now(),
            ]);
        }

        $conversation->loadMissing('chatbot');
        $conversation = $agentSession->prepareForInbound($conversation);

        if ($agentSession->isAiBlocked($conversation)) {
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
        if ($waInstance->typing_enabled) {
            app(WaChateryService::class)->sendTyping($waInstance->api_key, $from, $sessionId);
        }

        $result = $rag->processMessage($conversation, $text);
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

            throw new RuntimeException('WA outbound send failed');
        }

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
