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
        $this->chatModel  = (string) (config('services.sumopod.chat_model') ?? '');
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
            Log::debug('SumopodService: using tenant API key', [
                'key_prefix' => substr($clone->apiKey, 0, 6),
                'key_length' => strlen($clone->apiKey),
            ]);
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

    /**
     * Model efektif dari Settings → AI (global superadmin atau override tenant).
     */
    public function resolveModel(?Chatbot $chatbot = null, ?string $overrideModel = null): string
    {
        $model = $overrideModel ?? $this->chatModel;

        if ($model === '') {
            throw new \RuntimeException(
                'Model AI belum dikonfigurasi. Atur di Settings → AI (superadmin).'
            );
        }

        return VisionMessageFormatter::normalizeModel($model);
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
            Log::error('Sumopod embedding failed', [
                'status'     => $response->status(),
                'body'       => $response->body(),
                'base_url'   => $this->baseUrl,
                'key_prefix' => substr($this->apiKey, 0, 6),
                'key_length' => strlen($this->apiKey),
            ]);
            throw new \RuntimeException(
                'Embedding API gagal (HTTP ' . $response->status() . '): ' . self::extractApiErrorMessage($response)
            );
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
            Log::error('Sumopod batch embedding failed', [
                'status'     => $response->status(),
                'base_url'   => $this->baseUrl,
                'key_prefix' => substr($this->apiKey, 0, 6),
                'key_length' => strlen($this->apiKey),
            ]);
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
        $model       = $this->resolveModel($chatbot, $overrideModel);
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

    public function chatOnce(
        array $messages,
        ?Chatbot $chatbot = null,
        ?string $overrideModel = null,
        float $temperature = 0.7,
        int $maxTokens = 1500
    ): array {
        $model = $this->resolveModel($chatbot, $overrideModel);

        $response = Http::withToken($this->apiKey)
            ->baseUrl($this->baseUrl)
            ->timeout(60)
            ->post('/chat/completions', [
                'model'       => $model,
                'messages'    => $messages,
                'temperature' => $temperature,
                'max_tokens'  => $maxTokens,
            ]);

        if ($response->failed()) {
            Log::error('Sumopod chat completion failed', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \RuntimeException(
                'Chat API gagal (HTTP ' . $response->status() . '): ' . self::extractApiErrorMessage($response)
            );
        }

        return [
            'content' => $response->json('choices.0.message.content', ''),
            'tokens'  => $response->json('usage.total_tokens', 0),
            'model'   => $response->json('model', $model),
        ];
    }

    public function formatEmbeddingForStorage(array $embedding): string
    {
        return json_encode($embedding);
    }

    /**
     * Validasi nama model embedding — deteksi nilai terduplikasi dari korupsi .env lama.
     */
    public static function validateEmbedModelName(string $model): ?string
    {
        if ($model === '') {
            return 'Model embedding belum dikonfigurasi.';
        }

        return self::detectDuplicatedEmbedModelName($model);
    }

    public static function detectDuplicatedEmbedModelName(string $model): ?string
    {
        $len = strlen($model);
        if ($len > 30 && $len % 2 === 0) {
            $half = substr($model, 0, (int) ($len / 2));
            if ($half === substr($model, (int) ($len / 2))) {
                return 'Model embedding tidak valid (terduplikasi): "' . $model . '". Perbaiki di Pengaturan AI → Embed Model.';
            }
        }

        return null;
    }

    /**
     * @return array{
     *     success: bool,
     *     message: string,
     *     config: array{base_url: string, embed_model: string, chat_model: string},
     *     checks?: array{chat: bool, embedding: bool}
     * }
     */
    public function testConnection(): array
    {
        $config = [
            'base_url'    => $this->baseUrl,
            'embed_model' => $this->embedModel,
            'chat_model'  => $this->chatModel,
        ];

        if ($this->apiKey === '') {
            return [
                'success' => false,
                'message' => 'API key belum dikonfigurasi. Isi API key tenant atau minta superadmin mengatur global.',
                'config'  => $config,
            ];
        }

        if ($this->baseUrl === '') {
            return [
                'success' => false,
                'message' => 'Base URL belum dikonfigurasi.',
                'config'  => $config,
            ];
        }

        try {
            $this->resolveModel();
        } catch (\RuntimeException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'config'  => $config,
            ];
        }

        if ($dupError = self::detectDuplicatedEmbedModelName($this->embedModel)) {
            return [
                'success' => false,
                'message' => $dupError,
                'config'  => $config,
            ];
        }

        $checks = ['chat' => false, 'embedding' => false];
        $errors = [];

        try {
            $this->chatOnce(
                [['role' => 'user', 'content' => 'ping']],
                null,
                null,
                0,
                5
            );
            $checks['chat'] = true;
        } catch (\Exception $e) {
            $errors[] = $e->getMessage();
        }

        if ($this->embedModel !== '') {
            try {
                $this->embed('test');
                $checks['embedding'] = true;
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        if ($checks['chat']) {
            $message = 'Koneksi AI berhasil!';
            if (! $checks['embedding']) {
                $message .= ' Catatan: endpoint embedding tidak tersedia — chatbot berfungsi, tetapi Knowledge Base membutuhkan model embedding yang valid (mis. text-embedding-3-small).';
            }

            return [
                'success' => true,
                'message' => $message,
                'config'  => $config,
                'checks'  => $checks,
            ];
        }

        return [
            'success' => false,
            'message' => implode(' ', $errors) ?: 'Koneksi AI gagal.',
            'config'  => $config,
            'checks'  => $checks,
        ];
    }

    private static function extractApiErrorMessage(\Illuminate\Http\Client\Response $response): string
    {
        $message = $response->json('error.message')
            ?? $response->json('message')
            ?? $response->json('detail')
            ?? $response->json('error');

        if (is_string($message) && $message !== '') {
            return $message;
        }

        $body = trim($response->body());

        return $body !== '' ? mb_substr($body, 0, 200) : 'tanpa detail';
    }
}
