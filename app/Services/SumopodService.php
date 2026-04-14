<?php

namespace App\Services;

use App\Models\Chatbot;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SumopodService
{
    private string $apiKey;
    private string $baseUrl;
    private string $embedModel;
    private string $chatModel;

    public function __construct()
    {
        $this->apiKey     = config('services.sumopod.api_key', '');
        $this->baseUrl    = rtrim(config('services.sumopod.base_url', 'https://api.openai.com/v1'), '/');
        $this->embedModel = config('services.sumopod.embed_model', 'text-embedding-3-small');
        $this->chatModel  = config('services.sumopod.chat_model', 'gpt-4o');

        // Debug log untuk production (hapus setelah issue selesai)
        if (app()->environment('production') && $this->apiKey) {
            Log::debug('SumopodService init', [
                'key_prefix' => substr($this->apiKey, 0, 6),
                'key_suffix' => substr($this->apiKey, -4),
                'key_length' => strlen($this->apiKey),
                'base_url' => $this->baseUrl,
            ]);
        }
    }

    /**
     * Kembalikan instance baru dengan konfigurasi dari tenant.
     * Hanya field yang diisi oleh tenant yang akan meng-override global config.
     */
    public function withTenantSettings(array $settings): self
    {
        $clone = clone $this;

        if (! empty($settings['ai_api_key'])) {
            $clone->apiKey = $settings['ai_api_key'];
        }
        if (! empty($settings['ai_base_url'])) {
            $clone->baseUrl = rtrim($settings['ai_base_url'], '/');
        }
        if (! empty($settings['ai_embed_model'])) {
            $clone->embedModel = $settings['ai_embed_model'];
        }
        if (! empty($settings['ai_chat_model'])) {
            $clone->chatModel = $settings['ai_chat_model'];
        }

        return $clone;
    }

    public function getConfig(): array
    {
        return [
            'api_key'     => $this->apiKey,
            'base_url'    => $this->baseUrl,
            'embed_model' => $this->embedModel,
            'chat_model'  => $this->chatModel,
        ];
    }

    public function embed(string $text): array
    {
        $response = Http::withToken($this->apiKey)
            ->baseUrl($this->baseUrl)
            ->timeout(30)
            ->post('/embeddings', [
                'model' => $this->embedModel,
                'input' => $text,
            ]);

        if ($response->failed()) {
            Log::error('Sumopod embedding failed', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \RuntimeException('Embedding API request failed: ' . $response->status());
        }

        return $response->json('data.0.embedding', []);
    }

    public function embedBatch(array $texts): array
    {
        $response = Http::withToken($this->apiKey)
            ->baseUrl($this->baseUrl)
            ->timeout(60)
            ->post('/embeddings', [
                'model' => $this->embedModel,
                'input' => $texts,
            ]);

        if ($response->failed()) {
            Log::error('Sumopod batch embedding failed', ['status' => $response->status()]);
            throw new \RuntimeException('Batch embedding API request failed');
        }

        $data = $response->json('data', []);
        usort($data, fn ($a, $b) => $a['index'] - $b['index']);

        return array_map(fn ($item) => $item['embedding'], $data);
    }

    public function chat(
        array $messages,
        ?Chatbot $chatbot = null,
        ?string $overrideModel = null
    ): array {
        $model       = $overrideModel ?? $chatbot?->model ?? $this->chatModel;
        $temperature = $chatbot?->temperature ?? 0.7;

        $cacheKey = 'ai_resp_' . md5(json_encode($messages) . $model);
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        $response = Http::withToken($this->apiKey)
            ->baseUrl($this->baseUrl)
            ->timeout(60)
            ->post('/chat/completions', [
                'model'       => $model,
                'messages'    => $messages,
                'temperature' => $temperature,
                'max_tokens'  => 1500,
            ]);

        if ($response->failed()) {
            Log::error('Sumopod chat completion failed', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \RuntimeException('Chat completion API request failed: ' . $response->status());
        }

        $result = [
            'content' => $response->json('choices.0.message.content', ''),
            'tokens'  => $response->json('usage.total_tokens', 0),
            'model'   => $response->json('model', $model),
        ];

        Cache::put($cacheKey, $result, now()->addMinutes(30));

        return $result;
    }

    public function formatEmbeddingForStorage(array $embedding): string
    {
        return json_encode($embedding);
    }

    public function testConnection(): bool
    {
        try {
            $this->embed('test');
            return true;
        } catch (\Exception) {
            return false;
        }
    }
}
