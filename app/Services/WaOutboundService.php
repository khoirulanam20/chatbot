<?php

namespace App\Services;

use App\Models\WaInstance;
use Illuminate\Support\Facades\Cache;

class WaOutboundService
{
    private const OUTBOUND_CACHE_MINUTES = 10;

    public function __construct(
        private WaChateryService $chatery
    ) {}

    public function sendText(WaInstance $waInstance, string $to, string $message): bool
    {
        $sessionId = $waInstance->instance_id ?: 'default';

        $typingTime = $waInstance->typing_enabled
            ? max(500, min(10000, (int) ($waInstance->typing_duration_ms ?? 2000)))
            : null;

        $this->rememberOutbound($waInstance->id, $to, $message);

        $result = $this->chatery->sendMessage(
            $waInstance->api_key,
            $to,
            $message,
            $sessionId,
            $typingTime
        );

        if ($result['success'] && $result['message_id']) {
            $this->rememberOutboundMessageId($waInstance->id, $result['message_id']);
        }

        if ($waInstance->typing_enabled) {
            $this->chatery->clearTyping($waInstance->api_key, $to, $sessionId);
        }

        return $result['success'];
    }

    /**
     * @param  string[]  $chunks
     */
    public function sendChunks(WaInstance $waInstance, string $to, array $chunks, int $pacingMs = 1200): bool
    {
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
                $this->chatery->sendTyping($waInstance->api_key, $to, $sessionId);
            }

            $typingTime = null;
            if ($waInstance->typing_enabled) {
                $typingTime = max(500, min(4000, mb_strlen($chunk) * 40));
            }

            $this->rememberOutbound($waInstance->id, $to, $chunk);

            $result = $this->chatery->sendMessage(
                $waInstance->api_key,
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
            $this->chatery->clearTyping($waInstance->api_key, $to, $sessionId);
        }

        return $allSent;
    }

    public static function isOutboundEcho(
        int $waInstanceId,
        ?string $messageId,
        string $chatId,
        string $content
    ): bool {
        if ($messageId && Cache::has(self::outboundIdKey($waInstanceId, $messageId))) {
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
