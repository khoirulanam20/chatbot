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

    public function getAgentSessionMinutes(): int
    {
        return app(\App\Services\AgentSessionService::class)->getSessionMinutes($this);
    }

    public function getAgentSessionMessage(): string
    {
        return app(\App\Services\AgentSessionService::class)->getHoldMessage($this);
    }

    /**
     * @return array{role?: string, tone?: string, instructions?: string, restrictions?: string, greeting_style?: string}
     */
    public function getPersona(): array
    {
        return $this->settings['persona'] ?? [];
    }

    public function hasPersona(): bool
    {
        foreach (['role', 'tone', 'instructions', 'restrictions', 'greeting_style'] as $key) {
            $value = trim((string) ($this->getPersona()[$key] ?? ''));
            if ($value !== '') {
                return true;
            }
        }

        return false;
    }

    public function getEffectiveSystemPrompt(): string
    {
        if ($this->hasPersona()) {
            return self::composePersonaPrompt($this->getPersona());
        }

        return $this->system_prompt
            ?: 'Kamu adalah asisten layanan pelanggan yang membantu.';
    }

    /**
     * @param  array{role?: string, tone?: string, instructions?: string, restrictions?: string, greeting_style?: string}  $persona
     */
    public static function composePersonaPrompt(array $persona): string
    {
        $parts = [];

        $role = trim((string) ($persona['role'] ?? ''));
        if ($role !== '') {
            $parts[] = "Peran: {$role}";
        }

        $tone = trim((string) ($persona['tone'] ?? ''));
        if ($tone !== '') {
            $toneLabels = [
                'ramah' => 'ramah dan hangat',
                'formal' => 'formal dan sopan',
                'profesional' => 'profesional dan to the point',
                'santai' => 'santai dan akrab',
            ];
            $toneText = $toneLabels[$tone] ?? $tone;
            $parts[] = "Gaya bicara: {$toneText}";
        }

        $instructions = trim((string) ($persona['instructions'] ?? ''));
        if ($instructions !== '') {
            $parts[] = "Instruksi:\n{$instructions}";
        }

        $restrictions = trim((string) ($persona['restrictions'] ?? ''));
        if ($restrictions !== '') {
            $parts[] = "Larangan:\n{$restrictions}";
        }

        $greeting = trim((string) ($persona['greeting_style'] ?? ''));
        if ($greeting !== '') {
            $parts[] = "Gaya sapaan: {$greeting}";
        }

        return implode("\n\n", $parts);
    }
}
