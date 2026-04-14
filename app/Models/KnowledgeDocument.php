<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeDocument extends Model
{
    protected $fillable = [
        'chatbot_id', 'name', 'original_name', 'type', 'path',
        'status', 'chunk_count', 'error_message', 'tags', 'description',
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    public function chatbot(): BelongsTo
    {
        return $this->belongsTo(Chatbot::class);
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(KnowledgeChunk::class, 'document_id');
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isIndexed(): bool
    {
        return $this->status === 'indexed';
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            'queued'     => 'yellow',
            'processing' => 'blue',
            'indexed'    => 'green',
            'failed'     => 'red',
            default      => 'gray',
        };
    }
}
