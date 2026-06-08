<?php

namespace App\Jobs;

use App\Models\Message;
use App\Models\WaInstance;
use App\Services\AgentSessionService;
use App\Services\WaConversationResolver;
use App\Services\WaOutboundService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWhatsAppAgentReplyJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;
    public int $tries   = 2;

    public function __construct(
        public readonly array $payload,
        public readonly int $waInstanceId
    ) {
        $this->onQueue('whatsapp');
    }

    public function uniqueId(): string
    {
        $messageId = $this->payload['message_id'] ?? 'no-id';

        return "agent:{$this->waInstanceId}:{$messageId}";
    }

    public function handle(
        AgentSessionService $agentSession,
        WaConversationResolver $conversationResolver
    ): void {
        $customerId = $this->payload['customer_id'] ?? '';
        $message    = $this->payload['message'] ?? '';
        $messageId  = $this->payload['message_id'] ?? null;
        $type       = $this->payload['type'] ?? 'text';

        if ($customerId === '' || $message === '') {
            Log::warning('WA agent reply job skipped: empty customer or message', [
                'wa_instance_id' => $this->waInstanceId,
            ]);

            return;
        }

        $waInstance = WaInstance::withoutGlobalScopes()->find($this->waInstanceId);

        if (! $waInstance || ! $waInstance->chatbot) {
            Log::warning('WA agent reply job: instance not found', ['id' => $this->waInstanceId]);

            return;
        }

        if (WaOutboundService::isOutboundEcho(
            $waInstance->id,
            $messageId,
            $customerId,
            $message
        )) {
            Log::info('WA agent reply job skipped: outbound echo', [
                'wa_instance_id' => $waInstance->id,
                'message_id'     => $messageId,
            ]);

            return;
        }

        $contact = $conversationResolver->findOrCreateContact($waInstance, $customerId);
        $conversation = $conversationResolver->findOrCreateConversation($waInstance, $contact);

        $paused = $agentSession->pauseForHumanReply($conversation);

        $metadata = ['source' => 'whatsapp_direct'];
        if ($type === 'image') {
            $metadata['type'] = 'image';
            if (! empty($this->payload['media_url'])) {
                $metadata['url'] = $this->payload['media_url'];
            }
        }

        Message::create([
            'conversation_id' => $conversation->id,
            'role'            => 'agent',
            'content'         => $message,
            'metadata'        => $metadata,
        ]);

        $agentSession->touchActivity($conversation);

        Log::info('WA agent reply processed', [
            'conversation_id' => $conversation->id,
            'wa_instance_id'  => $waInstance->id,
            'ai_paused'       => $paused,
        ]);
    }
}
