@extends('layouts.admin')
@section('title', 'Percakapan')

@section('content')
<div class="space-y-5">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Percakapan</h2>
            <p class="text-sm text-gray-500 mt-0.5">Pantau dan kelola semua percakapan</p>
        </div>
        <a href="{{ route('admin.conversations.export') }}?{{ http_build_query(request()->all()) }}"
           class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50 transition-colors flex items-center gap-2">
            📥 Export CSV
        </a>
    </div>

    {{-- Livewire Real-time Inbox --}}
    @livewire('conversation-inbox')
</div>
@endsection
