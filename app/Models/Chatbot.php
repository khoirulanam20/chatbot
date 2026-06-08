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
     * @return string[]
     */
    public function getTakeoverKeywords(): array
    {
        $fromSettings = $this->settings['takeover_keywords'] ?? [];

        if (is_string($fromSettings)) {
            $fromSettings = array_map('trim', explode("\n", $fromSettings));
        }

        $legacy = $this->handoff_triggers ?? [];

        return array_values(array_filter(array_unique(array_merge(
            is_array($fromSettings) ? $fromSettings : [],
            is_array($legacy) ? $legacy : []
        ))));
    }

    public function getTakeoverIdleMinutes(): int
    {
        $minutes = (int) (
            $this->settings['takeover_idle_minutes']
            ?? $this->settings['agent_session_minutes']
            ?? \App\Services\AgentSessionService::DEFAULT_MINUTES
        );

        return max(1, min(1440, $minutes));
    }

    public function isPauseAiOnHumanReplyEnabled(): bool
    {
        $value = $this->settings['pause_ai_on_human_reply'] ?? true;

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function getTakeoverHoldMessage(): string
    {
        $message = $this->settings['takeover_hold_message'] ?? null;

        if (is_string($message) && $message !== '') {
            return $message;
        }

        return $this->getAgentSessionMessage();
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

    /**
     * @return array{
     *     enabled: bool,
     *     channels: string[],
     *     emoji_level: string,
     *     message_length: string,
     *     split_bubbles: bool,
     *     pacing_ms: int,
     *     use_fillers: bool,
     *     avoid_markdown: bool
     * }
     */
    public static function defaultHumanizeSettings(): array
    {
        return [
            'enabled' => true,
            'channels' => ['whatsapp', 'web'],
            'emoji_level' => 'minimal',
            'message_length' => 'short',
            'split_bubbles' => true,
            'pacing_ms' => 1200,
            'use_fillers' => true,
            'avoid_markdown' => true,
        ];
    }

    /**
     * @return array{
     *     enabled: bool,
     *     channels: string[],
     *     emoji_level: string,
     *     message_length: string,
     *     split_bubbles: bool,
     *     pacing_ms: int,
     *     use_fillers: bool,
     *     avoid_markdown: bool
     * }
     */
    public function hasExplicitHumanizeConfig(): bool
    {
        return is_array($this->getPersona()['humanize'] ?? null);
    }

    public function getHumanizeSettings(): array
    {
        $humanize = $this->getPersona()['humanize'] ?? null;

        if (! is_array($humanize)) {
            return array_merge(self::defaultHumanizeSettings(), ['enabled' => false]);
        }

        return array_merge(self::defaultHumanizeSettings(), array_filter(
            $humanize,
            fn ($v) => $v !== null
        ));
    }

    public function isHumanizeEnabledFor(string $channel): bool
    {
        $settings = $this->getHumanizeSettings();

        if (! $settings['enabled']) {
            return false;
        }

        $channels = $settings['channels'] ?? [];

        return in_array($channel, $channels, true);
    }

    public function composeHumanizeBlock(string $channel): string
    {
        return self::composeHumanizeBlockFromSettings($this->getHumanizeSettings(), $channel);
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public static function composeHumanizeBlockFromSettings(array $settings, string $channel): string
    {
        if (! ($settings['enabled'] ?? false)) {
            return '';
        }

        $channels = $settings['channels'] ?? [];
        if (! in_array($channel, $channels, true)) {
            return '';
        }

        $channelLabel = $channel === 'whatsapp' ? 'WhatsApp' : 'chat web';
        $lengthMap = [
            'short' => 'Pendek (1-3 kalimat per bubble, maks ~2 baris)',
            'medium' => 'Sedang (2-4 kalimat per bubble)',
            'long' => 'Lebih panjang (3-5 kalimat per bubble, tetap terbaca)',
        ];
        $emojiMap = [
            'none' => 'Jangan gunakan emoji sama sekali.',
            'minimal' => 'Emoji sangat jarang, maksimal 1 per beberapa pesan.',
            'medium' => 'Emoji sesekali untuk mengekspresikan empati atau konfirmasi.',
            'often' => 'Emoji cukup sering, tetap natural dan tidak berlebihan.',
        ];

        $length = $lengthMap[$settings['message_length'] ?? 'short'] ?? $lengthMap['short'];
        $emoji = $emojiMap[$settings['emoji_level'] ?? 'minimal'] ?? $emojiMap['minimal'];

        $lines = [
            "Panduan humanisasi (channel: {$channelLabel}):",
            '- Tulis seperti chat manusia, bukan email, dokumen, atau artikel.',
            '- Variasikan pembuka kalimat; hindari frasa robotik berulang seperti "Tentu!", "Baik, saya akan...", "Sebagai AI...".',
            "- Panjang pesan: {$length}.",
            "- Emoji: {$emoji}",
        ];

        if ($settings['use_fillers'] ?? true) {
            $lines[] = '- Sesekali gunakan filler natural Bahasa Indonesia (mis. "Oke", "Hmm", "Baik") bila cocok, jangan dipaksakan.';
        }

        if ($settings['avoid_markdown'] ?? true) {
            $lines[] = '- Jangan gunakan markdown, bullet list, heading, atau format teknis. Gunakan kalimat mengalir.';
        }

        if ($settings['split_bubbles'] ?? true) {
            $lines[] = '- Jika jawaban lebih dari satu gagasan, pisahkan bubble dengan baris berisi tepat "---" di antara bubble.';
            $lines[] = '- Setiap bubble harus terasa seperti satu pesan chat singkat yang dikirim berturut-turut.';
        }

        return implode("\n", $lines);
    }
}
