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
        $waInstance = WaInstance::find($this->waInstanceId);

        if (! $waInstance || ! $waInstance->chatbot) {
            Log::warning("WA instance not found", ['id' => $this->waInstanceId]);
            return;
        }

        $chatbot = $waInstance->chatbot;
        $from      = $this->payload['from'] ?? '';
        $text      = $this->payload['message'] ?? '';
        $messageId = $this->payload['message_id'] ?? null;

        if (empty($from) || empty($text)) {
            return;
        }

        if ($messageId) {
            $doneKey = "wa_done:{$waInstance->id}:{$messageId}";
            if (Cache::has($doneKey)) {
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

            if ($messageId) {
                Cache::put("wa_done:{$waInstance->id}:{$messageId}", true, now()->addDay());
            }

            return;
        }

        $sessionId = $waInstance->instance_id ?: 'default';
        if ($waInstance->typing_enabled) {
            app(WaChateryService::class)->sendTyping($waInstance->api_key, $from, $sessionId);
        }

        $result = $rag->processMessage($conversation, $text);

        if (! empty($result['silent']) || ($result['content'] ?? '') === '') {
            Log::info('WA reply skipped (handoff/silent)', [
                'conversation_id' => $conversation->id,
                'is_ai_active'    => $conversation->is_ai_active,
                'status'          => $conversation->status,
            ]);

            if ($messageId) {
                Cache::put("wa_done:{$waInstance->id}:{$messageId}", true, now()->addDay());
            }

            return;
        }

        $chunks = $result['chunks'] ?? [$result['content']];
        $humanize = $chatbot->isHumanizeEnabledFor('whatsapp');

        if ($humanize && count($chunks) > 1) {
            $waOutbound->sendChunks(
                $waInstance,
                $from,
                $chunks,
                (int) ($result['pacing_ms'] ?? $chatbot->getHumanizeSettings()['pacing_ms'])
            );
        } else {
            $waOutbound->sendText($waInstance, $from, $result['content']);
        }

        if ($messageId) {
            Cache::put("wa_done:{$waInstance->id}:{$messageId}", true, now()->addDay());
        }
    }
}
