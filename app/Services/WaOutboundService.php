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
}
