<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\WaInstance;

class WaConversationResolver
{
    /**
     * Identifier kanonik customer — selalu utamakan chatId (selaras webhook fromMe admin).
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $payload
     */
    public function resolveContactIdentifier(array $data, array $payload = []): string
    {
        if (! empty($data['chatId'])) {
            return (string) $data['chatId'];
        }

        if (! empty($data['recipientPhone'])) {
            return (string) $data['recipientPhone'];
        }

        if (! empty($data['senderPhone'])) {
            return (string) $data['senderPhone'];
        }

        return (string) ($payload['from'] ?? '');
    }

    public function findOrCreateContact(WaInstance $waInstance, string $customerIdentifier): Contact
    {
        $identifier = WaChateryService::normalizePhone($customerIdentifier);
        $waChatId   = $this->canonicalWaChatId($customerIdentifier);

        $contact = Contact::withoutGlobalScopes()->firstOrCreate(
            [
                'tenant_id'  => $waInstance->tenant_id,
                'identifier' => $identifier,
                'channel'    => 'whatsapp',
            ],
            ['name' => $identifier]
        );

        if ($waChatId !== null) {
            $metadata = $contact->metadata ?? [];
            if (($metadata['wa_chat_id'] ?? null) !== $waChatId) {
                $metadata['wa_chat_id'] = $waChatId;
                $contact->update(['metadata' => $metadata]);
                $contact->refresh();
            }
        }

        return $contact;
    }

    public function canonicalWaChatId(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        if (str_contains($raw, '@')) {
            return $raw;
        }

        $phone = WaChateryService::normalizePhone($raw);

        return $phone !== '' ? $phone . '@s.whatsapp.net' : null;
    }

    public function resolveOutboundChatId(Contact $contact): string
    {
        $waChatId = $contact->metadata['wa_chat_id'] ?? null;
        if (is_string($waChatId) && $waChatId !== '') {
            return $waChatId;
        }

        return $this->canonicalWaChatId($contact->identifier) ?? $contact->identifier;
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
