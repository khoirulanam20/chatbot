<?php

namespace App\Services;

use App\Models\AgentHandoff;
use App\Models\Chatbot;
use App\Models\Conversation;
use App\Models\User;
use App\Support\DebugWaTrace;

class AgentSessionService
{
    public const DEFAULT_MINUTES = 30;

    public const DEFAULT_HOLD_MESSAGE = 'Agen kami sedang menangani percakapan Anda. Mohon tunggu sebentar.';

    public const DEFAULT_TAKEOVER_HOLD_MESSAGE = 'Baik, permintaan Anda sudah diteruskan ke admin. Mohon tunggu sebentar...';

    public function getSessionMinutes(Chatbot $chatbot): int
    {
        return $chatbot->getTakeoverIdleMinutes();
    }

    public function getIdleMinutes(Chatbot $chatbot): int
    {
        return $chatbot->getTakeoverIdleMinutes();
    }

    public function getHoldMessage(Chatbot $chatbot): string
    {
        $message = $chatbot->settings['agent_session_message'] ?? null;

        return is_string($message) && $message !== ''
            ? $message
            : self::DEFAULT_HOLD_MESSAGE;
    }

    public function getTakeoverHoldMessage(Chatbot $chatbot): string
    {
        return $chatbot->getTakeoverHoldMessage();
    }

    public function isInHandoff(Conversation $conversation): bool
    {
        return ! $conversation->is_ai_active && $conversation->status === 'handoff';
    }

    public function isAiBlocked(Conversation $conversation): bool
    {
        return $this->isInHandoff($conversation);
    }

    public static function conversationLockKey(int $conversationId): string
    {
        return "wa_conversation:{$conversationId}";
    }

    /**
     * Perbaiki state percakapan sebelum memproses pesan masuk (WA/web).
     * Mengatasi data legacy: is_ai_active=false tapi status masih open.
     */
    public function prepareForInbound(Conversation $conversation): Conversation
    {
        // #region agent log
        DebugWaTrace::log('H3', 'AgentSessionService.php:prepareForInbound', 'prepare_before', [
            'conversation_id' => $conversation->id,
            'is_ai_active'    => $conversation->is_ai_active,
            'status'          => $conversation->status,
            'last_message_at' => $conversation->last_message_at?->toIso8601String(),
        ]);
        // #endregion

        $expired = $this->expireIfDue($conversation);
        $conversation->refresh();

        if (! $conversation->is_ai_active && $conversation->status !== 'handoff') {
            // #region agent log
            DebugWaTrace::log('H3', 'AgentSessionService.php:prepareForInbound', 'repair_orphan_triggered', [
                'conversation_id' => $conversation->id,
            ]);
            // #endregion
            $this->repairOrphanState($conversation);
        }

        // #region agent log
        DebugWaTrace::log('H3', 'AgentSessionService.php:prepareForInbound', 'prepare_after', [
            'conversation_id' => $conversation->id,
            'is_ai_active'    => $conversation->is_ai_active,
            'status'          => $conversation->status,
            'expired'         => $expired,
            'is_ai_blocked'   => $this->isAiBlocked($conversation),
        ]);
        // #endregion

        return $conversation;
    }

    public function isActive(Conversation $conversation): bool
    {
        if (! $this->isInHandoff($conversation)) {
            return false;
        }

        $this->expireIfDue($conversation);

        return $this->isInHandoff($conversation->fresh());
    }

    public function enterHandoffByKeyword(Conversation $conversation, string $keyword): void
    {
        $this->applyHandoffState($conversation);
        $conversation->update(['assigned_agent_id' => null]);
    }

    public function takeOver(Conversation $conversation, User $agent): void
    {
        $this->applyHandoffState($conversation, $agent);
    }

    /**
     * Pause AI saat admin (panel atau WhatsApp langsung) membalas customer.
     * Hanya berlaku jika toggle pause_ai_on_human_reply aktif di chatbot.
     */
    public function pauseForHumanReply(Conversation $conversation, ?User $agent = null): bool
    {
        $conversation->loadMissing('chatbot');

        $toggleEnabled = $conversation->chatbot?->isPauseAiOnHumanReplyEnabled() ?? false;

        // #region agent log
        DebugWaTrace::log('H1', 'AgentSessionService.php:pauseForHumanReply', 'pause_attempt', [
            'conversation_id'  => $conversation->id,
            'toggle_enabled'   => $toggleEnabled,
            'before_ai_active' => $conversation->is_ai_active,
            'before_status'    => $conversation->status,
        ]);
        // #endregion

        if (! $toggleEnabled) {
            return false;
        }

        $this->applyHandoffState($conversation, $agent);
        $conversation->refresh();

        // #region agent log
        DebugWaTrace::log('H1', 'AgentSessionService.php:pauseForHumanReply', 'pause_applied', [
            'conversation_id' => $conversation->id,
            'is_ai_active'    => $conversation->is_ai_active,
            'status'          => $conversation->status,
        ]);
        // #endregion

        return true;
    }

    public function startSession(Conversation $conversation, ?User $agent = null): void
    {
        $this->applyHandoffState($conversation, $agent);
    }

    private function applyHandoffState(Conversation $conversation, ?User $agent = null): void
    {
        $updates = [
            'is_ai_active'             => false,
            'status'                   => 'handoff',
            'agent_session_started_at' => now(),
            'agent_session_ends_at'    => null,
            'last_message_at'          => now(),
        ];

        if ($agent) {
            $updates['assigned_agent_id'] = $agent->id;
        }

        $conversation->update($updates);
    }

    private function repairOrphanState(Conversation $conversation): void
    {
        if ($this->hasRecentAgentMessage($conversation)) {
            $conversation->update([
                'status'                   => 'handoff',
                'agent_session_started_at' => $conversation->agent_session_started_at ?? now(),
            ]);
            $conversation->refresh();

            return;
        }

        $this->endSession($conversation, resumeAi: true);
        $conversation->refresh();
    }

    private function hasRecentAgentMessage(Conversation $conversation): bool
    {
        $chatbot = $conversation->chatbot ?? $conversation->load('chatbot')->chatbot;
        $idleMinutes = $this->getIdleMinutes($chatbot);

        return $conversation->messages()
            ->where('role', 'agent')
            ->where('created_at', '>=', now()->subMinutes($idleMinutes))
            ->exists();
    }

    public function touchActivity(Conversation $conversation): void
    {
        $conversation->update(['last_message_at' => now()]);
    }

    public function canAgentReply(Conversation $conversation, User $user): bool
    {
        if (! $this->isInHandoff($conversation) && $conversation->is_ai_active) {
            return $user->isOperator();
        }

        if (! $this->isInHandoff($conversation)) {
            return false;
        }

        if ($conversation->assigned_agent_id === null) {
            return $user->isOperator();
        }

        return $conversation->assigned_agent_id === $user->id || $user->isAdmin();
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
        if (! $this->isInHandoff($conversation)) {
            return false;
        }

        $chatbot = $conversation->chatbot ?? $conversation->load('chatbot')->chatbot;

        if (! $conversation->last_message_at) {
            return false;
        }

        $idleMinutes = $this->getIdleMinutes($chatbot);
        $expiresAt   = $conversation->last_message_at->copy()->addMinutes($idleMinutes);

        if ($expiresAt->isFuture()) {
            return false;
        }

        // #region agent log
        DebugWaTrace::log('H3', 'AgentSessionService.php:expireIfDue', 'session_expired_resuming_ai', [
            'conversation_id' => $conversation->id,
            'last_message_at' => $conversation->last_message_at->toIso8601String(),
            'idle_minutes'    => $idleMinutes,
        ]);
        // #endregion

        $this->endSession($conversation, resumeAi: true);

        return true;
    }

    public function expireDueSessions(): int
    {
        $count = 0;

        Conversation::query()
            ->where('is_ai_active', false)
            ->where('status', 'handoff')
            ->whereNotNull('last_message_at')
            ->chunkById(100, function ($conversations) use (&$count) {
                foreach ($conversations as $conversation) {
                    if ($this->expireIfDue($conversation)) {
                        $count++;
                    }
                }
            });

        return $count;
    }

    public function getIdleExpiresAt(Conversation $conversation): ?\Illuminate\Support\Carbon
    {
        if (! $this->isInHandoff($conversation) || ! $conversation->last_message_at) {
            return null;
        }

        $chatbot = $conversation->relationLoaded('chatbot')
            ? $conversation->getRelation('chatbot')
            : ($conversation->chatbot ?? $conversation->load('chatbot')->chatbot);

        return $conversation->last_message_at->copy()->addMinutes($this->getIdleMinutes($chatbot));
    }
}
