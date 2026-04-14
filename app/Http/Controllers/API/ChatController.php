<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Chatbot;
use App\Models\Contact;
use App\Models\Conversation;
use App\Services\RAGService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    public function __construct(
        private RAGService $ragService
    ) {}

    public function sendMessage(Request $request): JsonResponse
    {
        $request->validate([
            'bot_id'     => 'required|exists:chatbots,id',
            'session_id' => 'nullable|string|max:100',
            'message'    => 'required|string|max:2000',
        ]);

        $chatbot = Chatbot::withoutGlobalScopes()->findOrFail($request->bot_id);

        if (! $chatbot->is_active) {
            return response()->json(['error' => 'Chatbot tidak aktif'], 503);
        }

        $ip      = $request->ip();
        $limiter = "chat:{$chatbot->id}:{$ip}";

        if (RateLimiter::tooManyAttempts($limiter, 30)) {
            return response()->json(['error' => 'Terlalu banyak permintaan'], 429);
        }

        RateLimiter::hit($limiter, 60);

        $sessionId = $request->session_id ?: (string) Str::uuid();

        $contact = Contact::withoutGlobalScopes()->firstOrCreate(
            [
                'tenant_id'  => $chatbot->tenant_id,
                'identifier' => 'web_' . $sessionId,
                'channel'    => 'web',
            ]
        );

        $conversation = Conversation::firstOrCreate(
            ['session_id' => $sessionId],
            [
                'chatbot_id'      => $chatbot->id,
                'contact_id'      => $contact->id,
                'channel'         => 'web',
                'status'          => 'open',
                'is_ai_active'    => true,
                'last_message_at' => now(),
            ]
        );

        $result = $this->ragService->processMessage($conversation, $request->message);

        return response()->json([
            'session_id' => $sessionId,
            'message'    => $result['content'],
            'sources'    => $result['sources'] ?? [],
            'message_id' => $result['message_id'] ?? null,
            'handoff'    => $result['handoff'] ?? false,
        ]);
    }

    public function getHistory(Request $request, string $sessionId): JsonResponse
    {
        $conversation = Conversation::where('session_id', $sessionId)->first();

        if (! $conversation) {
            return response()->json(['messages' => []]);
        }

        $messages = $conversation->messages()
            ->whereIn('role', ['user', 'assistant', 'agent'])
            ->orderBy('created_at')
            ->get()
            ->map(fn ($msg) => [
                'id'         => $msg->id,
                'role'       => $msg->role,
                'content'    => $msg->content,
                'created_at' => $msg->created_at->toISOString(),
            ]);

        return response()->json(['messages' => $messages]);
    }

    public function rateMessage(Request $request): JsonResponse
    {
        $request->validate([
            'message_id' => 'required|exists:messages,id',
            'rating'     => 'required|integer|in:1,-1',
            'feedback'   => 'nullable|string|max:500',
        ]);

        \App\Models\MessageRating::updateOrCreate(
            ['message_id' => $request->message_id],
            ['rating' => $request->rating, 'feedback' => $request->feedback]
        );

        return response()->json(['success' => true]);
    }
}
