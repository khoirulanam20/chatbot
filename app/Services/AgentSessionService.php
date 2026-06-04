<?php

namespace App\Services;

use App\Models\AgentHandoff;
use App\Models\Chatbot;
use App\Models\Conversation;
use App\Models\User;

class AgentSessionService
{
    public const DEFAULT_MINUTES = 30;

    public const DEFAULT_HOLD_MESSAGE = 'Agen kami sedang menangani percakapan Anda. Mohon tunggu sebentar.';

    public function getSessionMinutes(Chatbot $chatbot): int
    {
        $minutes = (int) ($chatbot->settings['agent_session_minutes'] ?? self::DEFAULT_MINUTES);

        return max(1, min(1440, $minutes));
    }

    public function getHoldMessage(Chatbot $chatbot): string
    {
        $message = $chatbot->settings['agent_session_message'] ?? null;

        return is_string($message) && $message !== ''
            ? $message
            : self::DEFAULT_HOLD_MESSAGE;
    }

    public function isActive(Conversation $conversation): bool
    {
        if (! $conversation->agent_session_ends_at) {
            return false;
        }

        if ($conversation->agent_session_ends_at->isFuture()) {
            return true;
        }

        $this->expireIfDue($conversation);

        return false;
    }

    public function startSession(Conversation $conversation, ?User $agent = null): void
    {
        $chatbot = $conversation->chatbot ?? $conversation->load('chatbot')->chatbot;
        $minutes = $this->getSessionMinutes($chatbot);

        $updates = [
            'is_ai_active'              => false,
            'status'                    => 'handoff',
            'agent_session_started_at'  => now(),
            'agent_session_ends_at'       => now()->addMinutes($minutes),
        ];

        if ($agent) {
            $updates['assigned_agent_id'] = $agent->id;
        }

        $conversation->update($updates);
    }

    public function endSession(Conversation $conversation, bool $resumeAi = true): void
    {
        $updates = [
            'agent_session_started_at' => null,
            'agent_session_ends_at'    => null,
        ];

        if ($resumeAi) {
            $updates['is_ai_active'] = true;
            $updates['status']       = 'open';
        }

        $conversation->update($updates);

        AgentHandoff::where('conversation_id', $conversation->id)
            ->whereNull('resolved_at')
            ->update(['resolved_at' => now()]);
    }

    public function expireIfDue(Conversation $conversation): bool
    {
        if (! $conversation->agent_session_ends_at || $conversation->agent_session_ends_at->isFuture()) {
            return false;
        }

        $this->endSession($conversation, resumeAi: true);

        return true;
    }

    public function expireDueSessions(): int
    {
        $count = 0;

        Conversation::query()
            ->whereNotNull('agent_session_ends_at')
            ->where('agent_session_ends_at', '<', now())
            ->where('is_ai_active', false)
            ->chunkById(100, function ($conversations) use (&$count) {
                foreach ($conversations as $conversation) {
                    if ($this->expireIfDue($conversation)) {
                        $count++;
                    }
                }
            });

        return $count;
    }
}
