@extends('layouts.admin')
@section('title', 'Manajemen Tenant')

@section('content')
<div class="space-y-5">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Manajemen Tenant</h2>
            <p class="text-sm text-gray-500 mt-0.5">Kelola semua klien/bisnis di platform</p>
        </div>
        <a href="{{ route('admin.tenants.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors">
            ➕ Buat Tenant
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-3 text-left">Nama</th>
                        <th class="px-6 py-3 text-left">Slug</th>
                        <th class="px-6 py-3 text-left">Plan</th>
                        <th class="px-6 py-3 text-left">Users</th>
                        <th class="px-6 py-3 text-left">Chatbots</th>
                        <th class="px-6 py-3 text-left">Status</th>
                        <th class="px-6 py-3 text-left">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($tenants as $tenant)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium text-sm text-gray-800">{{ $tenant->name }}</td>
                        <td class="px-6 py-4 font-mono text-xs text-gray-600">{{ $tenant->slug }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-0.5 text-xs rounded-full font-medium {{ $tenant->plan === 'enterprise' ? 'bg-purple-100 text-purple-700' : ($tenant->plan === 'pro' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600') }}">
                                {{ ucfirst($tenant->plan) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $tenant->users_count }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $tenant->chatbots_count }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-0.5 text-xs rounded-full font-medium {{ $tenant->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $tenant->is_active ? '● Aktif' : '○ Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('admin.tenants.edit', $tenant) }}" class="text-indigo-600 hover:text-indigo-700 text-xs font-medium">Edit</a>
                                <form method="POST" action="{{ route('admin.tenants.destroy', $tenant) }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" onclick="return confirm('Hapus tenant ini? Semua data akan dihapus!')" class="text-red-500 hover:text-red-700 text-xs font-medium">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-6 py-10 text-center text-gray-500 text-sm">Belum ada tenant</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100">{{ $tenants->links() }}</div>
    </div>
</div>
@endsection
