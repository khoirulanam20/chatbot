<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Chatbot;
use Illuminate\Http\JsonResponse;

class BotConfigController extends Controller
{
    public function show(int $botId): JsonResponse
    {
        $chatbot = Chatbot::withoutGlobalScopes()
            ->with('embedConfig')
            ->findOrFail($botId);

        if (! $chatbot->is_active) {
            return response()->json(['error' => 'Chatbot tidak aktif'], 404);
        }

        $config = $chatbot->embedConfig;

        return response()->json([
            'bot_id'        => $chatbot->id,
            'name'          => $chatbot->name,
            'avatar'        => $chatbot->avatar ? asset('storage/' . $chatbot->avatar) : null,
            'primary_color' => $config?->primary_color ?? '#4F46E5',
            'position'      => $config?->position ?? 'bottom-right',
            'size'          => $config?->size ?? 'normal',
            'greeting'      => $config?->greeting ?? 'Halo! Ada yang bisa saya bantu?',
            'quick_replies' => $config?->quick_replies ?? [],
            'branding'      => $config?->branding ?? [],
            'sound_enabled' => $config?->sound_enabled ?? false,
            'auto_open_delay' => $config?->auto_open_delay,
        ]);
    }
}
