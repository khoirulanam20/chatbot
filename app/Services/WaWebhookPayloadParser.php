<?php

namespace App\Services;

class WaWebhookPayloadParser
{
    /**
     * @return array{type: string, message: string, media_url: ?string}|null
     */
    public static function parseInbound(array $data, array $payload = []): ?array
    {
        $messageType = $data['type'] ?? 'text';
        $isImage     = in_array($messageType, ['image', 'imageMessage', 'sticker'], true);
        $isText      = in_array($messageType, ['text', 'chat', 'conversation'], true);

        if (! $isImage && ! $isText) {
            return null;
        }

        if ($isImage) {
            return self::parseImage($data, $payload);
        }

        $text = self::firstNonEmptyString($data, ['content', 'text', 'body', 'message'])
            ?? trim((string) ($payload['message'] ?? ''));

        if ($text === '') {
            return null;
        }

        return [
            'type'      => 'text',
            'message'   => $text,
            'media_url' => null,
        ];
    }

    /**
     * @return array{type: string, message: string, media_url: ?string}|null
     */
    private static function parseImage(array $data, array $payload): ?array
    {
        $mediaUrl = self::resolveMediaUrl($data);

        $content = self::firstNonEmptyString($data, ['content', 'text', 'body']);

        if (! $mediaUrl && $content && self::looksLikeUrl($content)) {
            $mediaUrl = $content;
            $content  = null;
        }

        $caption = self::firstNonEmptyString($data, ['caption']);

        if (! $caption) {
            $caption = self::firstNonEmptyString($data, ['text', 'body']);
        }

        if (! $caption && $content && ! self::looksLikeUrl($content)) {
            $caption = $content;
        }

        if (! $mediaUrl) {
            return null;
        }

        return [
            'type'      => 'image',
            'message'   => ($caption !== null && $caption !== '') ? $caption : '[Gambar]',
            'media_url' => $mediaUrl,
        ];
    }

    public static function resolveMediaUrl(array $data): ?string
    {
        $url = self::firstNonEmptyString($data, [
            'mediaUrl',
            'mediaURL',
            'media_url',
            'imageUrl',
            'imageURL',
            'url',
            'media',
        ]);

        if (! $url) {
            return null;
        }

        return self::normalizeMediaUrl($url);
    }

    public static function normalizeMediaUrl(string $url): string
    {
        $url = trim($url);

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        if (str_starts_with($url, '/')) {
            $base = rtrim((string) config('services.chatery.base_url', ''), '/');
            $host = preg_replace('#/api/?$#', '', $base) ?: $base;

            return rtrim($host, '/') . $url;
        }

        return $url;
    }

    public static function looksLikeUrl(string $value): bool
    {
        $value = trim($value);

        if ($value === '') {
            return false;
        }

        return str_starts_with($value, 'http://')
            || str_starts_with($value, 'https://')
            || str_starts_with($value, '/');
    }

    /**
     * @param  list<string>  $keys
     */
    private static function firstNonEmptyString(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (! empty($data[$key]) && is_string($data[$key])) {
                $trimmed = trim($data[$key]);

                if ($trimmed !== '') {
                    return $trimmed;
                }
            }
        }

        return null;
    }
}
