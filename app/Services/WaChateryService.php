<?php

namespace App\Services;

use App\Models\WaInstance;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WaChateryService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.chatery.base_url', 'https://wa.firstudio.id/api'), '/') . '/';
    }

    public function resolveApiKey(?WaInstance $instance = null): ?string
    {
        $key = $instance?->api_key ?: config('services.chatery.api_key');

        return filled($key) ? $key : null;
    }

    /**
     * @return array{success: bool, message_id: ?string}
     */
    public function sendMessage(
        string $apiKey,
        string $to,
        string $message,
        string $sessionId = 'default',
        ?int $typingTime = null
    ): array {
        try {
            $payload = [
                'sessionId' => $sessionId,
                'chatId'    => $this->normalizeChatId($to),
                'message'   => $message,
            ];

            if ($typingTime !== null && $typingTime > 0) {
                $payload['typingTime'] = $typingTime;
            }

            // #region debug-point wa-send-stuck-chatery-send-request
            Log::info('WA Chatery send request', [
                'session_id'     => $sessionId,
                'to'             => $to,
                'normalized_to'  => $payload['chatId'],
                'typing_time'    => $payload['typingTime'] ?? null,
                'message_length' => mb_strlen($message),
            ]);
            // #endregion

            $response = Http::withHeaders([
                'X-Api-Key'    => $apiKey,
                'Content-Type' => 'application/json',
            ])
                ->baseUrl($this->baseUrl)
                ->timeout(15)
                ->post('whatsapp/chats/send-text', $payload);

            if ($response->failed()) {
                Log::error('WA Chatery send failed', [
                    'to'     => $to,
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return ['success' => false, 'message_id' => null];
            }

            // #region debug-point wa-send-stuck-chatery-send-response
            Log::info('WA Chatery send success response', [
                'session_id' => $sessionId,
                'to'         => $to,
                'status'     => $response->status(),
                'body'       => $response->json(),
            ]);
            // #endregion

            $messageId = $response->json('data.id')
                ?? $response->json('data.messageId')
                ?? $response->json('messageId')
                ?? $response->json('id');

            return [
                'success'    => true,
                'message_id' => is_string($messageId) ? $messageId : null,
            ];
        } catch (\Exception $e) {
            Log::error('WA Chatery exception', ['message' => $e->getMessage()]);

            return ['success' => false, 'message_id' => null];
        }
    }

    /**
     * @return array{success: bool, message_id: ?string}
     */
    public function sendImage(
        string $apiKey,
        string $to,
        string $imageUrl,
        ?string $caption = null,
        string $sessionId = 'default'
    ): array {
        try {
            $payload = [
                'sessionId' => $sessionId,
                'chatId'    => $this->normalizeChatId($to),
                'imageUrl'  => $imageUrl,
            ];

            if ($caption !== null && $caption !== '' && $caption !== '[Gambar]') {
                $payload['caption'] = $caption;
            }

            $response = Http::withHeaders([
                'X-Api-Key'    => $apiKey,
                'Content-Type' => 'application/json',
            ])
                ->baseUrl($this->baseUrl)
                ->timeout(30)
                ->post('whatsapp/chats/send-image', $payload);

            if ($response->failed()) {
                Log::error('WA Chatery send image failed', [
                    'to'     => $to,
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return ['success' => false, 'message_id' => null];
            }

            $messageId = $response->json('data.id')
                ?? $response->json('data.messageId')
                ?? $response->json('messageId')
                ?? $response->json('id');

            return [
                'success'    => true,
                'message_id' => is_string($messageId) ? $messageId : null,
            ];
        } catch (\Exception $e) {
            Log::error('WA Chatery send image exception', ['message' => $e->getMessage()]);

            return ['success' => false, 'message_id' => null];
        }
    }

    /**
     * @return array{success: bool, status: ?string, phone: ?string, is_connected: bool, error?: string}
     */
    public function getSessionStatus(string $apiKey, string $sessionId = 'default'): array
    {
        try {
            $response = Http::withHeaders([
                'X-Api-Key' => $apiKey,
            ])
                ->baseUrl($this->baseUrl)
                ->timeout(10)
                ->get('whatsapp/sessions/' . $sessionId . '/status');

            if ($response->successful()) {
                $data = $response->json('data') ?? [];
                $status = $data['status'] ?? null;

                if (! $status && isset($data['isConnected'])) {
                    $status = $data['isConnected'] ? 'connected' : 'disconnected';
                }

                return [
                    'success'      => true,
                    'status'       => $status,
                    'phone'        => $data['phoneNumber'] ?? $data['phone'] ?? null,
                    'is_connected' => ($status === 'connected') || ($data['isConnected'] ?? false),
                ];
            }

            $errorMessage = $response->json('message')
                ?? $response->json('error')
                ?? 'HTTP ' . $response->status() . ': ' . $response->body();

            Log::warning('WA Chatery session status failed', [
                'session_id' => $sessionId,
                'status'     => $response->status(),
                'body'       => $response->body(),
            ]);

            return ['success' => false, 'status' => null, 'phone' => null, 'is_connected' => false, 'error' => $errorMessage];
        } catch (\Exception $e) {
            return ['success' => false, 'status' => null, 'phone' => null, 'is_connected' => false, 'error' => $e->getMessage()];
        }
    }

    public function testConnection(string $apiKey, string $sessionId = 'default'): array
    {
        $result = $this->getSessionStatus($apiKey, $sessionId);

        if ($result['success']) {
            return [
                'success' => true,
                'status'  => $result['status'] ?? ($result['is_connected'] ? 'connected' : 'disconnected'),
                'phone'   => $result['phone'],
            ];
        }

        return ['success' => false, 'error' => $result['error'] ?? 'Unknown error'];
    }

    /**
     * @return array{success: bool, status: ?string, qr_code: ?string, error?: string}
     */
    public function connectSession(string $apiKey, string $sessionId, ?array $webhooks = null): array
    {
        try {
            $payload = [
                'webhooks' => $webhooks ?? [[
                    'url'    => $this->getWebhookUrl(),
                    'events' => ['all'],
                ]],
            ];

            $response = Http::withHeaders([
                'X-Api-Key'    => $apiKey,
                'Content-Type' => 'application/json',
            ])
                ->baseUrl($this->baseUrl)
                ->timeout(30)
                ->post('whatsapp/sessions/' . $sessionId . '/connect', $payload);

            if ($response->successful()) {
                $data = $response->json('data') ?? [];

                return [
                    'success'  => true,
                    'status'   => $data['status'] ?? 'connecting',
                    'qr_code'  => $data['qrCode'] ?? $data['qr_code'] ?? null,
                ];
            }

            $errorMessage = $response->json('message')
                ?? $response->json('error')
                ?? 'HTTP ' . $response->status() . ': ' . $response->body();

            Log::warning('WA Chatery connect failed', [
                'session_id' => $sessionId,
                'status'     => $response->status(),
                'body'       => $response->body(),
            ]);

            return ['success' => false, 'status' => null, 'qr_code' => null, 'error' => $errorMessage];
        } catch (\Exception $e) {
            return ['success' => false, 'status' => null, 'qr_code' => null, 'error' => $e->getMessage()];
        }
    }

    public function getQrImage(string $apiKey, string $sessionId): ?string
    {
        try {
            $response = Http::withHeaders(['X-Api-Key' => $apiKey])
                ->baseUrl($this->baseUrl)
                ->timeout(15)
                ->get('whatsapp/sessions/' . $sessionId . '/qr/image');

            if ($response->failed()) {
                Log::warning('WA Chatery QR image failed', [
                    'session_id' => $sessionId,
                    'status'     => $response->status(),
                ]);

                return null;
            }

            return $response->body();
        } catch (\Exception $e) {
            Log::warning('WA Chatery QR image exception', ['message' => $e->getMessage()]);

            return null;
        }
    }

    public function getWebhookUrl(): string
    {
        return url('/api/webhook/whatsapp');
    }

    public function sendTyping(string $apiKey, string $to, string $sessionId = 'default'): bool
    {
        try {
            $response = Http::withHeaders([
                'X-Api-Key'    => $apiKey,
                'Content-Type' => 'application/json',
            ])
                ->baseUrl($this->baseUrl)
                ->timeout(10)
                ->post('whatsapp/chats/presence', [
                    'sessionId' => $sessionId,
                    'chatId'    => $this->normalizeChatId($to),
                    'presence'  => 'composing',
                ]);

            // #region debug-point wa-send-stuck-typing-response
            Log::info('WA Chatery typing response', [
                'session_id' => $sessionId,
                'to'         => $to,
                'chat_id'    => $this->normalizeChatId($to),
                'status'     => $response->status(),
                'body'       => $response->json(),
            ]);
            // #endregion

            return $response->successful();
        } catch (\Exception $e) {
            Log::debug('WA Chatery typing failed', ['message' => $e->getMessage()]);

            return false;
        }
    }

    public function clearTyping(string $apiKey, string $to, string $sessionId = 'default'): bool
    {
        try {
            $response = Http::withHeaders([
                'X-Api-Key'    => $apiKey,
                'Content-Type' => 'application/json',
            ])
                ->baseUrl($this->baseUrl)
                ->timeout(10)
                ->post('whatsapp/chats/presence', [
                    'sessionId' => $sessionId,
                    'chatId'    => $this->normalizeChatId($to),
                    'presence'  => 'paused',
                ]);

            // #region debug-point wa-send-stuck-paused-response
            Log::info('WA Chatery paused response', [
                'session_id' => $sessionId,
                'to'         => $to,
                'chat_id'    => $this->normalizeChatId($to),
                'status'     => $response->status(),
                'body'       => $response->json(),
            ]);
            // #endregion

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Daftar sesi WhatsApp di Chatery (GET whatsapp/sessions).
     *
     * @return array{success: bool, sessions: array<int, array{id: string, phone: ?string, status: ?string, name: ?string}>, error?: string}
     */
    public function listSessions(string $apiKey): array
    {
        try {
            $response = Http::withHeaders(['X-Api-Key' => $apiKey])
                ->baseUrl($this->baseUrl)
                ->timeout(10)
                ->get('whatsapp/sessions');

            if (! $response->successful()) {
                return [
                    'success'  => false,
                    'sessions' => [],
                    'error'    => $response->json('message') ?? 'HTTP ' . $response->status(),
                ];
            }

            $raw = $response->json('data') ?? $response->json('sessions') ?? $response->json() ?? [];
            if (isset($raw['sessions']) && is_array($raw['sessions'])) {
                $raw = $raw['sessions'];
            }
            if (! is_array($raw)) {
                $raw = [];
            }

            $sessions = [];
            foreach ($raw as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $id = $item['id'] ?? $item['sessionId'] ?? $item['session_id'] ?? null;
                if (! $id) {
                    continue;
                }
                $sessions[] = [
                    'id'     => (string) $id,
                    'phone'  => $item['phoneNumber'] ?? $item['phone'] ?? $item['wid'] ?? null,
                    'status' => $item['status'] ?? ($item['isConnected'] ?? false ? 'connected' : 'disconnected'),
                    'name'   => $item['name'] ?? $item['pushName'] ?? null,
                ];
            }

            return ['success' => true, 'sessions' => $sessions];
        } catch (\Exception $e) {
            return ['success' => false, 'sessions' => [], 'error' => $e->getMessage()];
        }
    }

    private function normalizeChatId(string $phone): string
    {
        if (str_contains($phone, '@')) {
            return $phone;
        }

        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        return $phone . '@s.whatsapp.net';
    }

    public function downloadMedia(string $apiKey, string $mediaUrl): ?string
    {
        try {
            $response = Http::withHeaders(['X-Api-Key' => $apiKey])
                ->timeout(30)
                ->get($mediaUrl);

            if ($response->failed()) {
                Log::error('WA Chatery media download failed', [
                    'url'    => $mediaUrl,
                    'status' => $response->status(),
                ]);

                return null;
            }

            return $response->body();
        } catch (\Exception $e) {
            Log::error('WA Chatery media download exception', ['message' => $e->getMessage()]);

            return null;
        }
    }

    public static function normalizePhone(string $phone): string
    {
        if (str_contains($phone, '@')) {
            $phone = explode('@', $phone)[0];
        }

        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        return $phone;
    }

    public static function sessionIdForChatbot(int $chatbotId): string
    {
        return 'bot-' . $chatbotId;
    }

    public static function mapChateryStatusToLocal(?string $chateryStatus, bool $isConnected = false): string
    {
        if ($isConnected || $chateryStatus === 'connected') {
            return 'active';
        }

        if (in_array($chateryStatus, ['qr_ready', 'connecting'], true)) {
            return 'connecting';
        }

        if ($chateryStatus === 'disconnected') {
            return 'inactive';
        }

        return 'connecting';
    }
}
