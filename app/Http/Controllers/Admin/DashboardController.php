<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Chatbot;
use App\Models\Conversation;
use App\Models\KnowledgeDocument;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user       = Auth::user();
        $chatbotIds = Chatbot::pluck('id');

        $stats = [
            'total_today'       => Conversation::whereIn('chatbot_id', $chatbotIds)->whereDate('created_at', today())->count(),
            'total_week'        => Conversation::whereIn('chatbot_id', $chatbotIds)->whereBetween('created_at', [now()->startOfWeek(), now()])->count(),
            'total_month'       => Conversation::whereIn('chatbot_id', $chatbotIds)->whereMonth('created_at', now()->month)->count(),
            'open'              => Conversation::whereIn('chatbot_id', $chatbotIds)->where('status', 'open')->count(),
            'handoff'           => Conversation::whereIn('chatbot_id', $chatbotIds)->where('status', 'handoff')->count(),
            'resolved'          => Conversation::whereIn('chatbot_id', $chatbotIds)->where('status', 'resolved')->count(),
            'web_count'         => Conversation::whereIn('chatbot_id', $chatbotIds)->where('channel', 'web')->whereDate('created_at', today())->count(),
            'wa_count'          => Conversation::whereIn('chatbot_id', $chatbotIds)->where('channel', 'whatsapp')->whereDate('created_at', today())->count(),
            'total_documents'   => KnowledgeDocument::whereIn('chatbot_id', $chatbotIds)->where('status', 'indexed')->count(),
            'total_chatbots'    => Chatbot::count(),
        ];

        $avgRating = DB::table('message_ratings')
            ->join('messages', 'message_ratings.message_id', '=', 'messages.id')
            ->join('conversations', 'messages.conversation_id', '=', 'conversations.id')
            ->whereIn('conversations.chatbot_id', $chatbotIds)
            ->avg('message_ratings.rating') ?? 0;

        $stats['avg_rating'] = round($avgRating, 2);

        $trend = Conversation::whereIn('chatbot_id', $chatbotIds)
            ->where('created_at', '>=', now()->subDays(7))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $recentConversations = Conversation::with(['contact', 'chatbot'])
            ->whereIn('chatbot_id', $chatbotIds)
            ->latest('last_message_at')
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact('stats', 'trend', 'recentConversations'));
    }
}
