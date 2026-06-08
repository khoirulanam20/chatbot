<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class Tenant extends Model
{
    public const AI_API_KEY     = 'ai_api_key';
    public const AI_BASE_URL    = 'ai_base_url';
    public const AI_EMBED_MODEL = 'ai_embed_model';
    public const AI_CHAT_MODEL   = 'ai_chat_model';
    public const AI_VISION_MODEL = 'ai_vision_model';

    public const AI_FIELDS = [
        self::AI_API_KEY,
        self::AI_BASE_URL,
        self::AI_EMBED_MODEL,
        self::AI_CHAT_MODEL,
        self::AI_VISION_MODEL,
    ];

    protected $fillable = [
        'name', 'slug', 'logo_path', 'settings', 'plan', 'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function chatbots(): HasMany
    {
        return $this->hasMany(Chatbot::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function waInstances(): HasMany
    {
        return $this->hasMany(WaInstance::class);
    }

    public function personaTemplates(): HasMany
    {
        return $this->hasMany(PersonaTemplate::class);
    }

    /**
     * Ambil konfigurasi AI tenant (hanya key yang sudah diisi).
     */
    public function getAiConfig(): array
    {
        $settings = $this->settings ?? [];

        return array_filter([
            self::AI_API_KEY     => $this->decryptApiKey($settings[self::AI_API_KEY] ?? null),
            self::AI_BASE_URL    => isset($settings[self::AI_BASE_URL]) ? trim((string) $settings[self::AI_BASE_URL]) : null,
            self::AI_EMBED_MODEL => isset($settings[self::AI_EMBED_MODEL]) ? trim((string) $settings[self::AI_EMBED_MODEL]) : null,
            self::AI_CHAT_MODEL   => isset($settings[self::AI_CHAT_MODEL]) ? trim((string) $settings[self::AI_CHAT_MODEL]) : null,
            self::AI_VISION_MODEL => isset($settings[self::AI_VISION_MODEL]) ? trim((string) $settings[self::AI_VISION_MODEL]) : null,
        ], fn ($v) => $v !== null && $v !== '');
    }

    public function hasAiApiKey(): bool
    {
        $raw = $this->settings[self::AI_API_KEY] ?? null;

        return $raw !== null && $raw !== '';
    }

    public function updateAiSettings(array $data): void
    {
        $current = $this->settings ?? [];

        foreach (self::AI_FIELDS as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $value = is_string($data[$field]) ? trim($data[$field]) : $data[$field];

            if ($value === '' || $value === null) {
                unset($current[$field]);
            } elseif ($field === self::AI_API_KEY) {
                $current[$field] = $this->encryptApiKey((string) $value);
            } else {
                $current[$field] = $value;
            }
        }

        $this->update(['settings' => $current]);
    }

    private function encryptApiKey(string $value): string
    {
        return Crypt::encryptString($value);
    }

    private function decryptApiKey(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return $value;
        }
    }
}
