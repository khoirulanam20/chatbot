<!DOCTYPE html>
<html lang="id" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — AI CS Chatbot</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @livewireStyles
</head>
<body class="h-full">
<div class="flex h-full min-h-screen" x-data="{ sidebarOpen: false }">

    {{-- Sidebar --}}
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
           class="fixed lg:static z-40 inset-y-0 left-0 w-64 bg-gray-900 text-white flex flex-col transition-transform duration-300">

        <div class="px-6 py-5 border-b border-gray-700">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-indigo-500 flex items-center justify-center text-white font-bold text-lg">AI</div>
                <div>
                    <div class="font-semibold text-sm leading-none">AI CS Chatbot</div>
                    <div class="text-xs text-gray-400 mt-0.5">{{ auth()->user()->tenant?->name ?? 'Super Admin' }}</div>
                </div>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto py-4 space-y-1 px-3">
            @php $route = request()->route()?->getName() ?? ''; @endphp

            <a href="{{ route('admin.dashboard') }}" @class(['flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors', 'bg-indigo-600 text-white' => str_starts_with($route, 'admin.dashboard'), 'text-gray-300 hover:bg-gray-800' => !str_starts_with($route, 'admin.dashboard')])>
                <span class="text-lg">📊</span> Dashboard
            </a>

            <a href="{{ route('admin.conversations.index') }}" @class(['flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors', 'bg-indigo-600 text-white' => str_starts_with($route, 'admin.conversations'), 'text-gray-300 hover:bg-gray-800' => !str_starts_with($route, 'admin.conversations')])>
                <span class="text-lg">💬</span> Percakapan
            </a>

            <a href="{{ route('admin.chatbot.index') }}" @class(['flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors', 'bg-indigo-600 text-white' => str_starts_with($route, 'admin.chatbot'), 'text-gray-300 hover:bg-gray-800' => !str_starts_with($route, 'admin.chatbot')])>
                <span class="text-lg">🤖</span> Chatbot
            </a>

            <a href="{{ route('admin.knowledge.index') }}" @class(['flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors', 'bg-indigo-600 text-white' => str_starts_with($route, 'admin.knowledge'), 'text-gray-300 hover:bg-gray-800' => !str_starts_with($route, 'admin.knowledge')])>
                <span class="text-lg">📚</span> Knowledge Base
            </a>

            <a href="{{ route('admin.wa.index') }}" @class(['flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors', 'bg-indigo-600 text-white' => str_starts_with($route, 'admin.wa'), 'text-gray-300 hover:bg-gray-800' => !str_starts_with($route, 'admin.wa')])>
                <span class="text-lg">📱</span> WhatsApp
            </a>

            @if(auth()->user()->isAdmin())
            <a href="{{ route('admin.users.index') }}" @class(['flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors', 'bg-indigo-600 text-white' => str_starts_with($route, 'admin.users'), 'text-gray-300 hover:bg-gray-800' => !str_starts_with($route, 'admin.users')])>
                <span class="text-lg">👥</span> Pengguna
            </a>
            @endif

            @if(auth()->user()->isSuperAdmin())
            <a href="{{ route('admin.tenants.index') }}" @class(['flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors', 'bg-indigo-600 text-white' => str_starts_with($route, 'admin.tenants'), 'text-gray-300 hover:bg-gray-800' => !str_starts_with($route, 'admin.tenants')])>
                <span class="text-lg">🏢</span> Tenants
            </a>
            @endif

            <a href="{{ route('admin.settings.index') }}" @class(['flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors', 'bg-indigo-600 text-white' => str_starts_with($route, 'admin.settings'), 'text-gray-300 hover:bg-gray-800' => !str_starts_with($route, 'admin.settings')])>
                <span class="text-lg">⚙️</span> Pengaturan AI
            </a>
        </nav>

        <div class="p-4 border-t border-gray-700">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-8 h-8 rounded-full bg-indigo-500 flex items-center justify-center text-white text-xs font-bold">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</div>
                    <div class="text-xs text-gray-400 capitalize">{{ str_replace('_', ' ', auth()->user()->role) }}</div>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full text-left px-3 py-2 text-sm text-gray-400 hover:text-white hover:bg-gray-800 rounded-lg transition-colors">
                    🚪 Logout
                </button>
            </form>
        </div>
    </aside>

    {{-- Overlay mobile --}}
    <div @click="sidebarOpen = false" x-show="sidebarOpen" class="lg:hidden fixed inset-0 z-30 bg-black/50"></div>

    {{-- Main Content --}}
    <div class="flex-1 flex flex-col overflow-hidden min-w-0">
        <header class="bg-white border-b border-gray-200 px-4 py-3 flex items-center gap-3">
            <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-lg hover:bg-gray-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <div class="flex-1">
                <h1 class="text-lg font-semibold text-gray-800">@yield('title', 'Dashboard')</h1>
            </div>
            <div class="text-sm text-gray-500">{{ now()->format('d M Y') }}</div>
        </header>

        <main class="flex-1 overflow-y-auto p-6">
            @if(session('success'))
                <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-800 rounded-lg text-sm flex items-center gap-2">
                    <span>✅</span> {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">
                    <div class="font-medium mb-1">⚠️ Terjadi kesalahan:</div>
                    <ul class="list-disc pl-5 space-y-0.5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
@livewireScripts
</body>
</html>
