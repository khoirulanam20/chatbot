@extends('layouts.admin')
@section('title', 'Tambah WA Instance')

@section('content')
<div class="max-w-2xl">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.wa.index') }}" class="text-gray-500 hover:text-gray-700 text-sm">← Kembali</a>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-bold text-gray-800 mb-5">Tambah WA Instance Baru</h2>

        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-5">
            <h4 class="text-sm font-semibold text-green-800 mb-1">📋 Cara Setup WA Chatery</h4>
            <ol class="text-xs text-green-700 space-y-1 list-decimal pl-4">
                <li>Login ke dashboard WA Chatery di <a href="https://wa.firstudio.id" target="_blank" class="underline">wa.firstudio.id</a></li>
                <li>Buat instance baru dan scan QR code dengan WhatsApp</li>
                <li>Copy API Key dari dashboard WA Chatery</li>
                <li>Set webhook URL ke: <code class="font-mono bg-green-100 px-1 rounded">{{ url('/api/webhook/whatsapp') }}</code></li>
                <li>Isi form di bawah ini</li>
            </ol>
        </div>

        <form method="POST" action="{{ route('admin.wa.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Chatbot *</label>
                <select name="chatbot_id" required class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                    <option value="">Pilih chatbot...</option>
                    @foreach($chatbots as $bot)
                        <option value="{{ $bot->id }}">{{ $bot->name }} ({{ $bot->tenant?->name }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Nomor WhatsApp *</label>
                <input type="text" name="phone_number" value="{{ old('phone_number') }}" required
                       placeholder="6281234567890" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                <p class="text-xs text-gray-400 mt-1">Format: 62... (tanpa + atau spasi)</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">API Key WA Chatery *</label>
                <input type="password" name="api_key" value="{{ old('api_key') }}" required
                       placeholder="Paste API Key dari WA Chatery..." class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Instance ID <span class="text-gray-400 font-normal">(opsional)</span></label>
                <input type="text" name="instance_id" value="{{ old('instance_id') }}"
                       placeholder="Instance ID dari WA Chatery (jika ada)" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="bg-gray-50 rounded-lg p-3 text-sm">
                <div class="font-medium text-gray-700 mb-1">Webhook URL untuk WA Chatery:</div>
                <code class="text-indigo-700 font-mono text-xs break-all">{{ url('/api/webhook/whatsapp') }}</code>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="px-5 py-2.5 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 transition-colors">
                    Tambah Instance
                </button>
                <a href="{{ route('admin.wa.index') }}" class="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
