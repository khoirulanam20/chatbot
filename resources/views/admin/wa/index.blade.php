@extends('layouts.admin')
@section('title', 'WhatsApp Instances')

@section('content')
<div class="space-y-5">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-800">WhatsApp Instances</h2>
            <p class="text-sm text-gray-500 mt-0.5">Kelola koneksi WhatsApp via WA Chatery</p>
        </div>
        <a href="{{ route('admin.wa.create') }}" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 transition-colors">
            ➕ Tambah Instance
        </a>
    </div>

    <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-5">
        @forelse($instances as $instance)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-start justify-between mb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-xl">📱</div>
                    <div>
                        <div class="font-medium text-gray-800">{{ $instance->phone_number ?: 'Belum dikonfigurasi' }}</div>
                        <div class="text-xs text-gray-500">{{ $instance->tenant?->name }}</div>
                    </div>
                </div>
                <span class="px-2 py-0.5 text-xs rounded-full font-medium {{ $instance->status === 'active' ? 'bg-green-100 text-green-700' : ($instance->status === 'error' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600') }}">
                    {{ $instance->status === 'active' ? '● Aktif' : ($instance->status === 'error' ? '⚠️ Error' : '○ Tidak Aktif') }}
                </span>
            </div>
            <div class="text-xs text-gray-500 mb-4">
                <div>Chatbot: {{ $instance->chatbot?->name ?? '—' }}</div>
                @if($instance->instance_id)
                <div>Instance ID: {{ $instance->instance_id }}</div>
                @endif
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.wa.edit', $instance) }}" class="flex-1 text-center px-3 py-2 border border-gray-300 text-gray-700 rounded-lg text-xs font-medium hover:bg-gray-50 transition-colors">
                    ⚙️ Edit
                </a>
                <form method="POST" action="{{ route('admin.wa.test', $instance) }}" class="flex-1">
                    @csrf
                    <button type="submit" class="w-full px-3 py-2 bg-green-50 text-green-700 rounded-lg text-xs font-medium hover:bg-green-100 transition-colors">
                        🔌 Test
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.wa.destroy', $instance) }}">
                    @csrf @method('DELETE')
                    <button type="submit" onclick="return confirm('Hapus instance ini?')" class="px-3 py-2 border border-red-200 text-red-600 rounded-lg text-xs hover:bg-red-50 transition-colors">
                        🗑️
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="col-span-3 text-center py-16 text-gray-500">
            <div class="text-4xl mb-3">📱</div>
            <p class="text-sm">Belum ada WA instance. Tambahkan instance pertama Anda!</p>
            <a href="{{ route('admin.wa.create') }}" class="mt-4 inline-block px-5 py-2.5 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700">
                Tambah Instance
            </a>
        </div>
        @endforelse
    </div>
    {{ $instances->links() }}
</div>
@endsection
