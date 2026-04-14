<?php

namespace App\Livewire;

use App\Models\KnowledgeDocument;
use Livewire\Component;

class DocumentStatusChecker extends Component
{
    public int $chatbotId;
    public bool $hasProcessing = false;

    protected $listeners = ['refreshDocuments' => '$refresh'];

    public function mount(int $chatbotId): void
    {
        $this->chatbotId = $chatbotId;
    }

    public function render()
    {
        $documents = KnowledgeDocument::where('chatbot_id', $this->chatbotId)
            ->latest()
            ->limit(5)
            ->get();

        $this->hasProcessing = $documents->whereIn('status', ['queued', 'processing'])->isNotEmpty();

        return view('livewire.document-status-checker', compact('documents'));
    }
}
