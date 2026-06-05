<?php

namespace App\Services;

use App\Models\WaInstance;

class WaOutboundService
{
    public function __construct(
        private WaChateryService $chatery
    ) {}

    public function sendText(WaInstance $waInstance, string $to, string $message): bool
    {
        $sessionId = $waInstance->instance_id ?: 'default';

        $typingTime = $waInstance->typing_enabled
            ? max(500, min(10000, (int) ($waInstance->typing_duration_ms ?? 2000)))
            : null;

        $sent = $this->chatery->sendMessage(
            $waInstance->api_key,
            $to,
            $message,
            $sessionId,
            $typingTime
        );

        if ($waInstance->typing_enabled) {
            $this->chatery->clearTyping($waInstance->api_key, $to, $sessionId);
        }

        return $sent;
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

            $sent = $this->chatery->sendMessage(
                $waInstance->api_key,
                $to,
                $chunk,
                $sessionId,
                $typingTime
            );

            if (! $sent) {
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
}
