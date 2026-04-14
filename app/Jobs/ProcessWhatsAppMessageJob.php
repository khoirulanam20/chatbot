<?php

namespace App\Jobs;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\WaInstance;
use App\Services\RAGService;
use App\Services\WaChateryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWhatsAppMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;
    public int $tries   = 2;

    public function __construct(
        public readonly array $payload,
        public readonly int $waInstanceId
    ) {
        $this->onQueue('whatsapp');
    }

    public function handle(RAGService $rag, WaChateryService $chatery): void
    {
        $waInstance = WaInstance::find($this->waInstanceId);

        if (! $waInstance || ! $waInstance->chatbot) {
            Log::warning("WA instance not found", ['id' => $this->waInstanceId]);
            return;
        }

        $chatbot = $waInstance->chatbot;
        $from    = $this->payload['from'] ?? '';
        $text    = $this->payload['message'] ?? '';

        if (empty($from) || empty($text)) {
            return;
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

        if (! $conversation->is_ai_active) {
            return;
        }

        $result = $rag->processMessage($conversation, $text);

        $chatery->sendMessage($waInstance->api_key, $from, $result['content']);
    }
}
