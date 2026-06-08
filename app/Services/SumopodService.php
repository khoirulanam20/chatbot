<?php

namespace App\Services;

use App\Models\Chatbot;
use App\Models\Tenant;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SumopodService
{
    private const TIMEOUT_EMBED = 30;
    private const TIMEOUT_CHAT  = 60;
    private const CACHE_MINUTES = 30;
    private const DEFAULT_MAX_TOKENS = 1500;

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

        if (! empty($settings[Tenant::AI_API_KEY])) {
            $clone->apiKey = $settings[Tenant::AI_API_KEY];
            Log::debug('SumopodService: using tenant API key', [
                'key_configured' => true,
            ]);
        }
        if (! empty($settings[Tenant::AI_BASE_URL])) {
            $clone->baseUrl = rtrim($settings[Tenant::AI_BASE_URL], '/');
        }
        if (! empty($settings[Tenant::AI_EMBED_MODEL])) {
            $clone->embedModel = $settings[Tenant::AI_EMBED_MODEL];
        }
        if (! empty($settings[Tenant::AI_CHAT_MODEL])) {
            $clone->chatModel = $settings[Tenant::AI_CHAT_MODEL];
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
        $this->ensureApiKeyConfigured();

        $response = $this->post('/embeddings', [
            'model' => $this->embedModel,
            'input' => $text,
        ], self::TIMEOUT_EMBED, 'Embedding API');

        $embedding = $response->json('data.0.embedding');

        if (! is_array($embedding) || $embedding === []) {
            throw new \RuntimeException('Embedding API mengembalikan respons kosong.');
        }

        return $embedding;
    }

    public function embedBatch(array $texts): array
    {
        $this->ensureApiKeyConfigured();

        $response = $this->post('/embeddings', [
            'model' => $this->embedModel,
            'input' => $texts,
        ], self::TIMEOUT_EMBED, 'Batch embedding API');

        $data = $response->json('data', []);
        usort($data, fn ($a, $b) => $a['index'] - $b['index']);

        if (count($data) !== count($texts)) {
            throw new \RuntimeException(
                'Batch embedding API mengembalikan jumlah hasil tidak sesuai: '
                . count($data) . ' dari ' . count($texts)
            );
        }

        return array_map(fn ($item) => $item['embedding'], $data);
    }

    public function chat(
        array $messages,
        ?Chatbot $chatbot = null,
        ?string $overrideModel = null
    ): array {
        $model       = $this->resolveModel($chatbot, $overrideModel);
        $temperature = $chatbot?->temperature ?? 0.7;

        $cacheKey = $this->buildChatCacheKey($messages, $model, $temperature);
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        $result = $this->performChat($messages, $model, $temperature, self::DEFAULT_MAX_TOKENS);
        Cache::put($cacheKey, $result, now()->addMinutes(self::CACHE_MINUTES));

        return $result;
    }

    public function chatOnce(
        array $messages,
        ?Chatbot $chatbot = null,
        ?string $overrideModel = null,
        float $temperature = 0.7,
        int $maxTokens = self::DEFAULT_MAX_TOKENS
    ): array {
        $model = $this->resolveModel($chatbot, $overrideModel);

        return $this->performChat($messages, $model, $temperature, $maxTokens);
    }

    public function formatEmbeddingForStorage(array $embedding): string
    {
        return json_encode($embedding);
    }

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

    public static function isConfigurationError(\Throwable $e): bool
    {
        if (! $e instanceof \RuntimeException) {
            return false;
        }

        $message = $e->getMessage();

        return str_contains($message, 'belum dikonfigurasi')
            || str_contains($message, 'terduplikasi')
            || str_contains($message, 'API key belum');
    }

    private function ensureApiKeyConfigured(): void
    {
        if ($this->apiKey === '') {
            throw new \RuntimeException(
                'API key belum dikonfigurasi. Isi API key tenant atau minta superadmin mengatur global.'
            );
        }
    }

    private function post(string $endpoint, array $payload, int $timeout, string $label): Response
    {
        $response = Http::withToken($this->apiKey)
            ->baseUrl($this->baseUrl)
            ->timeout($timeout)
            ->post($endpoint, $payload);

        if ($response->failed()) {
            Log::error("Sumopod {$label} failed", [
                'status'   => $response->status(),
                'body'     => $response->body(),
                'base_url' => $this->baseUrl,
            ]);
            throw new \RuntimeException(
                "{$label} gagal (HTTP {$response->status()}): " . self::extractApiErrorMessage($response)
            );
        }

        return $response;
    }

    /**
     * @return array{content: string, tokens: int, model: string}
     */
    private function performChat(array $messages, string $model, float $temperature, int $maxTokens): array
    {
        $this->ensureApiKeyConfigured();

        $response = $this->post('/chat/completions', [
            'model'       => $model,
            'messages'    => $messages,
            'temperature' => $temperature,
            'max_tokens'  => $maxTokens,
        ], self::TIMEOUT_CHAT, 'Chat API');

        $content = $response->json('choices.0.message.content');

        if (! is_string($content) || $content === '') {
            throw new \RuntimeException('Chat API mengembalikan respons kosong.');
        }

        return [
            'content' => $content,
            'tokens'  => (int) $response->json('usage.total_tokens', 0),
            'model'   => (string) $response->json('model', $model),
        ];
    }

    private function buildChatCacheKey(array $messages, string $model, float $temperature): string
    {
        $credentialHash = md5($this->apiKey . '|' . $this->baseUrl);

        return 'ai_resp_' . md5(json_encode($messages) . $model . $temperature . $credentialHash);
    }

    private static function extractApiErrorMessage(Response $response): string
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
