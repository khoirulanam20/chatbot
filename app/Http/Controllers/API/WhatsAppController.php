<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessWhatsAppMessageJob;
use App\Models\WaInstance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class WhatsAppController extends Controller
{
    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::debug('WA Webhook received', $payload);

        $event = $payload['event'] ?? '';
        $data  = $payload['data'] ?? [];

        // Hanya proses event pesan masuk, abaikan event lainnya
        if ($event !== 'message') {
            return response()->json(['status' => 'ignored']);
        }

        // Abaikan pesan yang dikirim oleh bot sendiri
        if (! empty($data['fromMe'])) {
            return response()->json(['status' => 'ignored_self']);
        }

        $from    = $data['senderPhone'] ?? $payload['from'] ?? '';
        $message = $data['content'] ?? $payload['message'] ?? '';

        if (empty($from) || empty($message) || $data['type'] !== 'text') {
            return response()->json(['status' => 'skipped']);
        }

        $limiter = "wa_msg:{$from}";
        if (RateLimiter::tooManyAttempts($limiter, 10)) {
            return response()->json(['status' => 'rate_limited']);
        }
        RateLimiter::hit($limiter, 60);

        $sessionId   = $payload['sessionId'] ?? null;
        $phoneNumber = $data['senderPhone'] ?? null;

        $waInstance = WaInstance::withoutGlobalScopes()
            ->when($sessionId, fn ($q) => $q->where('instance_id', $sessionId))
            ->when(! $sessionId && $phoneNumber, fn ($q) => $q->where('phone_number', $phoneNumber))
            ->whereIn('status', ['active', 'inactive'])
            ->first();

        if (! $waInstance) {
            Log::warning('No WA instance found for webhook', ['sessionId' => $sessionId, 'phone' => $phoneNumber]);
            return response()->json(['status' => 'no_instance']);
        }

        // Normalisasi payload untuk job
        $normalizedPayload = [
            'from'    => $data['chatId'] ?? $from,
            'message' => $message,
            'name'    => $data['senderName'] ?? $from,
        ];

        ProcessWhatsAppMessageJob::dispatch($normalizedPayload, $waInstance->id);

        return response()->json(['status' => 'queued']);
    }
}
