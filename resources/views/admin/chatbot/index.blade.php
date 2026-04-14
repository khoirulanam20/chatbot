@extends('layouts.admin')
@section('title', 'Manajemen Chatbot')

@section('content')
<div class="space-y-5">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Manajemen Chatbot</h2>
            <p class="text-sm text-gray-500 mt-0.5">Konfigurasi chatbot dan widget embed</p>
        </div>
        @if(auth()->user()->isAdmin())
        <a href="{{ route('admin.chatbot.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors">
            ➕ Buat Chatbot
        </a>
        @endif
    </div>

    <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-5">
        @forelse($chatbots as $bot)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition-shadow">
            <div class="flex items-start gap-3 mb-4">
                <div class="w-12 h-12 rounded-xl bg-indigo-100 flex items-center justify-center text-2xl flex-shrink-0">
                    @if($bot->avatar)
                        <img src="{{ asset('storage/' . $bot->avatar) }}" class="w-full h-full rounded-xl object-cover">
                    @else
                        🤖
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <div class="font-semibold text-gray-800">{{ $bot->name }}</div>
                    <div class="text-xs text-gray-500 mt-0.5">{{ $bot->model }}</div>
                    <span class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 text-xs rounded-full {{ $bot->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $bot->is_active ? '● Aktif' : '○ Nonaktif' }}
                    </span>
                </div>
            </div>

            <div class="text-xs text-gray-500 mb-4 line-clamp-2">{{ $bot->system_prompt ?: 'Belum ada system prompt' }}</div>

            <div class="flex gap-2">
                <a href="{{ route('admin.chatbot.edit', $bot) }}" class="flex-1 text-center px-3 py-2 border border-gray-300 text-gray-700 rounded-lg text-xs font-medium hover:bg-gray-50 transition-colors">
                    ⚙️ Edit
                </a>
                <a href="{{ route('admin.chatbot.embed-code', $bot) }}" class="flex-1 text-center px-3 py-2 bg-indigo-50 text-indigo-700 rounded-lg text-xs font-medium hover:bg-indigo-100 transition-colors">
                    &lt;/&gt; Embed
                </a>
                <a href="{{ route('admin.knowledge.index') }}?chatbot_id={{ $bot->id }}" class="flex-1 text-center px-3 py-2 bg-blue-50 text-blue-700 rounded-lg text-xs font-medium hover:bg-blue-100 transition-colors">
                    📚 Docs
                </a>
            </div>
        </div>
        @empty
        <div class="col-span-3 text-center py-16 text-gray-500">
            <div class="text-4xl mb-3">🤖</div>
            <p class="text-sm">Belum ada chatbot. Buat chatbot pertama Anda!</p>
            @if(auth()->user()->isAdmin())
            <a href="{{ route('admin.chatbot.create') }}" class="mt-4 inline-block px-5 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
                Buat Chatbot
            </a>
            @endif
        </div>
        @endforelse
    </div>
    <div>{{ $chatbots->links() }}</div>
</div>
@endsection
