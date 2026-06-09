<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ChatImageService
{
    private const MAX_WIDTH = 1280;

    private const WEBP_QUALITY = 80;

    /**
     * @return array{path: string, url: string, size: int, mime: string}
     */
    public function store(UploadedFile $file, int $tenantId, int $conversationId): array
    {
        if (! function_exists('imagewebp')) {
            throw new RuntimeException('Ekstensi GD WebP tidak tersedia di server.');
        }

        $contents = file_get_contents($file->getRealPath());

        return $this->storeFromContents($contents, $tenantId, $conversationId);
    }

    /**
     * Download gambar dari URL (mis. media WhatsApp) lalu simpan sebagai WebP.
     *
     * @return array{path: string, url: string, size: int, mime: string}
     */
    public function storeFromUrl(string $url, int $tenantId, int $conversationId, ?string $apiKey = null): array
    {
        $url = WaWebhookPayloadParser::normalizeMediaUrl($url);

        $request = Http::timeout(30);
        if ($apiKey) {
            $request = $request->withHeaders(['X-Api-Key' => $apiKey]);
        }

        $response = $request->get($url);
        if ($response->failed()) {
            throw new RuntimeException('Gagal mengunduh gambar dari URL.');
        }

        return $this->storeFromContents($response->body(), $tenantId, $conversationId);
    }

    /**
     * @return array{path: string, url: string, size: int, mime: string}
     */
    public function storeFromContents(string $contents, int $tenantId, int $conversationId): array
    {
        if (! function_exists('imagewebp')) {
            throw new RuntimeException('Ekstensi GD WebP tidak tersedia di server.');
        }

        $source = @imagecreatefromstring($contents);

        if ($source === false) {
            throw new RuntimeException('File gambar tidak valid.');
        }

        $width  = imagesx($source);
        $height = imagesy($source);

        if ($width > self::MAX_WIDTH) {
            $newHeight = (int) round($height * (self::MAX_WIDTH / $width));
            $resized   = imagescale($source, self::MAX_WIDTH, $newHeight);
            imagedestroy($source);
            $source = $resized ?: $source;
        }

        $filename = Str::uuid() . '.webp';
        $path     = "chat-images/{$tenantId}/{$conversationId}/{$filename}";
        $fullPath = Storage::disk('public')->path($path);

        Storage::disk('public')->makeDirectory(dirname($path));

        if (! imagewebp($source, $fullPath, self::WEBP_QUALITY)) {
            imagedestroy($source);
            throw new RuntimeException('Gagal mengonversi gambar ke WebP.');
        }

        imagedestroy($source);

        return [
            'path' => $path,
            'url'  => '/storage/' . $path,
            'size' => (int) filesize($fullPath),
            'mime' => 'image/webp',
        ];
    }
}
