<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessWhatsAppMessageJob;
use App\Models\WaInstance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class WhatsAppController extends Controller
{
    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->all();
        $event   = $payload['event'] ?? '';
        $data    = $payload['data'] ?? [];
        $sessionId = $payload['sessionId'] ?? null;

        Log::info('WA webhook received', [
            'event'     => $event,
            'sessionId' => $sessionId,
            'fromMe'    => $data['fromMe'] ?? false,
            'type'      => $data['type'] ?? null,
        ]);

        if ($event !== 'message') {
            return $this->webhookResponse('ignored', $sessionId);
        }

        if (! empty($data['fromMe'])) {
            return $this->webhookResponse('ignored_self', $sessionId);
        }

        $from    = $data['senderPhone'] ?? $payload['from'] ?? '';
        $message = $data['content'] ?? $payload['message'] ?? '';

        $messageType = $data['type'] ?? 'text';
        $isImage     = in_array($messageType, ['image', 'imageMessage'], true);
        $isText      = in_array($messageType, ['text', 'chat'], true);

        if (empty($from) || (! $isText && ! $isImage)) {
            return $this->webhookResponse('skipped', $sessionId, [
                'from'        => $from,
                'messageType' => $messageType,
                'hasMessage'  => $message !== '',
            ]);
        }

        if ($isImage && empty($message)) {
            $message = $data['caption'] ?? '[Gambar]';
        }

        if ($isText && empty($message)) {
            return $this->webhookResponse('skipped', $sessionId, [
                'from'        => $from,
                'messageType' => $messageType,
                'hasMessage'  => false,
            ]);
        }

        $limiter = "wa_msg:{$from}";
        if (RateLimiter::tooManyAttempts($limiter, 10)) {
            return $this->webhookResponse('rate_limited', $sessionId, ['from' => $from]);
        }
        RateLimiter::hit($limiter, 60);

        $phoneNumber = $data['senderPhone'] ?? null;

        $waInstance = WaInstance::withoutGlobalScopes()
            ->when($sessionId, fn ($q) => $q->where('instance_id', $sessionId))
            ->when(! $sessionId && $phoneNumber, fn ($q) => $q->where('phone_number', $phoneNumber))
            ->whereIn('status', ['active', 'inactive'])
            ->first();

        if (! $waInstance) {
            return $this->webhookResponse('no_instance', $sessionId, [
                'phone' => $phoneNumber,
                'from'  => $from,
            ]);
        }

        $messageId = $data['id'] ?? $data['messageId'] ?? null;

        if ($messageId) {
            $doneKey = "wa_done:{$waInstance->id}:{$messageId}";
            if (Cache::has($doneKey)) {
                return $this->webhookResponse('duplicate', $sessionId, [
                    'wa_instance_id' => $waInstance->id,
                    'message_id'     => $messageId,
                ]);
            }
        }

        $normalizedPayload = [
            'from'       => $data['chatId'] ?? $from,
            'message'    => $message,
            'name'       => $data['senderName'] ?? $from,
            'message_id' => $messageId,
            'type'       => $isImage ? 'image' : 'text',
            'media_url'  => $isImage
                ? ($data['mediaUrl'] ?? $data['url'] ?? $data['media'] ?? $data['content'] ?? null)
                : null,
        ];

        ProcessWhatsAppMessageJob::dispatch($normalizedPayload, $waInstance->id);

        return $this->webhookResponse('queued', $sessionId, [
            'wa_instance_id' => $waInstance->id,
            'from'           => $normalizedPayload['from'],
            'message_id'     => $messageId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function webhookResponse(string $status, ?string $sessionId, array $context = []): JsonResponse
    {
        Log::info('WA webhook result', array_merge([
            'status'    => $status,
            'sessionId' => $sessionId,
        ], $context));

        return response()->json(['status' => $status]);
    }
}
