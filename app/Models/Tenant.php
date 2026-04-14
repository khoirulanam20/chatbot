<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
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

    /**
     * Ambil konfigurasi AI tenant (hanya key yang sudah diisi).
     */
    public function getAiConfig(): array
    {
        return array_filter([
            'ai_api_key'     => $this->settings['ai_api_key'] ?? null,
            'ai_base_url'    => $this->settings['ai_base_url'] ?? null,
            'ai_embed_model' => $this->settings['ai_embed_model'] ?? null,
            'ai_chat_model'  => $this->settings['ai_chat_model'] ?? null,
        ], fn ($v) => ! empty($v));
    }

    public function updateAiSettings(array $data): void
    {
        $current  = $this->settings ?? [];
        $aiFields = ['ai_api_key', 'ai_base_url', 'ai_embed_model', 'ai_chat_model'];

        foreach ($aiFields as $field) {
            if (array_key_exists($field, $data)) {
                if ($data[$field] === '' || $data[$field] === null) {
                    unset($current[$field]); // hapus → gunakan global default
                } else {
                    $current[$field] = $data[$field];
                }
            }
        }

        $this->update(['settings' => $current]);
    }
}
