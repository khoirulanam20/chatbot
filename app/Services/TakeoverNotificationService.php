<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\User;
use App\Notifications\NewWaMessageDuringHandoffNotification;
use App\Notifications\TakeoverRequestedNotification;
use Illuminate\Support\Facades\Notification;

class TakeoverNotificationService
{
    public function notifyTakeoverRequested(Conversation $conversation, string $keyword = ''): void
    {
        $conversation->loadMissing(['contact', 'chatbot']);

        $recipients = $this->getOperatorRecipients($conversation);

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send(
            $recipients,
            new TakeoverRequestedNotification($conversation, $keyword)
        );
    }

    public function notifyNewMessageDuringHandoff(Conversation $conversation, string $preview = ''): void
    {
        $conversation->loadMissing(['contact', 'chatbot', 'assignedAgent']);

        $recipients = $this->getOperatorRecipients($conversation);

        if ($conversation->assigned_agent_id) {
            $recipients = $recipients->where('id', $conversation->assigned_agent_id);
        }

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send(
            $recipients,
            new NewWaMessageDuringHandoffNotification($conversation, $preview)
        );
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function getOperatorRecipients(Conversation $conversation): \Illuminate\Support\Collection
    {
        $tenantId = $conversation->chatbot?->tenant_id;

        if (! $tenantId) {
            return collect();
        }

        return User::where('tenant_id', $tenantId)
            ->whereIn('role', ['operator', 'admin'])
            ->get();
    }
}
