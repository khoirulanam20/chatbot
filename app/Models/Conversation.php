<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Conversation extends Model
{
    protected $fillable = [
        'chatbot_id', 'session_id', 'channel', 'contact_id',
        'assigned_agent_id', 'status', 'is_ai_active', 'last_message_at', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_ai_active' => 'boolean',
        'last_message_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($conversation) {
            if (empty($conversation->session_id)) {
                $conversation->session_id = (string) Str::uuid();
            }
        });
    }

    public function chatbot(): BelongsTo
    {
        return $this->belongsTo(Chatbot::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_agent_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function handoff(): HasOne
    {
        return $this->hasOne(AgentHandoff::class);
    }

    public function getLastMessageAttribute(): ?Message
    {
        return $this->messages()->latest()->first();
    }
}
