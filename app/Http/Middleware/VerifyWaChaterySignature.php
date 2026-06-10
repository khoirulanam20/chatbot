<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyWaChaterySignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.chatery.webhook_secret');

        // Secret kosong = skip verifikasi (Chatery belum kirim X-Chatery-Signature).
        // Set CHATERY_WEBHOOK_SECRET + header signature di Chatery untuk mengaktifkan HMAC.
        if (empty($secret)) {
            Log::warning('WA webhook: signature verification disabled (CHATERY_WEBHOOK_SECRET empty)');

            return $next($request);
        }

        $signature = $request->header('X-Chatery-Signature');

        if (! $signature) {
            Log::warning('WA webhook rejected: missing X-Chatery-Signature header');

            return response()->json(['error' => 'Missing signature'], 401);
        }

        $payload = $request->getContent();
        $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        if (! hash_equals($expected, $signature)) {
            Log::warning('WA webhook rejected: invalid signature');

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        return $next($request);
    }
}
