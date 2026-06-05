<?php

namespace App\Notifications;

use App\Models\Conversation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TakeoverRequestedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Conversation $conversation,
        public string $keyword = ''
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $contact = $this->conversation->contact;
        $label   = $contact?->name ?: $contact?->identifier ?: 'Percakapan #' . $this->conversation->id;

        return [
            'type'            => 'takeover_requested',
            'conversation_id' => $this->conversation->id,
            'title'           => 'Customer minta admin',
            'body'            => $label . ' · ' . ($this->conversation->chatbot?->name ?? 'Chatbot'),
            'url'             => '/admin/conversations/' . $this->conversation->id,
            'keyword'         => $this->keyword,
        ];
    }
}
