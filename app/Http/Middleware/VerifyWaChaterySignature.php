<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWaChaterySignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.chatery.webhook_secret');

        if (empty($secret)) {
            return $next($request);
        }

        $signature = $request->header('X-Chatery-Signature');

        if (! $signature) {
            return response()->json(['error' => 'Missing signature'], 401);
        }

        $payload = $request->getContent();
        $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        if (! hash_equals($expected, $signature)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        return $next($request);
    }
}
