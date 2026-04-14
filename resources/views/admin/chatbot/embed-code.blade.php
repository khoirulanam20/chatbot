@extends('layouts.admin')
@section('title', 'Embed Code — ' . $chatbot->name)

@section('content')
<div class="max-w-3xl" x-data>
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.chatbot.edit', $chatbot) }}" class="text-gray-500 hover:text-gray-700 text-sm">← Kembali</a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-6">
        <div>
            <h2 class="text-lg font-bold text-gray-800">Embed Code untuk {{ $chatbot->name }}</h2>
            <p class="text-sm text-gray-500 mt-1">Tambahkan kode berikut ke website Anda, sebelum tag &lt;/body&gt;</p>
        </div>

        <div class="bg-gray-900 rounded-xl p-5">
            <pre class="text-green-400 text-sm overflow-x-auto leading-relaxed" id="embedCode">&lt;!-- AI CS Chatbot Widget --&gt;
&lt;script
  src="{{ url('/chatbot.js') }}"
  data-bot-id="{{ $chatbot->id }}"
  defer&gt;&lt;/script&gt;</pre>
        </div>

        <div class="flex gap-3">
            <button @click="
                const text = document.getElementById('embedCode').innerText;
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(text).then(() => {
                        $el.textContent = '✅ Tersalin!';
                        setTimeout(() => $el.textContent = '📋 Salin Kode', 2000);
                    });
                } else {
                    const textarea = document.createElement('textarea');
                    textarea.value = text;
                    textarea.style.position = 'fixed';
                    textarea.style.opacity = '0';
                    document.body.appendChild(textarea);
                    textarea.focus();
                    textarea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textarea);
                    $el.textContent = '✅ Tersalin!';
                    setTimeout(() => $el.textContent = '📋 Salin Kode', 2000);
                }
            "
            class="px-5 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors">
                📋 Salin Kode
            </button>
        </div>

        <div class="border-t border-gray-100 pt-5">
            <h3 class="font-semibold text-gray-800 mb-3">Preview Widget</h3>
            <div class="bg-gray-100 rounded-xl p-8 relative min-h-[200px] flex items-center justify-center">
                <p class="text-sm text-gray-500">Preview widget akan muncul di sini setelah Anda embed kode ke website.</p>
            </div>
        </div>

        <div class="border-t border-gray-100 pt-5">
            <h3 class="font-semibold text-gray-800 mb-3">Informasi Konfigurasi</h3>
            <div class="grid md:grid-cols-2 gap-3 text-sm">
                <div class="bg-gray-50 rounded-lg p-3">
                    <div class="text-xs text-gray-500 mb-1">Bot ID</div>
                    <div class="font-mono font-medium text-gray-800">{{ $chatbot->id }}</div>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                    <div class="text-xs text-gray-500 mb-1">Widget URL</div>
                    <div class="font-mono text-gray-800 text-xs break-all">{{ url('/chatbot.js') }}</div>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                    <div class="text-xs text-gray-500 mb-1">API Chat URL</div>
                    <div class="font-mono text-gray-800 text-xs break-all">{{ url('/api/chat/message') }}</div>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                    <div class="text-xs text-gray-500 mb-1">Bot Config URL</div>
                    <div class="font-mono text-gray-800 text-xs break-all">{{ url('/api/bot/config/' . $chatbot->id) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
