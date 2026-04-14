<div wire:poll="{{ $hasProcessing ? '3s' : '10s' }}">
    @foreach($documents as $doc)
        @php
            $colors = ['queued' => 'bg-yellow-100 text-yellow-700', 'processing' => 'bg-blue-100 text-blue-700', 'indexed' => 'bg-green-100 text-green-700', 'failed' => 'bg-red-100 text-red-700'];
            $icons = ['queued' => '⏳', 'processing' => '⚙️', 'indexed' => '✅', 'failed' => '❌'];
        @endphp
        <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
            <span class="text-sm text-gray-700 truncate max-w-xs">{{ $doc->name }}</span>
            <span class="px-2 py-0.5 text-xs rounded-full font-medium {{ $colors[$doc->status] ?? 'bg-gray-100 text-gray-700' }}">
                {{ $icons[$doc->status] ?? '' }} {{ $doc->status }}
            </span>
        </div>
    @endforeach
</div>
