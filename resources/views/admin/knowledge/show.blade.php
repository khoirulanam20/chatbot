@extends('layouts.admin')
@section('title', 'Detail Dokumen')

@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.knowledge.index') }}" class="text-gray-500 hover:text-gray-700 text-sm">← Kembali</a>
        <span class="text-gray-300">/</span>
        <span class="text-sm font-medium text-gray-800">{{ $document->name }}</span>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="grid md:grid-cols-3 gap-4 mb-6">
            <div><div class="text-xs text-gray-500">Nama</div><div class="font-medium text-gray-800">{{ $document->name }}</div></div>
            <div><div class="text-xs text-gray-500">Tipe</div><div class="font-medium text-gray-800 uppercase">{{ $document->type }}</div></div>
            <div><div class="text-xs text-gray-500">Status</div>
                @php $colors = ['queued' => 'text-yellow-700', 'processing' => 'text-blue-700', 'indexed' => 'text-green-700', 'failed' => 'text-red-700']; @endphp
                <div class="font-medium {{ $colors[$document->status] ?? '' }} capitalize">{{ $document->status }}</div>
            </div>
            <div><div class="text-xs text-gray-500">Total Chunks</div><div class="font-medium text-gray-800">{{ number_format($document->chunk_count) }}</div></div>
            <div><div class="text-xs text-gray-500">Diupload</div><div class="font-medium text-gray-800">{{ $document->created_at->format('d M Y H:i') }}</div></div>
        </div>

        <h3 class="font-semibold text-gray-800 mb-3">Preview Chunks Teks</h3>
        <div class="space-y-3">
            @foreach($chunks as $chunk)
                <div class="border border-gray-200 rounded-lg p-4 hover:border-indigo-300 transition-colors">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full font-medium">Chunk #{{ $chunk->chunk_index + 1 }}</span>
                        <span class="text-xs text-gray-400">{{ strlen($chunk->content) }} karakter</span>
                    </div>
                    <p class="text-sm text-gray-700 leading-relaxed">{{ $chunk->content }}</p>
                </div>
            @endforeach
        </div>
        <div class="mt-4">{{ $chunks->links() }}</div>
    </div>
</div>
@endsection
