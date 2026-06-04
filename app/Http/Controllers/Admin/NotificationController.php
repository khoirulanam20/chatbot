<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $notifications = $user->notifications()
            ->latest()
            ->paginate(20)
            ->through(fn ($n) => [
                'id'         => $n->id,
                'type'       => $n->data['type'] ?? 'unknown',
                'title'      => $n->data['title'] ?? '',
                'body'       => $n->data['body'] ?? '',
                'url'        => $n->data['url'] ?? '#',
                'read_at'    => $n->read_at?->toISOString(),
                'created_at' => $n->created_at->toISOString(),
            ]);

        return inertia('notifications/Index', [
            'notifications' => $notifications,
        ]);
    }

    public function markRead(string $id)
    {
        $notification = Auth::user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        return back();
    }

    public function markAllRead()
    {
        Auth::user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
