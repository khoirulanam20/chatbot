<?php

namespace App\Livewire;

use App\Models\Chatbot;
use App\Models\Conversation;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class ConversationInbox extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = '';
    public string $channel = '';

    protected $queryString = ['search', 'status', 'channel'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $chatbotIds = Chatbot::pluck('id');

        $conversations = Conversation::with(['contact', 'chatbot'])
            ->whereIn('chatbot_id', $chatbotIds)
            ->when($this->search, function ($query) {
                $query->whereHas('contact', fn ($q) => $q->where('identifier', 'like', "%{$this->search}%")
                    ->orWhere('name', 'like', "%{$this->search}%"));
            })
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->channel, fn ($q) => $q->where('channel', $this->channel))
            ->latest('last_message_at')
            ->paginate(15);

        return view('livewire.conversation-inbox', compact('conversations'));
    }
}
