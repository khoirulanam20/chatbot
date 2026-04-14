@extends('layouts.admin')
@section('title', 'Buat Chatbot')

@section('content')
<div class="max-w-3xl">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.chatbot.index') }}" class="text-gray-500 hover:text-gray-700 text-sm">← Kembali</a>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-bold text-gray-800 mb-6">Buat Chatbot Baru</h2>
        <form method="POST" action="{{ route('admin.chatbot.store') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf
            @if(auth()->user()->isSuperAdmin())
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Tenant *</label>
                <select name="tenant_id" required class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                    @foreach($tenants as $t)
                        <option value="{{ $t->id }}">{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            @else
            <input type="hidden" name="tenant_id" value="{{ auth()->user()->tenant_id }}">
            @endif

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Nama Chatbot *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="Contoh: Ava"
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Avatar</label>
                    <input type="file" name="avatar" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Model AI *</label>
                    <input type="text" name="model" value="{{ old('model', 'gpt-4o') }}" required
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                    <p class="text-xs text-gray-400 mt-1">Sesuaikan dengan model Sumopod Anda</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Bahasa</label>
                    <select name="language" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="id" {{ old('language') == 'id' ? 'selected' : '' }}>Indonesia</option>
                        <option value="en" {{ old('language') == 'en' ? 'selected' : '' }}>English</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Temperature (0.0 - 1.0)</label>
                    <input type="number" name="temperature" value="{{ old('temperature', 0.7) }}" step="0.1" min="0" max="1"
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Max Context (pesan)</label>
                    <input type="number" name="max_context" value="{{ old('max_context', 10) }}" min="1" max="50"
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">System Prompt</label>
                <textarea name="system_prompt" rows="4" placeholder="Kamu adalah asisten layanan pelanggan yang ramah..."
                          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">{{ old('system_prompt') }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Pesan Fallback</label>
                <input type="text" name="fallback_message" value="{{ old('fallback_message') }}"
                       placeholder="Maaf, saya tidak dapat menemukan jawaban..."
                       class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Kata Kunci Handoff <span class="text-gray-400 font-normal">(satu per baris)</span></label>
                <textarea name="handoff_triggers" rows="3" placeholder="agen&#10;manusia&#10;operator"
                          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">{{ old('handoff_triggers') }}</textarea>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600">
                <label for="is_active" class="text-sm text-gray-700">Chatbot aktif</label>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors">
                    Buat Chatbot
                </button>
                <a href="{{ route('admin.chatbot.index') }}" class="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
