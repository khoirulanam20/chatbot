<?php

namespace App\Http\Middleware;

use App\Support\DebugWaTrace;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyWaChaterySignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.chatery.webhook_secret');

        // #region agent log
        DebugWaTrace::log('H1', 'VerifyWaChaterySignature.php:handle', 'middleware_entry', [
            'env'           => app()->environment(),
            'secret_set'    => ! empty($secret),
            'has_signature' => $request->hasHeader('X-Chatery-Signature'),
        ]);
        // #endregion

        // Secret kosong = skip verifikasi (Chatery belum kirim X-Chatery-Signature).
        // Set CHATERY_WEBHOOK_SECRET + header signature di Chatery untuk mengaktifkan HMAC.
        if (empty($secret)) {
            Log::warning('WA webhook: signature verification disabled (CHATERY_WEBHOOK_SECRET empty)');

            // #region agent log
            DebugWaTrace::log('H1', 'VerifyWaChaterySignature.php:handle', 'allowed_no_secret', [], 'post-fix');
            // #endregion

            return $next($request);
        }

        $signature = $request->header('X-Chatery-Signature');

        if (! $signature) {
            Log::warning('WA webhook rejected: missing X-Chatery-Signature header');

            // #region agent log
            DebugWaTrace::log('H1', 'VerifyWaChaterySignature.php:handle', 'rejected_missing_signature');
            // #endregion

            return response()->json(['error' => 'Missing signature'], 401);
        }

        $payload = $request->getContent();
        $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        if (! hash_equals($expected, $signature)) {
            Log::warning('WA webhook rejected: invalid signature');

            // #region agent log
            DebugWaTrace::log('H1', 'VerifyWaChaterySignature.php:handle', 'rejected_invalid_signature');
            // #endregion

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // #region agent log
        DebugWaTrace::log('H1', 'VerifyWaChaterySignature.php:handle', 'signature_ok');
        // #endregion

        return $next($request);
    }
}
