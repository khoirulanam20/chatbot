<?php

namespace App\Notifications;

use App\Models\Conversation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewWaMessageDuringHandoffNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Conversation $conversation,
        public string $preview = ''
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $contact = $this->conversation->contact;
        $label   = $contact?->name ?: $contact?->identifier ?: 'Percakapan #' . $this->conversation->id;
        $preview = $this->preview !== '' ? mb_substr($this->preview, 0, 80) : 'Pesan baru';

        return [
            'type'            => 'handoff_message',
            'conversation_id' => $this->conversation->id,
            'title'           => 'Pesan baru saat handoff',
            'body'            => $label . ': ' . $preview,
            'url'             => '/admin/conversations/' . $this->conversation->id,
        ];
    }
}
