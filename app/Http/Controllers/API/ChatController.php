<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Chatbot;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\ChatImageService;
use App\Services\RAGService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    public function __construct(
        private RAGService $ragService,
        private ChatImageService $chatImageService
    ) {}

    public function sendMessage(Request $request): JsonResponse
    {
        $request->validate([
            'bot_id'     => 'required|exists:chatbots,id',
            'session_id' => 'nullable|string|max:100',
            'message'    => 'required|string|max:2000',
        ]);

        $chatbot = Chatbot::withoutGlobalScopes()->with('embedConfig')->findOrFail($request->bot_id);

        if (! $chatbot->is_active) {
            return response()->json(['error' => 'Chatbot tidak aktif'], 503);
        }

        $this->throttleChat($request, $chatbot);

        [$sessionId, $conversation] = $this->resolveWebConversation(
            $chatbot,
            $request->session_id
        );

        $conversation->refresh();
        $conversation->load('chatbot');

        $result = $this->ragService->processMessage($conversation, $request->message);
        $conversation->refresh();

        return response()->json([
            'session_id'      => $sessionId,
            'message'         => $result['content'] ?? '',
            'message_chunks'  => ! empty($result['silent']) ? [] : ($result['chunks'] ?? [$result['content']]),
            'pacing_ms'       => $result['pacing_ms'] ?? 0,
            'sources'         => $result['sources'] ?? [],
            'message_id'      => $result['message_id'] ?? null,
            'user_message_id' => $result['user_message_id'] ?? null,
            'handoff'         => $result['handoff'] ?? false,
            'agent_session'   => $result['agent_session'] ?? false,
            'silent'          => $result['silent'] ?? false,
            'idle_expires_at' => app(\App\Services\AgentSessionService::class)
                ->getIdleExpiresAt($conversation)?->toISOString(),
        ]);
    }

    public function getHistory(Request $request, string $sessionId): JsonResponse
    {
        $conversation = Conversation::where('session_id', $sessionId)->first();

        if (! $conversation) {
            return response()->json(['messages' => []]);
        }

        $query = $conversation->messages()
            ->whereIn('role', ['user', 'assistant', 'agent'])
            ->orderBy('created_at');

        if ($request->has('after')) {
            $query->where('id', '>', (int) $request->after);
        }

        $messages = $query->get()->map(fn ($msg) => [
            'id'         => $msg->id,
            'role'       => $msg->role,
            'content'    => $msg->content,
            'metadata'   => $msg->metadata,
            'created_at' => $msg->created_at->toISOString(),
        ]);

        return response()->json(['messages' => $messages]);
    }

    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'bot_id'     => 'required|exists:chatbots,id',
            'session_id' => 'nullable|string|max:100',
            'image'      => 'required|image|mimes:jpeg,png,gif,webp|max:10240',
            'caption'    => 'nullable|string|max:500',
        ]);

        $chatbot = Chatbot::withoutGlobalScopes()->with('embedConfig')->findOrFail($request->bot_id);

        if (! $chatbot->is_active) {
            return response()->json(['error' => 'Chatbot tidak aktif'], 503);
        }

        if (! $chatbot->embedConfig?->allow_file_upload) {
            return response()->json(['error' => 'Upload gambar tidak diizinkan untuk chatbot ini'], 403);
        }

        $this->throttleChat($request, $chatbot);

        [$sessionId, $conversation] = $this->resolveWebConversation(
            $chatbot,
            $request->session_id
        );

        try {
            $stored = $this->chatImageService->store(
                $request->file('image'),
                $chatbot->tenant_id,
                $conversation->id
            );
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $caption = $request->caption ?: '[Gambar]';

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'role'            => 'user',
            'content'         => $caption,
            'metadata'        => [
                'type' => 'image',
                'url'  => $stored['url'],
                'mime' => $stored['mime'],
                'size' => $stored['size'],
            ],
        ]);

        $conversation->update(['last_message_at' => now()]);

        return response()->json([
            'session_id'  => $sessionId,
            'message_id'  => $message->id,
            'metadata'    => $message->metadata,
            'content'     => $caption,
        ]);
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

    private function throttleChat(Request $request, Chatbot $chatbot): void
    {
        $ip      = $request->ip();
        $limiter = "chat:{$chatbot->id}:{$ip}";

        if (RateLimiter::tooManyAttempts($limiter, 30)) {
            abort(response()->json(['error' => 'Terlalu banyak permintaan'], 429));
        }

        RateLimiter::hit($limiter, 60);
    }

    /**
     * @return array{0: string, 1: Conversation}
     */
    private function resolveWebConversation(Chatbot $chatbot, ?string $sessionId): array
    {
        $sessionId = $sessionId ?: (string) Str::uuid();

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

        return [$sessionId, $conversation];
    }
}
