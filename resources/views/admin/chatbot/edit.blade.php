@extends('layouts.admin')
@section('title', 'Edit Chatbot — ' . $chatbot->name)

@section('content')
<div class="max-w-3xl">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.chatbot.index') }}" class="text-gray-500 hover:text-gray-700 text-sm">← Kembali</a>
        <span class="text-gray-300">/</span>
        <span class="text-sm font-medium">{{ $chatbot->name }}</span>
    </div>

    <div class="space-y-6">
        {{-- Bot Config --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-5">Konfigurasi Chatbot</h3>
            <form method="POST" action="{{ route('admin.chatbot.update', $chatbot) }}" enctype="multipart/form-data" class="space-y-5">
                @csrf @method('PUT')
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Nama Chatbot *</label>
                        <input type="text" name="name" value="{{ old('name', $chatbot->name) }}" required
                               class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Avatar</label>
                        @if($chatbot->avatar)
                            <div class="mb-2"><img src="{{ asset('storage/' . $chatbot->avatar) }}" class="w-12 h-12 rounded-lg object-cover"></div>
                        @endif
                        <input type="file" name="avatar" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Model AI *</label>
                        <input type="text" name="model" value="{{ old('model', $chatbot->model) }}" required
                               class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                        <p class="text-xs text-gray-400 mt-1">Model harus tersedia di Sumopod Anda</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Bahasa</label>
                        <select name="language" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                            <option value="id" {{ $chatbot->language === 'id' ? 'selected' : '' }}>Indonesia</option>
                            <option value="en" {{ $chatbot->language === 'en' ? 'selected' : '' }}>English</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Temperature</label>
                        <input type="number" name="temperature" value="{{ old('temperature', $chatbot->temperature) }}" step="0.1" min="0" max="1"
                               class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Max Context</label>
                        <input type="number" name="max_context" value="{{ old('max_context', $chatbot->max_context) }}" min="1" max="50"
                               class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">System Prompt</label>
                    <textarea name="system_prompt" rows="5" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">{{ old('system_prompt', $chatbot->system_prompt) }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Pesan Fallback</label>
                    <input type="text" name="fallback_message" value="{{ old('fallback_message', $chatbot->fallback_message) }}"
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Kata Kunci Handoff (satu per baris)</label>
                    <textarea name="handoff_triggers" rows="3"
                              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">{{ old('handoff_triggers', is_array($chatbot->handoff_triggers) ? implode("\n", $chatbot->handoff_triggers) : '') }}</textarea>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ $chatbot->is_active ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600">
                    <label for="is_active" class="text-sm text-gray-700">Chatbot aktif</label>
                </div>

                {{-- Widget Config Section --}}
                <hr class="my-4">
                <h4 class="font-semibold text-gray-800 mb-4">Konfigurasi Widget Embed</h4>
                @php $embed = $chatbot->embedConfig; @endphp
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Warna Utama</label>
                        <div class="flex items-center gap-2">
                            <input type="color" name="primary_color" value="{{ $embed?->primary_color ?? '#4F46E5' }}" class="w-10 h-10 rounded border border-gray-300 cursor-pointer">
                            <input type="text" value="{{ $embed?->primary_color ?? '#4F46E5' }}" class="flex-1 px-3 py-2.5 border border-gray-300 rounded-lg text-sm" readonly>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Posisi Widget</label>
                        <select name="position" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                            <option value="bottom-right" {{ ($embed?->position ?? 'bottom-right') === 'bottom-right' ? 'selected' : '' }}>Kanan Bawah</option>
                            <option value="bottom-left" {{ ($embed?->position ?? '') === 'bottom-left' ? 'selected' : '' }}>Kiri Bawah</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Pesan Sambutan</label>
                    <input type="text" name="greeting" value="{{ old('greeting', $embed?->greeting) }}"
                           placeholder="Halo! Ada yang bisa saya bantu?"
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Quick Replies (satu per baris)</label>
                    <textarea name="quick_replies" rows="4" placeholder="Informasi produk&#10;Jam operasional&#10;Cara pemesanan"
                              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">{{ old('quick_replies', is_array($embed?->quick_replies) ? implode("\n", $embed->quick_replies) : '') }}</textarea>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors">
                        💾 Simpan Perubahan
                    </button>
                    <a href="{{ route('admin.chatbot.embed-code', $chatbot) }}" class="px-5 py-2.5 border border-indigo-300 text-indigo-700 rounded-lg text-sm hover:bg-indigo-50">
                        &lt;/&gt; Lihat Embed Code
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
