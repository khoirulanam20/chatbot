@extends('layouts.admin')
@section('title', 'Detail Percakapan')

@section('content')
<div class="space-y-5">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.conversations.index') }}" class="text-gray-500 hover:text-gray-700 text-sm">← Kembali</a>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        {{-- Chat Messages --}}
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 flex flex-col" style="height: 600px;">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <div class="font-semibold text-gray-800">{{ $conversation->contact?->name ?: $conversation->contact?->identifier ?: 'Anonymous' }}</div>
                    <div class="text-xs text-gray-500">{{ $conversation->channel === 'whatsapp' ? '📱 WhatsApp' : '🌐 Web' }} · {{ $conversation->chatbot?->name }}</div>
                </div>
                @php
                    $sc = ['open' => 'bg-blue-100 text-blue-700', 'resolved' => 'bg-green-100 text-green-700', 'handoff' => 'bg-yellow-100 text-yellow-700', 'spam' => 'bg-red-100 text-red-700'];
                    $sl = ['open' => 'Aktif', 'resolved' => 'Selesai', 'handoff' => 'Handoff', 'spam' => 'Spam'];
                @endphp
                <span class="px-3 py-1 text-xs rounded-full font-medium {{ $sc[$conversation->status] ?? 'bg-gray-100 text-gray-700' }}">
                    {{ $sl[$conversation->status] ?? $conversation->status }}
                </span>
            </div>

            <div class="flex-1 overflow-y-auto p-5 space-y-4">
                @foreach($messages as $msg)
                    <div @class(['flex', 'justify-end' => $msg->role === 'user'])>
                        <div @class([
                            'max-w-xs lg:max-w-md px-4 py-3 rounded-2xl text-sm leading-relaxed',
                            'bg-indigo-600 text-white rounded-br-sm' => $msg->role === 'user',
                            'bg-gray-100 text-gray-800 rounded-bl-sm' => $msg->role === 'assistant',
                            'bg-yellow-50 border border-yellow-200 text-yellow-900 rounded-bl-sm' => $msg->role === 'agent',
                        ])>
                            @if($msg->role === 'agent')
                                <div class="text-xs font-medium text-yellow-700 mb-1">👤 Agen</div>
                            @endif
                            <div class="whitespace-pre-wrap">{{ $msg->content }}</div>
                            <div class="text-xs opacity-60 mt-1 text-right">{{ $msg->created_at->format('H:i') }}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($conversation->is_ai_active === false || $conversation->status === 'handoff')
            <div class="p-4 border-t border-gray-100">
                <form method="POST" action="{{ route('admin.conversations.message', $conversation) }}" class="flex gap-2">
                    @csrf
                    <input type="text" name="message" placeholder="Ketik balasan sebagai agen..." required
                           class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                    <button type="submit" class="px-4 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
                        Kirim
                    </button>
                </form>
            </div>
            @endif
        </div>

        {{-- Info Panel --}}
        <div class="space-y-4">
            {{-- Contact Info --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h4 class="font-semibold text-gray-800 mb-3">Info Kontak</h4>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">Identifier</span><span class="text-gray-800">{{ $conversation->contact?->identifier ?? '-' }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Channel</span><span class="text-gray-800">{{ $conversation->channel }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Mulai</span><span class="text-gray-800">{{ $conversation->created_at->format('d M Y H:i') }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Total Pesan</span><span class="text-gray-800">{{ $messages->count() }}</span></div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h4 class="font-semibold text-gray-800 mb-3">Aksi</h4>
                <div class="space-y-2">
                    <form method="POST" action="{{ route('admin.conversations.status', $conversation) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="resolved">
                        <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 transition-colors">
                            ✅ Tandai Selesai
                        </button>
                    </form>
                    @if(!$conversation->is_ai_active)
                    <form method="POST" action="{{ route('admin.conversations.resume-ai', $conversation) }}">
                        @csrf
                        <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors">
                            🤖 Aktifkan AI Kembali
                        </button>
                    </form>
                    @endif
                    <form method="POST" action="{{ route('admin.conversations.status', $conversation) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="spam">
                        <button type="submit" class="w-full px-4 py-2 border border-red-300 text-red-700 rounded-lg text-sm hover:bg-red-50 transition-colors">
                            🚫 Tandai Spam
                        </button>
                    </form>
                </div>
            </div>

            {{-- Assign Agent --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h4 class="font-semibold text-gray-800 mb-3">Assign ke Agen</h4>
                <form method="POST" action="{{ route('admin.conversations.assign', $conversation) }}">
                    @csrf
                    <select name="agent_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm mb-3 focus:ring-2 focus:ring-indigo-500">
                        <option value="">Pilih agen...</option>
                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}" {{ $conversation->assigned_agent_id == $agent->id ? 'selected' : '' }}>
                                {{ $agent->name }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="w-full px-4 py-2 bg-yellow-500 text-white rounded-lg text-sm font-medium hover:bg-yellow-600 transition-colors">
                        👤 Assign
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
