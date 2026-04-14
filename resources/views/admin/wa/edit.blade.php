@extends('layouts.admin')
@section('title', 'Edit WA Instance')

@section('content')
<div class="max-w-2xl">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.wa.index') }}" class="text-gray-500 hover:text-gray-700 text-sm">← Kembali</a>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-bold text-gray-800 mb-5">Edit WA Instance</h2>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-5">
            <h4 class="text-sm font-semibold text-blue-800 mb-1">🔗 URL Webhook Anda</h4>
            <p class="text-xs text-blue-700 mb-1">Pastikan URL ini sudah dikonfigurasi di dashboard WA Chatery:</p>
            <code class="text-blue-800 font-mono text-xs break-all bg-blue-100 px-2 py-1 rounded block">{{ $webhookUrl }}</code>
        </div>

        <form method="POST" action="{{ route('admin.wa.update', $waInstance) }}" class="space-y-4">
            @csrf @method('PUT')
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Chatbot *</label>
                <select name="chatbot_id" required class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                    @foreach($chatbots as $bot)
                        <option value="{{ $bot->id }}" {{ $waInstance->chatbot_id == $bot->id ? 'selected' : '' }}>
                            {{ $bot->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Nomor WhatsApp *</label>
                <input type="text" name="phone_number" value="{{ old('phone_number', $waInstance->phone_number) }}" required
                       class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">API Key Baru <span class="text-gray-400 font-normal">(kosongkan jika tidak diganti)</span></label>
                <input type="password" name="api_key" placeholder="Isi hanya jika ingin mengganti API key"
                       class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Instance ID</label>
                <input type="text" name="instance_id" value="{{ old('instance_id', $waInstance->instance_id) }}"
                       class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
                    💾 Simpan
                </button>
                <form method="POST" action="{{ route('admin.wa.test', $waInstance) }}" class="inline">
                    @csrf
                    <button type="submit" class="px-5 py-2.5 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700">
                        🔌 Test Koneksi
                    </button>
                </form>
                <a href="{{ route('admin.wa.index') }}" class="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
