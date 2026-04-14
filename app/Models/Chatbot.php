<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Chatbot extends Model
{
    protected $fillable = [
        'tenant_id', 'name', 'avatar', 'system_prompt', 'model',
        'temperature', 'max_context', 'language', 'fallback_message',
        'handoff_triggers', 'settings', 'is_active',
    ];

    protected $casts = [
        'handoff_triggers' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
        'temperature' => 'float',
        'max_context' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function knowledgeDocuments(): HasMany
    {
        return $this->hasMany(KnowledgeDocument::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function embedConfig(): HasOne
    {
        return $this->hasOne(BotEmbedConfig::class);
    }

    public function waInstance(): HasOne
    {
        return $this->hasOne(WaInstance::class);
    }

    public function getFallbackMessage(): string
    {
        return $this->fallback_message
            ?? 'Maaf, saya tidak dapat menemukan jawaban untuk pertanyaan Anda. Silakan hubungi agen kami.';
    }
}
