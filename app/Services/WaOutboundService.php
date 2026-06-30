<?php

namespace App\Services;

use App\Models\WaInstance;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WaOutboundService
{
    private const OUTBOUND_CACHE_MINUTES = 10;

    public function __construct(
        private WaChateryService $chatery
    ) {}

    public function sendText(WaInstance $waInstance, string $to, string $message): bool
    {
        $apiKey = $this->chatery->resolveApiKey($waInstance);

        if (! filled($apiKey)) {
            return false;
        }

        $sessionId = $waInstance->instance_id ?: 'default';

        $typingTime = $waInstance->typing_enabled
            ? max(500, min(10000, (int) ($waInstance->typing_duration_ms ?? 2000)))
            : null;

        // #region debug-point wa-send-stuck-sendtext-start
        Log::info('WA outbound sendText start', [
            'wa_instance_id'  => $waInstance->id,
            'session_id'      => $sessionId,
            'to'              => $to,
            'normalized_to'   => WaChateryService::normalizePhone($to),
            'typing_enabled'  => (bool) $waInstance->typing_enabled,
            'typing_time'     => $typingTime,
            'message_length'  => mb_strlen($message),
        ]);
        // #endregion

        $this->rememberOutbound($waInstance->id, $to, $message);

        $result = $this->chatery->sendMessage(
            $apiKey,
            $to,
            $message,
            $sessionId,
            $typingTime
        );

        // #region debug-point wa-send-stuck-sendtext-result
        Log::info('WA outbound sendText result', [
            'wa_instance_id' => $waInstance->id,
            'session_id'     => $sessionId,
            'to'             => $to,
            'success'        => $result['success'],
            'message_id'     => $result['message_id'],
        ]);
        // #endregion

        if ($result['success'] && $result['message_id']) {
            $this->rememberOutboundMessageId($waInstance->id, $result['message_id']);
        }

        if ($waInstance->typing_enabled) {
            $cleared = $this->chatery->clearTyping($apiKey, $to, $sessionId);
            // #region debug-point wa-send-stuck-clear-typing
            Log::info('WA outbound clearTyping result', [
                'wa_instance_id' => $waInstance->id,
                'session_id'     => $sessionId,
                'to'             => $to,
                'cleared'        => $cleared,
            ]);
            // #endregion
        }

        return $result['success'];
    }

    public function sendImage(
        WaInstance $waInstance,
        string $to,
        string $imageUrl,
        ?string $caption = null
    ): bool {
        $apiKey = $this->chatery->resolveApiKey($waInstance);

        if (! filled($apiKey)) {
            return false;
        }

        $sessionId = $waInstance->instance_id ?: 'default';

        $result = $this->chatery->sendImage(
            $apiKey,
            $to,
            $imageUrl,
            $caption,
            $sessionId
        );

        if ($result['success'] && $result['message_id']) {
            $this->rememberOutboundMessageId($waInstance->id, $result['message_id']);
        }

        return $result['success'];
    }

    /**
     * @param  string[]  $chunks
     */
    public function sendChunks(WaInstance $waInstance, string $to, array $chunks, int $pacingMs = 1200): bool
    {
        $apiKey = $this->chatery->resolveApiKey($waInstance);

        if (! filled($apiKey)) {
            return false;
        }

        $chunks = array_values(array_filter(
            array_map('trim', $chunks),
            fn ($c) => $c !== ''
        ));

        if (empty($chunks)) {
            return false;
        }

        if (count($chunks) === 1) {
            return $this->sendText($waInstance, $to, $chunks[0]);
        }

        $sessionId = $waInstance->instance_id ?: 'default';
        $allSent = true;

        foreach ($chunks as $index => $chunk) {
            if ($waInstance->typing_enabled) {
                $this->chatery->sendTyping($apiKey, $to, $sessionId);
            }

            $typingTime = null;
            if ($waInstance->typing_enabled) {
                $typingTime = max(500, min(4000, mb_strlen($chunk) * 40));
            }

            $this->rememberOutbound($waInstance->id, $to, $chunk);

            $result = $this->chatery->sendMessage(
                $apiKey,
                $to,
                $chunk,
                $sessionId,
                $typingTime
            );

            if ($result['success']) {
                if ($result['message_id']) {
                    $this->rememberOutboundMessageId($waInstance->id, $result['message_id']);
                }
            } else {
                $allSent = false;
            }

            if ($index < count($chunks) - 1 && $pacingMs > 0) {
                usleep($pacingMs * 1000);
            }
        }

        if ($waInstance->typing_enabled) {
            $this->chatery->clearTyping($apiKey, $to, $sessionId);
        }

        return $allSent;
    }

    public static function isOutboundMessageIdEcho(int $waInstanceId, ?string $messageId): bool
    {
        return $messageId !== null
            && $messageId !== ''
            && Cache::has(self::outboundIdKey($waInstanceId, $messageId));
    }

    public static function isOutboundEcho(
        int $waInstanceId,
        ?string $messageId,
        string $chatId,
        string $content
    ): bool {
        if (self::isOutboundMessageIdEcho($waInstanceId, $messageId)) {
            return true;
        }

        $normalizedChat = WaChateryService::normalizePhone($chatId);

        return Cache::has(self::outboundHashKey($waInstanceId, $normalizedChat, $content));
    }

    private function rememberOutbound(int $waInstanceId, string $to, string $content): void
    {
        $ttl = now()->addMinutes(self::OUTBOUND_CACHE_MINUTES);
        $normalizedChat = WaChateryService::normalizePhone($to);

        Cache::put(
            self::outboundHashKey($waInstanceId, $normalizedChat, $content),
            true,
            $ttl
        );
    }

    private function rememberOutboundMessageId(int $waInstanceId, string $messageId): void
    {
        Cache::put(
            self::outboundIdKey($waInstanceId, $messageId),
            true,
            now()->addMinutes(self::OUTBOUND_CACHE_MINUTES)
        );
    }

    private static function outboundIdKey(int $waInstanceId, string $messageId): string
    {
        return "wa_outbound:{$waInstanceId}:id:{$messageId}";
    }

    private static function outboundHashKey(int $waInstanceId, string $chatId, string $content): string
    {
        return 'wa_outbound:' . $waInstanceId . ':hash:' . $chatId . ':' . md5($content);
    }
}
