<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgentHandoff;
use App\Models\Chatbot;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\WaChateryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConversationController extends Controller
{
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

        return view('admin.conversations.index', compact('conversations', 'chatbots'));
    }

    public function show(Conversation $conversation)
    {
        $conversation->load(['contact', 'chatbot', 'assignedAgent', 'handoff.agent']);
        $messages = $conversation->messages()->orderBy('created_at')->get();
        $agents   = \App\Models\User::where('tenant_id', $conversation->chatbot->tenant_id)
            ->whereIn('role', ['operator', 'admin'])
            ->get();

        return view('admin.conversations.show', compact('conversation', 'messages', 'agents'));
    }

    public function sendMessage(Request $request, Conversation $conversation)
    {
        $request->validate(['message' => 'required|string|max:2000']);

        Message::create([
            'conversation_id' => $conversation->id,
            'role'            => 'agent',
            'content'         => $request->message,
        ]);

        $conversation->update(['last_message_at' => now()]);

        if ($conversation->channel === 'whatsapp') {
            $waInstance = $conversation->chatbot->waInstance;
            if ($waInstance) {
                app(WaChateryService::class)->sendMessage(
                    $waInstance->api_key,
                    $conversation->contact->identifier,
                    $request->message
                );
            }
        }

        return back()->with('success', 'Pesan terkirim.');
    }

    public function updateStatus(Request $request, Conversation $conversation)
    {
        $request->validate(['status' => 'required|in:open,resolved,spam,handoff']);
        $conversation->update(['status' => $request->status]);

        return back()->with('success', 'Status percakapan diperbarui.');
    }

    public function assign(Request $request, Conversation $conversation)
    {
        $request->validate(['agent_id' => 'nullable|exists:users,id']);

        $conversation->update([
            'assigned_agent_id' => $request->agent_id,
            'status'            => 'handoff',
            'is_ai_active'      => false,
        ]);

        AgentHandoff::updateOrCreate(
            ['conversation_id' => $conversation->id],
            ['agent_id' => $request->agent_id, 'reason' => 'Manual assignment by admin']
        );

        return back()->with('success', 'Percakapan berhasil di-assign ke agen.');
    }

    public function resumeAI(Conversation $conversation)
    {
        $conversation->update([
            'status'       => 'open',
            'is_ai_active' => true,
        ]);

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
