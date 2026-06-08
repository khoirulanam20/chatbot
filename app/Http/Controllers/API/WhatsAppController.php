<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessWhatsAppAgentReplyJob;
use App\Jobs\ProcessWhatsAppMessageJob;
use App\Models\WaInstance;
use App\Services\WaChateryService;
use App\Services\WaOutboundService;
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

        if ($this->isFromMe($data)) {
            return $this->handleAgentReplyWebhook($data, $sessionId);
        }

        $from    = $this->resolveCustomerIdentifier($data, $payload);
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

        $waInstance = $this->resolveWaInstance($sessionId, $data['senderPhone'] ?? null);

        if (! $waInstance) {
            return $this->webhookResponse('no_instance', $sessionId, [
                'phone' => $data['senderPhone'] ?? null,
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

            $lockKey = "wa_lock:{$waInstance->id}:{$messageId}";
            if (! Cache::add($lockKey, true, now()->addMinutes(10))) {
                return $this->webhookResponse('duplicate', $sessionId, [
                    'wa_instance_id' => $waInstance->id,
                    'message_id'     => $messageId,
                    'reason'         => 'processing',
                ]);
            }
        }

        $normalizedPayload = [
            'from'       => $data['chatId'] ?? $this->normalizeCustomerChatId($from),
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
     * @param  array<string, mixed>  $data
     */
    private function handleAgentReplyWebhook(array $data, ?string $sessionId): JsonResponse
    {
        $customerId = $data['chatId'] ?? $data['recipientPhone'] ?? '';
        $message    = $data['content'] ?? '';
        $messageType = $data['type'] ?? 'text';
        $isImage     = in_array($messageType, ['image', 'imageMessage'], true);
        $isText      = in_array($messageType, ['text', 'chat'], true);

        if ($customerId === '' || (! $isText && ! $isImage)) {
            return $this->webhookResponse('skipped_self', $sessionId, [
                'customerId'  => $customerId,
                'messageType' => $messageType,
            ]);
        }

        if ($isImage && $message === '') {
            $message = $data['caption'] ?? '[Gambar]';
        }

        if ($isText && $message === '') {
            return $this->webhookResponse('skipped_self', $sessionId, ['customerId' => $customerId]);
        }

        $waInstance = $this->resolveWaInstance($sessionId, $data['senderPhone'] ?? null);

        if (! $waInstance) {
            return $this->webhookResponse('no_instance', $sessionId, ['customerId' => $customerId]);
        }

        $messageId = $data['id'] ?? $data['messageId'] ?? null;

        if (WaOutboundService::isOutboundEcho($waInstance->id, $messageId, $customerId, $message)) {
            return $this->webhookResponse('ignored_outbound_echo', $sessionId, [
                'wa_instance_id' => $waInstance->id,
                'message_id'     => $messageId,
            ]);
        }

        ProcessWhatsAppAgentReplyJob::dispatch([
            'customer_id' => $customerId,
            'message'     => $message,
            'message_id'  => $messageId,
            'type'        => $isImage ? 'image' : 'text',
            'media_url'   => $isImage
                ? ($data['mediaUrl'] ?? $data['url'] ?? $data['media'] ?? null)
                : null,
        ], $waInstance->id);

        return $this->webhookResponse('queued_agent_reply', $sessionId, [
            'wa_instance_id' => $waInstance->id,
            'customer_id'    => WaChateryService::normalizePhone($customerId),
            'message_id'     => $messageId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $payload
     */
    private function resolveCustomerIdentifier(array $data, array $payload): string
    {
        if (! empty($data['senderPhone'])) {
            return (string) $data['senderPhone'];
        }

        if (! empty($data['chatId'])) {
            return (string) $data['chatId'];
        }

        return (string) ($payload['from'] ?? '');
    }

    private function normalizeCustomerChatId(string $identifier): string
    {
        if (str_contains($identifier, '@')) {
            return $identifier;
        }

        $phone = WaChateryService::normalizePhone($identifier);

        return $phone !== '' ? $phone . '@s.whatsapp.net' : $identifier;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function isFromMe(array $data): bool
    {
        $value = $data['fromMe'] ?? false;

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        return (bool) $value;
    }

    private function resolveWaInstance(?string $sessionId, ?string $phoneNumber): ?WaInstance
    {
        return WaInstance::withoutGlobalScopes()
            ->when($sessionId, fn ($q) => $q->where('instance_id', $sessionId))
            ->when(! $sessionId && $phoneNumber, fn ($q) => $q->where('phone_number', $phoneNumber))
            ->whereIn('status', ['active', 'inactive'])
            ->first();
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
