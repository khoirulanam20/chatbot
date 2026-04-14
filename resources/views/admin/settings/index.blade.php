@extends('layouts.admin')
@section('title', 'Pengaturan AI')

@section('content')
<div class="space-y-6 max-w-3xl">
    <div>
        <h2 class="text-xl font-bold text-gray-800">Pengaturan AI</h2>
        <p class="text-sm text-gray-500 mt-0.5">Konfigurasi API key dan model AI per tenant. Kosongkan field untuk menggunakan default global.</p>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg px-4 py-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-lg px-4 py-3">{{ $errors->first() }}</div>
    @endif

    {{-- SUPER ADMIN: Pilih tenant --}}
    @if(auth()->user()->isSuperAdmin() && $tenants->isNotEmpty())
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h3 class="font-semibold text-gray-800 mb-3 text-sm">Kelola Tenant</h3>
        <form method="GET" action="{{ route('admin.settings.index') }}" class="flex items-center gap-3">
            <select name="tenant_id" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500" onchange="this.form.submit()">
                @foreach($tenants as $t)
                <option value="{{ $t->id }}" {{ $tenant?->id == $t->id ? 'selected' : '' }}>
                    {{ $t->name }} {{ $t->id == auth()->user()->tenant_id ? '(tenant Anda)' : '' }}
                </option>
                @endforeach
            </select>
        </form>
    </div>
    @endif

    {{-- PENGATURAN AI PER TENANT --}}
    <div class="bg-white rounded-xl shadow-sm border border-indigo-100 p-6">
        <div class="flex items-center gap-2 mb-5">
            <div class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center text-indigo-600 font-bold text-sm">AI</div>
            <div>
                <h3 class="font-semibold text-gray-800">Pengaturan AI — <span class="text-indigo-600">{{ $tenant?->name ?? 'Tenant' }}</span></h3>
                <p class="text-xs text-gray-400">Override konfigurasi untuk tenant ini. Kosongkan = pakai global default.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-5">
            @csrf
            @if(auth()->user()->isSuperAdmin() && $tenant)
            <input type="hidden" name="tenant_id" value="{{ $tenant->id }}">
            @endif

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                    API Key
                    <span class="ml-2 text-xs font-normal text-gray-400">
                        (global: {{ filled($global['ai_api_key']) ? '••••••••' . substr($global['ai_api_key'], -4) : 'belum diset' }})
                    </span>
                </label>
                <div class="flex gap-2" x-data="{ show: false }">
                    <input :type="show ? 'text' : 'password'" name="ai_api_key"
                           value="{{ $tenantSettings['ai_api_key'] ?? '' }}"
                           placeholder="Kosongkan = pakai global default"
                           class="flex-1 px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 font-mono">
                    <button type="button" @click="show = !show"
                            class="px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50">
                        <span x-text="show ? '🙈' : '👁️'"></span>
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                    Base URL
                    <span class="ml-2 text-xs font-normal text-gray-400">(global: {{ $global['ai_base_url'] ?: 'belum diset' }})</span>
                </label>
                <input type="url" name="ai_base_url"
                       value="{{ $tenantSettings['ai_base_url'] ?? '' }}"
                       placeholder="{{ $global['ai_base_url'] ?: 'https://api.openai.com/v1' }}"
                       class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 font-mono">
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                        Model Embedding
                        <span class="ml-1 text-xs font-normal text-gray-400">(global: {{ $global['ai_embed_model'] ?: '-' }})</span>
                    </label>
                    <input type="text" name="ai_embed_model"
                           value="{{ $tenantSettings['ai_embed_model'] ?? '' }}"
                           placeholder="{{ $global['ai_embed_model'] ?: 'text-embedding-3-small' }}"
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                        Model Chat
                        <span class="ml-1 text-xs font-normal text-gray-400">(global: {{ $global['ai_chat_model'] ?: '-' }})</span>
                    </label>
                    <input type="text" name="ai_chat_model"
                           value="{{ $tenantSettings['ai_chat_model'] ?? '' }}"
                           placeholder="{{ $global['ai_chat_model'] ?: 'gpt-4o' }}"
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 font-mono">
                </div>
            </div>

            {{-- Preview konfigurasi efektif --}}
            <div class="bg-gray-50 rounded-lg p-3 text-xs text-gray-600 space-y-1">
                <div class="font-medium text-gray-700 mb-1">Konfigurasi aktif saat ini:</div>
                <div class="flex gap-2">
                    <span class="text-gray-400 w-28">Base URL</span>
                    <span class="font-mono">{{ $tenantSettings['ai_base_url'] ?? $global['ai_base_url'] ?: '—' }}</span>
                    @if(!empty($tenantSettings['ai_base_url']))<span class="bg-indigo-100 text-indigo-600 px-1.5 rounded text-[10px]">tenant</span>@else<span class="bg-gray-200 text-gray-500 px-1.5 rounded text-[10px]">global</span>@endif
                </div>
                <div class="flex gap-2">
                    <span class="text-gray-400 w-28">Embed Model</span>
                    <span class="font-mono">{{ $tenantSettings['ai_embed_model'] ?? $global['ai_embed_model'] ?: '—' }}</span>
                    @if(!empty($tenantSettings['ai_embed_model']))<span class="bg-indigo-100 text-indigo-600 px-1.5 rounded text-[10px]">tenant</span>@else<span class="bg-gray-200 text-gray-500 px-1.5 rounded text-[10px]">global</span>@endif
                </div>
                <div class="flex gap-2">
                    <span class="text-gray-400 w-28">Chat Model</span>
                    <span class="font-mono">{{ $tenantSettings['ai_chat_model'] ?? $global['ai_chat_model'] ?: '—' }}</span>
                    @if(!empty($tenantSettings['ai_chat_model']))<span class="bg-indigo-100 text-indigo-600 px-1.5 rounded text-[10px]">tenant</span>@else<span class="bg-gray-200 text-gray-500 px-1.5 rounded text-[10px]">global</span>@endif
                </div>
                <div class="flex gap-2">
                    <span class="text-gray-400 w-28">API Key</span>
                    <span class="font-mono">{{ filled($tenantSettings['ai_api_key'] ?? '') ? '••••••••' . substr($tenantSettings['ai_api_key'], -4) : (filled($global['ai_api_key']) ? '(dari global) ••••' . substr($global['ai_api_key'], -4) : '—') }}</span>
                    @if(!empty($tenantSettings['ai_api_key']))<span class="bg-indigo-100 text-indigo-600 px-1.5 rounded text-[10px]">tenant</span>@else<span class="bg-gray-200 text-gray-500 px-1.5 rounded text-[10px]">global</span>@endif
                </div>
            </div>

            <div class="flex gap-3 pt-1">
                <button type="submit"
                        class="px-5 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors">
                    💾 Simpan
                </button>
                <button type="button" id="testAIBtn"
                        onclick="testAIConnection({{ $tenant?->id ?? 'null' }})"
                        class="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50 transition-colors">
                    🔌 Test Koneksi
                </button>
                @if(!empty($tenantSettings['ai_api_key']) || !empty($tenantSettings['ai_base_url']) || !empty($tenantSettings['ai_embed_model']) || !empty($tenantSettings['ai_chat_model']))
                <button type="button"
                        onclick="if(confirm('Reset semua pengaturan AI tenant ini ke global default?')) resetTenantAI()"
                        class="px-4 py-2.5 border border-red-200 text-red-600 rounded-lg text-sm hover:bg-red-50 transition-colors">
                    🗑 Reset ke Global
                </button>
                @endif
            </div>
        </form>
    </div>

    {{-- SUPER ADMIN: Default Global --}}
    @if(auth()->user()->isSuperAdmin())
    <div class="bg-white rounded-xl shadow-sm border border-orange-100 p-6">
        <div class="flex items-center gap-2 mb-4">
            <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center text-orange-600 font-bold text-sm">⚙️</div>
            <div>
                <h3 class="font-semibold text-gray-800">Default Global <span class="text-xs font-normal text-orange-600 ml-1">Super Admin Only</span></h3>
                <p class="text-xs text-gray-400">Digunakan oleh tenant yang belum mengatur konfigurasi AI-nya sendiri.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.settings.update-global') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">API Key Default</label>
                <div class="flex gap-2" x-data="{ show: false }">
                    <input :type="show ? 'text' : 'password'" name="sumopod_api_key"
                           value="{{ $global['ai_api_key'] }}"
                           placeholder="sk-..."
                           class="flex-1 px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 font-mono">
                    <button type="button" @click="show = !show"
                            class="px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50">
                        <span x-text="show ? '🙈' : '👁️'"></span>
                    </button>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Base URL Default</label>
                <input type="url" name="sumopod_base_url" value="{{ $global['ai_base_url'] }}" required
                       placeholder="https://api.openai.com/v1"
                       class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 font-mono">
            </div>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Embed Model Default</label>
                    <input type="text" name="sumopod_embed_model" value="{{ $global['ai_embed_model'] }}" required
                           placeholder="text-embedding-3-small"
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Chat Model Default</label>
                    <input type="text" name="sumopod_chat_model" value="{{ $global['ai_chat_model'] }}" required
                           placeholder="gpt-4o"
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 font-mono">
                </div>
            </div>
            <div class="flex gap-3 pt-1">
                <button type="submit"
                        class="px-5 py-2.5 bg-orange-500 text-white rounded-lg text-sm font-medium hover:bg-orange-600 transition-colors">
                    💾 Simpan Default Global
                </button>
            </div>
        </form>
    </div>
    @endif

    {{-- Info Sistem --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-800 mb-3">Informasi Sistem</h3>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between py-2 border-b border-gray-100">
                <span class="text-gray-500">PHP Version</span>
                <span class="font-mono text-gray-800">{{ PHP_VERSION }}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-100">
                <span class="text-gray-500">Laravel Version</span>
                <span class="font-mono text-gray-800">{{ app()->version() }}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-100">
                <span class="text-gray-500">Queue Driver</span>
                <span class="font-mono text-gray-800">{{ config('queue.default') }}</span>
            </div>
            <div class="flex justify-between py-2">
                <span class="text-gray-500">Cache Driver</span>
                <span class="font-mono text-gray-800">{{ config('cache.default') }}</span>
            </div>
        </div>
    </div>
</div>

<script>
async function testAIConnection(tenantId) {
    const btn = document.getElementById('testAIBtn');
    btn.textContent = '⏳ Menguji...';
    btn.disabled = true;
    try {
        const url = '{{ route("admin.settings.test-ai") }}' + (tenantId ? '?tenant_id=' + tenantId : '');
        const res = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        });
        const data = await res.json();
        if (data.success) {
            btn.textContent = '✅ ' + data.message;
        } else {
            btn.textContent = '❌ ' + data.message;
        }
    } catch (e) {
        btn.textContent = '❌ Gagal terhubung';
    }
    btn.disabled = false;
    setTimeout(() => { btn.textContent = '🔌 Test Koneksi'; }, 6000);
}

function resetTenantAI() {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("admin.settings.update") }}';
    form.innerHTML = `
        @csrf
        @if(auth()->user()->isSuperAdmin() && $tenant)
        <input type="hidden" name="tenant_id" value="{{ $tenant->id }}">
        @endif
        <input type="hidden" name="ai_api_key" value="">
        <input type="hidden" name="ai_base_url" value="">
        <input type="hidden" name="ai_embed_model" value="">
        <input type="hidden" name="ai_chat_model" value="">
    `;
    document.body.appendChild(form);
    form.submit();
}
</script>
@endsection
