<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgentHandoff;
use App\Models\Chatbot;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Notifications\ConversationAssignedNotification;
use App\Services\AgentSessionService;
use App\Services\ChatImageService;
use App\Services\WaConversationResolver;
use App\Services\WaOutboundService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConversationController extends Controller
{
    public function __construct(
        private AgentSessionService $agentSession,
        private ChatImageService $chatImageService
    ) {}

    public function index(Request $request)
    {
        $chatbotIds = Chatbot::pluck('id');

        $query = Conversation::with(['contact', 'chatbot', 'assignedAgent'])
            ->whereIn('chatbot_id', $chatbotIds)
            ->latest('last_message_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }

        if ($request->filled('chatbot_id')) {
            $query->where('chatbot_id', $request->chatbot_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('contact', fn ($q) => $q->where('identifier', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%"));
        }

        $conversations = $query->paginate(20)->withQueryString();
        $chatbots      = Chatbot::all();

        return inertia('conversations/Index', [
            'conversations' => $conversations,
            'chatbots' => $chatbots,
            'filters' => $request->only(['status', 'channel', 'search', 'chatbot_id']),
        ]);
    }

    public function show(Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        $conversation->load(['contact', 'chatbot', 'assignedAgent', 'handoff.agent']);
        $messages = $conversation->messages()->orderBy('created_at')->get();
        $agents   = User::where('tenant_id', $conversation->chatbot->tenant_id)
            ->whereIn('role', ['operator', 'admin'])
            ->get();

        $idleExpiresAt = $this->agentSession->getIdleExpiresAt($conversation);

        return inertia('conversations/Show', [
            'conversation' => array_merge($conversation->toArray(), [
                'idle_expires_at' => $idleExpiresAt?->toISOString(),
            ]),
            'messages' => $messages,
            'agents' => $agents,
        ]);
    }

    public function sendMessage(Request $request, Conversation $conversation)
    {
        $this->authorize('respond', $conversation);

        $request->validate(['message' => 'required|string|max:2000']);

        $agent = Auth::user();
        if (! $agent instanceof User || ! $this->agentSession->canAgentReply($conversation, $agent)) {
            return back()->withErrors(['message' => 'Anda tidak dapat membalas percakapan ini.']);
        }

        $this->agentSession->pauseForHumanReply($conversation, $agent);

        Message::create([
            'conversation_id' => $conversation->id,
            'role'            => 'agent',
            'content'         => $request->message,
        ]);

        $this->agentSession->touchActivity($conversation);

        if ($conversation->channel === 'whatsapp') {
            $conversation->loadMissing(['chatbot.waInstance', 'contact']);
            $waInstance = $conversation->chatbot->waInstance;
            if ($waInstance && $conversation->contact) {
                $outboundChatId = app(WaConversationResolver::class)
                    ->resolveOutboundChatId($conversation->contact);

                $sent = app(WaOutboundService::class)->sendText(
                    $waInstance,
                    $outboundChatId,
                    $request->message
                );

                if (! $sent) {
                    return back()->withErrors(['message' => 'Gagal mengirim pesan ke WhatsApp. Coba lagi.']);
                }
            }
        }

        return back()->with('success', 'Pesan terkirim.');
    }

    public function sendImage(Request $request, Conversation $conversation)
    {
        $this->authorize('respond', $conversation);

        $request->validate([
            'image'   => 'required|image|mimes:jpeg,png,gif,webp|max:10240',
            'caption' => 'nullable|string|max:500',
        ]);

        $agent = Auth::user();
        if (! $agent instanceof User || ! $this->agentSession->canAgentReply($conversation, $agent)) {
            return back()->withErrors(['image' => 'Anda tidak dapat membalas percakapan ini.']);
        }

        $conversation->load('chatbot');

        try {
            $stored = $this->chatImageService->store(
                $request->file('image'),
                $conversation->chatbot->tenant_id,
                $conversation->id
            );
        } catch (\Throwable $e) {
            return back()->withErrors(['image' => $e->getMessage()]);
        }

        $caption = $request->caption ?: '[Gambar]';

        $this->agentSession->pauseForHumanReply($conversation, $agent);

        Message::create([
            'conversation_id' => $conversation->id,
            'role'            => 'agent',
            'content'         => $caption,
            'metadata'        => [
                'type' => 'image',
                'url'  => $stored['url'],
                'mime' => $stored['mime'],
                'size' => $stored['size'],
            ],
        ]);

        $this->agentSession->touchActivity($conversation);

        return back()->with('success', 'Gambar terkirim.');
    }

    public function updateStatus(Request $request, Conversation $conversation)
    {
        $this->authorize('update', $conversation);

        $request->validate(['status' => 'required|in:open,resolved,spam,handoff']);
        $newStatus = $request->status;

        if ($newStatus === 'handoff') {
            $this->agentSession->startSession($conversation);
        } elseif ($newStatus === 'open' && $this->agentSession->isInHandoff($conversation)) {
            $this->agentSession->endSession($conversation, resumeAi: true);
        } else {
            $conversation->update(['status' => $newStatus]);
        }

        return back()->with('success', 'Status percakapan diperbarui.');
    }

    public function takeOver(Conversation $conversation)
    {
        $this->authorize('update', $conversation);

        $agent = Auth::user();
        if (! $agent instanceof User || ! $agent->isOperator()) {
            return back()->withErrors(['message' => 'Anda tidak memiliki akses untuk mengambil alih percakapan.']);
        }

        if (
            $conversation->assigned_agent_id
            && $conversation->assigned_agent_id !== $agent->id
            && ! $agent->isAdmin()
        ) {
            return back()->withErrors(['message' => 'Percakapan sedang ditangani agen lain.']);
        }

        $this->agentSession->takeOver($conversation, $agent);

        AgentHandoff::updateOrCreate(
            ['conversation_id' => $conversation->id],
            [
                'agent_id' => $agent->id,
                'reason'   => 'Manual takeover by admin',
            ]
        );

        return back()->with('success', 'Percakapan berhasil diambil alih.');
    }

    public function assign(Request $request, Conversation $conversation)
    {
        $this->authorize('update', $conversation);

        $request->validate(['agent_id' => 'nullable|exists:users,id']);

        $agent = $request->agent_id ? User::find($request->agent_id) : null;

        if ($agent) {
            $this->agentSession->takeOver($conversation, $agent);
        } else {
            $conversation->update(['assigned_agent_id' => null]);
        }

        AgentHandoff::updateOrCreate(
            ['conversation_id' => $conversation->id],
            ['agent_id' => $request->agent_id, 'reason' => 'Manual assignment by admin']
        );

        if ($agent) {
            $conversation->load(['contact', 'chatbot']);
            $agent->notify(new ConversationAssignedNotification($conversation));
        }

        return back()->with('success', 'Percakapan berhasil di-assign ke agen.');
    }

    public function resumeAI(Conversation $conversation)
    {
        $this->authorize('update', $conversation);

        $this->agentSession->endSession($conversation, resumeAi: true);

        return back()->with('success', 'AI kembali aktif untuk percakapan ini.');
    }

    public function export(Request $request)
    {
        $chatbotIds = Chatbot::pluck('id');

        $conversations = Conversation::with(['contact', 'chatbot', 'messages'])
            ->whereIn('chatbot_id', $chatbotIds)
            ->when($request->filled('from'), fn ($q) => $q->where('created_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->where('created_at', '<=', $request->to . ' 23:59:59'))
            ->get();

        $filename = 'conversations_' . now()->format('Ymd_His') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($conversations) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Chatbot', 'Channel', 'Contact', 'Status', 'Total Pesan', 'Dibuat', 'Terakhir Pesan']);

            foreach ($conversations as $conv) {
                fputcsv($handle, [
                    $conv->id,
                    $conv->chatbot->name ?? '-',
                    $conv->channel,
                    $conv->contact->identifier ?? '-',
                    $conv->status,
                    $conv->messages->count(),
                    $conv->created_at->format('Y-m-d H:i:s'),
                    $conv->last_message_at?->format('Y-m-d H:i:s') ?? '-',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
