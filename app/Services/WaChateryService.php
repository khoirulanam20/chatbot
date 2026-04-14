<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WaChateryService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.chatery.base_url', 'https://wa.firstudio.id/api'), '/');
    }

    public function sendMessage(string $apiKey, string $to, string $message, string $sessionId = 'default'): bool
    {
        try {
            $response = Http::withHeaders([
                'X-Api-Key'    => $apiKey,
                'Content-Type' => 'application/json',
            ])
                ->baseUrl($this->baseUrl)
                ->timeout(15)
                ->post('/whatsapp/chats/send-text', [
                    'sessionId' => $sessionId,
                    'to'        => $this->normalizePhone($to),
                    'text'      => $message,
                ]);

            if ($response->failed()) {
                Log::error('WA Chatery send failed', [
                    'to'     => $to,
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('WA Chatery exception', ['message' => $e->getMessage()]);
            return false;
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
                ->get('/whatsapp/sessions/' . $sessionId . '/status');

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

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        return $phone;
    }
}
