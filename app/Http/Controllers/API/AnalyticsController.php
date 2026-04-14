<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Chatbot;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $user    = Auth::user();
        $period  = $request->get('period', 'month');
        $startDate = match ($period) {
            'day'   => now()->startOfDay(),
            'week'  => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            default => now()->startOfMonth(),
        };

        $chatbotIds = Chatbot::withoutGlobalScopes()
            ->when(! $user->isSuperAdmin(), fn ($q) => $q->where('tenant_id', $user->tenant_id))
            ->pluck('id');

        $baseQuery = Conversation::whereIn('chatbot_id', $chatbotIds)
            ->where('created_at', '>=', $startDate);

        $total        = (clone $baseQuery)->count();
        $resolved     = (clone $baseQuery)->where('status', 'resolved')->count();
        $handoffs     = (clone $baseQuery)->where('status', 'handoff')->count();
        $webCount     = (clone $baseQuery)->where('channel', 'web')->count();
        $waCount      = (clone $baseQuery)->where('channel', 'whatsapp')->count();

        $avgRating = DB::table('message_ratings')
            ->join('messages', 'message_ratings.message_id', '=', 'messages.id')
            ->join('conversations', 'messages.conversation_id', '=', 'conversations.id')
            ->whereIn('conversations.chatbot_id', $chatbotIds)
            ->where('message_ratings.created_at', '>=', $startDate)
            ->avg('message_ratings.rating') ?? 0;

        $trend = Conversation::whereIn('chatbot_id', $chatbotIds)
            ->where('created_at', '>=', now()->subDays(7))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'period'          => $period,
            'total'           => $total,
            'resolved'        => $resolved,
            'handoffs'        => $handoffs,
            'web_count'       => $webCount,
            'wa_count'        => $waCount,
            'resolution_rate' => $total > 0 ? round(($resolved / $total) * 100, 1) : 0,
            'avg_rating'      => round($avgRating, 2),
            'trend'           => $trend,
        ]);
    }
}
