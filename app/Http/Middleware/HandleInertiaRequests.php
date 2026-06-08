<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'app' => [
                'version' => config('app.version'),
            ],
            'csrf_token' => fn () => csrf_token(),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'role' => $request->user()->role,
                    'tenant_id' => $request->user()->tenant_id,
                    'tenant' => $request->user()->tenant ? [
                        'id' => $request->user()->tenant->id,
                        'name' => $request->user()->tenant->name,
                        'slug' => $request->user()->tenant->slug,
                    ] : null,
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'notifications' => fn () => $request->user() ? [
                'unread_count' => $request->user()->unreadNotifications()->count(),
                'recent' => $request->user()->notifications()->latest()->limit(5)->get()->map(fn ($n) => [
                    'id'         => $n->id,
                    'title'      => $n->data['title'] ?? '',
                    'body'       => $n->data['body'] ?? '',
                    'url'        => $n->data['url'] ?? '#',
                    'read_at'    => $n->read_at?->toISOString(),
                    'created_at' => $n->created_at->diffForHumans(),
                ]),
            ] : ['unread_count' => 0, 'recent' => []],
        ]);
    }
}
