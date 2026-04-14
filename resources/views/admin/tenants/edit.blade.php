@extends('layouts.admin')
@section('title', 'Edit Tenant')

@section('content')
<div class="max-w-xl">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.tenants.index') }}" class="text-gray-500 hover:text-gray-700 text-sm">← Kembali</a>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-bold text-gray-800 mb-2">Edit: {{ $tenant->name }}</h2>
        <div class="text-xs text-gray-500 mb-5">
            {{ $tenant->users_count }} users · {{ $tenant->chatbots_count }} chatbots
        </div>
        <form method="POST" action="{{ route('admin.tenants.update', $tenant) }}" class="space-y-4">
            @csrf @method('PUT')
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Nama Bisnis *</label>
                <input type="text" name="name" value="{{ old('name', $tenant->name) }}" required class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Plan *</label>
                <select name="plan" required class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                    <option value="free" {{ $tenant->plan === 'free' ? 'selected' : '' }}>Free</option>
                    <option value="pro" {{ $tenant->plan === 'pro' ? 'selected' : '' }}>Pro</option>
                    <option value="enterprise" {{ $tenant->plan === 'enterprise' ? 'selected' : '' }}>Enterprise</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ $tenant->is_active ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600">
                <label for="is_active" class="text-sm text-gray-700">Tenant aktif</label>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">💾 Simpan</button>
                <a href="{{ route('admin.tenants.index') }}" class="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
