<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessWhatsAppMessageJob;
use App\Models\WaInstance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class WhatsAppController extends Controller
{
    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::debug('WA Webhook received', $payload);

        $event = $payload['event'] ?? '';

        if ($event !== 'message.received') {
            return response()->json(['status' => 'ignored']);
        }

        $from    = $payload['from'] ?? '';
        $message = $payload['message'] ?? '';

        if (empty($from) || empty($message)) {
            return response()->json(['status' => 'skipped']);
        }

        $limiter = "wa_msg:{$from}";
        if (RateLimiter::tooManyAttempts($limiter, 10)) {
            return response()->json(['status' => 'rate_limited']);
        }
        RateLimiter::hit($limiter, 60);

        $instanceId  = $payload['instance_id'] ?? null;
        $phoneNumber = $payload['phone'] ?? null;

        $waInstance = WaInstance::withoutGlobalScopes()
            ->when($instanceId, fn ($q) => $q->where('instance_id', $instanceId))
            ->when(! $instanceId && $phoneNumber, fn ($q) => $q->where('phone_number', $phoneNumber))
            ->where('status', 'active')
            ->first();

        if (! $waInstance) {
            Log::warning('No WA instance found for webhook', ['instance_id' => $instanceId, 'phone' => $phoneNumber]);
            return response()->json(['status' => 'no_instance']);
        }

        ProcessWhatsAppMessageJob::dispatch($payload, $waInstance->id);

        return response()->json(['status' => 'queued']);
    }
}
