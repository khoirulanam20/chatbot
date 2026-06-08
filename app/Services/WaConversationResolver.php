<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\WaInstance;

class WaConversationResolver
{
    public function findOrCreateContact(WaInstance $waInstance, string $customerIdentifier): Contact
    {
        $identifier = WaChateryService::normalizePhone($customerIdentifier);

        return Contact::withoutGlobalScopes()->firstOrCreate(
            [
                'tenant_id'  => $waInstance->tenant_id,
                'identifier' => $identifier,
                'channel'    => 'whatsapp',
            ],
            ['name' => $identifier]
        );
    }

    public function findRecentConversation(WaInstance $waInstance, Contact $contact): ?Conversation
    {
        return Conversation::where('chatbot_id', $waInstance->chatbot_id)
            ->where('contact_id', $contact->id)
            ->whereIn('status', ['open', 'handoff'])
            ->where('channel', 'whatsapp')
            ->where('last_message_at', '>=', now()->subHours(24))
            ->latest()
            ->first();
    }

    public function findOrCreateConversation(WaInstance $waInstance, Contact $contact): Conversation
    {
        $existing = $this->findRecentConversation($waInstance, $contact);

        if ($existing) {
            return $existing;
        }

        return Conversation::create([
            'chatbot_id'      => $waInstance->chatbot_id,
            'contact_id'      => $contact->id,
            'channel'         => 'whatsapp',
            'status'          => 'open',
            'is_ai_active'    => true,
            'last_message_at' => now(),
        ]);
    }
}
