<?php

namespace App\Services;

use App\Models\Message;

class VisionMessageFormatter
{
    /**
     * Normalisasi nama model (termasuk typo umum gpt-40 → gpt-4o).
     */
    public static function normalizeModel(string $model): string
    {
        $model = strtolower(trim($model));

        $aliases = [
            'gpt-40-mini' => 'gpt-4o-mini',
            'gpt-40'      => 'gpt-4o',
        ];

        return $aliases[$model] ?? $model;
    }

    public static function supportsVision(string $model): bool
    {
        $model = self::normalizeModel($model);

        foreach ([
            'gpt-4o-mini',
            'gpt-4o',
            'gpt-4-turbo',
            'gpt-4-vision',
            'o1',
            'o3',
            'o4',
            'claude-3',
            'gemini',
        ] as $needle) {
            if (str_contains($model, $needle)) {
                return true;
            }
        }

        return false;
    }

    public static function toAbsoluteUrl(string $url): string
    {
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return rtrim(config('app.url'), '/') . '/' . ltrim($url, '/');
    }

    /**
     * @return array{role: string, content: array<int, array<string, mixed>>}
     */
    public static function formatImageUserMessage(string $imageUrl, string $caption): array
    {
        $absoluteUrl = self::toAbsoluteUrl($imageUrl);
        $text        = $caption !== '[Gambar]' ? $caption : 'Apa yang ada di gambar ini?';

        return [
            'role'    => 'user',
            'content' => [
                ['type' => 'text', 'text' => $text],
                ['type' => 'image_url', 'image_url' => ['url' => $absoluteUrl]],
            ],
        ];
    }

    /**
     * @return array{role: string, content: string|array<int, array<string, mixed>>}
     */
    public static function formatMessage(Message $message): array
    {
        $metadata = $message->metadata ?? [];

        if (($metadata['type'] ?? null) === 'image' && ! empty($metadata['url'])) {
            return self::formatImageUserMessage(
                $metadata['url'],
                $message->content ?: '[Gambar]'
            );
        }

        return [
            'role'    => $message->role,
            'content' => $message->content,
        ];
    }
}
