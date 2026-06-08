<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WaChateryService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.chatery.base_url', 'https://wa.firstudio.id/api'), '/') . '/';
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

    public function testConnection(string $apiKey, string $sessionId = 'default'): array
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
                return [
                    'success' => true,
                    'status'  => $data['status'] ?? $data['isConnected'] ? 'connected' : 'disconnected',
                    'phone'   => $data['phoneNumber'] ?? $data['phone'] ?? null,
                ];
            }

            $errorMessage = $response->json('message')
                ?? $response->json('error')
                ?? 'HTTP ' . $response->status() . ': ' . $response->body();

            Log::warning('WA Chatery test connection failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return ['success' => false, 'error' => $errorMessage];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
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
        // Jika sudah format chatId (mengandung @), kembalikan langsung
        if (str_contains($phone, '@')) {
            return $phone;
        }

        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        return $phone . '@s.whatsapp.net';
    }

    /**
     * Unduh media dari Chatery (gambar WA).
     */
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
}
